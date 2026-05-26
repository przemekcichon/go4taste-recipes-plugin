<?php
/**
 * Recipe composition slice entrypoint.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/options-service.php';
require_once __DIR__ . '/strategy-factory.php';
