<?php
/**
 * Smoke test: prove the package + Etch render pipeline works end-to-end.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests;

use HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock;
use WP_UnitTestCase;

/**
 * Verifies render_block works through the real Etch runtime.
 */
final class SmokeTest extends WP_UnitTestCase {

	public function test_etch_element_renders_non_empty_html(): void {
		$markup = ElementBlock::new()
			->tag( 'div' )
			->attribute( 'class', 'test-smoke' )
			->child(
				ElementBlock::new()
					->tag( 'p' )
					->content( 'Hello from Etch Builders' )
					->to_block()
			)
			->to_block()
			->to_string();

		$parsed   = parse_blocks( $markup );
		$rendered = render_block( $parsed[0] );

		self::assertNotEmpty( $rendered, 'render_block must return non-empty HTML for an etch/element.' );
		self::assertStringContainsString( 'test-smoke', $rendered );
		self::assertStringContainsString( 'Hello from Etch Builders', $rendered );
	}

	public function test_environment_is_configured_with_wp_storage(): void {
		$storage = \HonestlyDesign\EtchBuilders\Environment::storage();
		self::assertInstanceOf( WpStorage::class, $storage, 'Environment must be configured with WpStorage.' );
	}

	public function test_etch_plugin_is_active(): void {
		self::assertTrue( class_exists( 'Etch\\Blocks\\ElementBlock\\ElementBlock' ), 'Etch plugin must be active.' );
	}
}
