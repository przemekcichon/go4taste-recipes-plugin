<?php
/**
 * Reverse Sync: post_meta → Gutenberg Blocks
 *
 * Odbudowuje bloki Gutenberg w post_content na podstawie danych z post_meta
 * dla przepisów importowanych przez WP All Import.
 *
 * @package Go4Taste_Recipes_Plugin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Zbuduj blok hero z post_meta
 *
 * Uses shared converter from includes/sync/converters.php
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_hero_block_from_meta( int $post_id ): string {
	$attrs = g4t_convert_hero_meta_to_attrs( $post_id );

	if ( empty( $attrs ) ) {
		return '<!-- wp:go4taste-recipes/hero /-->';
	}

	$attrs_json = wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	return '<!-- wp:go4taste-recipes/hero ' . $attrs_json . ' /-->';
}

/**
 * Zbuduj blok ingredients z post_meta
 *
 * Uses shared converter from includes/sync/converters.php
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_ingredients_block_from_meta( int $post_id ): string {
	$items = g4t_convert_ingredients_meta_to_array( $post_id );

	if ( empty( $items ) ) {
		return '<!-- wp:go4taste-recipes/ingredients /-->';
	}

	$items_json = wp_json_encode( $items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	// Pobierz photo URL jeśli jest
	$photo_url = get_post_meta( $post_id, 'recipe_ingredients_photo_url', true );
	$attrs = '"itemsList":' . $items_json;
	if ( ! empty( $photo_url ) ) {
		$attrs .= ',"photoUrl":"' . esc_url( $photo_url ) . '"';
	}

	return '<!-- wp:go4taste-recipes/ingredients {' . $attrs . '} /-->';
}

/**
 * Zbuduj blok steps z post_meta
 *
 * Uses shared converter from includes/sync/converters.php
 * Now includes nested images repeater support.
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_steps_block_from_meta( int $post_id ): string {
	$steps = g4t_convert_steps_meta_to_array( $post_id );

	if ( empty( $steps ) ) {
		return '<!-- wp:go4taste-recipes/steps /-->';
	}

	$steps_json = wp_json_encode( $steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	return '<!-- wp:go4taste-recipes/steps {"stepsList":' . $steps_json . '} /-->';
}

/**
 * Zbuduj blok tips z post_meta
 *
 * Uses wp_json_encode for safe JSON attribute encoding.
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_tips_block_from_meta( int $post_id ): string {
	$tips = get_post_meta( $post_id, 'recipe_tips', true );

	if ( empty( $tips ) ) {
		return '<!-- wp:go4taste-recipes/tips /-->';
	}

	$attrs      = array( 'tips' => (string) $tips );
	$attrs_json = wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	return '<!-- wp:go4taste-recipes/tips ' . $attrs_json . ' /-->';
}

/**
 * Zbuduj blok source z post_meta
 *
 * Uses wp_json_encode for safe JSON attribute encoding.
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_source_block_from_meta( int $post_id ): string {
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
 * Napraw brakujące meta keys (counters, tips, images)
 *
 * @param int $post_id Post ID.
 * @return array Stats: ['ingredients_counter' => bool, 'steps_counter' => bool, 'tips_added' => int, 'images_added' => int]
 */
function g4t_fix_missing_meta_keys( int $post_id ): array {
	$stats = [
		'ingredients_counter' => false,
		'steps_counter'       => false,
		'tips_added'          => 0,
		'images_added'        => 0,
	];

	// 1. Dodaj recipe_ingredients counter jeśli brakuje
	if ( ! metadata_exists( 'post', $post_id, 'recipe_ingredients' ) ) {
		$count = 0;
		while ( metadata_exists( 'post', $post_id, "recipe_ingredients_{$count}_name" ) ) {
			$count++;
		}
		if ( $count > 0 ) {
			update_post_meta( $post_id, 'recipe_ingredients', $count );
			$stats['ingredients_counter'] = true;
		}
	}

	// 2. Dodaj recipe_steps counter jeśli brakuje
	if ( ! metadata_exists( 'post', $post_id, 'recipe_steps' ) ) {
		$count = 0;
		while ( metadata_exists( 'post', $post_id, "recipe_steps_{$count}_step_header" ) ) {
			$count++;
		}
		if ( $count > 0 ) {
			update_post_meta( $post_id, 'recipe_steps', $count );
			$stats['steps_counter'] = true;
		}
	}

	// 3. Dodaj brakujące recipe_steps_{i}_tip
	$steps_count = (int) get_post_meta( $post_id, 'recipe_steps', true );
	for ( $i = 0; $i < $steps_count; $i++ ) {
		if ( ! metadata_exists( 'post', $post_id, "recipe_steps_{$i}_step_tip" ) ) {
			update_post_meta( $post_id, "recipe_steps_{$i}_step_tip", '' );
			$stats['tips_added']++;
		}
	}

	// 4. Dodaj brakujące recipe_steps_{i}_step_image_url
	for ( $i = 0; $i < $steps_count; $i++ ) {
		if ( ! metadata_exists( 'post', $post_id, "recipe_steps_{$i}_step_image_url" ) ) {
			update_post_meta( $post_id, "recipe_steps_{$i}_step_image_url", '' );
			$stats['images_added']++;
		}
	}

	return $stats;
}

