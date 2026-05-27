<?php
/**
 * Plugin Name:       GO4TASTE Recipes Plugin
 * Description:       Domain data plugin for recipes (ACF + normalized view models + Timber context filters).
 * Version:           0.1.0
 * Author:            GO4TASTE
 * Requires PHP:      8.0
 * Text Domain:       go4taste-recipes-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GO4TASTE_RECIPES_PLUGIN_VERSION', '0.1.0' );
define( 'GO4TASTE_RECIPES_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'GO4TASTE_RECIPES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once GO4TASTE_RECIPES_PLUGIN_PATH . 'features/frontend-creator/index.php';
require_once GO4TASTE_RECIPES_PLUGIN_PATH . 'features/admin-promoted-products/index.php';
require_once GO4TASTE_RECIPES_PLUGIN_PATH . 'features/recipe-display/index.php';
require_once GO4TASTE_RECIPES_PLUGIN_PATH . 'features/archive-filtering/index.php';
require_once GO4TASTE_RECIPES_PLUGIN_PATH . 'features/admin-settings/index.php';
require_once GO4TASTE_RECIPES_PLUGIN_PATH . 'core/class-plugin-bootstrap.php';

function go4taste_recipes_plugin_bootstrap() {
    return g4t_bootstrap_plugin();
}

go4taste_recipes_plugin_bootstrap();
