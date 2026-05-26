<?php
/**
 * Recipe options service.
 *
 * Provides a shared, testable options cache for the plugin.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Internal storage for recipe options cache.
 *
 * @return array<string,mixed>|null
 */
function g4t_recipe_options_cache_state( ?array $new_state = null, bool $write = false ): ?array {
    static $cached_options = null;

    if ( $write ) {
        $cached_options = $new_state;
    }

    return $cached_options;
}

/**
 * @return array<string,mixed>
 */
function g4t_get_recipe_options(): array {
    $cached_options = g4t_recipe_options_cache_state();

    if ( null !== $cached_options ) {
        return $cached_options;
    }

    $raw = get_option( Go4Taste_Recipes_Plugin::OPTION_KEY, array() );
    $raw = is_array( $raw ) ? $raw : array();

    $defaults = array(
        'source_mode'             => Go4Taste_Recipes_Plugin::MODE_POST,
        'existing_post_type'      => 'post',
        'creator_min_role'        => 'editor',
        'creator_brand_mark'      => 0,
        'creator_brand_name'      => '',
        'creator_heartbeat_roles' => array(),
    );

    $cached_options = wp_parse_args( $raw, $defaults );
    g4t_recipe_options_cache_state( $cached_options, true );

    return $cached_options;
}

/**
 * Clear shared options cache.
 */
function g4t_clear_recipe_options_cache(): void {
    g4t_recipe_options_cache_state( null, true );
}
