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

require_once __DIR__ . '/registration/post-types.php';
require_once __DIR__ . '/registration/taxonomies.php';
require_once __DIR__ . '/registration/blocks.php';
require_once __DIR__ . '/acf/field-groups.php';
require_once __DIR__ . '/sync/converters.php';
require_once __DIR__ . '/sync/detection.php';
require_once __DIR__ . '/sync/meta-sync.php';
require_once __DIR__ . '/sync/acf-to-blocks.php';
require_once __DIR__ . '/view-model/normalizers.php';
require_once __DIR__ . '/view-model/builder.php';
require_once __DIR__ . '/admin/settings-page.php';
require_once __DIR__ . '/admin/reverse-sync.php';
require_once __DIR__ . '/integrations/facetwp.php';
require_once __DIR__ . '/timber/context-filters.php';

class Go4Taste_Recipes_Plugin {
    public const OPTION_KEY = 'go4taste_recipes_options';

    public const MODE_POST         = 'post';
    public const MODE_EXISTING_CPT = 'existing_cpt';
    public const MODE_RECIPES_CPT  = 'recipes_cpt';

    /**
     * @var array<string,mixed>|null
     */
    private ?array $cached_options = null;

    public function __construct() {
        add_action( 'init', array( $this, 'proxy_register_post_type' ) );
        add_action( 'init', array( $this, 'proxy_register_ingredient_taxonomy' ) );
        add_action( 'init', array( $this, 'proxy_register_flat_taxonomies' ) );
        add_action( 'init', array( $this, 'proxy_register_blocks' ) );
        add_action( 'acf/init', array( $this, 'proxy_register_acf_fields' ) );
        add_action( 'acf/save_post', array( $this, 'proxy_sync_acf_to_blocks' ), 5 );
        add_action( 'save_post', array( $this, 'proxy_sync_meta' ), 20, 3 );
        add_action( 'admin_init', array( $this, 'proxy_register_settings' ) );
        add_action( 'admin_menu', array( $this, 'proxy_register_options_page' ) );
        add_action( 'admin_notices', array( $this, 'proxy_render_dependency_notices' ) );
        add_action( 'admin_notices', array( $this, 'proxy_show_ui_switch_notice' ) );
        add_action( 'admin_post_g4t_reverse_sync', array( $this, 'proxy_handle_reverse_sync' ) );

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
        add_filter( 'register_post_type_args',          array( $this, 'proxy_register_post_type_args' ), 10, 2 );
    }

    /**
     * @return array<string,mixed>
     */
    public function get_options(): array {
        if ( null !== $this->cached_options ) {
            return $this->cached_options;
        }

        $raw = get_option( self::OPTION_KEY, array() );
        $raw = is_array( $raw ) ? $raw : array();

        $defaults = array(
            'source_mode'        => self::MODE_POST,
            'existing_post_type' => 'post',
        );

        $this->cached_options = wp_parse_args( $raw, $defaults );

        return $this->cached_options;
    }

    /**
     * @return array{mode:string,post_type:string,fallback_used:bool}
     */
    public function resolve_source_strategy(): array {
        $options = $this->get_options();
        $mode    = isset( $options['source_mode'] ) ? sanitize_key( (string) $options['source_mode'] ) : self::MODE_POST;

        if ( ! in_array( $mode, array( self::MODE_POST, self::MODE_EXISTING_CPT, self::MODE_RECIPES_CPT ), true ) ) {
            $mode = self::MODE_POST;
        }

        $fallback_used = false;
        $post_type     = 'post';

        if ( self::MODE_RECIPES_CPT === $mode ) {
            $post_type = G4T_RECIPES_POST_TYPE;
        } elseif ( self::MODE_EXISTING_CPT === $mode ) {
            $candidate = isset( $options['existing_post_type'] ) ? sanitize_key( (string) $options['existing_post_type'] ) : '';
            if ( '' === $candidate || ! post_type_exists( $candidate ) ) {
                $post_type     = 'post';
                $fallback_used = true;
            } else {
                $post_type = $candidate;
            }
        }

        return array(
            'mode'          => $mode,
            'post_type'     => $post_type,
            'fallback_used' => $fallback_used,
        );
    }

    public function clear_options_cache(): void {
        $this->cached_options = null;
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

    public function proxy_register_blocks(): void {
        g4t_register_recipe_blocks( $this );
    }

    public function proxy_register_acf_fields(): void {
        g4t_register_acf_fields( $this );
    }

    public function proxy_sync_meta( int $post_id, WP_Post $post, bool $update ): void {
        g4t_sync_recipe_meta_from_blocks( $post_id, $post, $update, $this );
    }

    public function proxy_sync_acf_to_blocks( int|string $post_id ): void {
        g4t_sync_acf_to_blocks( $post_id );
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

    public function proxy_render_dependency_notices(): void {
        g4t_render_dependency_notices( $this );
    }

    public function proxy_show_ui_switch_notice(): void {
        g4t_maybe_show_ui_switch_notice();
    }

    public function proxy_handle_reverse_sync(): void {
        g4t_handle_reverse_sync_action();
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
     * @param array<string,mixed> $args
     * @return array<string,mixed>
     */
    public function proxy_register_post_type_args( array $args, string $post_type ): array {
        return g4t_apply_recipe_block_template( $args, $post_type, $this );
    }
}