/**
 * Odbuduj bloki dla pojedynczego posta
 *
 * @param int $post_id Post ID.
 * @return array|WP_Error Success stats array or WP_Error on failure.
 */
function g4t_rebuild_blocks_for_single_post( int $post_id ) {
	// 1. Napraw brakujące meta keys
	$fix_stats = g4t_fix_missing_meta_keys( $post_id );

	// 2. Zbuduj wszystkie bloki
	$blocks = [
		g4t_build_hero_block_from_meta( $post_id ),
		'',
		g4t_build_ingredients_block_from_meta( $post_id ),
		'',
		g4t_build_steps_block_from_meta( $post_id ),
		'',
		g4t_build_tips_block_from_meta( $post_id ),
		'',
		g4t_build_source_block_from_meta( $post_id ),
		'',
		'<!-- wp:go4taste-ads/inline-banner /-->',
	];

	$post_content = implode( "\n", $blocks );

	// 3. Zapisz do post_content (wyłącz meta-sync żeby uniknąć re-entry)
	remove_action( 'save_post', 'g4t_sync_recipe_meta_from_blocks', 20 );
	
	$updated = wp_update_post(
		[
			'ID'           => $post_id,
			'post_content' => $post_content,
		],
		true
	);

	if ( is_wp_error( $updated ) ) {
		return $updated;
	}

	return [
		'blocks_count'        => substr_count( $post_content, '<!-- wp:' ),
		'ingredients_counter' => $fix_stats['ingredients_counter'],
		'steps_counter'       => $fix_stats['steps_counter'],
		'tips_added'          => $fix_stats['tips_added'],
		'images_added'        => $fix_stats['images_added'],
	];
}

/**
 * Uruchom reverse sync dla wszystkich recipe postów z pustym post_content
 *
 * @return array Stats: ['total' => int, 'empty' => int, 'processed' => int, 'failed' => int, 'errors' => array]
 */
function g4t_run_reverse_sync(): array {
	$stats = [
		'total'     => 0,
		'empty'     => 0,
		'processed' => 0,
		'failed'    => 0,
		'errors'    => [],
	];

	// Pobierz wszystkie recipe posty z danymi meta
	$args = [
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_query'     => [
			[
				'key'     => 'recipe_time_prep',
				'compare' => 'EXISTS',
			],
		],
	];

	$query = new WP_Query( $args );

	if ( ! $query->have_posts() ) {
		return $stats;
	}

	$stats['total'] = $query->post_count;

	while ( $query->have_posts() ) {
		$query->the_post();
		$post_id = get_the_ID();

		// Sprawdź czy post_content jest pusty
		$content = get_post_field( 'post_content', $post_id );

		if ( empty( trim( $content ) ) ) {
			$stats['empty']++;

			$result = g4t_rebuild_blocks_for_single_post( $post_id );

			if ( is_wp_error( $result ) ) {
				$stats['failed']++;
				$stats['errors'][] = [
					'post_id' => $post_id,
					'title'   => get_the_title( $post_id ),
					'error'   => $result->get_error_message(),
				];
			} else {
				$stats['processed']++;
			}
		}
	}

	wp_reset_postdata();

	return $stats;
}

/**
 * Handler dla admin action reverse sync
 */
function g4t_handle_reverse_sync_action() {
	// Security checks
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Nie masz uprawnień do wykonania tej operacji.', 'go4taste-recipes-plugin' ) );
	}

	if ( ! isset( $_POST['g4t_reverse_sync_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['g4t_reverse_sync_nonce'] ), 'g4t_reverse_sync' ) ) {
		wp_die( esc_html__( 'Weryfikacja bezpieczeństwa nieudana.', 'go4taste-recipes-plugin' ) );
	}

	// Uruchom reverse sync
	$stats = g4t_run_reverse_sync();

	// Zapisz rezultaty do transient (5 minut)
	set_transient( 'g4t_reverse_sync_result', $stats, 300 );

	// Redirect z success message
	$redirect_url = add_query_arg(
		[
			'page'                => 'go4taste-recipes',
			'g4t_reverse_sync'    => 'done',
		],
		admin_url( 'options-general.php' )
	);

	wp_safe_redirect( $redirect_url );
	exit;
}
