<?php
/**
 * Archive filtering feature entrypoint.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/facetwp-integration.php';

function g4t_bootstrap_archive_filtering_feature(): void {
    add_filter( 'facetwp_is_main_query', 'g4t_archive_filtering_filter_is_main_query', 10, 2 );
    add_filter( 'facetwp_preload_url_vars', 'g4t_archive_filtering_filter_preload_url_vars', 10, 1 );
    add_filter( 'facetwp_query_args', 'g4t_archive_filtering_filter_query_args', 10, 2 );
    add_filter( 'facetwp_template_use_archive', 'g4t_archive_filtering_filter_template_use_archive', 10, 1 );
}

function g4t_archive_filtering_filter_is_main_query( bool $is_main_query, WP_Query $query ): bool {
    return g4t_filter_facetwp_is_main_query( $is_main_query, $query, g4t_bootstrap_plugin() );
}

function g4t_archive_filtering_filter_preload_url_vars( array $url_vars ): array {
    return g4t_filter_facetwp_preload_url_vars( $url_vars, g4t_bootstrap_plugin() );
}

function g4t_archive_filtering_filter_query_args( array $query_args, $facetwp ): array {
    return g4t_filter_facetwp_query_args( $query_args, $facetwp, g4t_bootstrap_plugin() );
}

function g4t_archive_filtering_filter_template_use_archive( bool $use_archive ): bool {
    return g4t_filter_facetwp_template_use_archive( $use_archive, g4t_bootstrap_plugin() );
}

g4t_bootstrap_archive_filtering_feature();
