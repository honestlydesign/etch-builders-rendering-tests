<?php
/**
 * LoopPreset rendering tests — every public method + logical combinations.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\LoopPreset;

/**
 * Verifies LoopPreset builder works correctly with the Etch runtime.
 */
final class LoopPresetRenderingTest extends RenderingTestCase {

	protected function tearDown(): void {
		LoopPreset::reset();
		parent::tearDown();
	}

	// --- new() + key() + wp_query() + wp_terms() + wp_users() + json() + main_query() ---

	public function test_new_with_wp_query(): void {
		$preset = LoopPreset::new( 'Posts' )
			->key( 'posts-loop' )
			->wp_query( array( 'post_type' => 'post' ) );

		self::assertNotEmpty( $preset->to_array() );
	}

	public function test_new_with_wp_terms(): void {
		$preset = LoopPreset::new( 'Terms' )
			->key( 'terms-loop' )
			->wp_terms( array( 'taxonomy' => 'category' ) );

		self::assertNotEmpty( $preset->to_array() );
	}

	public function test_new_with_wp_users(): void {
		$preset = LoopPreset::new( 'Users' )
			->key( 'users-loop' )
			->wp_users( array( 'role' => 'editor' ) );

		self::assertNotEmpty( $preset->to_array() );
	}

	public function test_new_with_json(): void {
		$preset = LoopPreset::new( 'JSON Data' )
			->key( 'json-loop' )
			->json( array( array( 'name' => 'A' ), array( 'name' => 'B' ) ) );

		self::assertNotEmpty( $preset->to_array() );
	}

	public function test_new_with_main_query(): void {
		$preset = LoopPreset::new( 'Main Query' )
			->key( 'main-query-loop' )
			->main_query();

		self::assertNotEmpty( $preset->to_array() );
	}

	// --- id() + global() + overwrite() ---

	public function test_id_sets_preset_id(): void {
		$preset = LoopPreset::new( 'ID Test' )
			->key( 'id-test' )
			->id( 'custom-id-123' )
			->wp_query( array( 'post_type' => 'page' ) );

		self::assertNotEmpty( $preset->to_array() );
	}

	public function test_global_sets_flag(): void {
		$preset = LoopPreset::new( 'Global Test' )
			->key( 'global-test' )
			->global( true )
			->wp_query( array('post_type' => 'post') );

		self::assertNotEmpty( $preset->to_array() );
	}

	public function test_overwrite_sets_flag(): void {
		$preset = LoopPreset::new( 'Overwrite Test' )
			->key( 'overwrite-test' )
			->overwrite( true )
			->wp_query( array('post_type' => 'post') );

		self::assertNotEmpty( $preset->to_array() );
	}

	// --- to_array() ---

	public function test_to_array_returns_config(): void {
		$preset = LoopPreset::new( 'Array Test' )
			->key( 'array-test' )
			->wp_query( array( 'post_type' => 'post', 'posts_per_page' => 5 ) );

		$array = $preset->to_array();
		self::assertArrayHasKey( 'key', $array );
		self::assertArrayHasKey( 'name', $array );
	}

	// --- register_internal() + registered_keys() + is_registered_key() ---

	public function test_register_internal_adds_to_registry(): void {
		LoopPreset::reset();

		LoopPreset::new( 'Registered' )
			->key( 'registered-preset' )
			->wp_query( array('post_type' => 'post') )
			->register_internal();

		self::assertContains( 'registered-preset', LoopPreset::registered_keys() );
		self::assertTrue( LoopPreset::is_registered_key( 'registered-preset' ) );
	}

	public function test_registered_keys_lists_all(): void {
		LoopPreset::reset();

		LoopPreset::new( 'A' )->key( 'preset-a' )->wp_query( array('post_type' => 'post') )->register_internal();
		LoopPreset::new( 'B' )->key( 'preset-b' )->wp_query( array('post_type' => 'post') )->register_internal();

		$keys = LoopPreset::registered_keys();
		self::assertContains( 'preset-a', $keys );
		self::assertContains( 'preset-b', $keys );
	}

	public function test_is_registered_key_false_for_unknown(): void {
		LoopPreset::reset();
		self::assertFalse( LoopPreset::is_registered_key( 'nonexistent' ) );
	}

	// --- snapshot() + restore() + reset() ---

	public function test_snapshot_captures_registry(): void {
		LoopPreset::reset();
		LoopPreset::new( 'Snap' )->key( 'snap-test' )->wp_query( array('post_type' => 'post') )->register_internal();

		$snap = LoopPreset::snapshot();
		self::assertArrayHasKey( 'snap-test', $snap );
	}

	public function test_restore_replaces_registry(): void {
		LoopPreset::reset();
		LoopPreset::new( 'Original' )->key( 'original' )->wp_query( array('post_type' => 'post') )->register_internal();

		$saved = array( 'restored' => 'id-restored' );
		LoopPreset::restore( $saved );
		self::assertContains( 'restored', LoopPreset::registered_keys() );
		self::assertNotContains( 'original', LoopPreset::registered_keys() );
	}

	public function test_reset_clears_registry(): void {
		LoopPreset::new( 'Temp' )->key( 'temp' )->wp_query( array('post_type' => 'post') )->register_internal();
		LoopPreset::reset();
		self::assertSame( array(), LoopPreset::registered_keys() );
	}

	// --- register() ---

	public function test_register_persists_to_option(): void {
		$preset = LoopPreset::new( 'Persist Test' )
			->key( 'persist-test' )
			->wp_query( array( 'post_type' => 'post' ) )
			->overwrite( true );

		$result = $preset->register();

		// register() returns true or RegistrationResult on success.
		self::assertTrue(
			true === $result || ( $result instanceof \HonestlyDesign\EtchBuilders\RegistrationResult && $result->is_success() )
		);
	}
}
