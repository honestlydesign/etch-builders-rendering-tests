<?php
/**
 * Base class for all Etch builder rendering tests.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\Environment;
use HonestlyDesign\EtchBuildersWpTests\WpAssetRegistry;
use HonestlyDesign\EtchBuildersWpTests\WpComponentRefResolver;
use HonestlyDesign\EtchBuildersWpTests\WpMode;
use HonestlyDesign\EtchBuildersWpTests\WpStorage;
use WP_UnitTestCase;

/**
 * Provides render_block_markup(), render_post(), seed helpers, teardown,
 * and Etch-specific assertion helpers.
 */
abstract class RenderingTestCase extends WP_UnitTestCase {

	/**
	 * IDs of posts created during this test (for teardown).
	 *
	 * @var array<int, int>
	 */
	protected array $created_posts = array();

	/**
	 * IDs of terms created during this test, as [term_id => taxonomy].
	 *
	 * @var array<int, string>
	 */
	protected array $created_terms = array();

	/**
	 * IDs of users created during this test (for teardown).
	 *
	 * @var array<int, int>
	 */
	protected array $created_users = array();

	/**
	 * Etch option keys to clear on teardown.
	 *
	 * @var array<int, string>
	 */
	private const ETCH_OPTIONS = array(
		'etch_styles',
		'etch_loops',
		'etch_global_stylesheets',
		'oh_my_id_etch_builder_stylesheets',
		'oh_my_id_etch_builder_stylesheet_fragments',
	);

	/**
	 * Render a block markup string through the Etch runtime.
	 *
	 * Captures both the rendered HTML and the <style id="etch-page-styles"> tag
	 * emitted via wp_head by StylesRegister::render_frontend_styles.
	 *
	 * @param string $markup Serialized block markup (from a builder's to_string()).
	 * @return RenderResult
	 */
	protected function render_block_markup( string $markup ): RenderResult {
		$parsed = parse_blocks( $markup );

		return $this->render_parsed_blocks( $parsed );
	}

	/**
	 * Render an existing post through the Etch runtime.
	 *
	 * For dynamic-data tests where blocks reference {this.title} of the post.
	 *
	 * @param int $post_id Post ID to render.
	 * @return RenderResult
	 */
	protected function render_post( int $post_id ): RenderResult {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new RenderResult( '', '' );
		}

