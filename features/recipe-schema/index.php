<?php
/**
 * Recipe schema feature entrypoint.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/schema-builder.php';
require_once __DIR__ . '/schema-context.php';
require_once __DIR__ . '/standalone-output.php';
require_once __DIR__ . '/yoast-piece.php';

function g4t_bootstrap_recipe_schema_feature(): void {
    // After recipe-display (priority 10) has put the view model into the context.
    add_filter( 'go4taste/recipes/context/single', 'g4t_recipe_schema_capture_context', 20, 2 );
    // Yoast prints its graph on wp_head priority 1; the standalone output runs later and skips a printed node.
    add_filter( 'wpseo_schema_graph_pieces', 'g4t_recipe_schema_add_yoast_piece', 20, 2 );
    add_action( 'wp_head', 'g4t_recipe_schema_print_standalone', 20 );
}

g4t_bootstrap_recipe_schema_feature();
