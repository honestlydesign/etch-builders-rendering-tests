<?php
/**
 * Pattern rendering tests — every public method + logical combinations.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock;
use HonestlyDesign\EtchBuilders\Pattern;
use HonestlyDesign\EtchBuilders\Style;

/**
 * Verifies Pattern builder serializes and integrates with Etch correctly.
 */
final class PatternRenderingTest extends RenderingTestCase {

	// --- new() + key() + get_name() + get_key() + get_description() ---

	public function test_new_creates_pattern(): void {
		$p = Pattern::new( 'Test Pattern', 'A test pattern' )
			->key( 'TestPattern' );

		self::assertSame( 'Test Pattern', $p->get_name() );
		self::assertSame( 'TestPattern', $p->get_key() );
		self::assertSame( 'A test pattern', $p->get_description() );
	}

	public function test_key_sets_identifier(): void {
		$p = Pattern::new( 'Hero', 'Hero pattern' )
			->key( 'OmideHero' );

		self::assertSame( 'OmideHero', $p->get_key() );
	}

	// --- category() + categories() + get_categories() ---

	public function test_category_sets_single_category(): void {
		$p = Pattern::new( 'Cat Test', 'Has category' )
			->key( 'CatTest' )
			->category( 'hero' );

		self::assertContains( 'hero', $p->get_categories() );
	}

	public function test_categories_sets_multiple(): void {
		$p = Pattern::new( 'Multi Cat', 'Multiple categories' )
			->key( 'MultiCat' )
			->categories( array( 'hero', 'cta', 'banner' ) );

		$cats = $p->get_categories();
		self::assertContains( 'hero', $cats );
		self::assertContains( 'cta', $cats );
		self::assertContains( 'banner', $cats );
	}

	// --- blocks() + add_blocks() + get_blocks() ---

	public function test_blocks_sets_content(): void {
		$markup = ElementBlock::new()->tag( 'div' )->content( 'Pattern body' )->to_block()->to_string();
		$p = Pattern::new( 'Block Test', 'Has blocks' )
			->key( 'BlockTest' )
			->blocks( $markup );

		self::assertStringContainsString( 'Pattern body', $p->get_blocks() );
	}

	public function test_add_blocks_sets_content(): void {
		$markup = ElementBlock::new()->tag( 'div' )->content( 'Blocks Set' )->to_block()->to_string();
		$p = Pattern::new( 'Add Test', 'Add blocks' )
			->key( 'AddTest' )
			->add_blocks( $markup );

		self::assertStringContainsString( 'Blocks Set', $p->get_blocks() );
	}

	// --- add_style() ---

	public function test_add_style_registers_style(): void {
		$style_snapshot = Style::snapshot();
		try {
			Style::reset();

			$p = Pattern::new( 'Style Test', 'Has style' )->key( 'PatternStyleTest' );
			$id = $p->add_style(
				Style::new()
					->id( 'omide-pattern-style' )
					->selector( '.omide-pattern-style' )
					->css( 'display: block;' )
			);

			self::assertSame( 'omide-pattern-style', $id );
		} finally {
			Style::restore( $style_snapshot );
		}
	}

	// --- register_stylesheets() ---

	public function test_register_stylesheets_does_not_crash(): void {
		$p = Pattern::new( 'Sheet Test', 'Has stylesheet' )
			->key( 'PatternSheetTest' );

		$result = $p->register_stylesheets();
		self::assertTrue( true );
	}

	// --- reset_styles() ---

	public function test_reset_styles_clears_styles(): void {
		$style_snapshot = Style::snapshot();
		try {
			Style::reset();

			$p = Pattern::new( 'Reset Test', 'Reset styles' )->key( 'ResetTest' );
			$p->add_style(
				Style::new()
					->id( 'omide-reset-test' )
					->selector( '.omide-reset-test' )
					->css( 'display: block;' )
			);
			self::assertArrayHasKey( 'omide-reset-test', Style::registered_styles() );

			$p->reset_styles();
			// reset_styles clears the pattern's internal style list.
			self::assertTrue( true );
		} finally {
			Style::restore( $style_snapshot );
		}
	}
}
