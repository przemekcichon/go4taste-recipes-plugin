<?php
/**
 * GO4TASTE Recipes Plugin — Standalone Recipe JSON-LD output
 *
 * Prints the `Recipe` node as its own `<script type="application/ld+json">`
 * when no schema graph (Yoast) has already included it.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Encode a JSON-LD document for safe inline output.
 *
 * JSON_HEX_TAG keeps `</script>` sequences from closing the element; Polish
 * characters and URL slashes stay readable.
 *
 * @param array<string,mixed> $document
 */
function g4t_recipe_schema_encode( array $document ): string {
    $json = wp_json_encode( $document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG );

    return is_string( $json ) ? $json : '';
}

/**
 * `wp_head` callback: print the standalone Recipe JSON-LD.
 */
function g4t_recipe_schema_print_standalone(): void {
    $state = g4t_recipe_schema_state();
    if ( $state['printed'] || g4t_recipe_schema_yoast_graph_expected() ) {
        return;
    }

    $node = g4t_recipe_schema_get_node();
    if ( null === $node ) {
        return;
    }

    $json = g4t_recipe_schema_encode( array_merge( array( '@context' => 'https://schema.org' ), $node ) );
    if ( '' === $json ) {
        return;
    }

    g4t_recipe_schema_mark_printed();

    // JSON is encoded with JSON_HEX_TAG, so it cannot break out of the script element.
    echo "<script type=\"application/ld+json\" class=\"g4t-recipe-schema\">" . $json . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
