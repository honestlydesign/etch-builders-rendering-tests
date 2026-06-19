<?php
/**
 * PHPUnit bootstrap — runs inside wp-env tests-cli container.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( false === $_tests_dir || '' === $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Load the project's Composer autoloader FIRST (before WP test lib needs polyfills).
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load Etch's autoloader so its classes are available in the test process.
$etch_autoloader = '/var/www/html/wp-content/plugins/etch/vendor/autoload.php';
if ( file_exists( $etch_autoloader ) ) {
	require_once $etch_autoloader;
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Wire Environment::configure() as early as possible.
 */
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		$plugin_base = dirname( __DIR__ ) . '/.wp-env-sources/etch-builders-test-bootstrap';
		if ( file_exists( $plugin_base . '/src/WpStorage.php' ) ) {
			require_once $plugin_base . '/src/WpStorage.php';
			require_once $plugin_base . '/src/WpMode.php';
			require_once $plugin_base . '/src/WpAssetRegistry.php';
			require_once $plugin_base . '/src/WpComponentRefResolver.php';

			\HonestlyDesign\EtchBuilders\Environment::configure(
				new \HonestlyDesign\EtchBuildersWpTests\WpStorage(),
				new \HonestlyDesign\EtchBuildersWpTests\WpMode(),
				new \HonestlyDesign\EtchBuildersWpTests\WpAssetRegistry(),
				new \HonestlyDesign\EtchBuildersWpTests\WpComponentRefResolver()
			);
		}

		// Load the Etch plugin's main file so its block registration hooks fire.
		// The WP test library only loads mu-plugins; regular plugins need manual loading.
		$etch_main = '/var/www/html/wp-content/plugins/etch/etch.php';
		if ( file_exists( $etch_main ) ) {
			require_once $etch_main;
		}
	}
);

require $_tests_dir . '/includes/bootstrap.php';

// Configure Environment one more time AFTER WP test lib loaded, in case
// muplugins_loaded already fired before our hook registered.
if ( class_exists( \HonestlyDesign\EtchBuilders\Environment::class ) ) {
	$plugin_base = dirname( __DIR__ ) . '/.wp-env-sources/etch-builders-test-bootstrap';
	if ( file_exists( $plugin_base . '/src/WpStorage.php' ) ) {
		require_once $plugin_base . '/src/WpStorage.php';
		require_once $plugin_base . '/src/WpMode.php';
		require_once $plugin_base . '/src/WpAssetRegistry.php';
		require_once $plugin_base . '/src/WpComponentRefResolver.php';

		\HonestlyDesign\EtchBuilders\Environment::configure(
			new \HonestlyDesign\EtchBuildersWpTests\WpStorage(),
			new \HonestlyDesign\EtchBuildersWpTests\WpMode(),
			new \HonestlyDesign\EtchBuildersWpTests\WpAssetRegistry(),
			new \HonestlyDesign\EtchBuildersWpTests\WpComponentRefResolver()
		);
	}
}
