<?php
/**
 * Core domain entrypoint.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/recipe-options.php';
require_once __DIR__ . '/recipe-source-strategy.php';
require_once __DIR__ . '/recipe-pt.php';
require_once __DIR__ . '/recipe-taxonomies.php';

function g4t_bootstrap_core_domain(): void {
    add_action( 'init', 'g4t_core_domain_register_recipe_post_type' );
    add_action( 'init', 'g4t_core_domain_register_ingredient_taxonomy' );
    add_action( 'init', 'g4t_core_domain_register_flat_taxonomies' );
    add_filter( 'use_block_editor_for_post_type', 'g4t_core_domain_disable_block_editor_for_recipe_post_type', 10, 2 );
}

function g4t_core_domain_register_recipe_post_type(): void {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        g4t_register_recipe_post_type( $g4t_recipes_plugin );
    }
}

function g4t_core_domain_register_ingredient_taxonomy(): void {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        g4t_register_ingredient_taxonomy( $g4t_recipes_plugin );
    }
}

function g4t_core_domain_register_flat_taxonomies(): void {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        g4t_register_flat_taxonomies( $g4t_recipes_plugin );
    }
}

function g4t_core_domain_disable_block_editor_for_recipe_post_type( bool $use_block_editor, string $post_type ): bool {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        return g4t_disable_block_editor_for_recipe_post_type( $use_block_editor, $post_type, $g4t_recipes_plugin );
    }

    return $use_block_editor;
}

g4t_bootstrap_core_domain();
