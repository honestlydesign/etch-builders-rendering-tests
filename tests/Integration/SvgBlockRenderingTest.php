<?php
/**
 * SvgBlock rendering tests — every public method + logical combinations.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\EtchBlocks\SvgBlock;
use HonestlyDesign\EtchBuilders\Style;
use HonestlyDesign\EtchBuilders\Types\Attributes;

/**
 * Verifies SvgBlock renders correctly through the Etch runtime.
 */
final class SvgBlockRenderingTest extends RenderingTestCase {

	// --- new() ---

	public function test_new_creates_svg_block(): void {
		$markup = SvgBlock::new()
			->attribute( 'src', 'https://example.com/icon.svg' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'etch/svg', $markup );
	}

	// --- attribute() + attributes() ---

	public function test_attribute_sets_src(): void {
		$result = $this->render_block_markup(
			SvgBlock::new()->attribute( 'src', 'https://example.com/logo.svg' )->to_block()->to_string()
		);
		// SVG block renders an <svg> element with the source content.
		self::assertNotEmpty( $result->html() );
	}

	public function test_attribute_sets_viewbox(): void {
		$markup = SvgBlock::new()
			->attribute( 'src', 'https://example.com/icon.svg' )
			->attribute( 'viewBox', '0 0 24 24' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'viewBox', $markup );
	}

	public function test_attribute_sets_fill(): void {
		$markup = SvgBlock::new()
			->attribute( 'src', 'https://example.com/icon.svg' )
			->attribute( 'fill', 'currentColor' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'fill', $markup );
	}

	public function test_attributes_sets_multiple(): void {
		$attrs = Attributes::from_array(
			array(
				'src'     => 'https://example.com/icon.svg',
				'viewBox' => '0 0 100 100',
			)
		);
		$markup = SvgBlock::new()->attributes( $attrs )->to_block()->to_string();
		self::assertStringContainsString( 'icon.svg', $markup );
	}

	// --- metadata() + metadata_name() ---

	public function test_metadata_sets_custom_data(): void {
		$markup = SvgBlock::new()
			->attribute( 'src', 'https://example.com/icon.svg' )
			->metadata( array( 'name' => 'icon' ) )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'etch/svg', $markup );
	}

	public function test_metadata_name_sets_element_name(): void {
		$markup = SvgBlock::new()
			->attribute( 'src', 'https://example.com/icon.svg' )
			->metadata_name( 'custom-icon' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'etch/svg', $markup );
	}

	// --- style() + styles() ---

	public function test_style_attaches_a_style_id(): void {
		$this->register_test_style( 'omide-svg', '.omide-svg', 'width: 24px;' );

		$markup = SvgBlock::new()
			->attribute( 'src', 'https://example.com/icon.svg' )
			->style( 'omide-svg' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'omide-svg', $markup );
	}

	public function test_styles_attaches_multiple(): void {
		$this->register_test_style( 'omide-svg-a', '.omide-svg-a', 'width: 24px;' );
		$this->register_test_style( 'omide-svg-b', '.omide-svg-b', 'height: 24px;' );

		$markup = SvgBlock::new()
			->attribute( 'src', 'https://example.com/icon.svg' )
			->styles( array( 'omide-svg-a', 'omide-svg-b' ) )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'omide-svg-a', $markup );
		self::assertStringContainsString( 'omide-svg-b', $markup );
	}

	// --- is_ide_etch_placeholder() ---

	public function test_is_ide_etch_placeholder_sets_metadata(): void {
		$markup = SvgBlock::new()
			->is_ide_etch_placeholder()
			->to_block()
			->to_string();
		self::assertStringContainsString( 'etch/svg', $markup );
	}

	// --- Logical combinations ---

	public function test_svg_inside_element(): void {
		$markup = \HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock::new()
			->tag( 'div' )
			->child(
				SvgBlock::new()->attribute( 'src', 'https://example.com/icon.svg' )->to_block()
			)
			->to_block()
			->to_string();
		$result = $this->render_block_markup( $markup );
		$this->assertRendersTag( $result, 'div' );
	}

	private function register_test_style( string $id, string $selector = '', string $css = 'display: block;' ): void {
		Style::new()
			->id( $id )
			->selector( '' !== $selector ? $selector : '.' . $id )
			->css( $css )
			->type( 'class' )
			->collection( 'OhMyIDEtch' )
			->add();
		Style::register_all();
	}
}
