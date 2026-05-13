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
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_hero_block_from_meta( int $post_id ): string {
	$attrs = array();
	
	$lead           = get_post_meta( $post_id, 'recipe_lead', true );
	$difficulty     = get_post_meta( $post_id, 'recipe_difficulty', true );
	$servings_value = get_post_meta( $post_id, 'recipe_servings_value', true );
	$servings_unit  = get_post_meta( $post_id, 'recipe_servings_unit', true );
	$time_prep      = get_post_meta( $post_id, 'recipe_time_prep', true );
	$time_cook      = get_post_meta( $post_id, 'recipe_time_cook', true );
	$time_total     = get_post_meta( $post_id, 'recipe_time_total', true );
	$time_soak      = get_post_meta( $post_id, 'recipe_time_soak', true );

	if ( ! empty( $lead ) ) {
		$attrs['lead'] = (string) $lead;
	}
	if ( ! empty( $difficulty ) ) {
		$attrs['difficulty'] = (string) $difficulty;
	}
	if ( ! empty( $servings_value ) ) {
		$attrs['servingsValue'] = $servings_value;
	}
	if ( ! empty( $servings_unit ) ) {
		$attrs['servingsUnit'] = (string) $servings_unit;
	}
	if ( ! empty( $time_prep ) ) {
		$attrs['timePrep'] = (string) $time_prep;
	}
	if ( ! empty( $time_cook ) ) {
		$attrs['timeCook'] = (string) $time_cook;
	}
	if ( ! empty( $time_total ) ) {
		$attrs['timeTotal'] = (string) $time_total;
	}
	if ( ! empty( $time_soak ) ) {
		$attrs['timeSoak'] = (string) $time_soak;
	}

	if ( empty( $attrs ) ) {
		return '<!-- wp:go4taste-recipes/hero /-->';
	}

	$attrs_json = wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	return '<!-- wp:go4taste-recipes/hero ' . $attrs_json . ' /-->';
}

/**
 * Zbuduj blok ingredients z post_meta
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_ingredients_block_from_meta( int $post_id ): string {
	// Policz składniki
	$count = 0;
	while ( metadata_exists( 'post', $post_id, "recipe_ingredients_{$count}_name" ) ) {
		$count++;
	}

	if ( $count === 0 ) {
		return '<!-- wp:go4taste-recipes/ingredients /-->';
	}

	// Pobierz photo URL jeśli jest
	$photo_url = get_post_meta( $post_id, 'recipe_ingredients_photo_url', true );

	// Zbuduj JSON array składników
	$items = [];
	for ( $i = 0; $i < $count; $i++ ) {
		$name        = get_post_meta( $post_id, "recipe_ingredients_{$i}_name", true );
		$amount      = get_post_meta( $post_id, "recipe_ingredients_{$i}_amount", true );
		$unit        = get_post_meta( $post_id, "recipe_ingredients_{$i}_unit", true );
		$term_id_raw = get_post_meta( $post_id, "recipe_ingredients_{$i}_ingredient_term_id", true );
		
		$term_id = ( '' !== (string) $term_id_raw && is_numeric( (string) $term_id_raw ) ) ? (int) $term_id_raw : null;

		$items[] = [
			'ingredient_term_id' => $term_id,
			'name'               => $name,
			'amount'             => $amount,
			'unit'               => $unit,
		];
	}

	$items_json = wp_json_encode( $items, JSON_UNESCAPED_UNICODE );

	$attrs = '"itemsList":' . $items_json;
	if ( ! empty( $photo_url ) ) {
		$attrs .= ',"photoUrl":"' . esc_url( $photo_url ) . '"';
	}

	return '<!-- wp:go4taste-recipes/ingredients {' . $attrs . '} /-->';
}

/**
 * Zbuduj blok steps z post_meta
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_steps_block_from_meta( int $post_id ): string {
	// Policz kroki
	$count = 0;
	while ( metadata_exists( 'post', $post_id, "recipe_steps_{$count}_heading" ) ) {
		$count++;
	}

	if ( $count === 0 ) {
		return '<!-- wp:go4taste-recipes/steps /-->';
	}

	// Zbuduj JSON array kroków
	$steps = [];
	for ( $i = 0; $i < $count; $i++ ) {
		$heading     = get_post_meta( $post_id, "recipe_steps_{$i}_heading", true );
		$description = get_post_meta( $post_id, "recipe_steps_{$i}_description", true );
		$tip         = get_post_meta( $post_id, "recipe_steps_{$i}_tip", true );

		$steps[] = [
			'heading'     => $heading,
			'description' => $description,
			'images'      => [],
			'tip'         => $tip ?: '',
		];
	}

	$steps_json = wp_json_encode( $steps, JSON_UNESCAPED_UNICODE );

	return '<!-- wp:go4taste-recipes/steps {"stepsList":' . $steps_json . '} /-->';
}

/**
 * Zbuduj blok tips z post_meta
 *
 * @param int $post_id Post ID.
 * @return string Block markup.
 */
function g4t_build_tips_block_from_meta( int $post_id ): string {
	$tips = get_post_meta( $post_id, 'recipe_tips', true );

	if ( empty( $tips ) ) {
		return '<!-- wp:go4taste-recipes/tips /-->';
	}

	return '<!-- wp:go4taste-recipes/tips {"tips":"' . esc_attr( $tips ) . '"} /-->';
}

/**
 * Zbuduj blok source z post_meta
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

	$attrs = [];
	if ( ! empty( $label ) ) {
		$attrs[] = '"sourceLabel":"' . esc_attr( $label ) . '"';
	}
	if ( ! empty( $url ) ) {
		$attrs[] = '"sourceUrl":"' . esc_url( $url ) . '"';
	}

	return '<!-- wp:go4taste-recipes/source {' . implode( ',', $attrs ) . '} /-->';
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
		while ( metadata_exists( 'post', $post_id, "recipe_steps_{$count}_heading" ) ) {
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
		if ( ! metadata_exists( 'post', $post_id, "recipe_steps_{$i}_tip" ) ) {
			update_post_meta( $post_id, "recipe_steps_{$i}_tip", '' );
			$stats['tips_added']++;
		}
	}

	// 4. Dodaj brakujące recipe_steps_{i}_images
	for ( $i = 0; $i < $steps_count; $i++ ) {
		if ( ! metadata_exists( 'post', $post_id, "recipe_steps_{$i}_images" ) ) {
			update_post_meta( $post_id, "recipe_steps_{$i}_images", '0' );
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
	
	// Przywróć hook (bloki są już zbudowane, więc meta-sync przy następnym zapisie będzie OK)
	add_action( 'save_post', 'g4t_sync_recipe_meta_from_blocks', 20, 4 );

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
