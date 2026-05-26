<?php
/**
 * GO4TASTE Recipes Plugin — Recipe Creator Helper Functions
 *
 * Utility functions shared across recipe creator modules.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get WordPress login URL with redirect back to current page.
 *
 * @return string Login URL with redirect parameter.
 */
function g4t_get_recipe_creator_login_url(): string {
    return wp_login_url( get_permalink() );
}

/**
 * Get the message to display when user lacks permission.
 *
 * @return string HTML-safe message.
 */
function g4t_get_recipe_creator_access_denied_message(): string {
    return sprintf(
        /* translators: %s = login URL */
        wp_kses_post( __( 'You do not have permission to access the recipe creator. <a href="%s">Log in</a> first.', 'go4taste-recipes-plugin' ) ),
        esc_url( g4t_get_recipe_creator_login_url() )
    );
}

/**
 * Get a recipe post for editing, with ownership check.
 *
 * @param int $post_id Recipe post ID.
 * @return WP_Post|false Post object if accessible, false otherwise.
 */
function g4t_get_recipe_for_editing( int $post_id ) {
    $post = get_post( $post_id );

    if ( ! $post || ! in_array( $post->post_type, array( 'post', 'recipe' ), true ) ) {
        return false;
    }

    if ( ! g4t_can_user_edit_recipe( $post_id ) ) {
        return false;
    }

    return $post;
}

/**
 * Get the ACF form configuration for recipe creation/editing.
 *
 * Returns array ready for acf_form() function.
 *
 * @param int|string $post_id Post ID for editing, or 'new_post' for creation.
 * @return array<string,mixed> ACF form configuration.
 */
function g4t_get_recipe_form_config( $post_id ) {
    // Front creator on this site must always create standard WP posts.
    $cpt_key = 'post';
    $return_post_id = is_numeric( $post_id ) ? (string) (int) $post_id : '%post_id%';

    return array(
        'post_id'              => $post_id,
        'new_post'             => array(
            'post_type'   => $cpt_key,
            'post_status' => 'publish',
        ),
        'field_groups'         => array(
            'group_recipe_hero',
            'group_recipe_ingredients',
            'group_recipe_steps',
            'group_recipe_tips',
            'group_recipe_source',
        ),
        'return'               => add_query_arg(
            array(
                'saved'   => '1',
                'status'  => 'published',
                'post_id' => $return_post_id,
            ),
            get_the_permalink()
        ),
        'submit_value'         => __( 'Opublikuj przepis', 'go4taste-recipes-plugin' ),
        'updated_message'      => __( 'Recipe updated.', 'go4taste-recipes-plugin' ),
    );
}

/**
 * Get the requested publish action from the front recipe creator submit.
 *
 * @return string Empty string when the request is not a creator publish submit.
 */
