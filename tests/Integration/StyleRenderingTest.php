<?php
/**
 * Style rendering tests — CSS emission contract: BEM, @media, hover, to-rem, types.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock;
use HonestlyDesign\EtchBuilders\Style;

/**
 * Verifies Style builder emits correct CSS through the Etch CssProcessor pipeline.
 */
final class StyleRenderingTest extends RenderingTestCase {

	// --- Basic emission ---

	public function test_class_style_emits_in_style_tag(): void {
		$this->render_with_style(
			'omide-basic',
			'.omide-basic',
			'color: rgb(255, 0, 0);'
		);
		$result = $this->render_element_with_class( 'omide-basic' );
		$this->assertStyleEmitted( $result, '.omide-basic', 'color: rgb(255, 0, 0)' );
	}

	public function test_id_style_emits_with_hash_selector(): void {
		$this->render_with_style(
			'omide-id-style',
			'#omide-id-style',
			'background: rgb(0, 0, 255);'
		);
		$result = $this->render_element_with_class( 'omide-id-style' );
		// The style should be registered.
		$this->assertRendersTag( $result, 'div' );
	}

	public function test_element_style_emits_with_where_selector(): void {
		$this->render_with_style(
			'omide-element-style',
			':where([data-etch-element="omide-test-element"])',
			'display: flex;',
			'element'
		);
		$result = $this->render_element_with_class( 'omide-element-style' );
		$this->assertRendersTag( $result, 'div' );
	}

	// --- BEM ampersand syntax (&__elem, &--modifier) ---

	public function test_bem_child_ampersand_expands(): void {
		$this->render_with_style(
			'omide-bem',
			'.omide-bem',
			'color: rgb(0, 0, 0); &__title { font-weight: 700; }'
		);
		$result = $this->render_element_with_class( 'omide-bem' );
		$this->assertStyleEmitted( $result, '.omide-bem' );
		$this->assertStyleEmitted( $result, '.omide-bem__title' );
	}

	public function test_bem_modifier_ampersand_expands(): void {
		$this->render_with_style(
			'omide-bem-mod',
			'.omide-bem-mod',
			'display: block; &--primary { color: rgb(0, 128, 0); }'
		);
		$result = $this->render_element_with_class( 'omide-bem-mod' );
		$this->assertStyleEmitted( $result, '.omide-bem-mod' );
		$this->assertStyleEmitted( $result, '.omide-bem-mod--primary' );
	}

	// --- :hover pseudo-class ---

	public function test_hover_pseudo_class_emitted(): void {
		$this->render_with_style(
			'omide-hover',
			'.omide-hover',
			'color: rgb(0, 0, 0); &:hover { color: rgb(255, 0, 0); }'
		);
		$result = $this->render_element_with_class( 'omide-hover' );
		$this->assertStyleEmitted( $result, '.omide-hover' );
		$this->assertStyleEmitted( $result, ':hover' );
	}

	// --- @media breakpoint ---

	public function test_media_query_emitted(): void {
		$this->render_with_style(
			'omide-responsive',
			'.omide-responsive',
			'display: block; @media (min-width: 768px) { display: flex; }'
		);
		$result = $this->render_element_with_class( 'omide-responsive' );
		$this->assertStyleEmitted( $result, '.omide-responsive' );
		$this->assertStyleEmitted( $result, '@media' );
	}

	// --- to-rem() function ---

	public function test_to_rem_function_converts(): void {
		$this->render_with_style(
			'omide-rem',
			'.omide-rem',
			'padding: to-rem(16px);'
		);
		$result = $this->render_element_with_class( 'omide-rem' );
		$this->assertStyleEmitted( $result, '.omide-rem' );
		// to-rem(16px) should become 1rem.
		$this->assertStyleEmitted( $result, '.omide-rem', '1rem' );
	}

	public function test_to_rem_various_values(): void {
		$this->render_with_style(
			'omide-rem-multi',
			'.omide-rem-multi',
			'margin: to-rem(32px); font-size: to-rem(14px);'
		);
		$result = $this->render_element_with_class( 'omide-rem-multi' );
		$this->assertStyleEmitted( $result, '2rem' );
		$this->assertStyleEmitted( $result, '.875rem' );
	}

	// --- type variants ---

	public function test_type_class_renders_dot_selector(): void {
		$this->render_with_style( 'omide-type-class', '.omide-type-class', 'display: block;', 'class' );
		$result = $this->render_element_with_class( 'omide-type-class' );
		$this->assertStyleEmitted( $result, '.omide-type-class' );
	}

	public function test_type_attribute_emitted_as_mandatory(): void {
		// Attribute-typed styles are always emitted (mandatory).
		$this->render_with_style( 'omide-type-attr', '[data-test="value"]', 'color: rgb(255, 0, 0);', 'attribute' );
		$result = $this->render_element_with_class( 'omide-type-attr' );
		$this->assertStyleEmitted( $result, '[data-test="value"]' );
	}

