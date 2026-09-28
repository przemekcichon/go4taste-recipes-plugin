<?php
/**
 * GO4TASTE Recipes Plugin — ItemList schema request context
 *
 * Captures the recipes shown on a listing page (recipe home, recipe
 * archives) from the public `go4taste/recipes/context/home` and
 * `go4taste/recipes/context/archive` filters and resolves the `ItemList`
 * node once per request for both output paths.
 *
 * Filtered FacetWP states get no list: they are not meant to be indexed.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Internal per-request state: captured post IDs, resolved node, printed flag.
 *
 * @param array<string,mixed>|null $new_state
 * @return array<string,mixed>
 */
function g4t_recipe_schema_listing_state( ?array $new_state = null ): array {
    static $state = array(
        'post_ids' => null,
        'node'     => null,
        'resolved' => false,
        'printed'  => false,
    );

    if ( null !== $new_state ) {
        $state = array_replace( $state, $new_state );
    }

    return $state;
}

/**
 * Filter callback: remember the recipes listed by the home or archive context.
 *
 * @param array<string,mixed> $context
 * @param array<string,mixed> $meta
 * @return array<string,mixed> Unchanged context.
 */
function g4t_recipe_schema_capture_listing_context( array $context, array $meta = array() ): array {
    $recipes    = isset( $context['recipes'] ) && is_array( $context['recipes'] ) ? $context['recipes'] : array();
    $is_listing = ! empty( $context['is_recipe_home'] ) || ! empty( $recipes['use_archive_recipe_template'] );

    if ( ! $is_listing || ! isset( $context['posts'] ) || ! is_iterable( $context['posts'] ) ) {
        return $context;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check of public filter params.
    if ( g4t_recipe_schema_listing_is_filtered( wp_unslash( $_GET ), (string) ( $recipes['facetwp_prefix'] ?? '' ) ) ) {
        return $context;
    }

    $post_ids = array();
    foreach ( $context['posts'] as $post ) {
        $post_id = is_object( $post ) && isset( $post->ID ) ? (int) $post->ID : 0;
        if ( $post_id > 0 ) {
            $post_ids[] = $post_id;
        }
    }

    g4t_recipe_schema_listing_state(
        array(
            'post_ids' => $post_ids,
            'node'     => null,
            'resolved' => false,
        )
    );

    return $context;
}

/**
 * Build the `ItemList` node for the current request (memoized).
 *
 * Only published, not password-protected posts of the recipe source post
 * type are listed.
 *
 * @return array<string,mixed>|null Node without `@context`, or null when nothing should be printed.
 */
function g4t_recipe_schema_get_item_list_node(): ?array {
    $state = g4t_recipe_schema_listing_state();
    if ( $state['resolved'] ) {
        return $state['node'];
    }

    $node = null;

    if ( is_array( $state['post_ids'] ) ) {
        $strategy = g4t_get_source_strategy();
        $urls     = array();

        foreach ( $state['post_ids'] as $post_id ) {
            $post = get_post( $post_id );
            if ( $post instanceof WP_Post && $strategy['post_type'] === $post->post_type && 'publish' === $post->post_status && ! post_password_required( $post ) ) {
                $urls[] = (string) get_permalink( $post );
            }
        }

        $list = g4t_recipe_schema_build_item_list( $urls, (string) get_pagenum_link( max( 1, (int) get_query_var( 'paged' ) ) ) );

        if ( ! empty( $list['itemListElement'] ) ) {
            $node = $list;
        }
    }

    g4t_recipe_schema_listing_state(
        array(
            'node'     => $node,
            'resolved' => true,
        )
    );

    return $node;
}

/**
 * Mark the ItemList node as printed, so no other output path prints it again in this request.
 */
function g4t_recipe_schema_mark_item_list_printed(): void {
    g4t_recipe_schema_listing_state( array( 'printed' => true ) );
}
