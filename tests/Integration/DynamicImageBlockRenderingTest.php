<?php
/**
 * DynamicImageBlock rendering tests — every public method + logical combinations.
 *
 * @package HonestlyDesign\EtchBuildersWpTests
 */

declare( strict_types=1 );

namespace HonestlyDesign\EtchBuildersWpTests\Integration;

use HonestlyDesign\EtchBuilders\EtchBlocks\DynamicImageBlock;
use HonestlyDesign\EtchBuilders\Style;
use HonestlyDesign\EtchBuilders\Types\Attributes;

/**
 * Verifies DynamicImageBlock renders correctly through the Etch runtime.
 */
final class DynamicImageBlockRenderingTest extends RenderingTestCase {

	// --- new() ---

	public function test_new_creates_dynamic_image_block(): void {
		$markup = DynamicImageBlock::new()
			->media_id( 1 )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'etch/dynamic-image', $markup );
	}

	// --- attribute() + attributes() ---

	public function test_attribute_sets_alt(): void {
		$markup = DynamicImageBlock::new()
			->media_id( 1 )
			->attribute( 'alt', 'Test image' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'alt', $markup );
	}

	public function test_attribute_sets_class(): void {
		$markup = DynamicImageBlock::new()
			->media_id( 1 )
			->attribute( 'class', 'hero-image' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'hero-image', $markup );
	}

	public function test_attributes_sets_multiple(): void {
		$attrs = Attributes::from_array(
			array(
				'alt'   => 'Multi',
				'class' => 'multi-img',
			)
		);
		$markup = DynamicImageBlock::new()
			->media_id( 1 )
			->attributes( $attrs )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'multi-img', $markup );
	}

	// --- media_id() ---

	public function test_media_id_sets_attachment_id(): void {
		$markup = DynamicImageBlock::new()
			->media_id( 42 )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'mediaId', $markup );
		self::assertStringContainsString( '42', $markup );
	}

	public function test_media_id_string_value(): void {
		$markup = DynamicImageBlock::new()
			->media_id( '{this.featuredImage}' )
			->to_block()
			->to_string();
		// Dynamic mediaId stored as expression.
		self::assertStringContainsString( 'featuredImage', $markup );
	}

	// --- use_srcset() ---

	public function test_use_srcset_enables(): void {
		$markup = DynamicImageBlock::new()
			->media_id( 1 )
			->use_srcset( true )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'useSrcSet', $markup );
	}

	public function test_use_srcset_disables(): void {
		$markup = DynamicImageBlock::new()
			->media_id( 1 )
			->use_srcset( false )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'etch/dynamic-image', $markup );
	}

	// --- maximum_size() ---

	public function test_maximum_size_sets_value(): void {
		$markup = DynamicImageBlock::new()
			->media_id( 1 )
			->maximum_size( 'large' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'maximumSize', $markup );
		self::assertStringContainsString( 'large', $markup );
	}

	public function test_maximum_size_thumbnail(): void {
		$markup = DynamicImageBlock::new()
			->media_id( 1 )
			->maximum_size( 'thumbnail' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'thumbnail', $markup );
	}

	// --- style() + styles() ---

	public function test_style_attaches_a_style_id(): void {
		$this->register_test_style( 'omide-dyn-img', '.omide-dyn-img', 'max-width: 100%;' );

		$markup = DynamicImageBlock::new()
			->media_id( 1 )
			->style( 'omide-dyn-img' )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'omide-dyn-img', $markup );
	}

	public function test_styles_attaches_multiple(): void {
		$this->register_test_style( 'omide-img-a', '.omide-img-a', 'display: block;' );
		$this->register_test_style( 'omide-img-b', '.omide-img-b', 'border-radius: 4px;' );

		$markup = DynamicImageBlock::new()
			->media_id( 1 )
			->styles( array( 'omide-img-a', 'omide-img-b' ) )
			->to_block()
			->to_string();
		self::assertStringContainsString( 'omide-img-a', $markup );
		self::assertStringContainsString( 'omide-img-b', $markup );
	}

	// --- Logical combinations ---

	public function test_image_inside_element(): void {
		$markup = \HonestlyDesign\EtchBuilders\EtchBlocks\ElementBlock::new()
			->tag( 'figure' )
			->child(
				DynamicImageBlock::new()
					->media_id( 1 )
					->attribute( 'alt', 'Figure image' )
					->to_block()
			)
			->to_block()
			->to_string();
		self::assertStringContainsString( 'figure', $markup );
		self::assertStringContainsString( 'etch/dynamic-image', $markup );
	}

	public function test_image_with_all_attributes(): void {
		$this->register_test_style( 'omide-full-img', '.omide-full-img', 'width: 100%;' );

		$markup = DynamicImageBlock::new()
			->media_id( 99 )
			->attribute( 'alt', 'Complete image' )
			->attribute( 'class', 'omide-full-img' )
			->use_srcset( true )
			->maximum_size( 'full' )
			->style( 'omide-full-img' )
			->to_block()
			->to_string();
		self::assertStringContainsString( '99', $markup );
		self::assertStringContainsString( 'Complete image', $markup );
		self::assertStringContainsString( 'useSrcSet', $markup );
		self::assertStringContainsString( 'full', $markup );
	}

	private function register_test_style( string $id, string $selector = '', string $css = 'display: block;' ): void {
		Style::new()
			->id( $id )
			->selector( '' !== $selector ? $selector : '.' . $id )
			->css( $css )
			->type( 'class' )
			->collection( 'OhMyIDEtch' )
			->add();
		Style::register_all();
	}
}
