<?php
/**
 * GO4TASTE Recipes Plugin — FacetWP integration.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check whether the current request is a recipe listing request.
 */
function g4t_is_recipe_facetwp_target_request( Go4Taste_Recipes_Plugin $plugin ): bool {
    $source_post_type = $plugin->resolve_source_strategy()['post_type'];

    if ( is_home() || is_front_page() ) {
        return true;
    }

    if ( is_post_type_archive( $source_post_type ) ) {
        return true;
    }

    if ( is_tax() ) {
        $queried_object = get_queried_object();
        if ( $queried_object instanceof WP_Term ) {
            $taxonomy = get_taxonomy( $queried_object->taxonomy );
            if ( $taxonomy && is_array( $taxonomy->object_type ) && in_array( $source_post_type, $taxonomy->object_type, true ) ) {
                return true;
            }
        }
    }

    if ( is_archive() && apply_filters( 'go4taste/recipes/use_archive_template', false, get_queried_object() ) ) {
        return true;
    }

    return false;
}

/**
 * Allow FacetWP to recognize the main recipe listing query.
 *
 * @param bool     $is_main_query
 * @param WP_Query $query
 */
function g4t_filter_facetwp_is_main_query( bool $is_main_query, WP_Query $query, Go4Taste_Recipes_Plugin $plugin ): bool {
    if ( ! g4t_is_recipe_facetwp_target_request( $plugin ) ) {
        return $is_main_query;
    }

    $source_post_type = $plugin->resolve_source_strategy()['post_type'];
    $post_type        = $query->get( 'post_type' );

    if ( $query->is_main_query() ) {
        return true;
    }

    if ( $query->get( 'facetwp' ) ) {
        return true;
    }

    if ( is_array( $post_type ) ) {
        return in_array( $source_post_type, $post_type, true );
    }

    if ( is_string( $post_type ) && '' !== $post_type ) {
        return $post_type === $source_post_type;
    }

    return $is_main_query;
}

/**
 * Normalize FacetWP preload URL vars.
 *
 * @param array<string,mixed> $url_vars
 * @return array<string,mixed>
 */
function g4t_filter_facetwp_preload_url_vars( array $url_vars, Go4Taste_Recipes_Plugin $plugin ): array {
    $normalized = array();

    foreach ( $url_vars as $name => $values ) {
        if ( ! is_string( $name ) || '' === $name ) {
            continue;
        }

        if ( is_array( $values ) ) {
            $normalized[ $name ] = array_values(
                array_filter(
                    array_map(
                        static function ( $value ): string {
                            return sanitize_text_field( (string) $value );
                        },
                        $values
                    ),
                    static function ( string $value ): bool {
                        return '' !== $value;
                    }
                )
            );
            continue;
        }

        if ( is_string( $values ) && '' !== $values ) {
            $normalized[ $name ] = array_values(
                array_filter(
                    array_map( 'sanitize_text_field', explode( ',', $values ) ),
                    static function ( string $value ): bool {
                        return '' !== $value;
                    }
                )
            );
        }
    }

    return $normalized;
}

/**
 * Keep FacetWP queries aligned with the active recipe source post type.
 *
 * @param array<string,mixed> $query_args
 * @param mixed               $facetwp
 * @return array<string,mixed>
 */
function g4t_filter_facetwp_query_args( array $query_args, $facetwp, Go4Taste_Recipes_Plugin $plugin ): array {
    if ( ! g4t_is_recipe_facetwp_target_request( $plugin ) ) {
        return $query_args;
    }

    $source_post_type = $plugin->resolve_source_strategy()['post_type'];

    if ( empty( $query_args['post_type'] ) ) {
        $query_args['post_type'] = $source_post_type;
    }

    return $query_args;
}

/**
 * Tell FacetWP to reuse the archive query for recipe views.
 */
function g4t_filter_facetwp_template_use_archive( bool $use_archive, Go4Taste_Recipes_Plugin $plugin ): bool {
    return g4t_is_recipe_facetwp_target_request( $plugin );
}