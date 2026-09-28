<?php
/**
 * GO4TASTE Recipes Plugin — Recipe schema request context
 *
 * Captures the single recipe view model from the public
 * `go4taste/recipes/context/single` filter and resolves the final `Recipe`
 * node once per request for both output paths.
 *
 * WHY capture from the context filter instead of building the view model:
 *
 * Feature slices never call each other directly, and building the view
 * model a second time would re-fetch the promoted ads products. The theme
 * applies the filter before rendering, so the captured model is available
 * when `wp_head` fires.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Internal per-request state: captured view model, post ID, resolved node, printed flag.
 *
 * @param array<string,mixed>|null $new_state
 * @return array<string,mixed>
 */
function g4t_recipe_schema_state( ?array $new_state = null ): array {
    static $state = array(
        'recipe'   => null,
        'post_id'  => 0,
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
 * Filter callback: remember the recipe view model built for the single context.
 *
 * @param array<string,mixed> $context
 * @param array<string,mixed> $meta
 * @return array<string,mixed> Unchanged context.
 */
function g4t_recipe_schema_capture_context( array $context, array $meta = array() ): array {
    $post = get_queried_object();

    if ( empty( $context['is_recipe'] ) || empty( $context['recipe'] ) || ! is_array( $context['recipe'] ) || ! ( $post instanceof WP_Post ) ) {
        return $context;
    }

    g4t_recipe_schema_state(
        array(
            'recipe'   => $context['recipe'],
            'post_id'  => (int) $post->ID,
            'node'     => null,
            'resolved' => false,
        )
    );

    return $context;
}

/**
 * Resolve WordPress-specific post data consumed by g4t_recipe_schema_build().
 *
 * @param WP_Post $post
 * @return array<string,mixed>
 */
function g4t_recipe_schema_post_data( WP_Post $post ): array {
    $image    = null;
    $image_id = (int) get_post_thumbnail_id( $post );
    if ( $image_id > 0 ) {
        $src = wp_get_attachment_image_src( $image_id, 'full' );
        if ( is_array( $src ) && ! empty( $src[0] ) ) {
            $image = array(
                'url'    => (string) $src[0],
                'width'  => (int) $src[1],
                'height' => (int) $src[2],
            );
        }
    }

    $categories = array();
    $terms      = get_the_terms( $post, G4T_MEAL_TYPE_TAXONOMY );
    if ( is_array( $terms ) ) {
        foreach ( $terms as $term ) {
            $categories[] = (string) $term->name;
        }
    }

    $options    = g4t_get_recipe_options();
    $brand_name = trim( (string) ( $options['creator_brand_name'] ?? '' ) );

    return array(
        'url'            => (string) get_permalink( $post ),
        'image'          => $image,
        'date_published' => (string) get_post_time( 'c', true, $post ),
        'date_modified'  => (string) get_post_modified_time( 'c', true, $post ),
        'language'       => (string) get_bloginfo( 'language' ),
        'categories'     => $categories,
        'author'         => array(
            '@type' => 'Organization',
            'name'  => '' !== $brand_name ? $brand_name : (string) get_bloginfo( 'name' ),
            'url'   => home_url( '/' ),
        ),
    );
}

/**
 * Return the post the captured recipe belongs to, when the current request is its single view.
 */
function g4t_recipe_schema_current_post(): ?WP_Post {
    $state = g4t_recipe_schema_state();
    if ( null === $state['recipe'] || $state['post_id'] < 1 ) {
        return null;
    }

    $strategy = g4t_get_source_strategy();
    if ( ! is_singular( $strategy['post_type'] ) || get_queried_object_id() !== $state['post_id'] ) {
        return null;
    }

    $post = get_post( $state['post_id'] );
    if ( ! ( $post instanceof WP_Post ) || 'publish' !== $post->post_status || post_password_required( $post ) ) {
        return null;
    }

    return $post;
}

/**
 * Build, filter and validate the `Recipe` node for the current request (memoized).
 *
 * @return array<string,mixed>|null Node without `@context`, or null when nothing should be printed.
 */
function g4t_recipe_schema_get_node(): ?array {
    $state = g4t_recipe_schema_state();
    if ( $state['resolved'] ) {
        return $state['node'];
    }

    $node = null;
    $post = g4t_recipe_schema_current_post();

    if ( $post instanceof WP_Post ) {
        $data = g4t_recipe_schema_build( $state['recipe'], g4t_recipe_schema_post_data( $post ) );

        /**
         * Filter the schema.org Recipe node before it is printed (standalone or Yoast graph).
         *
         * @param array<string,mixed> $data Recipe node without `@context`.
         * @param WP_Post             $post Recipe post.
         */
        $data = apply_filters( 'go4taste/recipes/schema/recipe', $data, $post );

        if ( is_array( $data ) && g4t_recipe_schema_is_printable( $data ) ) {
            $node = $data;
        }
    }

    g4t_recipe_schema_state(
        array(
            'node'     => $node,
            'resolved' => true,
        )
    );

    return $node;
}

/**
 * Mark the node as printed, so no other output path prints it again in this request.
 */
function g4t_recipe_schema_mark_printed(): void {
    g4t_recipe_schema_state( array( 'printed' => true ) );
}
