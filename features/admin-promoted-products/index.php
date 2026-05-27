<?php
/**
 * Admin promoted products feature entrypoint.
 *
 * Parking-plan slice for Algolia/promoted products.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/algolia-products.php';

function g4t_bootstrap_admin_promoted_products_feature(): void {
    add_action( 'wp_ajax_g4t_save_recipe_ads_products', 'g4t_handle_save_recipe_ads_products' );
}

g4t_bootstrap_admin_promoted_products_feature();
