<?php
/**
 * Core plugin bootstrap.
 *
 * Loads core domain runtime and creates the plugin context instance used
 * by feature slices.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/domain/index.php';
require_once __DIR__ . '/class-plugin-context.php';

/**
 * @return Go4Taste_Recipes_Plugin
 */
function g4t_bootstrap_plugin() {
    global $g4t_recipes_plugin;

    if ( null === $g4t_recipes_plugin ) {
        $g4t_recipes_plugin = new Go4Taste_Recipes_Plugin();
    }

    return $g4t_recipes_plugin;
}
