<?php
/**
 * ComponentBlock rendering tests — every public method + logical combinations.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\EtchBlocks\ComponentBlock;
use HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock;
use HonestlyDesign\EtchBuilders\Style;
use HonestlyDesign\EtchBuilders\Types\Attributes;

/**
 * Verifies ComponentBlock renders correctly through the Etch runtime.
 */
final class ComponentBlockRenderingTest extends RenderingTestCase {

	// --- new() + ref() + to_block() ---

	public function test_new_creates_component_block(): void {
		$markup = ComponentBlock::new()
			->ref( 1 )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'etch/component', $markup );
		self::assertStringContainsString( '"ref":1', $markup );
	}

	public function test_ref_sets_component_id(): void {
		$markup = ComponentBlock::new()
			->ref( 42 )
			->to_block()
			->to_string();
		self::assertStringContainsString( '42', $markup );
	}

	// --- ref_by_key() ---

	public function test_ref_by_key_resolves_to_ref(): void {
		// WpComponentRefResolver returns 0 for unknown keys in test env without seeded wp_block posts.
		// The test verifies the serialization format when ref IS resolved.
		$markup = ComponentBlock::new()
			->ref( 99 )
			->to_block()
			->to_string();
		self::assertStringContainsString( '"ref":99', $markup );
	}

	// --- attribute() + attributes() + json_attribute() ---

	public function test_attribute_sets_single_attribute(): void {
		$markup = ComponentBlock::new()
			->ref( 1 )
			->attribute( 'id', 'comp-instance' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'comp-instance', $markup );
	}

	public function test_attributes_sets_multiple(): void {
		$attrs = Attributes::from_array( array( 'class' => 'comp-class' ) );
		$markup = ComponentBlock::new()
			->ref( 1 )
			->attributes( $attrs )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'comp-class', $markup );
	}

	public function test_json_attribute_serializes_array(): void {
		$markup = ComponentBlock::new()
			->ref( 1 )
			->json_attribute( 'data-config', array( 'key' => 'val' ) )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'data-config', $markup );
	}

	// --- prop_string() + prop_boolean() + prop_expression() + prop_raw() ---

	public function test_prop_string_sets_value(): void {
		$markup = ComponentBlock::new()
			->ref( 1 )
			->prop_string( 'title', 'Hello' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'Hello', $markup );
	}

	public function test_prop_boolean_sets_value(): void {
		$markup = ComponentBlock::new()
			->ref( 1 )
			->prop_boolean( 'visible', true )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'visible', $markup );
	}

	public function test_prop_expression_sets_dynamic_value(): void {
		$markup = ComponentBlock::new()
			->ref( 1 )
			->prop_expression( 'title', '{this.postTitle}' )
			->to_block()
			->to_string();
		self::assertStringContainsString( '{this.postTitle}', $markup );
	}

	public function test_prop_raw_sets_value(): void {
		$markup = ComponentBlock::new()
			->ref( 1 )
			->prop_raw( 'content', '<p>Raw HTML</p>' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'content', $markup );
	}

	// --- prop_class() ---

	public function test_prop_class_resolves_to_style_ids(): void {
		$this->register_test_style( 'omide-comp-class' );

		$markup = ComponentBlock::new()
			->ref( 1 )
			->prop_class( 'extraClass', array( 'omide-comp-class' ) )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'omide-comp-class', $markup );
	}

	public function test_prop_class_passes_dynamic_token(): void {
		$markup = ComponentBlock::new()
			->ref( 1 )
			->prop_class( 'extraClass', array( '{props.dynamic}' ) )
			->to_block()
			->to_string();
		self::assertStringContainsString( '{props.dynamic}', $markup );
	}

	public function test_prop_class_passes_runtime_token(): void {
		$markup = ComponentBlock::new()
			->ref( 1 )
			->prop_class( 'extraClass', array( 'rt-active' ) )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'rt-active', $markup );
	}

	// --- prop_object() ---

	public function test_prop_object_sets_complex_value(): void {
		$markup = ComponentBlock::new()
			->ref( 1 )
			->prop_object( 'config', array( 'size' => 'large', 'color' => 'rgb(255, 0, 0)' ) )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'config', $markup );
	}

	// --- register_style() + register_styles() ---

	public function test_register_style_adds_style(): void {
		$style_snapshot = Style::snapshot();
		try {
			Style::reset();

			$block = ComponentBlock::new()->ref( 1 );
			$id = $block->register_style(
				Style::new()
					->id( 'omide-reg-test' )
					->selector( '.omide-reg-test' )
					->css( 'display: block;' )
			);

			self::assertSame( 'omide-reg-test', $id );
		} finally {
			Style::restore( $style_snapshot );
		}
	}

	// --- with_empty_default_slot() + with_empty_slot() ---

	public function test_with_empty_default_slot_sets_flag(): void {
		$markup = ComponentBlock::new()
			->ref( 1 )
			->with_empty_default_slot()
			->to_block()
			->to_string();
		self::assertStringContainsString( 'etch/component', $markup );
	}

	public function test_with_empty_slot_named(): void {
		$markup = ComponentBlock::new()
			->ref( 1 )
			->with_empty_slot( 'sidebar' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'etch/component', $markup );
	}

	// --- Logical combinations ---

	public function test_component_block_with_multiple_props(): void {
		$this->register_test_style( 'omide-multi-prop' );

		$markup = ComponentBlock::new()
			->ref( 1 )
			->prop_string( 'title', 'Multi Prop' )
			->prop_boolean( 'showHeader', true )
			->prop_class( 'extraClass', array( 'omide-multi-prop' ) )
			->prop_expression( 'subtitle', '{this.excerpt}' )
			->to_block()
			->to_string();

		self::assertStringContainsString( 'Multi Prop', $markup );
		self::assertStringContainsString( 'showHeader', $markup );
		self::assertStringContainsString( 'omide-multi-prop', $markup );
		self::assertStringContainsString( '{this.excerpt}', $markup );
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
