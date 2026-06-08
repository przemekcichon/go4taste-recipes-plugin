<?php
/**
 * Admin settings feature entrypoint.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/settings-logic.php';
require_once __DIR__ . '/settings-page.php';

function g4t_bootstrap_admin_settings_feature(): void {
    add_action( 'admin_init', 'g4t_admin_settings_register' );
    add_action( 'admin_init', 'g4t_admin_access_guard_check' );
    add_action( 'admin_menu', 'g4t_admin_settings_register_options_page' );
    add_action( 'admin_enqueue_scripts', 'g4t_admin_settings_enqueue_assets' );
    add_action( 'admin_notices', 'g4t_admin_settings_render_dependency_notices' );
    // Priority 11: runs after WordPress registers built-in taxonomies at priority 0.
    add_action( 'init', 'g4t_maybe_unregister_recipe_taxonomies', 11 );
}

function g4t_admin_settings_register(): void {
    g4t_register_settings( g4t_bootstrap_plugin() );
}

function g4t_admin_access_guard_check(): void {
    g4t_admin_access_guard();
}

function g4t_admin_settings_register_options_page(): void {
    g4t_register_options_page( g4t_bootstrap_plugin() );
}

function g4t_admin_settings_enqueue_assets( string $hook_suffix ): void {
    g4t_enqueue_settings_assets( $hook_suffix );
}

function g4t_admin_settings_render_dependency_notices(): void {
    g4t_render_dependency_notices( g4t_bootstrap_plugin() );
}

g4t_bootstrap_admin_settings_feature();