		$blocks = parse_blocks( $post->post_content );
		return $this->render_parsed_blocks( $blocks );
	}

	/**
	 * Render parsed blocks with <style> tag capture.
	 *
	 * @param array<int, array<string, mixed>> $parsed Parsed blocks.
	 * @return RenderResult
	 */
	private function render_parsed_blocks( array $parsed ): RenderResult {
		// Render the blocks, capturing the HTML.
		$html = '';
		foreach ( $parsed as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}
			$html .= render_block( $block );
		}

		// Immediately capture page_styles (before anything resets them).
		$style_tag = '';
		if ( class_exists( \Etch\Blocks\Global\StylesRegister::class ) ) {
			$ref = new \ReflectionClass( \Etch\Blocks\Global\StylesRegister::class );
			$prop = $ref->getProperty( 'page_styles' );
			$prop->setAccessible( true );
			$registered = $prop->getValue();

			if ( ! empty( $registered ) ) {
				$compile = $ref->getMethod( 'compile_style_rules' );
				$compile->setAccessible( true );
				$css      = $compile->invoke( null, array_keys( $registered ) );
				$style_tag = is_string( $css ) ? $css : '';
				// Reset for the next render.
				$prop->setValue( null, array() );
			}
		}

		return new RenderResult( $html, $style_tag, $parsed );
	}

	/**
	 * Capture the Etch <style id="etch-page-styles"> tag via output buffering.
	 *
	 * Etch's StylesRegister::render_frontend_styles is hooked on wp_head priority 99.
	 * We simulate wp_head by calling the hook directly.
	 */
	private function capture_etch_styles(): string {
		if ( ! class_exists( \Etch\Blocks\Global\StylesRegister::class ) ) {
			return '';
		}

		$ref = new \ReflectionClass( \Etch\Blocks\Global\StylesRegister::class );

		// Get the registered page_styles (populated by render_block → register_block_styles).
		$page_styles_prop = $ref->getProperty( 'page_styles' );
		$page_styles_prop->setAccessible( true );
		$registered = $page_styles_prop->getValue();
		if ( empty( $registered ) ) {
			return '';
		}

		// Compile the CSS directly (bypassing the is_admin() guard in render_frontend_styles).
		$compile = $ref->getMethod( 'compile_style_rules' );
		$compile->setAccessible( true );
		$css = $compile->invoke( null, array_keys( $registered ) );

		// Reset page_styles for the next test.
		$page_styles_prop->setValue( null, array() );

		return is_string( $css ) ? $css : '';
	}

	/**
	 * Seed a test post.
	 *
	 * @param array<string, mixed> $overrides Field overrides (post_title, post_content, post_status, etc).
	 * @return int Post ID.
	 */
	protected function seed_post( array $overrides = array() ): int {
		$defaults = array(
			'post_title'   => 'Test Post',
			'post_content' => 'Test content.',
			'post_status'  => 'publish',
			'post_type'    => 'post',
			'post_author'  => 1,
		);

		$post_id = wp_insert_post( wp_parse_args( $overrides, $defaults ) );
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			$this->created_posts[] = $post_id;
		}

		return is_wp_error( $post_id ) ? 0 : $post_id;
	}

	/**
	 * Seed a test term.
	 *
	 * @param array<string, mixed> $overrides Field overrides (name, taxonomy, description, etc).
	 * @return int Term ID.
	 */
	protected function seed_term( array $overrides = array() ): int {
		$defaults = array(
			'name'     => 'Test Term',
			'taxonomy' => 'category',
			'slug'     => '',
		);

		$args     = wp_parse_args( $overrides, $defaults );
		$taxonomy = $args['taxonomy'];
		unset( $args['taxonomy'] );

		$term = wp_insert_term( $args['name'], $taxonomy, $args );
		if ( is_wp_error( $term ) ) {
			return 0;
		}

		$term_id = $term['term_id'];
		$this->created_terms[ $term_id ] = $taxonomy;
		return $term_id;
	}

	/**
	 * Seed a test user.
	 *
	 * @param array<string, mixed> $overrides Field overrides (user_login, user_email, role, etc).
	 * @return int User ID.
	 */
	protected function seed_user( array $overrides = array() ): int {
		$defaults = array(
			'user_login' => 'testuser_' . uniqid(),
			'user_email' => 'test_' . uniqid() . '@example.com',
			'user_pass'  => 'password',
			'role'       => 'editor',
			'display_name' => 'Test User',
		);

		$user_id = wp_insert_user( wp_parse_args( $overrides, $defaults ) );
		if ( is_wp_error( $user_id ) ) {
			return 0;
		}

		$this->created_users[] = $user_id;
		return $user_id;
	}

	// =========================================================================
	// Etch-specific assertion helpers.
	// =========================================================================

	/**
	 * Assert the rendered HTML contains a specific tag.
	 *
	 * @param RenderResult $result Render result.
	 * @param string       $tag    HTML tag (e.g. 'div', 'section').
	 */
	protected function assertRendersTag( RenderResult $result, string $tag ): void {
		self::assertStringContainsString( '<' . $tag, $result->html(), "Expected rendered HTML to contain a <{$tag}> tag." );
	}

	/**
	 * Assert the rendered HTML contains a specific class on an element.
	 *
	 * @param RenderResult $result Render result.
	 * @param string       $class  CSS class name.
	 */
	protected function assertRendersClass( RenderResult $result, string $class ): void {
		self::assertStringContainsString( $class, $result->html(), "Expected rendered HTML to contain class '{$class}'." );
	}

	/**
	 * Assert the rendered HTML contains specific text content.
	 *
	 * @param RenderResult $result Render result.
	 * @param string       $text   Text to find.
	 */
	protected function assertRendersContent( RenderResult $result, string $text ): void {
		self::assertStringContainsString( $text, $result->html(), "Expected rendered HTML to contain text '{$text}'." );
	}

	/**
	 * Assert a style block for the given selector was emitted in the <style> tag.
	 *
	 * @param RenderResult $result   Render result.
	 * @param string       $selector CSS selector (e.g. '.hero', ':where([data-etch-element="section"])').
	 * @param string       $css      Optional: CSS declarations that must appear in the block.
	 */
	protected function assertStyleEmitted( RenderResult $result, string $selector, string $css = '' ): void {
		$style = $result->style_tag();
		self::assertStringContainsString(
			$selector,
			$style,
			"Expected <style id=\"etch-page-styles\"> to contain selector '{$selector}'. Full style: {$style}"
		);
		if ( '' !== $css ) {
			self::assertStringContainsString(
				$css,
				$style,
				"Expected style block for '{$selector}' to contain CSS '{$css}'. Full style: {$style}"
			);
		}
	}

	/**
	 * Assert NO style block for the given selector was emitted.
	 *
	 * @param RenderResult $result   Render result.
	 * @param string       $selector CSS selector.
	 */
	protected function assertStyleNotEmitted( RenderResult $result, string $selector ): void {
		$style = $result->style_tag();
		self::assertStringNotContainsString(
			$selector,
			$style,
			"Expected <style id=\"etch-page-styles\"> to NOT contain selector '{$selector}'."
		);
	}

	// =========================================================================
	// Teardown — clean state between tests.
	// =========================================================================

	/**
	 * Clean up all created entities + reset Etch state + reset Environment.
	 */
	protected function tearDown(): void {
		// Delete created posts.
		foreach ( $this->created_posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		$this->created_posts = array();

		// Delete created terms.
		foreach ( $this->created_terms as $term_id => $taxonomy ) {
			wp_delete_term( $term_id, $taxonomy );
		}
		$this->created_terms = array();

		// Delete created users.
		foreach ( $this->created_users as $user_id ) {
			wp_delete_user( $user_id );
		}
		$this->created_users = array();

		// Clear Etch persisted state for test-registered styles only.
		// We remove the etch_styles option entirely; Etch re-seeds its defaults
		// on the next request via Etch\Styles::init(), but in the test process
		// the defaults are already in the option from wp-env startup.
		// We only delete if we added custom styles; otherwise leave it.
		foreach ( self::ETCH_OPTIONS as $option ) {
			delete_option( $option );
		}

		// Re-trigger Etch's default style seeding via the init action.
		// Etch\Styles is instantiated and hooks on 'init'; calling do_action('init')
		// would re-run all init hooks which is too broad. Instead, manually re-seed
		// by calling the Styles class if it has a static accessor.
		if ( class_exists( \Etch\Styles::class ) ) {
			try {
				$instance = new \Etch\Styles();
				$ref      = new \ReflectionClass( $instance );
				$method   = $ref->getMethod( 'initialize_default_styles' );
				$method->setAccessible( true );
				$method->invoke( $instance );
			} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// If re-seeding fails, the defaults will be absent for subsequent tests,
				// but that's acceptable — tests register their own styles explicitly.
			}
		}

		// Reset the in-memory registries.
		if ( class_exists( \HonestlyDesign\EtchBuilders\Style::class ) ) {
			\HonestlyDesign\EtchBuilders\Style::reset();
		}
		if ( class_exists( \HonestlyDesign\EtchBuilders\ClassStyleRegistry::class ) ) {
			\HonestlyDesign\EtchBuilders\ClassStyleRegistry::reset_cache();
		}
		if ( class_exists( \HonestlyDesign\EtchBuilders\LoopPreset::class ) ) {
			\HonestlyDesign\EtchBuilders\LoopPreset::reset();
		}
		if ( class_exists( \HonestlyDesign\EtchBuilders\Stylesheet::class ) ) {
			\HonestlyDesign\EtchBuilders\Stylesheet::reset_active_owner_keys();
			\HonestlyDesign\EtchBuilders\Stylesheet::reset_custom_media();
		}

		// Re-configure Environment with fresh WP adapters (the old ones may hold stale state).
		if ( class_exists( Environment::class ) ) {
			Environment::configure(
				new WpStorage(),
				new WpMode(),
				new WpAssetRegistry(),
				new WpComponentRefResolver()
			);
		}

		// Reset StylesRegister's page_styles static + the $all_styles cache.
		// Etch caches get_all_styles() in a static; without resetting it,
		// subsequent tests get stale style data from the first test.
		if ( class_exists( \Etch\Blocks\Global\StylesRegister::class ) ) {
			$ref = new \ReflectionClass( \Etch\Blocks\Global\StylesRegister::class );
			if ( $ref->hasProperty( 'page_styles' ) ) {
				$prop = $ref->getProperty( 'page_styles' );
				$prop->setAccessible( true );
				$prop->setValue( null, array() );
			}
			if ( $ref->hasProperty( 'all_styles' ) ) {
				$prop = $ref->getProperty( 'all_styles' );
				$prop->setAccessible( true );
				$prop->setValue( null, null );
			}
		}

		parent::tearDown();
	}
}
