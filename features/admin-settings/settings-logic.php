<?php
/**
 * Admin settings runtime logic.
 *
 * Applies settings-driven behaviors that must run on every request,
 * not just in wp-admin (e.g. taxonomy unregistering affects save_post on frontend too).
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Block wp-admin access for roles listed in the admin_block_roles setting.
 *
 * Runs on admin_init — fires before any admin page is rendered, making it
 * the correct place for access-control redirects.
 *
 * WHY each early-return guard exists:
 *  - wp_doing_ajax()      : wp-admin issues XHR requests in the background (autosave,
 *                           heartbeat, media upload). Blocking these breaks other plugins.
 *  - REST_REQUEST         : Gutenberg and REST consumers route through /wp-json/, not
 *                           a rendered admin page. admin_init fires for REST too.
 *  - wp_doing_cron()      : WP-Cron runs scheduled tasks under a wp-admin context.
 *  - manage_options       : Administrators must always have access — prevents self-lockout.
 *  - profile.php          : Editors need to be able to change their own password.
 */
function g4t_admin_access_guard(): void {
    if ( wp_doing_ajax() ) {
        return;
    }
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }
    if ( wp_doing_cron() ) {
        return;
    }
    if ( ! is_user_logged_in() ) {
        return;
    }
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    global $pagenow;
    if ( 'profile.php' === $pagenow ) {
        return;
    }

    $options      = g4t_get_recipe_options();
    $blocked_roles = isset( $options['admin_block_roles'] ) && is_array( $options['admin_block_roles'] )
        ? $options['admin_block_roles']
        : array();

    if ( empty( $blocked_roles ) ) {
        return;
    }

    $user_roles = (array) wp_get_current_user()->roles;
    if ( ! empty( array_intersect( $user_roles, $blocked_roles ) ) ) {
        wp_safe_redirect( home_url( '/' ) );
        exit;
    }
}

/**
 * Unregister built-in WordPress taxonomies from the recipe post type based on plugin settings.
 *
 * Runs on init at priority 11 — after WordPress registers built-in taxonomies at priority 0.
 * Only applies when the resolved source post type has the taxonomy registered for it.
 *
 * WHY priority 11: WordPress calls create_initial_taxonomies() on init priority 0,
 * which registers 'category' and 'post_tag' for 'post'. We must run after that.
 */
function g4t_maybe_unregister_recipe_taxonomies(): void {
    $options = g4t_get_recipe_options();

    $unregister_category = ! empty( $options['unregister_category'] );
    $unregister_post_tag = ! empty( $options['unregister_post_tag'] );

    if ( ! $unregister_category && ! $unregister_post_tag ) {
        return;
    }

    $strategy  = g4t_get_source_strategy();
    $post_type = $strategy['post_type'] ?? 'post';

    if ( $unregister_category ) {
        unregister_taxonomy_for_object_type( 'category', $post_type );
    }

    if ( $unregister_post_tag ) {
        unregister_taxonomy_for_object_type( 'post_tag', $post_type );
    }
}
