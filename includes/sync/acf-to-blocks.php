<?php
/**
 * ACF → Blocks Synchronization
 *
 * Syncs ACF field edits to Gutenberg blocks in post_content.
 * Fires on acf/save_post after ACF has already written submitted values to meta.
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
 * This must run after ACF's own priority-10 meta write. The front creator submits
 * fresh values only in $_POST['acf']; reading post_meta earlier would rebuild empty
 * blocks from stale data and the subsequent save_post sync would wipe the recipe.
 *
 * Performance optimization: sets transient to skip block parsing in meta-sync.
 *
 * WHY int|string type hint:
 * ACF calls acf/save_post with int for posts (e.g. 123) but string for taxonomy
 * terms (e.g. 'term_18'). We only process posts, so early return for terms.
 *
 * @param int|string $post_id Post ID being saved, or 'term_{id}' for taxonomy terms.
 */
function g4t_sync_acf_to_blocks( int|string $post_id, Go4Taste_Recipes_Plugin $plugin ): void {
	static $running = false;
	if ( $running ) {
		return;
	}
	$running = true;

	// Guard: skip taxonomy term saves (ACF passes 'term_{id}' as string)
	if ( is_string( $post_id ) ) {
		$running = false;
		return;
	}

	// Guards
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		$running = false;
		return;
	}

	// Never run during REST API requests (Gutenberg saves always use REST).
	// Belt-and-suspenders guard: ACF Pro 6.x can fire acf/save_post during REST
	// saves with $_POST['acf'] populated, which would fool g4t_detect_save_source().
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$running = false;
		return;
	}

	// Skip if this post was just saved via Gutenberg REST API.
	// After the REST PUT, Gutenberg sends a follow-up meta box form POST
	// ($_POST['acf'] set, REST_REQUEST false) containing stale field values
	// from when the page loaded. Without this guard those stale values would
	// rebuild blocks from old meta and overwrite the blocks the REST PUT saved.
	if ( get_transient( "g4t_rest_save_{$post_id}" ) ) {
		delete_transient( "g4t_rest_save_{$post_id}" );
		$running = false;
		return;
	}

	// Only run on ACF save
	if ( g4t_detect_save_source() !== 'acf' ) {
		$running = false;
		return;
	}

	// Featured image source of truth: always sync ACF field -> WP post thumbnail.
	g4t_sync_post_thumbnail_from_acf_featured_image( $post_id );

	// ACF writes the repeater rows first, but new ingredient terms are normally
	// created during block->meta sync. Front creator saves skip that phase via the
	// transient below, so we must materialize missing terms before rebuilding blocks.
	g4t_sync_ingredient_taxonomy_from_existing_meta( $post_id );

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
		g4t_build_inline_banner_block_from_existing_content( $post_id ),
	);

	$post_content = implode( "\n", $blocks );
	$requested_action = '';
	// Only honour publish action when the request genuinely originates from the
	// front recipe creator form (not from admin ACF meta boxes or other contexts).
	// Guard: must be a non-admin, non-REST request with the creator action set.
	if (
		function_exists( 'g4t_get_recipe_creator_requested_action' )
		&& ! is_admin()
		&& ! ( defined( 'REST_REQUEST' ) && REST_REQUEST )
	) {
		$requested_action = g4t_get_recipe_creator_requested_action();
	}

	// Set flag to skip block→meta sync (performance optimization)
	set_transient( "g4t_acf_save_{$post_id}", true, 60 );

	// Disable block→meta sync to avoid loop
	remove_action( 'save_post', array( $plugin, 'proxy_sync_meta' ), 20 );

	$update_args = array(
		'ID'           => $post_id,
		'post_content' => $post_content,
	);

	if ( 'publish' === $requested_action ) {
		$update_args['post_status'] = 'publish';
	}

	$result = wp_update_post( $update_args );

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
	add_action( 'save_post', array( $plugin, 'proxy_sync_meta' ), 20, 3 );

	$running = false;
}

/**
 * Sync WP post thumbnail from ACF featured image field.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function g4t_sync_post_thumbnail_from_acf_featured_image( int $post_id ): void {
	$raw_featured = trim( (string) get_post_meta( $post_id, 'recipe_featured_image', true ) );

	if ( '' === $raw_featured ) {
		delete_post_thumbnail( $post_id );
		return;
	}

	if ( ! ctype_digit( $raw_featured ) ) {
		return;
	}

	$attachment_id = (int) $raw_featured;
	if ( $attachment_id <= 0 ) {
		delete_post_thumbnail( $post_id );
		return;
	}

	set_post_thumbnail( $post_id, $attachment_id );
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
	$photo_meta = get_post_meta( $post_id, 'recipe_ingredients_photo_url', true );
	$photo_url  = g4t_resolve_image_meta_to_url( $photo_meta );

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

/**
 * Preserve the existing inline-banner block (with attributes) during ACF sync.
 *
 * WHY this helper exists:
 *
 * ACF->blocks rebuild previously appended an empty inline-banner block comment,
 * which removed selected products from block attributes on the next save.
 * This helper keeps the existing block instance when available.
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_inline_banner_block_from_existing_content( int $post_id ): string {
	$default_block = '<!-- wp:go4taste-ads/inline-banner /-->';

	if ( ! function_exists( 'parse_blocks' ) || ! function_exists( 'serialize_block' ) ) {
		return $default_block;
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return $default_block;
	}

	$found = g4t_find_inline_banner_block( parse_blocks( (string) $post->post_content ) );

	if ( null === $found ) {
		return $default_block;
	}

	return serialize_block( $found );
}

/**
 * Recursively find the first go4taste-ads/inline-banner block.
 *
 * @param array<int,array<string,mixed>> $blocks Parsed block tree.
 * @return array<string,mixed>|null
 */
function g4t_find_inline_banner_block( array $blocks ): ?array {
	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
		if ( 'go4taste-ads/inline-banner' === $block_name ) {
			return $block;
		}

		$inner_blocks = isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ? $block['innerBlocks'] : array();
		if ( ! empty( $inner_blocks ) ) {
			$nested_found = g4t_find_inline_banner_block( $inner_blocks );
			if ( null !== $nested_found ) {
				return $nested_found;
			}
		}
	}

	return null;
}
