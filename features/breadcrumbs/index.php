<?php
/**
 * Breadcrumbs feature entrypoint.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/breadcrumb-trail.php';

function g4t_bootstrap_breadcrumbs_feature(): void {
    add_filter( 'go4taste/recipes/context/single', 'g4t_breadcrumbs_filter_context', 10, 2 );
    add_filter( 'go4taste/recipes/context/archive', 'g4t_breadcrumbs_filter_context', 10, 2 );
}

g4t_bootstrap_breadcrumbs_feature();
