<?php
/**
 * WP AssetRegistryInterface adapter — records assets in-process for assertions.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests;

use HonestlyDesign\EtchBuilders\Contracts\AssetRegistryInterface;

final class WpAssetRegistry implements AssetRegistryInterface {

	/**
	 * Backing map.
	 *
	 * @var array<string, array<string, array<int, array{handle: string, path: string}>>>
	 */
	private array $assets = array();

	public function register( string $component_key, string $type, string $handle, string $path ): void {
		$this->assets[ $component_key ][ $type ][] = array(
			'handle' => $handle,
			'path'   => $path,
		);
	}

	public function get_assets( string $component_key ): array {
		return $this->assets[ $component_key ] ?? array();
	}

	public function has_assets( string $component_key ): bool {
		return isset( $this->assets[ $component_key ] ) && array() !== $this->assets[ $component_key ];
	}

	public function get_registered_keys(): array {
		return array_keys( $this->assets );
	}
}
