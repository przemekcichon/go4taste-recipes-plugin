<?php
/**
 * ACF → Blocks Synchronization
 *
 * Syncs ACF field edits to Gutenberg blocks in post_content.
 * Fires on acf/save_post priority 5 (before block→meta sync at priority 20).
 *
 * WHY this sync is needed:
 *
 * Plugin supports dual UI:
 *  - Block Editor (primary) — data in block attributes
 *  - ACF Meta Boxes (alternative) — data in post_meta
 *
 * When user edits ACF fields, we rebuild blocks in post_content so:
 *  - Block Editor shows current data
 *  - Block→meta sync (priority 20) keeps meta up to date
 *  - Single source of truth (blocks) is maintained
 *
 * @package Go4Taste_Recipes_Plugin
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sync ACF fields → Gutenberg blocks
 *
 * Priority 5 ensures this runs BEFORE g4t_sync_recipe_meta_from_blocks() at priority 20.
 * Performance optimization: sets transient to skip block parsing in meta-sync.
 *
 * @param int $post_id Post ID being saved.
 */
function g4t_sync_acf_to_blocks( int $post_id ): void {
	static $running = false;
	if ( $running ) {
		return;
	}
	$running = true;

	// Guards
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		$running = false;
		return;
	}

	// Only run on ACF save
	if ( g4t_detect_save_source() !== 'acf' ) {
		$running = false;
		return;
	}

	// Build blocks from ACF using shared converters
	$blocks = array(
		g4t_build_hero_block_from_acf( $post_id ),
		'',
		g4t_build_ingredients_block_from_acf( $post_id ),
		'',
		'',
		g4t_build_steps_block_from_acf( $post_id ),
		'',
		g4t_build_tips_block_from_acf( $post_id ),
		'',
		g4t_build_source_block_from_acf( $post_id ),
		'',
		'<!-- wp:go4taste-ads/inline-banner /-->',
	);

	$post_content = implode( "\n", $blocks );

	// Set flag to skip block→meta sync (performance optimization)
	set_transient( "g4t_acf_save_{$post_id}", true, 60 );

	// Disable block→meta sync to avoid loop
	remove_action( 'save_post', 'g4t_sync_recipe_meta_from_blocks', 20 );

	$result = wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $post_content,
		)
	);

	if ( is_wp_error( $result ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'GO4TASTE Recipes ACF→blocks sync failed for post %d: %s',
				$post_id,
				$result->get_error_message()
			) );
		}
	}

	// Re-enable (for future saves)
	add_action( 'save_post', 'g4t_sync_recipe_meta_from_blocks', 20, 4 );

	// Track that this edit came from ACF
	g4t_track_edit_source( $post_id, 'acf' );

	$running = false;
}

/**
 * Build hero block from ACF fields
 *
 * Uses shared converter g4t_convert_hero_meta_to_attrs() to read meta keys
 * and build block attributes array, then encodes to JSON.
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_hero_block_from_acf( int $post_id ): string {
	$attrs = g4t_convert_hero_meta_to_attrs( $post_id );

	if ( empty( $attrs ) ) {
		return '<!-- wp:go4taste-recipes/hero /-->';
	}

	$attrs_json = wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	return '<!-- wp:go4taste-recipes/hero ' . $attrs_json . ' /-->';
}

/**
 * Build tips block from ACF field
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_tips_block_from_acf( int $post_id ): string {
	$tips = get_post_meta( $post_id, 'recipe_tips', true );

	if ( empty( $tips ) ) {
		return '<!-- wp:go4taste-recipes/tips /-->';
	}

	$attrs      = array( 'tips' => (string) $tips );
	$attrs_json = wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	return '<!-- wp:go4taste-recipes/tips ' . $attrs_json . ' /-->';
}

/**
 * Build source block from ACF fields
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_source_block_from_acf( int $post_id ): string {
	$label = get_post_meta( $post_id, 'recipe_source_label', true );
	$url   = get_post_meta( $post_id, 'recipe_source_url', true );

	if ( empty( $label ) && empty( $url ) ) {
		return '<!-- wp:go4taste-recipes/source /-->';
	}

	$attrs = array();
	if ( ! empty( $label ) ) {
		$attrs['sourceLabel'] = (string) $label;
	}
	if ( ! empty( $url ) ) {
		$attrs['sourceUrl'] = (string) $url;
	}

	$attrs_json = wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	return '<!-- wp:go4taste-recipes/source ' . $attrs_json . ' /-->';
}

/**
 * Build ingredients block from ACF fields
 *
 * Uses shared converter g4t_convert_ingredients_meta_to_array() to read
 * the repeater meta keys and build the itemsList array.
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_ingredients_block_from_acf( int $post_id ): string {
	$items = g4t_convert_ingredients_meta_to_array( $post_id );

	if ( empty( $items ) ) {
		return '<!-- wp:go4taste-recipes/ingredients /-->';
	}

	$items_json = wp_json_encode( $items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	// Photo URL (outside repeater)
	$photo_url = get_post_meta( $post_id, 'recipe_ingredients_photo_url', true );

	$attrs = '"itemsList":' . $items_json;
	if ( ! empty( $photo_url ) ) {
		$attrs .= ',"photoUrl":"' . esc_url( $photo_url ) . '"';
	}

	return '<!-- wp:go4taste-recipes/ingredients {' . $attrs . '} /-->';
}

/**
 * Build steps block from ACF fields
 *
 * Uses shared converter g4t_convert_steps_meta_to_array() to read
 * the flat repeater meta keys and build the stepsList array.
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_steps_block_from_acf( int $post_id ): string {
	$steps = g4t_convert_steps_meta_to_array( $post_id );

	if ( empty( $steps ) ) {
		return '<!-- wp:go4taste-recipes/steps /-->';
	}

	$steps_json = wp_json_encode( $steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	return '<!-- wp:go4taste-recipes/steps {"stepsList":' . $steps_json . '} /-->';
}
