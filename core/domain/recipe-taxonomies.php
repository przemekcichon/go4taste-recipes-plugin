<?php
/**
 * Core domain: recipe taxonomies.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'G4T_INGREDIENT_TAXONOMY' ) ) {
    define( 'G4T_INGREDIENT_TAXONOMY', 'recipe_ingredient' );
}
if ( ! defined( 'G4T_MEAL_TYPE_TAXONOMY' ) ) {
    define( 'G4T_MEAL_TYPE_TAXONOMY', 'recipe_meal_type' );
}
if ( ! defined( 'G4T_PREP_TIME_TAXONOMY' ) ) {
    define( 'G4T_PREP_TIME_TAXONOMY', 'recipe_prep_time_range' );
}
if ( ! defined( 'G4T_FEATURE_TAXONOMY' ) ) {
    define( 'G4T_FEATURE_TAXONOMY', 'recipe_feature' );
}

/**
 * Register primary ingredient taxonomy.
 *
 * Not public: ingredient term pages (formerly /skladniki/<slug>) would compete
 * with the go4taste.pl shop and blog, so old URLs return 404. Terms still
 * drive filtering (?g4t_ingredients=, FacetWP), the admin UI and REST.
 */
function g4t_register_ingredient_taxonomy( Go4Taste_Recipes_Plugin $plugin ): void {
    $strategy  = g4t_get_source_strategy();
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
            'public'             => false,
            'publicly_queryable' => false,
            'hierarchical'       => false,
            'show_ui'            => true,
            'show_in_nav_menus'  => false,
            'show_tagcloud'      => false,
            'show_admin_column'  => false,
            'show_in_rest'       => true,
            'rest_base'          => G4T_INGREDIENT_TAXONOMY,
            'query_var'          => false,
            'rewrite'            => false,
        )
    );
}

/**
 * Register classification taxonomies.
 */
function g4t_register_flat_taxonomies( Go4Taste_Recipes_Plugin $plugin ): void {
    $strategy  = g4t_get_source_strategy();
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