function g4t_get_recipe_creator_requested_action(): string {
    if ( ! isset( $_POST['g4t_recipe_creator_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return '';
    }

    return sanitize_key( wp_unslash( (string) $_POST['g4t_recipe_creator_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}

/**
 * Render a success notice after creator redirect.
 *
 * @return void
 */
function g4t_render_recipe_creator_notice(): void {
    $saved  = isset( $_GET['saved'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['saved'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( (string) $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( '1' !== $saved || 'published' !== $status ) {
        return;
    }

    $post_id = isset( $_GET['post_id'] ) && is_numeric( (string) $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $view_url = $post_id > 0 ? get_permalink( $post_id ) : '';

    echo '<div class="g4t-recipe-creator__notice" role="status" aria-live="polite">';
    echo '<div class="g4t-recipe-creator__notice-copy">';
    echo '<strong>' . esc_html__( 'Przepis został opublikowany.', 'go4taste-recipes-plugin' ) . '</strong>';
    echo '<span>' . esc_html__( 'Możesz dalej edytować treść w kreatorze albo przejść do opublikowanego wpisu.', 'go4taste-recipes-plugin' ) . '</span>';
    echo '</div>';
    if ( ! empty( $view_url ) ) {
        echo '<a class="g4t-recipe-creator__notice-link" href="' . esc_url( $view_url ) . '">' . esc_html__( 'Zobacz wpis', 'go4taste-recipes-plugin' ) . '</a>';
    }
    echo '</div>';
}

/**
 * Render a "not allowed" message block.
 * @return void
 */
function g4t_render_recipe_creator_access_denied(): void {
    echo '<div class="g4t-recipe-creator__access-denied">';
    echo '<div class="g4t-recipe-creator__access-denied-content">';
    echo '<h2>' . esc_html__( 'Access Denied', 'go4taste-recipes-plugin' ) . '</h2>';
    echo g4t_get_recipe_creator_access_denied_message(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '</div>';
    echo '</div>';
}

/**
 * Render a loading/placeholder message while ACF form initializes.
 *
 * @return void
 */
function g4t_render_recipe_creator_loading(): void {
    echo '<div class="g4t-recipe-creator__loading" aria-live="polite">';
    echo '<p>' . esc_html__( 'Loading recipe form...', 'go4taste-recipes-plugin' ) . '</p>';
    echo '</div>';
}

/**
 * Ensure front creator submissions always resolve to a real WP post.
 *
 * Runs after ACF's default pre_save_post callback. If ACF still returns
 * "new_post", we create the post explicitly and pass its ID back.
 *
 * @param int|string       $post_id ACF-resolved post ID or "new_post".
 * @param array<string,mixed> $form   Decoded ACF form settings.
 * @return int|string
 */
function g4t_recipe_creator_force_pre_save_post( $post_id, array $form ) {
    $requested_action = g4t_get_recipe_creator_requested_action();

    if ( is_numeric( $post_id ) && in_array( $requested_action, array( 'update', 'publish' ), true ) ) {
        g4t_clear_recipe_creator_user_draft_state( (int) $post_id );
    }

    if ( 'new_post' !== $post_id ) {
        return $post_id;
    }

    $field_groups = isset( $form['field_groups'] ) && is_array( $form['field_groups'] ) ? $form['field_groups'] : array();
    if ( ! in_array( 'group_recipe_hero', $field_groups, true ) ) {
        return $post_id;
    }

    $title = '';
    if ( isset( $_POST['acf']['_post_title'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $title = sanitize_text_field( wp_unslash( (string) $_POST['acf']['_post_title'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    } elseif ( isset( $_POST['post_title'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $title = sanitize_text_field( wp_unslash( (string) $_POST['post_title'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    }

    $inserted = wp_insert_post(
        array(
            'post_type'   => 'post',
            'post_status' => 'publish' === $requested_action ? 'publish' : 'draft',
            'post_title'  => '' !== $title ? $title : __( 'Nowy przepis', 'go4taste-recipes-plugin' ),
            'post_author' => get_current_user_id(),
        ),
        true
    );

    if ( is_wp_error( $inserted ) || ! is_numeric( $inserted ) ) {
        return $post_id;
    }

    return (int) $inserted;
}

/**
 * Normalize ACF taxonomy sub-field value for ingredient term IDs.
 *
 * Prevents invalid values (including stale string placeholders) from being
 * treated as term objects by ACF field rendering.
 *
 * @param mixed                $value Raw ACF value.
 * @param int|string           $post_id Post context.
 * @param array<string,mixed>  $field Field config.
 * @return int|string Empty string for null/invalid, or valid term ID.
 */
function g4t_normalize_acf_ingredient_term_value( $value, $post_id, array $field ) {
    if ( $value instanceof WP_Term ) {
        return (int) $value->term_id;
    }

    if ( is_wp_error( $value ) || null === $value || '' === $value ) {
        return '';
    }

    if ( is_array( $value ) ) {
        if ( empty( $value ) ) {
            return '';
        }

        $first = reset( $value );
        $value = $first;
    }

    if ( ! is_numeric( (string) $value ) ) {
        return '';
    }

    $term_id = (int) $value;
    if ( $term_id <= 0 ) {
        return '';
    }

    $term = get_term( $term_id, G4T_INGREDIENT_TAXONOMY );
    if ( ! ( $term instanceof WP_Term ) || is_wp_error( $term ) ) {
        return '';
    }

    return $term_id;
}