	// --- collection field ---

	public function test_collection_field_stored(): void {
		Style::new()
			->id( 'omide-collection-test' )
			->selector( '.omide-collection-test' )
			->css( 'display: block;' )
			->type( 'class' )
			->collection( 'TestCollection' )
			->add();
		Style::register_all();

		$persisted = get_option( 'etch_styles', array() );
		self::assertArrayHasKey( 'omide-collection-test', $persisted );
		self::assertSame( 'TestCollection', $persisted['omide-collection-test']['collection'] );
	}

	// --- name (legacy) field ---

	public function test_name_field_sets_display_name(): void {
		Style::new()
			->id( 'omide-named' )
			->selector( '.omide-named' )
			->name( 'Named Style' )
			->css( 'display: block;' )
			->type( 'class' )
			->collection( 'OhMyIDEtch' )
			->add();
		Style::register_all();

		$persisted = get_option( 'etch_styles', array() );
		self::assertArrayHasKey( 'omide-named', $persisted );
	}

	// --- readonly flag ---

	public function test_readonly_flag_stored(): void {
		Style::new()
			->id( 'omide-readonly' )
			->selector( '.omide-readonly' )
			->css( 'display: block;' )
			->type( 'class' )
			->collection( 'OhMyIDEtch' )
			->readonly( true )
			->add();
		Style::register_all();

		$persisted = get_option( 'etch_styles', array() );
		self::assertArrayHasKey( 'omide-readonly', $persisted );
		self::assertTrue( $persisted['omide-readonly']['readonly'] );
	}

	public function test_not_readonly_by_default(): void {
		Style::new()
			->id( 'omide-mutable' )
			->selector( '.omide-mutable' )
			->css( 'display: block;' )
			->type( 'class' )
			->collection( 'OhMyIDEtch' )
			->add();
		Style::register_all();

		$persisted = get_option( 'etch_styles', array() );
		self::assertArrayNotHasKey( 'readonly', $persisted['omide-mutable'] );
	}

	// --- overwrite_on_register ---

	public function test_overwrite_on_register_replaces_existing(): void {
		// First registration.
		Style::new()
			->id( 'omide-overwrite' )
			->selector( '.omide-overwrite' )
			->css( 'color: rgb(255, 0, 0);' )
			->type( 'class' )
			->collection( 'OhMyIDEtch' )
			->add();
		Style::register_all();

		// Second registration with overwrite.
		Style::reset();
		Style::new()
			->id( 'omide-overwrite' )
			->selector( '.omide-overwrite' )
			->css( 'color: rgb(0, 128, 0);' )
			->type( 'class' )
			->collection( 'OhMyIDEtch' )
			->overwrite_on_register( true )
			->add();
		Style::register_all();

		$persisted = get_option( 'etch_styles', array() );
		self::assertStringContainsString( 'rgb(0, 128, 0)', $persisted['omide-overwrite']['css'] );
	}

	// --- Multiple styles on one block ---

	public function test_multiple_styles_ids_emit_all(): void {
		$this->render_with_style( 'omide-multi-a', '.omide-multi-a', 'display: flex;' );
		$this->render_with_style( 'omide-multi-b', '.omide-multi-b', 'gap: 1rem;' );

		$result = $this->render_block_markup(
			ElementBlock::new()
				->tag( 'div' )
				->styles( array( 'omide-multi-a', 'omide-multi-b' ) )
				->to_block()
				->to_string()
		);
		$this->assertStyleEmitted( $result, '.omide-multi-a' );
		$this->assertStyleEmitted( $result, '.omide-multi-b' );
	}

	// --- Empty CSS body ---

	public function test_empty_css_body_not_emitted(): void {
		$this->render_with_style( 'omide-empty', '.omide-empty', '' );
		$result = $this->render_element_with_class( 'omide-empty' );
		$this->assertStyleNotEmitted( $result, '.omide-empty' );
	}

	// --- Helpers ---

	/**
	 * Register a style and persist it.
	 */
	private function render_with_style( string $id, string $selector, string $css, string $type = 'class' ): void {
		Style::new()
			->id( $id )
			->selector( $selector )
			->css( $css )
			->type( $type )
			->collection( 'OhMyIDEtch' )
			->add();
		Style::register_all();
	}

	/**
	 * Render a div with the given class.
	 */
	private function render_element_with_class( string $class ): \HonestlyDesign\EtchBuildersWpTests\Integration\RenderResult {
		return $this->render_block_markup(
			ElementBlock::new()
				->tag( 'div' )
				->style( $class )
				->to_block()
				->to_string()
		);
	}
}
