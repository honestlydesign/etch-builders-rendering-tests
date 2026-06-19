<?php
/**
 * WP StorageInterface adapter — delegates to get_option/update_option/delete_option.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests;

use HonestlyDesign\EtchBuilders\Contracts\StorageInterface;

final class WpStorage implements StorageInterface {

	public function get( string $key, mixed $default = null ): mixed {
		$value = get_option( $key, $default );
		return false === $value ? $default : $value;
	}

	public function set( string $key, mixed $value ): bool {
		return update_option( $key, $value, false );
	}

	public function delete( string $key ): bool {
		return delete_option( $key );
	}
}
