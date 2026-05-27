<?php
/**
 * Recipe display feature entrypoint.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/normalizers.php';
require_once __DIR__ . '/view-model-builder.php';
require_once __DIR__ . '/timber-context.php';

function g4t_bootstrap_recipe_display_feature(): void {
    add_filter( 'go4taste/recipes/context/single', 'g4t_recipe_display_filter_single_context', 10, 2 );
    add_filter( 'go4taste/recipes/context/archive', 'g4t_recipe_display_filter_archive_context', 10, 2 );
    add_filter( 'go4taste/recipes/context/home', 'g4t_recipe_display_filter_home_context', 10, 2 );
    add_filter( 'go4taste/recipes/use_archive_template', 'g4t_recipe_display_filter_use_archive_template', 10, 2 );
    add_filter( 'go4taste/recipes/home_query_args', 'g4t_recipe_display_filter_home_query_args', 10, 1 );
    add_filter( 'go4taste/recipes/source_post_type', 'g4t_recipe_display_filter_source_post_type', 10, 1 );
}

function g4t_recipe_display_filter_single_context( array $context, array $meta = array() ): array {
    return g4t_filter_single_context( $context, $meta, g4t_bootstrap_plugin() );
}

function g4t_recipe_display_filter_archive_context( array $context, array $meta = array() ): array {
    return g4t_filter_archive_context( $context, $meta, g4t_bootstrap_plugin() );
}

function g4t_recipe_display_filter_home_context( array $context, array $meta = array() ): array {
    return g4t_filter_home_context( $context, $meta, g4t_bootstrap_plugin() );
}

function g4t_recipe_display_filter_use_archive_template( bool $use_template, $queried_object = null ): bool {
    return g4t_filter_use_archive_template( $use_template, $queried_object, g4t_bootstrap_plugin() );
}

function g4t_recipe_display_filter_home_query_args( array $query_args = array() ): array {
    return g4t_filter_home_query_args( $query_args, g4t_bootstrap_plugin() );
}

function g4t_recipe_display_filter_source_post_type( string $post_type = 'post' ): string {
    return g4t_filter_source_post_type( $post_type, g4t_bootstrap_plugin() );
}

g4t_bootstrap_recipe_display_feature();
