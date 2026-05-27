<?php
/**
 * Admin promoted products / Algolia integration.
 *
 * Parking-plan slice for promoted products selection.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX handler: Save recipe product IDs from Algolia search result selection.
 *
 * Receives POST data with:
 *  - post_id: Recipe post ID (int)
 *  - product_ids: Array of product IDs (array<string>)
 *  - nonce: Security token (string)
 *
 * @return void Outputs JSON response.
 */
function g4t_handle_save_recipe_ads_products(): void {
    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'g4t_save_recipe_ads_products' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed', 'go4taste-recipes-plugin' ) ) );
    }

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'You must be logged in', 'go4taste-recipes-plugin' ) ) );
    }

    $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    if ( $post_id <= 0 ) {
        wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'go4taste-recipes-plugin' ) ) );
    }

    if ( ! g4t_can_user_edit_recipe( $post_id ) ) {
        wp_send_json_error( array( 'message' => __( 'You do not have permission to edit this recipe', 'go4taste-recipes-plugin' ) ) );
    }

    $product_ids = isset( $_POST['product_ids'] ) ? wp_unslash( $_POST['product_ids'] ) : array();
    if ( ! is_array( $product_ids ) ) {
        $product_ids = array();
    }

    $sanitized_ids = array();
    foreach ( $product_ids as $id ) {
        $sanitized_id = sanitize_text_field( (string) $id );
        if ( '' !== $sanitized_id ) {
            $sanitized_ids[] = $sanitized_id;
        }
    }

    $json_string = wp_json_encode( $sanitized_ids );
    update_post_meta( $post_id, 'recipe_ads_products_algolia', $json_string );

    wp_send_json_success(
        array(
            'message'       => __( 'Products saved successfully', 'go4taste-recipes-plugin' ),
            'product_ids'   => $sanitized_ids,
            'product_count' => count( $sanitized_ids ),
        )
    );
}
