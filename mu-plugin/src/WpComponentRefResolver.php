<?php
/**
 * WP ComponentRefResolverInterface adapter — queries wp_block posts by meta.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests;

use HonestlyDesign\EtchBuilders\Contracts\ComponentRefResolverInterface;

final class WpComponentRefResolver implements ComponentRefResolverInterface {

	public function ref_by_key( string $component_key ): int {
		$posts = get_posts(
			array(
				'post_type'      => 'wp_block',
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'   => 'etch_component_html_key',
						'value' => $component_key,
					),
				),
				'fields'         => 'ids',
			)
		);

		return ! empty( $posts ) ? (int) $posts[0] : 0;
	}

	public function key_by_ref( int $ref ): ?string {
		$key = get_post_meta( $ref, 'etch_component_html_key', true );
		return is_string( $key ) && '' !== $key ? $key : null;
	}
}
