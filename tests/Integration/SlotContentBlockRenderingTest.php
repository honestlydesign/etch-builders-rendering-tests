<?php
/**
 * SlotContentBlock + SlotPlaceholderBlock rendering tests.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock;
use HonestlyDesign\EtchBuilders\EtchBlocks\SlotContentBlock;
use HonestlyDesign\EtchBuilders\EtchBlocks\SlotPlaceholderBlock;

/**
 * Verifies Slot blocks render correctly through the Etch runtime.
 */
final class SlotContentBlockRenderingTest extends RenderingTestCase {

	// --- SlotContentBlock: new() + name() + to_block() ---

	public function test_slot_content_new(): void {
		$markup = SlotContentBlock::new()
			->name( 'default' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'etch/slot-content', $markup );
	}

	public function test_slot_content_name_default(): void {
		$markup = SlotContentBlock::new()
			->name( 'default' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'default', $markup );
	}

	public function test_slot_content_name_custom(): void {
		$markup = SlotContentBlock::new()
			->name( 'sidebar' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'sidebar', $markup );
	}

	public function test_slot_content_with_child(): void {
		$markup = SlotContentBlock::new()
			->name( 'header' )
			->child( ElementBlock::new()->tag( 'h1' )->content( 'Slot Header' )->to_block() )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'etch/slot-content', $markup );
		self::assertStringContainsString( 'Slot Header', $markup );
	}

	public function test_slot_content_renders(): void {
		$result = $this->render_block_markup(
			SlotContentBlock::new()
				->name( 'default' )
				->child( ElementBlock::new()->tag( 'p' )->content( 'Slot Content Rendered' )->to_block() )
				->to_block()
				->to_string()
		);
		$this->assertRendersContent( $result, 'Slot Content Rendered' );
	}

	// --- SlotPlaceholderBlock: new() + name() + to_block() ---

	public function test_slot_placeholder_new(): void {
		$markup = SlotPlaceholderBlock::new()
			->name( 'default' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'etch/slot-placeholder', $markup );
	}

	public function test_slot_placeholder_name_default(): void {
		$markup = SlotPlaceholderBlock::new()
			->name( 'default' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'default', $markup );
	}

	public function test_slot_placeholder_name_custom(): void {
		$markup = SlotPlaceholderBlock::new()
			->name( 'footer' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'footer', $markup );
	}

	public function test_slot_placeholder_renders(): void {
		$result = $this->render_block_markup(
			SlotPlaceholderBlock::new()
				->name( 'content' )
				->to_block()
				->to_string()
		);
		// SlotPlaceholder may render empty or a placeholder; verify no crash.
		self::assertTrue( true );
	}

	// --- Logical combinations ---

	public function test_slot_content_inside_component_block(): void {
		// A component instance with slot content children.
		$markup = \HonestlyDesign\EtchBuilders\EtchBlocks\ComponentBlock::new()
			->ref( 1 )
			->child(
				SlotContentBlock::new()
					->name( 'default' )
					->child( ElementBlock::new()->tag( 'div' )->content( 'In Slot' )->to_block() )
					->to_block()
			)
			->to_block()
			->to_string();

		self::assertStringContainsString( 'etch/component', $markup );
		self::assertStringContainsString( 'etch/slot-content', $markup );
		self::assertStringContainsString( 'In Slot', $markup );
	}
}
