<?php
/**
 * WP ModeProviderInterface adapter — tests always run in dev mode.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests;

use HonestlyDesign\EtchBuilders\Contracts\ModeProviderInterface;

final class WpMode implements ModeProviderInterface {

	public function is_dev_mode(): bool {
		return true;
	}
}
