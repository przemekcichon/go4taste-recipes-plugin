<?php
/**
 * Admin settings runtime logic.
 *
 * Applies settings-driven behaviors that must run on every request,
 * not just in wp-admin (e.g. taxonomy unregistering affects save_post on frontend too).
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Unregister built-in WordPress taxonomies from the recipe post type based on plugin settings.
 *
 * Runs on init at priority 11 — after WordPress registers built-in taxonomies at priority 0.
 * Only applies when the resolved source post type has the taxonomy registered for it.
 *
 * WHY priority 11: WordPress calls create_initial_taxonomies() on init priority 0,
 * which registers 'category' and 'post_tag' for 'post'. We must run after that.
 */
function g4t_maybe_unregister_recipe_taxonomies(): void {
    $options = g4t_get_recipe_options();

    $unregister_category = ! empty( $options['unregister_category'] );
    $unregister_post_tag = ! empty( $options['unregister_post_tag'] );

    if ( ! $unregister_category && ! $unregister_post_tag ) {
        return;
    }

    $strategy  = g4t_get_source_strategy();
    $post_type = $strategy['post_type'] ?? 'post';

    if ( $unregister_category ) {
        unregister_taxonomy_for_object_type( 'category', $post_type );
    }

    if ( $unregister_post_tag ) {
        unregister_taxonomy_for_object_type( 'post_tag', $post_type );
    }
}
