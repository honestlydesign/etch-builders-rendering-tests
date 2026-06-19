<?php
/**
 * Plugin Name: Etch Builders Test Bootstrap
 * Description: Wires honestlydesign/etch-builders Environment with WP adapters.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

use HonestlyDesign\EtchBuilders\Environment;
use HonestlyDesign\EtchBuildersWpTests\WpAssetRegistry;
use HonestlyDesign\EtchBuildersWpTests\WpComponentRefResolver;
use HonestlyDesign\EtchBuildersWpTests\WpMode;
use HonestlyDesign\EtchBuildersWpTests\WpStorage;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/src/WpStorage.php';
require_once __DIR__ . '/src/WpMode.php';
require_once __DIR__ . '/src/WpAssetRegistry.php';
require_once __DIR__ . '/src/WpComponentRefResolver.php';

add_action(
	'muplugins_loaded',
	static function (): void {
		Environment::configure(
			new WpStorage(),
			new WpMode(),
			new WpAssetRegistry(),
			new WpComponentRefResolver()
		);
	}
);
