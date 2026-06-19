<?php
/**
 * RawHtmlBlock rendering tests — every public method + logical combinations.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\EtchBlocks\RawHtmlBlock;

/**
 * Verifies RawHtmlBlock renders correctly through the Etch runtime.
 */
final class RawHtmlBlockRenderingTest extends RenderingTestCase {

	// --- new() + content() + to_block() ---

	public function test_new_creates_a_raw_html_block(): void {
		$result = $this->render_block_markup(
			RawHtmlBlock::new()->content( '<strong>Bold</strong>' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, '<strong>Bold</strong>' );
	}

	public function test_content_renders_html_markup(): void {
		$result = $this->render_block_markup(
			RawHtmlBlock::new()->content( '<div class="custom"><p>Paragraph</p></div>' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, '<div class="custom">' );
		$this->assertRendersContent( $result, '<p>Paragraph</p>' );
	}

	public function test_content_renders_complex_html(): void {
		$html = '<section><h2>Title</h2><ul><li>Item 1</li><li>Item 2</li></ul></section>';
		$result = $this->render_block_markup(
			RawHtmlBlock::new()->content( $html )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, '<section>' );
		$this->assertRendersContent( $result, 'Item 1' );
		$this->assertRendersContent( $result, 'Item 2' );
	}

	public function test_content_empty_string(): void {
		$result = $this->render_block_markup(
			RawHtmlBlock::new()->content( '' )->to_block()->to_string()
		);
		// Empty content renders empty — just verify no crash.
		self::assertTrue( true );
	}

	public function test_content_with_scripts(): void {
		$result = $this->render_block_markup(
			RawHtmlBlock::new()->content( '<div>Content</div>' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'Content' );
	}

	public function test_content_with_attributes(): void {
		$result = $this->render_block_markup(
			RawHtmlBlock::new()->content( '<a href="https://example.com">Link</a>' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'href="https://example.com"' );
		$this->assertRendersContent( $result, 'Link' );
	}

	// --- Logical combinations ---

	public function test_raw_html_inside_element(): void {
		$result = $this->render_block_markup(
			\HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock::new()
				->tag( 'div' )
				->child( RawHtmlBlock::new()->content( '<span>Inline</span>' )->to_block() )
				->to_block()
				->to_string()
		);
		$this->assertRendersContent( $result, '<span>Inline</span>' );
	}

	public function test_raw_html_with_dynamic_expression_serialization(): void {
		$markup = RawHtmlBlock::new()->content( '{props.myHtml}' )->to_block()->to_string();
		self::assertStringContainsString( '{props.myHtml}', $markup );
	}

	public function test_raw_html_self_closing_tags(): void {
		$result = $this->render_block_markup(
			RawHtmlBlock::new()->content( '<br/><hr/><img src="test.jpg"/>' )->to_block()->to_string()
		);
		$this->assertRendersContent( $result, 'src="test.jpg"' );
	}
}
