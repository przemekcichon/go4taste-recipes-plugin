<?php
/**
 * Recipe Data Converters: post_meta → block attributes/arrays
 *
 * Shared conversion utilities used by:
 * - includes/admin/reverse-sync.php (meta → blocks for WP All Import)
 * - includes/sync/acf-to-blocks.php (ACF → blocks for dual UI)
 *
 * @package Go4Taste_Recipes_Plugin
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve image meta value to an absolute URL.
 *
 * ACF image fields are often stored as attachment IDs in raw post meta, while
 * legacy/import paths may store direct URLs. This helper accepts both forms.
 *
 * @param mixed $value Raw meta value.
 * @return string Resolved URL or empty string.
 */
function g4t_resolve_image_meta_to_url( $value ): string {
	$raw = trim( (string) $value );
	if ( '' === $raw ) {
		return '';
	}

	if ( ctype_digit( $raw ) ) {
		$attachment_url = wp_get_attachment_url( (int) $raw );
		if ( is_string( $attachment_url ) && '' !== $attachment_url ) {
			return esc_url_raw( $attachment_url );
		}
	}

	return esc_url_raw( $raw );
}

/**
 * Convert hero meta keys to block attributes array
 *
 * @param int $post_id Post ID.
 * @return array<string,mixed> Block attributes (empty array if no data).
 */
function g4t_convert_hero_meta_to_attrs( int $post_id ): array {
	$attrs = array();

	$lead = get_post_meta( $post_id, 'recipe_lead', true );
	if ( ! empty( $lead ) ) {
		$attrs['lead'] = (string) $lead;
	}

	$difficulty = get_post_meta( $post_id, 'recipe_difficulty', true );
	if ( ! empty( $difficulty ) ) {
		$attrs['difficulty'] = (string) $difficulty;
	}

	$servings_value = get_post_meta( $post_id, 'recipe_servings_value', true );
	if ( ! empty( $servings_value ) ) {
		$attrs['servingsValue'] = $servings_value;
	}

	$servings_unit = get_post_meta( $post_id, 'recipe_servings_unit', true );
	if ( ! empty( $servings_unit ) ) {
		$attrs['servingsUnit'] = (string) $servings_unit;
	}

	$time_prep = get_post_meta( $post_id, 'recipe_time_prep', true );
	if ( ! empty( $time_prep ) ) {
		$attrs['timePrep'] = (string) $time_prep;
	}

	$time_cook = get_post_meta( $post_id, 'recipe_time_cook', true );
	if ( ! empty( $time_cook ) ) {
		$attrs['timeCook'] = (string) $time_cook;
	}

	$time_total = get_post_meta( $post_id, 'recipe_time_total', true );
	if ( ! empty( $time_total ) ) {
		$attrs['timeTotal'] = (string) $time_total;
	}

	$time_soak = get_post_meta( $post_id, 'recipe_time_soak', true );
	if ( ! empty( $time_soak ) ) {
		$attrs['timeSoak'] = (string) $time_soak;
	}

	return $attrs;
}

/**
 * Convert ingredients meta to array for itemsList attribute
 *
 * @param int $post_id Post ID.
 * @return array<int,array{ingredient_term_id:int|null,name:string,amount:string,unit:string}> Items array.
 */
function g4t_convert_ingredients_meta_to_array( int $post_id ): array {
	$count = 0;
	while ( metadata_exists( 'post', $post_id, "recipe_ingredients_{$count}_name" ) ) {
		$count++;
	}

	if ( $count === 0 ) {
		return array();
	}

	$items = array();
	for ( $i = 0; $i < $count; $i++ ) {
		$name            = get_post_meta( $post_id, "recipe_ingredients_{$i}_name", true );
		$amount          = get_post_meta( $post_id, "recipe_ingredients_{$i}_amount", true );
		$unit            = get_post_meta( $post_id, "recipe_ingredients_{$i}_unit", true );
		$term_id_raw     = get_post_meta( $post_id, "recipe_ingredients_{$i}_ingredient_term_id", true );
		$ingredient_note = get_post_meta( $post_id, "recipe_ingredients_{$i}_ingredient_note", true );

		$term_id = ( '' !== (string) $term_id_raw && is_numeric( (string) $term_id_raw ) ) ? (int) $term_id_raw : null;

		$items[] = array(
			'ingredient_term_id' => $term_id,
			'name'               => sanitize_text_field( (string) $name ),
			'amount'             => sanitize_text_field( trim( (string) $amount ) ),
			'unit'               => sanitize_text_field( (string) $unit ),
			'ingredient_note'    => sanitize_text_field( (string) $ingredient_note ),
		);
	}

	return $items;
}

/**
 * Convert steps meta to array for stepsList attribute
 *
 * Reads flat repeater meta keys (step_header, step_description, step_tip, step_image_url)
 * and builds stepsList array matching block editor structure.
 *
 * @param int $post_id Post ID.
 * @return array<int,array{heading:string,description:string,tip:string,imageUrl:string}> Steps array.
 */
function g4t_convert_steps_meta_to_array( int $post_id ): array {
	$count = (int) get_post_meta( $post_id, 'recipe_steps', true );

	if ( $count === 0 ) {
		return array();
	}

	$steps = array();
	for ( $i = 0; $i < $count; $i++ ) {
		$heading     = get_post_meta( $post_id, "recipe_steps_{$i}_step_header", true );
		$description = get_post_meta( $post_id, "recipe_steps_{$i}_step_description", true );
		$tip         = get_post_meta( $post_id, "recipe_steps_{$i}_step_tip", true );
		$image_url   = get_post_meta( $post_id, "recipe_steps_{$i}_step_image_url", true );

		$steps[] = array(
			'heading'     => sanitize_text_field( (string) $heading ),
			'description' => sanitize_textarea_field( (string) $description ),
			'tip'         => sanitize_textarea_field( (string) ( $tip ?: '' ) ),
			'imageUrl'    => g4t_resolve_image_meta_to_url( $image_url ),
		);
	}

	return $steps;
}
