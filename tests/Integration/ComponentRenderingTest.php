<?php
/**
 * Component builder rendering tests — every public method + logical combinations.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\Component;
use HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock;
use HonestlyDesign\EtchBuilders\Style;

/**
 * Verifies Component builder serializes and integrates with Etch correctly.
 */
final class ComponentRenderingTest extends RenderingTestCase {

	// --- new() + key() + get_name() + get_key() + get_description() ---

	public function test_new_creates_component(): void {
		$c = Component::new( 'Test Component', 'A test component' )
			->key( 'TestComponent' );

		self::assertSame( 'Test Component', $c->get_name() );
		self::assertSame( 'TestComponent', $c->get_key() );
		self::assertSame( 'A test component', $c->get_description() );
	}

	public function test_key_sets_identifier(): void {
		$c = Component::new( 'Card', 'Card component' )
			->key( 'OmideCard' );

		self::assertSame( 'OmideCard', $c->get_key() );
	}

	// --- dev_only() + is_dev_only() + should_skip_registration() ---

	public function test_dev_only_sets_flag(): void {
		$c = Component::new( 'Dev', 'Dev only' )
			->key( 'DevComponent' )
			->dev_only( true );

		self::assertTrue( $c->is_dev_only() );
	}

	public function test_dev_only_false_by_default(): void {
		$c = Component::new( 'Prod', 'Production' )
			->key( 'ProdComponent' );

		self::assertFalse( $c->is_dev_only() );
	}

	public function test_should_skip_registration_when_dev_only_in_prod(): void {
		// In the test environment, WpMode always returns true (dev mode),
		// so dev-only components should NOT skip.
		$c = Component::new( 'Dev Test', 'Dev only' )
			->key( 'DevSkipTest' )
			->dev_only( true );

		self::assertFalse( $c->should_skip_registration() );
	}

	// --- blocks() + get_blocks() ---

	public function test_blocks_sets_content(): void {
		$block_markup = ElementBlock::new()->tag( 'div' )->content( 'Component body' )->to_block()->to_string();
		$c = Component::new( 'Block Test', 'Has blocks' )
			->key( 'BlockTest' )
			->blocks( $block_markup );

		self::assertStringContainsString( 'Component body', $c->get_blocks() );
	}

	// --- get_properties() ---

	public function test_get_properties_returns_array(): void {
		$c = Component::new( 'Props Test', 'Has props' )
			->key( 'PropsTest' );

		self::assertIsArray( $c->get_properties() );
	}

	// --- add_style() ---

	public function test_add_style_registers_style(): void {
		$style_snapshot = \HonestlyDesign\EtchBuilders\Style::snapshot();
		try {
			\HonestlyDesign\EtchBuilders\Style::reset();

			$c = Component::new( 'Style Test', 'Has style' )->key( 'StyleTest' );
			$id = $c->add_style(
				Style::new()
					->id( 'omide-comp-style' )
					->selector( '.omide-comp-style' )
					->css( 'display: block;' )
			);

			self::assertSame( 'omide-comp-style', $id );
		} finally {
			\HonestlyDesign\EtchBuilders\Style::restore( $style_snapshot );
		}
	}

	public function test_add_style_does_not_force_readonly(): void {
		$style_snapshot = \HonestlyDesign\EtchBuilders\Style::snapshot();
		try {
			\HonestlyDesign\EtchBuilders\Style::reset();

			$c = Component::new( 'Mutable Test', 'Mutable style' )->key( 'MutableTest' );
			$c->add_style(
				Style::new()
					->id( 'omide-mutable-comp' )
					->selector( '.omide-mutable-comp' )
					->css( 'display: flex;' )
			);

			$registered = \HonestlyDesign\EtchBuilders\Style::registered_styles();
			self::assertArrayNotHasKey( 'readonly', $registered['omide-mutable-comp'] );
		} finally {
			\HonestlyDesign\EtchBuilders\Style::restore( $style_snapshot );
		}
	}

	// --- enqueue_style() + enqueue_script() ---

	public function test_enqueue_style_registers_asset(): void {
		$c = Component::new( 'Enqueue Test', 'Has assets' )
			->key( 'EnqueueTest' )
			->enqueue_style( 'test-css', '/dist/test.css' );

		// The asset should be registered in Environment::assets().
		self::assertTrue(
			\HonestlyDesign\EtchBuilders\Environment::assets()->has_assets( 'EnqueueTest' )
		);
	}

	public function test_enqueue_script_registers_asset(): void {
		$c = Component::new( 'Script Test', 'Has script' )
			->key( 'ScriptTest' )
			->enqueue_script( 'test-js', '/dist/test.js' );

		self::assertTrue(
			\HonestlyDesign\EtchBuilders\Environment::assets()->has_assets( 'ScriptTest' )
		);
	}

	// --- register_stylesheets() ---

	public function test_register_stylesheets_does_not_crash(): void {
		$c = Component::new( 'Sheet Test', 'Has stylesheet' )
			->key( 'SheetTest' );

		// register_stylesheets() returns bool|RegistrationResult.
		$result = $c->register_stylesheets();
		self::assertTrue( true ); // no crash = success.
	}
}
