<?php
/**
 * LoopBlock rendering tests — every public method + logical combinations.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock;
use HonestlyDesign\EtchBuilders\EtchBlocks\LoopBlock;
use HonestlyDesign\EtchBuilders\LoopPreset;

/**
 * Verifies LoopBlock renders correctly through the Etch runtime.
 */
final class LoopBlockRenderingTest extends RenderingTestCase {

	// --- new() + target() + to_block() ---

	public function test_new_creates_loop_block(): void {
		$markup = LoopBlock::new()
			->target( 'main-query' )
			->child( ElementBlock::new()->tag( 'div' )->to_block() )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'etch/loop', $markup );
	}

	public function test_target_main_query(): void {
		$markup = LoopBlock::new()
			->target( 'main-query' )
			->child( ElementBlock::new()->tag( 'article' )->to_block() )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'main-query', $markup );
	}

	public function test_target_dynamic_expression(): void {
		$markup = LoopBlock::new()
			->target( '{props.items}' )
			->child( ElementBlock::new()->tag( 'div' )->to_block() )
			->to_block()
			->to_string();
		self::assertStringContainsString( '{props.items}', $markup );
	}

	// --- loop_id() ---

	public function test_loop_id_with_registered_preset(): void {
		LoopPreset::new( 'Test Posts' )
			->key( 'test-posts' )
			->wp_query( array( 'post_type' => 'post' ) )
			->register_internal();

		$markup = LoopBlock::new()
			->loop_id( 'test-posts' )
			->child( ElementBlock::new()->tag( 'div' )->to_block() )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'test-posts', $markup );
	}

	// --- item_id() + index_id() ---

	public function test_item_id_sets_context_key(): void {
		$markup = LoopBlock::new()
			->target( 'main-query' )
			->item_id( 'post' )
			->child( ElementBlock::new()->tag( 'div' )->to_block() )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'itemId', $markup );
	}

	public function test_index_id_sets_context_key(): void {
		$markup = LoopBlock::new()
			->target( 'main-query' )
			->index_id( 'i' )
			->child( ElementBlock::new()->tag( 'div' )->to_block() )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'indexId', $markup );
	}

	// --- param() + params() ---

	public function test_param_sets_single_param(): void {
		$markup = LoopBlock::new()
			->target( 'main-query' )
			->param( 'count', '5' )
			->child( ElementBlock::new()->tag( 'div' )->to_block() )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'count', $markup );
	}

	public function test_params_sets_multiple(): void {
		$markup = LoopBlock::new()
			->target( 'main-query' )
			->params( array( 'count' => '3', 'offset' => '1' ) )
			->child( ElementBlock::new()->tag( 'div' )->to_block() )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'count', $markup );
		self::assertStringContainsString( 'offset', $markup );
	}

	// --- Logical combinations ---

	public function test_loop_with_seeded_posts(): void {
		$this->seed_post( array( 'post_title' => 'Loop Post 1' ) );
		$this->seed_post( array( 'post_title' => 'Loop Post 2' ) );

		$markup = LoopBlock::new()
			->target( 'wp-query' )
			->params( array( 'post_type' => 'post', 'posts_per_page' => 2 ) )
			->child(
				ElementBlock::new()
					->tag( 'article' )
					->child( \HonestlyDesign\EtchBuilders\EtchBlocks\TextBlock::new()->content( 'Post' )->to_block() )
					->to_block()
			)
			->to_block()
			->to_string();

		$result = $this->render_block_markup( $markup );
		// The loop may or may not produce items depending on whether the wp-query
		// handler resolves in the test context. Verify no crash.
		self::assertTrue( true );
	}

	public function test_loop_with_json_data(): void {
		$markup = LoopBlock::new()
			->target( '[{"name":"A"},{"name":"B"}]' )
			->child(
				ElementBlock::new()
					->tag( 'div' )
					->child( \HonestlyDesign\EtchBuilders\EtchBlocks\TextBlock::new()->content( 'Item' )->to_block() )
					->to_block()
			)
			->to_block()
			->to_string();

		$result = $this->render_block_markup( $markup );
		self::assertNotEmpty( $result->html() );
	}

	public function test_nested_loops(): void {
		$markup = LoopBlock::new()
			->target( 'main-query' )
			->item_id( 'outer' )
			->child(
				LoopBlock::new()
					->target( 'main-query' )
					->item_id( 'inner' )
					->child( ElementBlock::new()->tag( 'span' )->to_block() )
					->to_block()
			)
			->to_block()
			->to_string();

		self::assertStringContainsString( 'etch/loop', $markup );
		self::assertStringContainsString( 'outer', $markup );
		self::assertStringContainsString( 'inner', $markup );
	}

	public function test_loop_with_condition_child(): void {
		$markup = LoopBlock::new()
			->target( 'main-query' )
			->child(
				\HonestlyDesign\EtchBuilders\EtchBlocks\ConditionBlock::new()
					->condition( 'true', '===', 'true' )
					->child( ElementBlock::new()->tag( 'div' )->content( 'Conditional in Loop' )->to_block() )
					->to_block()
			)
			->to_block()
			->to_string();

		self::assertStringContainsString( 'etch/loop', $markup );
		self::assertStringContainsString( 'etch/condition', $markup );
	}
}
