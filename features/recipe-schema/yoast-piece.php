<?php
/**
 * GO4TASTE Recipes Plugin — Yoast SEO schema graph integration
 *
 * When Yoast SEO prints its schema graph, the `Recipe` node joins that graph
 * (one graph per page). The piece marks the node as printed, so the standalone
 * `wp_head` output (later priority) skips it. If Yoast is inactive or its schema
 * output is disabled, the piece never generates and the standalone path prints.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Whether Yoast SEO will print its schema graph on this request.
 *
 * Yoast's own "disable schema" setting and third-party code both switch the
 * graph off through `wpseo_json_ld_output` (checked in Yoast 28.5
 * Schema_Presenter::present()). While the graph is on, the Recipe belongs in
 * it only, even if a Yoast filter removes the piece — never a second graph.
 */
function g4t_recipe_schema_yoast_graph_expected(): bool {
    if ( ! defined( 'WPSEO_VERSION' ) ) {
        return false;
    }

    $output = apply_filters( 'wpseo_json_ld_output', array( '_deprecated' => '' ), '' );

    return array() !== $output && false !== $output;
}

/**
 * `wpseo_schema_graph_pieces` callback: append the Recipe piece.
 *
 * @param array<int,mixed> $pieces  Yoast graph pieces.
 * @param mixed            $context Yoast Meta_Tags_Context.
 * @return array<int,mixed>
 */
function g4t_recipe_schema_add_yoast_piece( $pieces, $context = null ) {
    if ( ! is_array( $pieces ) || ! class_exists( 'Yoast\\WP\\SEO\\Generators\\Schema\\Abstract_Schema_Piece' ) ) {
        return $pieces;
    }

    require_once __DIR__ . '/class-yoast-recipe-piece.php';

    $pieces[] = new G4T_Yoast_Recipe_Piece();

    return $pieces;
}
