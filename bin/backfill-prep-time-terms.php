<?php
/**
 * Backfill recipe_prep_time_range taxonomy terms from recipe_time_total meta.
 *
 * Usage (dry run — default, nothing is written):
 *   wp eval-file wp-content/plugins/go4taste-recipes-plugin/bin/backfill-prep-time-terms.php
 *
 * Usage (real run):
 *   wp eval-file wp-content/plugins/go4taste-recipes-plugin/bin/backfill-prep-time-terms.php run
 *
 * Mapping:
 *   ≤ 15 min  → do-15-min      (term_id 19)
 *   ≤ 30 min  → do-30-min      (term_id 980)
 *   ≤ 45 min  → do-45-min      (term_id 981)
 *   > 45 min  → powyzej-45-min (term_id 11)
 */

$dry_run = ( ( $args[0] ?? '' ) !== 'run' );

if ( $dry_run ) {
	WP_CLI::log( '=== DRY RUN — nothing will be written ===' );
}

$taxonomy = defined( 'G4T_PREP_TIME_TAXONOMY' ) ? G4T_PREP_TIME_TAXONOMY : 'recipe_prep_time_range';

$strategy  = function_exists( 'g4t_get_source_strategy' ) ? g4t_get_source_strategy() : array();
$post_type = $strategy['post_type'] ?? 'post';

$range_map = array(
	array( 'max' => 15, 'slug' => 'do-15-min' ),
	array( 'max' => 30, 'slug' => 'do-30-min' ),
	array( 'max' => 45, 'slug' => 'do-45-min' ),
	array( 'max' => PHP_INT_MAX, 'slug' => 'powyzej-45-min' ),
);

$terms_by_slug = array();
foreach ( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) ) as $term ) {
	$terms_by_slug[ $term->slug ] = (int) $term->term_id;
}

foreach ( $range_map as $range ) {
	if ( ! isset( $terms_by_slug[ $range['slug'] ] ) ) {
		WP_CLI::error( "Term slug '{$range['slug']}' not found in taxonomy '{$taxonomy}'. Aborting." );
	}
}

$query = new WP_Query( array(
	'post_type'              => $post_type,
	'post_status'            => 'publish',
	'posts_per_page'         => -1,
	'fields'                 => 'ids',
	'no_found_rows'          => true,
	'update_post_meta_cache' => false,
	'update_post_term_cache' => false,
) );

$post_ids = $query->posts;
WP_CLI::log( "Found {$query->post_count} published {$post_type} posts." );

$assigned = 0;
$skipped  = 0;
$no_time  = 0;
$invalid  = 0;
$errors   = 0;

foreach ( $post_ids as $post_id ) {
	$existing = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );

	if ( ! empty( $existing ) && ! is_wp_error( $existing ) ) {
		$skipped++;
		continue;
	}

	$raw = get_post_meta( $post_id, 'recipe_time_total', true );

	if ( '' === $raw || false === $raw ) {
		WP_CLI::log( "SKIP post {$post_id}: brak recipe_time_total" );
		$no_time++;
		continue;
	}

	$minutes = (int) $raw;

	if ( $minutes <= 0 ) {
		WP_CLI::log( "SKIP post {$post_id}: nieprawidłowa wartość '{$raw}'" );
		$invalid++;
		continue;
	}

	$matched_slug    = null;
	$matched_term_id = null;

	foreach ( $range_map as $range ) {
		if ( $minutes <= $range['max'] ) {
			$matched_slug    = $range['slug'];
			$matched_term_id = $terms_by_slug[ $range['slug'] ];
			break;
		}
	}

	if ( $dry_run ) {
		WP_CLI::log( "WOULD SET post {$post_id}: {$minutes} min → {$matched_slug} (term_id {$matched_term_id})" );
		$assigned++;
		continue;
	}

	$result = wp_set_object_terms( $post_id, array( $matched_term_id ), $taxonomy );

	if ( is_wp_error( $result ) ) {
		WP_CLI::warning( "ERROR post {$post_id}: {$result->get_error_message()}" );
		$errors++;
	} else {
		WP_CLI::log( "SET post {$post_id}: {$minutes} min → {$matched_slug}" );
		$assigned++;
	}
}

WP_CLI::log( '' );
WP_CLI::log( "=== Podsumowanie ===" );
WP_CLI::log( "Przypisano:            {$assigned}" );
WP_CLI::log( "Pominięto (miał term): {$skipped}" );
WP_CLI::log( "Brak recipe_time_total:{$no_time}" );
WP_CLI::log( "Nieprawidłowa wartość: {$invalid}" );
WP_CLI::log( "Błędy wp_set_object_terms: {$errors}" );

if ( $dry_run ) {
	WP_CLI::log( '' );
	WP_CLI::log( 'Dry run zakończony. Aby uruchomić realnie:' );
	WP_CLI::log( '  wp eval-file plugins/go4taste-recipes-plugin/bin/backfill-prep-time-terms.php --dry-run=false' );
}
