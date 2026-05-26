<?php
/**
 * GO4TASTE Recipes Plugin — Post Type Registration
 *
 * Responsibilities:
 *  1. Register the dedicated recipe CPT when source_mode = 'recipes_cpt'.
 *  2. Disable Gutenberg for the configured recipe post type.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'G4T_RECIPES_POST_TYPE', 'recipe' );

/**
 * Register the plugin's own recipe CPT — only when source_mode = 'recipes_cpt'.
 *
 * In 'post' or 'existing_cpt' modes this function exits immediately, so it is
 * safe to call unconditionally from the orchestrator's 'init' hook.
 *
 * NOTE: Gutenberg is disabled at post-type level via
 * g4t_disable_block_editor_for_recipe_post_type().
 */
function g4t_register_recipe_post_type( Go4Taste_Recipes_Plugin $plugin ): void {
    $strategy = $plugin->resolve_source_strategy();

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
 * Disable the block editor for the post type configured as the recipe source.
 *
 * Applies to all three source modes (post, existing_cpt, recipes_cpt): whatever
 * post type the admin has designated for recipes, Gutenberg is turned off for it.
 * The frontend creator is the only authoring surface.
 *
 * @param bool   $use_block_editor Whether to use the block editor.
 * @param string $post_type        The post type being edited.
 * @param Go4Taste_Recipes_Plugin $plugin Plugin instance.
 * @return bool
 */
function g4t_disable_block_editor_for_recipe_post_type( bool $use_block_editor, string $post_type, Go4Taste_Recipes_Plugin $plugin ): bool {
    $strategy = $plugin->resolve_source_strategy();

    if ( $post_type === $strategy['post_type'] ) {
        return false;
    }

    return $use_block_editor;
}
