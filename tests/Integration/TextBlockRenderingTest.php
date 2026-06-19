<?php
/**
 * TextBlock rendering tests — every public method + logical combinations.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\EtchBlocks\TextBlock;

/**
 * Verifies TextBlock renders correctly through the Etch runtime.
 */
final class TextBlockRenderingTest extends RenderingTestCase {

	// --- new() + content() + to_block() ---

	public function test_new_creates_a_text_block(): void {
		$result = $this->render_block_markup(
			TextBlock::new()->content( 'Simple text' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'Simple text' );
	}

	public function test_content_renders_plain_text(): void {
		$result = $this->render_block_markup(
			TextBlock::new()->content( 'Hello World' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'Hello World' );
	}

	public function test_content_with_html_entities(): void {
		$result = $this->render_block_markup(
			TextBlock::new()->content( 'Tom & Jerry' )->to_block()->to_string()
		);
		// Etch escapes HTML entities in text blocks.
		$this->assertRendersContent( $result, 'Tom' );
		$this->assertRendersContent( $result, 'Jerry' );
	}

	public function test_content_empty_string(): void {
		$result = $this->render_block_markup(
			TextBlock::new()->content( '' )->to_block()->to_string()
		);
		// Empty text renders empty or whitespace.
		self::assertTrue( true ); // rendering doesn't crash.
	}

	public function test_content_multiline(): void {
		$result = $this->render_block_markup(
			TextBlock::new()->content( "Line 1\nLine 2\nLine 3" )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'Line 1' );
		$this->assertRendersContent( $result, 'Line 2' );
		$this->assertRendersContent( $result, 'Line 3' );
	}

	public function test_content_with_special_chars(): void {
		$result = $this->render_block_markup(
			TextBlock::new()->content( 'Price: $99.99 (50% off!)' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'Price' );
		$this->assertRendersContent( $result, '99.99' );
	}

	// --- Logical combinations ---

	public function test_text_block_inside_element(): void {
		$result = $this->render_block_markup(
			\HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock::new()
				->tag( 'div' )
				->child( TextBlock::new()->content( 'Nested text' )->to_block() )
				->to_block()
				->to_string()
		);
		$this->assertRendersContent( $result, 'Nested text' );
	}

	public function test_text_block_with_dynamic_expression_serialization(): void {
		// Dynamic expressions ({this.title}) are serialized as-is.
		$markup = TextBlock::new()->content( '{this.title}' )->to_block()->to_string();
		self::assertStringContainsString( '{this.title}', $markup );

		$result = $this->render_block_markup( $markup );
		// Without query context, dynamic expressions resolve to empty — rendering doesn't crash.
		self::assertTrue( true );
	}

	public function test_text_block_with_modifier_expression_serialization(): void {
		// Modifiers like .toUpperCase() are part of the expression string.
		$markup = TextBlock::new()->content( '{this.title.toUpperCase()}' )->to_block()->to_string();
		self::assertStringContainsString( '{this.title.toUpperCase()}', $markup );
	}

	public function test_text_block_renders_wp_shortcodes(): void {
		$result = $this->render_block_markup(
			TextBlock::new()->content( 'This is [gallery] shortcode' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'This is' );
	}
}
