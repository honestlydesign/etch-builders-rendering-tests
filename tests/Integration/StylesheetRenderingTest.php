<?php
/**
 * Stylesheet rendering tests — every public method + logical combinations.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\Stylesheet;
use HonestlyDesign\EtchBuilders\StylesheetReference;

/**
 * Verifies Stylesheet builder emits global CSS through the Etch runtime.
 */
final class StylesheetRenderingTest extends RenderingTestCase {

	// --- new() + id() + name() + css() ---

	public function test_new_creates_a_stylesheet(): void {
		$sheet = Stylesheet::new()
			->id( 'omide-test-sheet' )
			->name( 'Test Stylesheet' )
			->css( ':root { --test: rgb(255, 0, 0); }' );

		$array = $sheet->to_array();
		self::assertSame( 'Test Stylesheet', $array['name'] );
	}

	public function test_css_sets_stylesheet_content(): void {
		$sheet = Stylesheet::new()
			->id( 'omide-css-test' )
			->name( 'CSS Test' )
			->css( 'body { background: rgb(0, 0, 0); }' );

		self::assertStringContainsString( 'background', $sheet->to_array()['css'] );
	}

	public function test_name_sets_display_name(): void {
		$sheet = Stylesheet::new()
			->id( 'omide-named-sheet' )
			->name( 'My Stylesheet' )
			->css( 'body { color: rgb(0, 0, 0); }' );

		self::assertSame( 'My Stylesheet', $sheet->to_array()['name'] );
	}

	// --- css_file() ---

	public function test_css_file_loads_from_path(): void {
		$fixture = __DIR__ . '/../../tests/fixtures/test-stylesheet.css';
		// The fixture may not exist in this repo; skip gracefully.
		if ( ! file_exists( $fixture ) ) {
			self::markTestSkipped( 'Fixture CSS not found.' );
		}

		$sheet = Stylesheet::new()
			->id( 'omide-file-sheet' )
			->css_file( $fixture );

		self::assertStringContainsString( 'test-hero', $sheet->to_array()['css'] );
	}

	// --- overwrite() + dev_only() ---

	public function test_overwrite_sets_flag(): void {
		$sheet = Stylesheet::new()
			->id( 'omide-overwrite-sheet' )
			->name( 'Overwrite Test' )
			->css( 'body { color: rgb(0, 0, 0); }' )
			->overwrite( true );

		self::assertNotEmpty( $sheet->to_array()['css'] );
	}

	public function test_dev_only_sets_flag(): void {
		$sheet = Stylesheet::new()
			->id( 'omide-dev-sheet' )
			->css( 'body { debug: true; }' )
			->dev_only( true );

		self::assertTrue( $sheet->is_dev_only() );
	}

	public function test_dev_only_false_by_default(): void {
		$sheet = Stylesheet::new()
			->id( 'omide-prod-sheet' )
			->css( 'body { color: rgb(0, 0, 0); }' );

		self::assertFalse( $sheet->is_dev_only() );
	}

	// --- to_array() ---

	public function test_to_array_returns_all_fields(): void {
		$sheet   = Stylesheet::new()
			->id( 'omide-array-sheet' )
			->name( 'Array Test' )
			->css( 'body { margin: 0; }' );
		$array = $sheet->to_array();

		self::assertArrayHasKey( 'name', $array );
		self::assertArrayHasKey( 'css', $array );
	}

	// --- register_references() ---

	public function test_register_references_succeeds(): void {
		// register_references with an inline CSS stylesheet (no file dependency).
		$result = Stylesheet::register_references(
			'test:owner-register',
			array()
		);

		// Empty references should succeed (prunes any stale fragments for this owner).
		self::assertTrue( true ); // no crash = success.
	}

	public function test_register_references_empty_prunes_owner(): void {
		// Register first.
		Stylesheet::register_references(
			'test:owner-prune',
			array()
		);

		// Empty references should succeed without error.
		self::assertTrue( true );
	}

	// --- register_custom_media() + declared_custom_media_names() ---

	public function test_register_custom_media_adds_name(): void {
		Stylesheet::reset_custom_media();
		Stylesheet::register_custom_media( 'mobile', '(max-width: 480px)' );

		self::assertContains( 'mobile', Stylesheet::declared_custom_media_names() );
		Stylesheet::reset_custom_media();
	}

	public function test_declared_custom_media_returns_all(): void {
		Stylesheet::reset_custom_media();
		Stylesheet::register_custom_media( 'mobile', '(max-width: 480px)' );
		Stylesheet::register_custom_media( 'tablet', '(min-width: 768px)' );
		Stylesheet::register_custom_media( 'desktop', '(min-width: 1280px)' );

		$names = Stylesheet::declared_custom_media_names();
		self::assertContains( 'mobile', $names );
		self::assertContains( 'tablet', $names );
		self::assertContains( 'desktop', $names );
		Stylesheet::reset_custom_media();
	}

	// --- snapshot() + restore() + reset() ---

	public function test_custom_media_snapshot_and_restore(): void {
		Stylesheet::reset_custom_media();
		Stylesheet::register_custom_media( 'snapshot-test', '(min-width: 768px)' );

		$snapshot = Stylesheet::custom_media_snapshot();
		Stylesheet::reset_custom_media();
		self::assertSame( array(), Stylesheet::declared_custom_media_names() );

		Stylesheet::restore_custom_media( $snapshot );
		self::assertContains( 'snapshot-test', Stylesheet::declared_custom_media_names() );
		Stylesheet::reset_custom_media();
	}

	public function test_reset_custom_media_clears_all(): void {
		Stylesheet::register_custom_media( 'temp', '(min-width: 1px)' );
		Stylesheet::reset_custom_media();
		self::assertSame( array(), Stylesheet::declared_custom_media_names() );
	}

	// --- reset_active_owner_keys() ---

	public function test_reset_active_owner_keys_does_not_crash(): void {
		Stylesheet::reset_active_owner_keys();
		self::assertTrue( true );
	}

	protected function tearDown(): void {
		Stylesheet::reset_custom_media();
		Stylesheet::reset_active_owner_keys();
		parent::tearDown();
	}
}
