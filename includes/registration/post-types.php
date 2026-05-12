<?php
/**
 * GO4TASTE Recipes Plugin — Post Type Registration
 *
 * Responsibilities:
 *  1. Register the dedicated recipe CPT when source_mode = 'recipes_cpt'.
 *  2. Apply the block template + template_lock to the configured post type,
 *     regardless of who registered that post type (post, custom CPT, or our own).
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
 * NOTE: 'template' and 'template_lock' are intentionally NOT set here.
 * They are applied by g4t_apply_recipe_block_template() via the
 * 'register_post_type_args' filter so that the same logic works for
 * CPTs registered by *other* plugins (see that function for details).
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
            'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ),
            'rewrite'      => array( 'slug' => 'recipes' ),
        )
    );
}

/**
 * Apply the default block template and content lock to the configured post type.
 *
 * WHY this uses 'register_post_type_args' instead of setting 'template' directly:
 *
 * WordPress fires 'register_post_type_args' for every post type, including ones
 * registered before this plugin loads. This is the only way to add a block
 * template to a post type you do not own — such as the built-in 'post' type or
 * a CPT registered by WooCommerce. Passing 'template' in register_post_type()
 * would only work for post types *this plugin* registers.
 *
 * WHY template_lock = 'all':
 *  - 'all' (contentOnly) — users can edit block content only; they cannot add,
 *    remove, or reorder blocks. Use this when the data schema requires all blocks
 *    to be present (e.g. a structured recipe form).
 *  - 'insert' — users can add new blocks but cannot remove the template blocks.
 *    Use this when the template is a minimum structure and editors can extend it.
 *  - null / false — no lock; editors have full control.
 *
 * @param array<string,mixed> $args      The post type args being registered.
 * @param string              $post_type The post type key.
 * @param Go4Taste_Recipes_Plugin $plugin Plugin instance for context.
 * @return array<string,mixed> Modified args with template and template_lock.
 */
function g4t_apply_recipe_block_template( array $args, string $post_type, Go4Taste_Recipes_Plugin $plugin ): array {
    $strategy = $plugin->resolve_source_strategy();

    if ( $post_type !== $strategy['post_type'] ) {
        return $args;
    }

    $args['template'] = array_map(
        static fn( string $block_name ): array => array( $block_name ),
        G4T_RECIPE_BLOCK_NAMES
    );
    $args['template'][] = array( 'go4taste-ads/inline-banner' );
    $args['template_lock'] = 'contentOnly';

    return $args;
}
