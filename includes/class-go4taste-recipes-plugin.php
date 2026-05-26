<?php
/**
 * GO4TASTE Recipes Plugin — Main Orchestrator Class
 *
 * This class is the ORCHESTRATOR ONLY. It does not contain business logic.
 * Its responsibilities are:
 *  1. Wire action/filter hooks to proxy methods.
 *  2. Provide resolve_source_strategy() and get_options() for module access.
 *  3. Cache plugin options to avoid repeated database reads.
 *
 * WHY no logic in this class:
 *
 * All actual work (registration, sync, view model building) is delegated to
 * free functions in modular files (registration/, sync/, view-model/, etc.).
 * This keeps the orchestrator thin and makes it easy to unit test individual
 * modules without instantiating the entire plugin.
 *
 * The proxy pattern (add_action → proxy method → free function) ensures hooks
 * can be removed with remove_action() if needed and keeps the __construct()
 * readable as a hook registration manifest.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/slices/recipe-composition/index.php';
require_once __DIR__ . '/slices/recipe-registration/index.php';
require_once __DIR__ . '/slices/recipe-storage/index.php';
require_once __DIR__ . '/view-model/normalizers.php';
require_once __DIR__ . '/view-model/builder.php';
require_once __DIR__ . '/slices/recipe-admin-config/index.php';
require_once __DIR__ . '/slices/recipe-input-surface/index.php';
require_once __DIR__ . '/slices/recipe-output-surface/index.php';

class Go4Taste_Recipes_Plugin {
    public const OPTION_KEY = 'go4taste_recipes_options';

    public const MODE_POST         = 'post';
    public const MODE_EXISTING_CPT = 'existing_cpt';
    public const MODE_RECIPES_CPT  = 'recipes_cpt';

    public function __construct() {
        add_action( 'init', array( $this, 'proxy_register_post_type' ) );
        add_action( 'init', array( $this, 'proxy_register_ingredient_taxonomy' ) );
        add_action( 'init', array( $this, 'proxy_register_flat_taxonomies' ) );
        add_action( 'acf/init', array( $this, 'proxy_register_acf_fields' ) );
        add_filter( 'use_block_editor_for_post_type', array( $this, 'proxy_disable_block_editor_for_recipe_post_type' ), 10, 2 );
        add_action( 'admin_init', array( $this, 'proxy_register_settings' ) );
        add_action( 'admin_menu', array( $this, 'proxy_register_options_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'proxy_enqueue_settings_assets' ) );
        add_action( 'admin_notices', array( $this, 'proxy_render_dependency_notices' ) );

        add_filter( 'go4taste/recipes/context/single',  array( $this, 'proxy_filter_single_context' ), 10, 2 );
        add_filter( 'go4taste/recipes/context/archive', array( $this, 'proxy_filter_archive_context' ), 10, 2 );
        add_filter( 'go4taste/recipes/context/home',    array( $this, 'proxy_filter_home_context' ), 10, 2 );
        add_filter( 'go4taste/recipes/use_archive_template', array( $this, 'proxy_filter_use_archive_template' ), 10, 2 );
        add_filter( 'go4taste/recipes/home_query_args', array( $this, 'proxy_filter_home_query_args' ), 10, 1 );
        add_filter( 'go4taste/recipes/source_post_type', array( $this, 'proxy_filter_source_post_type' ), 10, 1 );
        add_filter( 'facetwp_is_main_query', array( $this, 'proxy_filter_facetwp_is_main_query' ), 10, 2 );
        add_filter( 'facetwp_preload_url_vars', array( $this, 'proxy_filter_facetwp_preload_url_vars' ), 10, 1 );
        add_filter( 'facetwp_query_args', array( $this, 'proxy_filter_facetwp_query_args' ), 10, 2 );
        add_filter( 'facetwp_template_use_archive', array( $this, 'proxy_filter_facetwp_template_use_archive' ), 10, 1 );

        add_filter( 'theme_page_templates', array( $this, 'proxy_register_recipe_creator_template' ) );
        add_filter( 'template_include', array( $this, 'proxy_load_recipe_creator_template' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'proxy_enqueue_recipe_creator_assets' ) );
        add_action( 'wp_ajax_g4t_save_recipe_ads_products', array( $this, 'proxy_save_recipe_ads_products' ) );
        add_action( 'wp_ajax_g4t_upload_image', array( $this, 'proxy_upload_image' ) );
        add_action( 'wp_ajax_g4t_save_recipe_draft', array( $this, 'proxy_save_recipe_draft' ) );
        add_filter( 'heartbeat_received', array( $this, 'proxy_recipe_creator_heartbeat_received' ), 10, 2 );
        add_filter( 'acf/pre_save_post', array( $this, 'proxy_recipe_creator_force_pre_save_post' ), 10, 2 );

        add_filter( 'acf/load_value/key=field_ingredient_term_id', array( $this, 'proxy_normalize_acf_ingredient_term_value' ), 10, 3 );
        add_filter( 'acf/update_value/key=field_ingredient_term_id', array( $this, 'proxy_normalize_acf_ingredient_term_value' ), 10, 3 );
    }

    /**
     * @return array<string,mixed>
     */
    public function get_options(): array {
        return g4t_get_recipe_options();
    }

    /**
     * @return array{mode:string,post_type:string,fallback_used:bool}
     */
    public function resolve_source_strategy(): array {
        return g4t_get_source_strategy();
    }

    public function clear_options_cache(): void {
        g4t_clear_recipe_options_cache();
    }

    // -------------------------------------------------------------------------
    // Proxy methods — one-liner delegates to free functions
    // -------------------------------------------------------------------------

    public function proxy_register_post_type(): void {
        g4t_register_recipe_post_type( $this );
    }

    public function proxy_register_ingredient_taxonomy(): void {
        g4t_register_ingredient_taxonomy( $this );
    }

    public function proxy_register_flat_taxonomies(): void {
        g4t_register_flat_taxonomies( $this );
    }

    public function proxy_register_acf_fields(): void {
        g4t_register_acf_fields( $this );
    }

    public function proxy_disable_block_editor_for_recipe_post_type( bool $use_block_editor, string $post_type ): bool {
        return g4t_disable_block_editor_for_recipe_post_type( $use_block_editor, $post_type, $this );
    }

    /**
     * @param int|string          $post_id
     * @param array<string,mixed> $form
     * @return int|string
     */
    public function proxy_recipe_creator_force_pre_save_post( $post_id, array $form ) {
        return g4t_recipe_creator_force_pre_save_post( $post_id, $form );
    }

    /**
     * @param mixed               $value
     * @param int|string          $post_id
     * @param array<string,mixed> $field
     * @return int|string
     */
    public function proxy_normalize_acf_ingredient_term_value( $value, $post_id, array $field ) {
        return g4t_normalize_acf_ingredient_term_value( $value, $post_id, $field );
    }

    /**
     * @param array<string,mixed> $response
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function proxy_recipe_creator_heartbeat_received( array $response, array $data ): array {
        return g4t_recipe_creator_handle_heartbeat( $response, $data, $this );
    }

    public function proxy_register_settings(): void {
        g4t_register_settings( $this );
    }
    // -------------------------------------------------------------------------
    // Proxy methods — one-liner delegates to free functions
    // -------------------------------------------------------------------------
    // WHY proxy methods instead of direct callbacks:
    //
    // WordPress add_action/add_filter require callbacks that can be referenced
    // by name for remove_action/remove_filter. Anonymous closures that capture
    // $this cannot be removed. Proxy methods solve this: they're removable and
    // keep __construct() readable as a hook registration manifest.
    //
    // Each proxy method is a one-liner: it passes control to a free function
    // in a module file. This lets us unit test the free functions without
    // instantiating the entire plugin class.
    // -------------------------------------------------------------------------
    public function proxy_register_options_page(): void {
        g4t_register_options_page( $this );
    }

    public function proxy_enqueue_settings_assets( string $hook_suffix ): void {
        g4t_enqueue_settings_assets( $hook_suffix );
    }

    public function proxy_render_dependency_notices(): void {
        g4t_render_dependency_notices( $this );
    }

    /**
     * @param array<string,mixed> $context
     * @param array<string,mixed> $meta
     * @return array<string,mixed>
     */
    public function proxy_filter_single_context( array $context, array $meta = array() ): array {
        return g4t_filter_single_context( $context, $meta, $this );
    }

    /**
     * @param array<string,mixed> $context
     * @param array<string,mixed> $meta
     * @return array<string,mixed>
     */
    public function proxy_filter_archive_context( array $context, array $meta = array() ): array {
        return g4t_filter_archive_context( $context, $meta, $this );
    }

    /**
     * @param array<string,mixed> $context
     * @param array<string,mixed> $meta
     * @return array<string,mixed>
     */
    public function proxy_filter_home_context( array $context, array $meta = array() ): array {
        return g4t_filter_home_context( $context, $meta, $this );
    }

    /**
     * @param bool  $use_template
     * @param mixed $queried_object
     */
    public function proxy_filter_use_archive_template( bool $use_template, $queried_object = null ): bool {
        return g4t_filter_use_archive_template( $use_template, $queried_object, $this );
    }

    /**
     * @param array<string,mixed> $query_args
     * @return array<string,mixed>
     */
    public function proxy_filter_home_query_args( array $query_args = array() ): array {
        return g4t_filter_home_query_args( $query_args, $this );
    }

    public function proxy_filter_source_post_type( string $post_type = 'post' ): string {
        return g4t_filter_source_post_type( $post_type, $this );
    }

    /**
     * @param bool     $is_main_query
     * @param WP_Query $query
     */
    public function proxy_filter_facetwp_is_main_query( bool $is_main_query, WP_Query $query ): bool {
        if ( function_exists( 'g4t_filter_facetwp_is_main_query' ) ) {
            return g4t_filter_facetwp_is_main_query( $is_main_query, $query, $this );
        }

        return $is_main_query;
    }

    /**
     * @param array<string,mixed> $url_vars
     * @return array<string,mixed>
     */
    public function proxy_filter_facetwp_preload_url_vars( array $url_vars ): array {
        if ( function_exists( 'g4t_filter_facetwp_preload_url_vars' ) ) {
            return g4t_filter_facetwp_preload_url_vars( $url_vars, $this );
        }

        return $url_vars;
    }

    /**
     * @param array<string,mixed> $query_args
     * @param mixed               $facetwp
     * @return array<string,mixed>
     */
    public function proxy_filter_facetwp_query_args( array $query_args, $facetwp ): array {
        if ( function_exists( 'g4t_filter_facetwp_query_args' ) ) {
            return g4t_filter_facetwp_query_args( $query_args, $facetwp, $this );
        }

        return $query_args;
    }

    /**
     * @param bool $use_archive
     */
    public function proxy_filter_facetwp_template_use_archive( bool $use_archive ): bool {
        if ( function_exists( 'g4t_filter_facetwp_template_use_archive' ) ) {
            return g4t_filter_facetwp_template_use_archive( $use_archive, $this );
        }

        return $use_archive;
    }

    /**
     * Register recipe creator page template.
     *
     * @param array<string,string> $templates
     * @return array<string,string>
     */
    public function proxy_register_recipe_creator_template( array $templates ): array {
        return g4t_register_recipe_creator_template( $templates );
    }

    /**
     * Load recipe creator page template.
     *
     * @param string $template
     * @return string
     */
    public function proxy_load_recipe_creator_template( string $template ): string {
        return g4t_load_recipe_creator_template( $template );
    }

    /**
     * Enqueue recipe creator assets (CSS, JS).
     */
    public function proxy_enqueue_recipe_creator_assets(): void {
        g4t_enqueue_recipe_creator_assets();
    }

    /**
     * Handle saving recipe product selections from Algolia.
     */
    public function proxy_save_recipe_ads_products(): void {
        g4t_handle_save_recipe_ads_products();
    }

    /**
     * Handle AJAX image upload to WordPress Media Library.
     */
    public function proxy_upload_image(): void {
        g4t_handle_upload_image();
    }

    /**
     * Handle explicit draft save from recipe creator modal.
     */
    public function proxy_save_recipe_draft(): void {
        g4t_handle_recipe_creator_save_draft( $this );
    }
}
