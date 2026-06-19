<?php
/**
 * Value object holding the result of a render operation.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

/**
 * Captures rendered HTML + emitted <style> tags from an Etch render pass.
 */
final class RenderResult {

	/**
	 * The rendered HTML body.
	 *
	 * @var string
	 */
	private string $html;

	/**
	 * The emitted <style> tag content (from wp_head / StylesRegister).
	 *
	 * @var string
	 */
	private string $style_tag;

	/**
	 * The raw parsed block tree that was rendered.
	 *
	 * @var array<string, mixed>
	 */
	private array $raw_blocks;

	/**
	 * Constructor.
	 *
	 * @param string                $html       Rendered HTML.
	 * @param string                $style_tag  Emitted <style> tag content.
	 * @param array<string, mixed>  $raw_blocks Raw parsed block tree.
	 */
	public function __construct( string $html, string $style_tag, array $raw_blocks = array() ) {
		$this->html       = $html;
		$this->style_tag  = $style_tag;
		$this->raw_blocks = $raw_blocks;
	}

	/**
	 * The rendered HTML body.
	 */
	public function html(): string {
		return $this->html;
	}

	/**
	 * The emitted <style> tag content (everything inside <style id="etch-page-styles">...</style>).
	 */
	public function style_tag(): string {
		return $this->style_tag;
	}

	/**
	 * The raw parsed block tree that was rendered.
	 *
	 * @return array<string, mixed>
	 */
	public function raw_blocks(): array {
		return $this->raw_blocks;
	}
}
