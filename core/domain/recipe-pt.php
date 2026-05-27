<?php
/**
 * Core domain: recipe post type behavior.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'G4T_RECIPES_POST_TYPE' ) ) {
    define( 'G4T_RECIPES_POST_TYPE', 'recipe' );
}

/**
 * Register the plugin's own recipe CPT — only when source_mode = 'recipes_cpt'.
 */
function g4t_register_recipe_post_type( Go4Taste_Recipes_Plugin $plugin ): void {
    $strategy = g4t_get_source_strategy();

    if ( Go4Taste_Recipes_Plugin::MODE_RECIPES_CPT !== $strategy['mode'] ) {
        return;
    }

    if ( post_type_exists( G4T_RECIPES_POST_TYPE ) ) {
        return;
    }

    register_post_type(
        G4T_RECIPES_POST_TYPE,
        array(
            'labels' => array(
                'name'          => __( 'Recipes', 'go4taste-recipes-plugin' ),
                'singular_name' => __( 'Recipe', 'go4taste-recipes-plugin' ),
            ),
            'public'       => true,
            'show_in_rest' => true,
            'has_archive'  => true,
            'supports'     => array( 'title', 'excerpt', 'thumbnail', 'author', 'revisions' ),
            'rewrite'      => array( 'slug' => 'recipes' ),
        )
    );
}

/**
 * Disable block editor for source recipe post type.
 */
function g4t_disable_block_editor_for_recipe_post_type( bool $use_block_editor, string $post_type, Go4Taste_Recipes_Plugin $plugin ): bool {
    $strategy = g4t_get_source_strategy();

    if ( $post_type === $strategy['post_type'] ) {
        return false;
    }

    return $use_block_editor;
}
