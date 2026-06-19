<?php
/**
 * Infrastructure test: proves RenderingTestCase works end-to-end.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock;
use HonestlyDesign\EtchBuilders\Style;

/**
 * Verifies the RenderingTestCase base class renders + captures styles correctly.
 */
final class RenderingInfrastructureTest extends RenderingTestCase {

	public function test_renders_element_with_linked_style_emission(): void {
		// Register a style.
		Style::new()
			->id( 'omide-infra-test' )
			->selector( '.omide-infra-test' )
			->css( 'display: flex; color: rgb(255, 0, 0);' )
			->type( 'class' )
			->collection( 'OhMyIDEtch' )
			->add();

		// Register all pending styles to the WP option.
		Style::register_all();

		// Build + render an element using that class.
		$markup = ElementBlock::new()
			->tag( 'section' )
			->class( 'omide-infra-test' )
			->child(
				ElementBlock::new()
					->tag( 'h2' )
					->content( 'Infra Test Heading' )
					->to_block()
			)
			->to_block()
			->to_string();

		$result = $this->render_block_markup( $markup );

		// Assert the HTML rendered correctly.
		$this->assertRendersTag( $result, 'section' );
		$this->assertRendersClass( $result, 'omide-infra-test' );
		$this->assertRendersContent( $result, 'Infra Test Heading' );

		// Assert the linked style was emitted in <style id="etch-page-styles">.
		$this->assertStyleEmitted( $result, '.omide-infra-test', 'display: flex' );
	}

	public function test_seed_post_creates_a_published_post(): void {
		$post_id = $this->seed_post(
			array(
				'post_title'  => 'Seeded Post',
				'post_content'=> 'Seeded content.',
			)
		);

		self::assertGreaterThan( 0, $post_id );

		$post = get_post( $post_id );
		self::assertNotNull( $post );
		self::assertSame( 'Seeded Post', $post->post_title );
		self::assertSame( 'publish', $post->post_status );
	}

	public function test_seed_term_creates_a_category(): void {
		$term_id = $this->seed_term(
			array(
				'name' => 'Seeded Category',
			)
		);

		self::assertGreaterThan( 0, $term_id );

		$term = get_term( $term_id, 'category' );
		self::assertInstanceOf( \WP_Term::class, $term );
		self::assertSame( 'Seeded Category', $term->name );
	}

	public function test_seed_user_creates_an_editor(): void {
		$user_id = $this->seed_user(
			array(
				'display_name' => 'Seeded User',
				'role'         => 'editor',
			)
		);

		self::assertGreaterThan( 0, $user_id );

		$user = get_userdata( $user_id );
		self::assertSame( 'Seeded User', $user->display_name );
		self::assertContains( 'editor', $user->roles );
	}

	public function test_tear_down_cleans_up_seeded_data(): void {
		// This test creates data; the parent tearDown should clean it.
		// We verify by creating data, then checking it exists.
		$post_id = $this->seed_post( array( 'post_title' => 'To Be Deleted' ) );
		$term_id = $this->seed_term( array( 'name' => 'To Be Deleted Term' ) );
		$user_id = $this->seed_user( array( 'display_name' => 'To Be Deleted User' ) );

		self::assertNotNull( get_post( $post_id ) );
		self::assertInstanceOf( \WP_Term::class, get_term( $term_id, 'category' ) );
		self::assertNotFalse( get_userdata( $user_id ) );

		// The tearDown() will delete these after the test completes.
		// We can't verify the deletion within this test (it happens after),
		// but the created_posts/terms/users arrays prove they're tracked.
		self::assertContains( $post_id, $this->created_posts );
		self::assertArrayHasKey( $term_id, $this->created_terms );
		self::assertContains( $user_id, $this->created_users );
	}
}
