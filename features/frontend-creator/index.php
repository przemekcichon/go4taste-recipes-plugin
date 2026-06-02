<?php
/**
 * Frontend creator feature entrypoint.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/creator-helpers.php';
require_once __DIR__ . '/creator-meta-sync.php';
require_once __DIR__ . '/acf-fields.php';
require_once __DIR__ . '/creator-form.php';

function g4t_bootstrap_frontend_creator_feature(): void {
    add_action( 'acf/init', 'g4t_frontend_creator_register_acf_fields' );
    add_filter( 'theme_page_templates', 'g4t_frontend_creator_register_recipe_creator_template' );
    add_filter( 'template_include', 'g4t_frontend_creator_load_recipe_creator_template' );
    add_action( 'wp_enqueue_scripts', 'g4t_frontend_creator_enqueue_assets' );
    add_action( 'wp_ajax_g4t_upload_image', 'g4t_frontend_creator_upload_image' );
    add_action( 'wp_ajax_g4t_save_recipe_draft', 'g4t_frontend_creator_save_recipe_draft' );
    add_filter( 'heartbeat_received', 'g4t_frontend_creator_heartbeat_received', 10, 2 );
    add_action( 'acf/validate_save_post', 'g4t_frontend_creator_validate_classification_taxonomies' );
    add_filter( 'acf/pre_save_post', 'g4t_frontend_creator_force_pre_save_post', 10, 2 );
    add_action( 'acf/save_post', 'g4t_frontend_creator_sync_recipe_bottom_strip_meta_after_acf_save', 20 );
    add_filter( 'acf/load_value/key=field_ingredient_term_id', 'g4t_frontend_creator_normalize_acf_ingredient_term_value', 10, 3 );
    add_filter( 'acf/update_value/key=field_ingredient_term_id', 'g4t_frontend_creator_normalize_acf_ingredient_term_value', 10, 3 );
}

function g4t_frontend_creator_register_acf_fields(): void {
    g4t_register_acf_fields( g4t_bootstrap_plugin() );
}

function g4t_frontend_creator_register_recipe_creator_template( array $post_templates ): array {
    return g4t_register_recipe_creator_template( $post_templates );
}

function g4t_frontend_creator_load_recipe_creator_template( string $template ): string {
    return g4t_load_recipe_creator_template( $template );
}

function g4t_frontend_creator_enqueue_assets(): void {
    g4t_enqueue_recipe_creator_assets();
}

function g4t_frontend_creator_upload_image(): void {
    g4t_handle_upload_image();
}

function g4t_frontend_creator_save_recipe_draft(): void {
    g4t_handle_recipe_creator_save_draft( g4t_bootstrap_plugin() );
}

function g4t_frontend_creator_heartbeat_received( array $response, array $data ): array {
    return g4t_recipe_creator_handle_heartbeat( $response, $data, g4t_bootstrap_plugin() );
}

function g4t_frontend_creator_validate_classification_taxonomies(): void {
    g4t_validate_recipe_creator_classification_taxonomies();
}

function g4t_frontend_creator_force_pre_save_post( $post_id, array $form ) {
    return g4t_recipe_creator_force_pre_save_post( $post_id, $form );
}

function g4t_frontend_creator_sync_recipe_bottom_strip_meta_after_acf_save( $post_id ): void {
    if ( ! is_numeric( $post_id ) ) {
        return;
    }

    g4t_recipe_creator_sync_recipe_bottom_strip_meta_after_acf_save( (int) $post_id );
}

function g4t_frontend_creator_normalize_acf_ingredient_term_value( $value, $post_id, array $field ) {
    return g4t_normalize_acf_ingredient_term_value( $value, $post_id, $field );
}

g4t_bootstrap_frontend_creator_feature();
