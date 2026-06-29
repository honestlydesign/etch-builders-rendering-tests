<?php
/**
 * StylesParser rendering tests against the real Etch runtime.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock;
use HonestlyDesign\EtchBuilders\Style;
use HonestlyDesign\EtchBuilders\StylesParser;

/**
 * Verifies parsed CSS registers style IDs that render through Etch.
 */
final class StylesParserRenderingTest extends RenderingTestCase {

	/**
	 * Temporary CSS files created during tests.
	 *
	 * @var array<int, string>
	 */
	private array $temporary_css_files = array();

	public function test_styles_parser_comment_free_css_emits_runtime_style(): void {
		$parser = $this->register_parsed_styles(
			$this->write_temp_css(
				'.parser-runtime-card { color: rgb(255, 0, 0); @media (min-width: 768px) { color: rgb(0, 0, 255); } }'
			)
		);

		self::assertSame( array( 'parser-runtime-card' ), $parser->get_style_ids() );

		$result = $this->render_element_with_class( 'parser-runtime-card' );

		$this->assertRendersClass( $result, 'parser-runtime-card' );
		$this->assertStyleEmitted( $result, '.parser-runtime-card', 'color: rgb(255, 0, 0)' );
		$this->assertStyleEmitted( $result, '@media' );
	}

	public function test_styles_parser_reuses_persisted_id_for_selector_and_renders_by_class(): void {
		update_option(
			'etch_styles',
			array(
				'legacy-runtime-style' => array(
					'selector'   => '.parser-runtime-legacy',
					'css'        => 'color: rgb(0, 0, 0);',
					'type'       => 'class',
					'collection' => 'OhMyIDEtch',
				),
			)
		);

		$parser = $this->register_parsed_styles(
			$this->write_temp_css( '.parser-runtime-legacy { color: rgb(255, 0, 0); }' )
		);

		self::assertSame( array( 'legacy-runtime-style' ), $parser->get_style_ids() );

		$persisted = get_option( 'etch_styles', array() );
		self::assertIsArray( $persisted );
		self::assertArrayHasKey( 'legacy-runtime-style', $persisted );
		self::assertArrayNotHasKey( 'parser-runtime-legacy', $persisted );
		self::assertSame( 'color: rgb(255, 0, 0)', $persisted['legacy-runtime-style']['css'] );

		$result = $this->render_element_with_class_attribute( 'parser-runtime-legacy' );

		$this->assertRendersClass( $result, 'parser-runtime-legacy' );
		$this->assertStyleEmitted( $result, '.parser-runtime-legacy', 'color: rgb(255, 0, 0)' );
	}

	protected function tearDown(): void {
		foreach ( $this->temporary_css_files as $file_path ) {
			if ( file_exists( $file_path ) ) {
				unlink( $file_path );
			}
		}
		$this->temporary_css_files = array();

		parent::tearDown();
	}

	private function register_parsed_styles( string $file_path ): StylesParser {
		$parser = StylesParser::new( $file_path );

		foreach ( $parser->get_all() as $style ) {
			$style
				->collection( 'OhMyIDEtch' )
				->readonly( true )
				->add();
		}
		Style::register_all();

		return $parser;
	}

	private function render_element_with_class( string $class ): RenderResult {
		return $this->render_block_markup(
			ElementBlock::new()
				->tag( 'div' )
				->class( $class )
				->to_block()
				->to_string()
		);
	}

	private function render_element_with_class_attribute( string $class ): RenderResult {
		return $this->render_block_markup(
			ElementBlock::new()
				->tag( 'div' )
				->attribute( 'class', $class )
				->to_block()
				->to_string()
		);
	}

	private function write_temp_css( string $css ): string {
		$file_path = tempnam( sys_get_temp_dir(), 'etch-parser-runtime-' );
		self::assertIsString( $file_path );

		$css_path = $file_path . '.css';
		rename( $file_path, $css_path );
		file_put_contents( $css_path, $css );

		$this->temporary_css_files[] = $css_path;

		return $css_path;
	}
}
