<?php
/**
 * GO4TASTE Recipes Plugin — Taxonomy Registration
 *
 * Registers two types of taxonomy:
 *  1. Ingredient taxonomy — term-synced. Block editor creates/links terms automatically
 *     on post save (see sync/meta-sync.php). Used for the main recipe ingredient list.
 *  2. Classification taxonomies — standard flat taxonomies for filtering and
 *     categorisation (meal type, prep time range, recipe features).
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'G4T_INGREDIENT_TAXONOMY',  'recipe_ingredient' );
define( 'G4T_MEAL_TYPE_TAXONOMY',   'recipe_meal_type' );
define( 'G4T_PREP_TIME_TAXONOMY',   'recipe_prep_time_range' );
define( 'G4T_FEATURE_TAXONOMY',     'recipe_feature' );

/**
 * Register the primary term-synced ingredient taxonomy.
 *
 * WHY the 'taxonomy_exists' guard:
 *
 * When source_mode = 'existing_cpt', this plugin attaches its taxonomies to a CPT
 * it does not own. If that CPT's plugin also registers this taxonomy (unlikely but
 * possible), WordPress would throw a notice. The guard checks for the existing
 * taxonomy and calls register_taxonomy_for_object_type() instead — this associates
 * the taxonomy with our post type without re-declaring it. This pattern is safe to
 * use even if the taxonomy is first registered by a completely different plugin.
 *
 * WHY show_admin_column = false:
 * Ingredient assignment is managed entirely via the block editor. Showing a column
 * in wp-admin post list would be redundant and potentially confusing since the
 * taxonomy is auto-synced from repeater data, not manually assigned.
 */
function g4t_register_ingredient_taxonomy( Go4Taste_Recipes_Plugin $plugin ): void {
    $strategy  = $plugin->resolve_source_strategy();
    $post_type = $strategy['post_type'];

    if ( taxonomy_exists( G4T_INGREDIENT_TAXONOMY ) ) {
        register_taxonomy_for_object_type( G4T_INGREDIENT_TAXONOMY, $post_type );
        return;
    }

    register_taxonomy(
        G4T_INGREDIENT_TAXONOMY,
        array( $post_type ),
        array(
            'labels' => array(
                'name'          => __( 'Ingredients', 'go4taste-recipes-plugin' ),
                'singular_name' => __( 'Ingredient', 'go4taste-recipes-plugin' ),
            ),
            'public'            => true,
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => false,
            'show_in_rest'      => true,
            'rest_base'         => G4T_INGREDIENT_TAXONOMY,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'skladniki' ),
        )
    );
}

/**
 * Register classification taxonomies (flat, for filtering and categorisation).
 *
 * Add, remove, or rename entries in $taxonomies to match your domain.
 * The common settings (public, flat, REST-exposed, admin column) are merged in
 * via array_merge() so you only need to declare what differs per taxonomy.
 *
 * WHY show_admin_column = true for classification taxonomies:
 * Unlike ingredients (which are auto-synced), these taxonomies are manually assigned
 * by editors. Showing them in the post list makes filtering and bulk editing easier.
 *
 * @param Go4Taste_Recipes_Plugin $plugin Plugin instance for context.
 */
function g4t_register_flat_taxonomies( Go4Taste_Recipes_Plugin $plugin ): void {
    $strategy  = $plugin->resolve_source_strategy();
    $post_type = $strategy['post_type'];

    $taxonomies = array(
        G4T_MEAL_TYPE_TAXONOMY => array(
            'labels' => array(
                'name'          => __( 'Typy dania', 'go4taste-recipes-plugin' ),
                'singular_name' => __( 'Typ dania', 'go4taste-recipes-plugin' ),
                'all_items'     => __( 'Wszystkie typy dań', 'go4taste-recipes-plugin' ),
                'edit_item'     => __( 'Edytuj typ dania', 'go4taste-recipes-plugin' ),
                'add_new_item'  => __( 'Dodaj typ dania', 'go4taste-recipes-plugin' ),
            ),
            'rewrite'   => array( 'slug' => 'typ-dania' ),
            'rest_base' => G4T_MEAL_TYPE_TAXONOMY,
        ),
        G4T_PREP_TIME_TAXONOMY => array(
            'labels' => array(
                'name'          => __( 'Czas przygotowania', 'go4taste-recipes-plugin' ),
                'singular_name' => __( 'Czas przygotowania', 'go4taste-recipes-plugin' ),
                'all_items'     => __( 'Wszystkie zakresy czasów', 'go4taste-recipes-plugin' ),
                'edit_item'     => __( 'Edytuj zakres czasu', 'go4taste-recipes-plugin' ),
                'add_new_item'  => __( 'Dodaj zakres czasu', 'go4taste-recipes-plugin' ),
            ),
            'rewrite'   => array( 'slug' => 'czas-przygotowania' ),
            'rest_base' => G4T_PREP_TIME_TAXONOMY,
        ),
        G4T_FEATURE_TAXONOMY => array(
            'labels' => array(
                'name'          => __( 'Cechy', 'go4taste-recipes-plugin' ),
                'singular_name' => __( 'Cecha', 'go4taste-recipes-plugin' ),
                'all_items'     => __( 'Wszystkie cechy', 'go4taste-recipes-plugin' ),
                'edit_item'     => __( 'Edytuj cechę', 'go4taste-recipes-plugin' ),
                'add_new_item'  => __( 'Dodaj cechę', 'go4taste-recipes-plugin' ),
            ),
            'rewrite'   => array( 'slug' => 'cechy' ),
            'rest_base' => G4T_FEATURE_TAXONOMY,
        ),
    );

    foreach ( $taxonomies as $taxonomy_key => $args ) {
        if ( taxonomy_exists( $taxonomy_key ) ) {
            register_taxonomy_for_object_type( $taxonomy_key, $post_type );
            continue;
        }

        register_taxonomy(
            $taxonomy_key,
            array( $post_type ),
            array_merge(
                $args,
                array(
                    'public'            => true,
                    'hierarchical'      => false,
                    'show_ui'           => true,
                    'show_admin_column' => true,
                    'show_in_rest'      => true,
                    'query_var'         => true,
                )
            )
        );
    }
}
