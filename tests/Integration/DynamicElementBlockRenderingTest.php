<?php
/**
 * DynamicElementBlock rendering tests — every public method + logical combinations.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\EtchBlocks\DynamicElementBlock;
use HonestlyDesign\EtchBuilders\Style;
use HonestlyDesign\EtchBuilders\Types\Attributes;

/**
 * Verifies DynamicElementBlock renders correctly through the Etch runtime.
 *
 * DynamicElementBlock is like ElementBlock but the tag can itself be a dynamic
 * attribute (resolved at render time).
 */
final class DynamicElementBlockRenderingTest extends RenderingTestCase {

	// --- new() + tag() ---

	public function test_new_with_tag_div(): void {
		$result = $this->render_block_markup(
			DynamicElementBlock::new()->tag( 'div' )->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'div' );
	}

	public function test_tag_sets_html_tag(): void {
		$result = $this->render_block_markup(
			DynamicElementBlock::new()->tag( 'article' )->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'article' );
	}

	public function test_tag_section(): void {
		$result = $this->render_block_markup(
			DynamicElementBlock::new()->tag( 'section' )->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'section' );
	}

	public function test_tag_span(): void {
		$result = $this->render_block_markup(
			DynamicElementBlock::new()->tag( 'span' )->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'span' );
	}

	public function test_tag_header_footer(): void {
		$header = $this->render_block_markup(
			DynamicElementBlock::new()->tag( 'header' )->to_block()->to_string()
		);
		$this->assertRendersTag( $header, 'header' );

		$footer = $this->render_block_markup(
			DynamicElementBlock::new()->tag( 'footer' )->to_block()->to_string()
		);
		$this->assertRendersTag( $footer, 'footer' );
	}

	// --- attribute() + attributes() ---

	public function test_attribute_sets_a_single_attribute(): void {
		$result = $this->render_block_markup(
			DynamicElementBlock::new()->tag( 'div' )->attribute( 'id', 'dynamic-main' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'id="dynamic-main"' );
	}

	public function test_attribute_sets_data_attribute(): void {
		$result = $this->render_block_markup(
			DynamicElementBlock::new()->tag( 'div' )->attribute( 'data-dynamic', 'value' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'data-dynamic="value"' );
	}

	public function test_attribute_sets_alpine_directive(): void {
		$result = $this->render_block_markup(
			DynamicElementBlock::new()->tag( 'div' )->attribute( 'x-data', '{ open: false }' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'x-data' );
	}

	public function test_attributes_sets_multiple(): void {
		$attrs = Attributes::from_array(
			array(
				'id'    => 'dyn-multi',
				'class' => 'dyn-class',
			)
		);
		$result = $this->render_block_markup(
			DynamicElementBlock::new()->tag( 'div' )->attributes( $attrs )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'id="dyn-multi"' );
	}

	// --- style() + styles() ---

	public function test_style_attaches_a_style_id(): void {
		$this->register_test_style( 'omide-dyn-style', '.omide-dyn-style', 'display: flex;' );

		$result = $this->render_block_markup(
			DynamicElementBlock::new()->tag( 'div' )->style( 'omide-dyn-style' )->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'div' );
		$this->assertStyleEmitted( $result, '.omide-dyn-style' );
	}

	public function test_styles_attaches_multiple(): void {
		$this->register_test_style( 'omide-dyn-a', '.omide-dyn-a', 'display: block;' );
		$this->register_test_style( 'omide-dyn-b', '.omide-dyn-b', 'color: rgb(255, 0, 0);' );

		$result = $this->render_block_markup(
			DynamicElementBlock::new()->tag( 'div' )->styles( array( 'omide-dyn-a', 'omide-dyn-b' ) )->to_block()->to_string()
		);
		$this->assertStyleEmitted( $result, '.omide-dyn-a' );
		$this->assertStyleEmitted( $result, '.omide-dyn-b' );
	}

	// --- Dynamic tag (the key difference from ElementBlock) ---

	public function test_dynamic_tag_serialization(): void {
		// DynamicElementBlock can have the tag resolved from an attribute at render time.
		// The serialized markup stores the tag in attrs.tag.
		$markup = DynamicElementBlock::new()->tag( 'div' )->to_block()->to_string();
		self::assertStringContainsString( '"tag":"div"', $markup );
	}

	public function test_dynamic_tag_with_content(): void {
		$result = $this->render_block_markup(
			DynamicElementBlock::new()
				->tag( 'div' )
				->attribute( 'class', 'dyn-content' )
				->to_block()
				->to_string()
		);
		$this->assertRendersClass( $result, 'dyn-content' );
	}

	// --- Logical combinations ---

	public function test_nested_dynamic_elements(): void {
		$result = $this->render_block_markup(
			DynamicElementBlock::new()
				->tag( 'div' )
				->attribute( 'class', 'outer' )
				->child(
					DynamicElementBlock::new()
						->tag( 'div' )
						->attribute( 'class', 'inner' )
						->to_block()
				)
				->to_block()
				->to_string()
		);
		$this->assertRendersClass( $result, 'outer' );
		$this->assertRendersClass( $result, 'inner' );
	}

	public function test_dynamic_element_with_text_child(): void {
		$result = $this->render_block_markup(
			DynamicElementBlock::new()
				->tag( 'div' )
				->child( \HonestlyDesign\EtchBuilders\EtchBlocks\TextBlock::new()->content( 'Dynamic child' )->to_block() )
				->to_block()
				->to_string()
		);
		$this->assertRendersContent( $result, 'Dynamic child' );
	}

	public function test_dynamic_element_with_linked_styles(): void {
		$this->register_test_style( 'omide-dyn-linked', '.omide-dyn-linked', 'padding: 1rem;' );

		$result = $this->render_block_markup(
			DynamicElementBlock::new()
				->tag( 'section' )
				->style( 'omide-dyn-linked' )
				->child( \HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock::new()->tag( 'h2' )->content( 'Styled Section' )->to_block() )
				->to_block()
				->to_string()
		);
		$this->assertRendersTag( $result, 'section' );
		$this->assertRendersContent( $result, 'Styled Section' );
		$this->assertStyleEmitted( $result, '.omide-dyn-linked' );
	}

	// --- Helper ---

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
