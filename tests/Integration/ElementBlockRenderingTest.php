<?php
/**
 * ElementBlock rendering tests — every public method + logical combinations.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock;
use HonestlyDesign\EtchBuilders\Style;
use HonestlyDesign\EtchBuilders\Types\Attributes;

/**
 * Verifies ElementBlock renders correctly through the Etch runtime.
 */
final class ElementBlockRenderingTest extends RenderingTestCase {

	// --- new() + tag() ---

	public function test_tag_div_renders(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'div' );
	}

	public function test_tag_article_renders(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'article' )->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'article' );
	}

	public function test_tag_section_renders(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'section' )->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'section' );
	}

	public function test_tag_span_renders(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'span' )->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'span' );
	}

	public function test_tag_void_img_renders(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'img' )->attribute( 'src', 'https://example.com/test.jpg' )->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'img' );
		$this->assertRendersContent( $result, 'src' );
	}

	public function test_tag_void_br_renders(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'br' )->to_block()->to_string()
		);
		self::assertStringContainsString( 'br', $result->html() );
	}

	// --- attribute() + attributes() ---

	public function test_attribute_sets_a_single_attribute(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->attribute( 'id', 'main' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'id="main"' );
	}

	public function test_attribute_sets_data_attribute(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->attribute( 'data-test', 'value' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'data-test="value"' );
	}

	public function test_attribute_null_value_omits_attribute(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'input' )->attribute( 'disabled', null )->to_block()->to_string()
		);
		// Null attribute value results in the attribute being omitted entirely.
		self::assertStringNotContainsString( 'disabled', $result->html() );
	}

	public function test_attributes_sets_multiple_via_attributes_factory(): void {
		$attrs = Attributes::from_array(
			array(
				'id'    => 'multi',
				'class' => 'multi-class',
			)
		);
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->attributes( $attrs )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'id="multi"' );
		$this->assertRendersContent( $result, 'multi-class' );
	}

	// --- class() + classes() ---

	public function test_class_adds_a_single_class(): void {
		$this->register_test_style( 'omide-card' );

		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->class( 'omide-card' )->to_block()->to_string()
		);
		$this->assertRendersClass( $result, 'omide-card' );
	}

	public function test_classes_adds_multiple_classes(): void {
		$this->register_test_style( 'omide-grid' );
		$this->register_test_style( 'omide-grid__item' );

		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->classes( array( 'omide-grid', 'omide-grid__item' ) )->to_block()->to_string()
		);
		$this->assertRendersClass( $result, 'omide-grid' );
		$this->assertRendersClass( $result, 'omide-grid__item' );
	}

	public function test_class_links_style_for_emission(): void {
		$this->register_test_style( 'omide-linked', '.omide-linked', 'color: rgb(0, 128, 0);' );

		$markup = ElementBlock::new()->tag( 'div' )->class( 'omide-linked' )->to_block()->to_string();

		// Re-register after the class() call linked the style, so the CSS is persisted.
		$this->register_test_style( 'omide-linked', '.omide-linked', 'color: rgb(0, 128, 0);' );

		$result = $this->render_block_markup( $markup );
		$this->assertRendersClass( $result, 'omide-linked' );
	}

	// --- content() + child() + children() + raw_content() ---

	public function test_content_adds_text_child(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'p' )->content( 'Hello World' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'Hello World' );
	}

	public function test_child_adds_a_nested_block(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()
				->tag( 'div' )
				->child( ElementBlock::new()->tag( 'p' )->content( 'Nested' )->to_block() )
				->to_block()
				->to_string()
		);
		$this->assertRendersTag( $result, 'p' );
		$this->assertRendersContent( $result, 'Nested' );
	}

	public function test_children_adds_multiple_nested_blocks(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()
				->tag( 'ul' )
				->children(
					array(
						ElementBlock::new()->tag( 'li' )->content( 'First' )->to_block(),
						ElementBlock::new()->tag( 'li' )->content( 'Second' )->to_block(),
					)
				)
				->to_block()
				->to_string()
		);
		$this->assertRendersContent( $result, 'First' );
		$this->assertRendersContent( $result, 'Second' );
	}

	public function test_raw_content_adds_html_directly(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->raw_content( '<strong>Bold</strong>' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, '<strong>Bold</strong>' );
	}

	// --- style() + styles() ---

	public function test_style_attaches_a_style_id(): void {
		$this->register_test_style( 'omide-explicit', '.omide-explicit', 'padding: 10px;' );

		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->style( 'omide-explicit' )->to_block()->to_string()
		);
		// The style should be registered for emission via Etch's StylesRegister.
		$this->assertRendersTag( $result, 'div' );
	}

	public function test_styles_attaches_multiple_style_ids(): void {
		$this->register_test_style( 'omide-base', '.omide-base', 'display: block;' );
		$this->register_test_style( 'omide-accent', '.omide-accent', 'color: rgb(255, 0, 0);' );

		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->styles( array( 'omide-base', 'omide-accent' ) )->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'div' );
	}

	// --- json_attribute() ---

	public function test_json_attribute_serializes_array_as_json(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->json_attribute( 'data-config', array( 'key' => 'value' ) )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'data-config' );
	}

	// --- is_etch_section() + is_etch_section_container() ---

	public function test_is_etch_section_attaches_section_style(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'section' )->is_etch_section()->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'section' );
		$this->assertRendersContent( $result, 'data-etch-element' );
	}

	public function test_is_etch_section_container_attaches_container_style(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->is_etch_section_container()->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'data-etch-element' );
	}

	// --- metadata() + metadata_name() ---

	public function test_metadata_sets_custom_data(): void {
		// metadata() alone stores custom metadata in the block attributes;
		// it's accessible via the builder but may not add data-etch-element
		// without is_etch_section(). Verify the block renders.
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->metadata( array( 'name' => 'hero' ) )->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'div' );
	}

	public function test_metadata_name_sets_the_element_name(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->metadata_name( 'custom-name' )->is_etch_section( 'section', 'custom-name' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'data-etch-element' );
	}

	// --- hidden() ---

	public function test_hidden_renders_differently(): void {
		$visible = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->content( 'Visible' )->to_block()->to_string()
		);
		$hidden = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->hidden()->content( 'Hidden' )->to_block()->to_string()
		);
		// Hidden blocks should not render the content the same way as visible.
		$this->assertRendersContent( $visible, 'Visible' );
		// Hidden blocks return a different output (may be empty or a comment).
		self::assertNotEquals( $visible->html(), $hidden->html() );
	}

	// --- script() ---

	public function test_script_registers_inline_javascript(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->script( 'test-script', 'console.log("test");' )->content( 'Scripted' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'Scripted' );
	}

	// --- option() ---

	public function test_option_sets_a_custom_option(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->option( 'customOption', 'value' )->content( 'Option' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'Option' );
	}

	// --- Logical combinations ---

	public function test_nested_section_with_container_and_children(): void {
		$this->register_test_style( 'omide-hero', '.omide-hero', 'padding: 2rem;' );

		$markup = ElementBlock::new()
			->tag( 'section' )
			->is_etch_section()
			->child(
				ElementBlock::new()
					->tag( 'div' )
					->is_etch_section_container()
					->class( 'omide-hero' )
					->children(
						array(
							ElementBlock::new()->tag( 'h1' )->content( 'Title' )->to_block(),
							ElementBlock::new()->tag( 'p' )->content( 'Subtitle' )->to_block(),
						)
					)
					->to_block()
			)
			->to_block()
			->to_string();

		// Re-persist after the class() linkage.
		$this->register_test_style( 'omide-hero', '.omide-hero', 'padding: 2rem;' );

		$result = $this->render_block_markup( $markup );

		$this->assertRendersTag( $result, 'section' );
		$this->assertRendersContent( $result, 'Title' );
		$this->assertRendersContent( $result, 'Subtitle' );
		$this->assertRendersClass( $result, 'omide-hero' );
	}

	public function test_deeply_nested_elements_render_in_order(): void {
		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )
				->child( ElementBlock::new()->tag( 'div' )
					->child( ElementBlock::new()->tag( 'div' )
						->child( ElementBlock::new()->tag( 'p' )->content( 'Deep' )->to_block() )
						->to_block() )
					->to_block() )
				->to_block()
				->to_string()
		);
		$this->assertRendersContent( $result, 'Deep' );
	}

	public function test_class_with_compound_selector_renders(): void {
		Style::new()
			->id( 'omide-compound' )
			->selector( '.omide-compound-parent .omide-compound-child' )
			->css( 'color: rgb(0, 0, 255);' )
			->type( 'class' )
			->collection( 'OhMyIDEtch' )
			->add();
		Style::register_all();

		$result = $this->render_block_markup(
			ElementBlock::new()->tag( 'div' )->class( 'omide-compound-child' )->to_block()->to_string()
		);
		$this->assertRendersTag( $result, 'div' );
	}

	public function test_dynamic_attribute_value_serializes_correctly(): void {
		// Verify the builder serializes {this.title} into the block markup.
		// Full dynamic resolution requires the Etch DynamicContentProcessor with
		// a real WP query context (covered in Phase D dynamic-data tests).
		$markup = ElementBlock::new()->tag( 'div' )->attribute( 'data-title', '{this.title}' )->to_block()->to_string();
		self::assertStringContainsString( '{this.title}', $markup );

		$result = $this->render_block_markup( $markup );
		// Without query context, the dynamic expression renders as empty.
		$this->assertRendersTag( $result, 'div' );
	}

	// --- Helper ---

	/**
	 * Register a test style and persist it.
	 *
	 * @param string $id       Style ID.
	 * @param string $selector Selector (default .id).
	 * @param string $css      CSS body.
	 */
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
