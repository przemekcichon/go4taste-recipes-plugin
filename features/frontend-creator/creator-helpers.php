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

    if ( ! $post ) {
        return false;
    }

    // Determine the current recipe post type from plugin settings rather than a
    // hardcoded list. This supports all three source modes (post, existing_cpt,
    // recipes_cpt) without manual maintenance.
    $recipe_post_type = 'post'; // Fallback when plugin not yet bootstrapped.
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        $strategy         = g4t_get_source_strategy();
        $recipe_post_type = $strategy['post_type'];
    }

    if ( $post->post_type !== $recipe_post_type ) {
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
    // Read the recipe post type from plugin settings so all three source modes
    // (post, existing_cpt, recipes_cpt) work without hardcoded lists.
    $cpt_key = 'post'; // Fallback when plugin not yet bootstrapped.
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        $strategy = g4t_get_source_strategy();
        $cpt_key  = $strategy['post_type'];
    }

    // Build return URL. For existing posts (numeric $post_id), pass the ID directly
    // through add_query_arg — safe because it is a plain integer.
    // For new posts, %post_id% must NOT go through add_query_arg: WordPress's
    // build_query URL-encodes it to %25post_id%25, which ACF's str_replace cannot
    // find and leaves unreplaced. The browser then receives %post_id% as a literal
    // string; (int)'%post_id%' === 0, producing ?post_id=0 and a "not found" error.
    if ( is_numeric( $post_id ) ) {
        $return_url = add_query_arg(
            array(
                'saved'   => '1',
                'status'  => 'published',
                'post_id' => (int) $post_id,
            ),
            get_the_permalink()
        );
    } else {
        $return_url = add_query_arg(
            array(
                'saved'   => '1',
                'status'  => 'published',
            ),
            get_the_permalink()
        ) . '&post_id=%post_id%';
    }

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
        'return'               => $return_url,
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
    $submitted_taxonomies = g4t_get_submitted_recipe_classification_taxonomy_term_ids();

    // ACF passes integer 0 (not the string 'new_post') when the form was rendered
    // for a new post. Treat both as "new post" to avoid returning 0 as the post ID.
    $is_new_post = ( 'new_post' === $post_id ) || ( is_numeric( $post_id ) && (int) $post_id <= 0 );

    if ( ! $is_new_post ) {
        if ( is_numeric( $post_id ) && in_array( $requested_action, array( 'update', 'publish' ), true ) ) {
            $numeric_post_id = (int) $post_id;
            g4t_clear_recipe_creator_user_draft_state( $numeric_post_id );
            $taxonomy_term_ids = g4t_filter_present_recipe_classification_taxonomy_term_ids( $submitted_taxonomies );

            if ( ! empty( $taxonomy_term_ids ) ) {
                g4t_sync_recipe_classification_taxonomies( $numeric_post_id, $taxonomy_term_ids );
            }
        }

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

    $target_status = 'publish' === $requested_action ? 'publish' : 'draft';

    // If the heartbeat already created a draft for this session, publish that post
    // instead of creating a new one. This prevents orphaned duplicate posts when the
    // user clicks "Opublikuj" after autosave has already run.
    $heartbeat_draft_id = isset( $_POST['g4t_heartbeat_draft_id'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
        ? (int) $_POST['g4t_heartbeat_draft_id'] // phpcs:ignore WordPress.Security.NonceVerification.Missing
        : 0;

    if ( $heartbeat_draft_id > 0 && g4t_can_user_edit_recipe( $heartbeat_draft_id ) ) {
        wp_update_post(
            array(
                'ID'          => $heartbeat_draft_id,
                'post_status' => $target_status,
                'post_title'  => '' !== $title ? $title : get_the_title( $heartbeat_draft_id ),
            )
        );

        $taxonomy_term_ids = g4t_filter_present_recipe_classification_taxonomy_term_ids( $submitted_taxonomies );
        if ( ! empty( $taxonomy_term_ids ) ) {
            g4t_sync_recipe_classification_taxonomies( $heartbeat_draft_id, $taxonomy_term_ids );
        }

        return $heartbeat_draft_id;
    }

    $inserted = wp_insert_post(
        array(
            'post_type'   => 'post',
            'post_status' => $target_status,
            'post_title'  => '' !== $title ? $title : __( 'Nowy przepis', 'go4taste-recipes-plugin' ),
            'post_author' => get_current_user_id(),
        ),
        true
    );

    if ( is_wp_error( $inserted ) || ! is_numeric( $inserted ) ) {
        return $post_id;
    }

    $created_post_id = (int) $inserted;
    $taxonomy_term_ids = g4t_filter_present_recipe_classification_taxonomy_term_ids( $submitted_taxonomies );
    if ( ! empty( $taxonomy_term_ids ) ) {
        g4t_sync_recipe_classification_taxonomies( $created_post_id, $taxonomy_term_ids );
    }

    return $created_post_id;
}

/**
 * Validate recipe creator classification taxonomy state before ACF save.
 *
 * @return void
 */
function g4t_validate_recipe_creator_classification_taxonomies(): void {
    if ( ! isset( $_POST['g4t_recipe_creator_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return;
    }

    $submitted_taxonomies = g4t_get_submitted_recipe_classification_taxonomy_term_ids();
    foreach ( g4t_get_recipe_creator_classification_taxonomy_validation_errors( $submitted_taxonomies ) as $error ) {
        acf_add_validation_error( $error['field'], $error['message'] );
    }
}

/**
 * Get submitted classification taxonomy term IDs from creator hidden fields.
 *
 * Missing fields return null so callers can distinguish "not submitted" from
 * "submitted but intentionally empty".
 *
 * @param string $field_name Request field name.
 * @return array<int,int>|null
 */
function g4t_get_submitted_taxonomy_term_ids( string $field_name ): ?array {
    if ( '' === $field_name ) {
        return null;
    }

    $marker_name = $field_name . '_present';
    if ( ! isset( $_POST[ $marker_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return null;
    }

    if ( ! isset( $_POST[ $field_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return array();
    }

    $raw = wp_unslash( $_POST[ $field_name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( ! is_array( $raw ) ) {
        return array();
    }

    $term_ids = array();
    foreach ( $raw as $value ) {
        if ( ! is_numeric( (string) $value ) ) {
            continue;
        }

        $term_id = (int) $value;
        if ( $term_id > 0 ) {
            $term_ids[] = $term_id;
        }
    }

    return array_values( array_unique( $term_ids ) );
}

/**
 * Read submitted taxonomy payload for all classification fields.
 *
 * @return array<string,array<int,int>|null>
 */
function g4t_get_submitted_recipe_classification_taxonomy_term_ids(): array {
    return array(
        G4T_MEAL_TYPE_TAXONOMY => g4t_get_submitted_taxonomy_term_ids( 'g4t_meal_type_term_ids' ),
        G4T_PREP_TIME_TAXONOMY => g4t_get_submitted_taxonomy_term_ids( 'g4t_prep_time_term_ids' ),
        G4T_FEATURE_TAXONOMY   => g4t_get_submitted_taxonomy_term_ids( 'g4t_feature_term_ids' ),
    );
}

/**
 * Filter out taxonomy fields that were not submitted.
 *
 * @param array<string,array<int,int>|null> $submitted_taxonomies Submitted taxonomy payload.
 * @return array<string,array<int,int>>
 */
function g4t_filter_present_recipe_classification_taxonomy_term_ids( array $submitted_taxonomies ): array {
    $taxonomy_term_ids = array();

    foreach ( $submitted_taxonomies as $taxonomy_key => $term_ids ) {
        if ( ! is_array( $term_ids ) ) {
            continue;
        }

        $taxonomy_term_ids[ $taxonomy_key ] = $term_ids;
    }

    return $taxonomy_term_ids;
}

/**
 * Build validation errors for creator classification taxonomies.
 *
 * @param array<string,array<int,int>|null> $submitted_taxonomies Submitted taxonomy payload.
 * @return array<int,array{field:string,message:string}>
 */
function g4t_get_recipe_creator_classification_taxonomy_validation_errors( array $submitted_taxonomies ): array {
    $rules = array(
        G4T_MEAL_TYPE_TAXONOMY => array(
            'field'    => 'g4t_meal_type_term_ids',
            'label'    => __( 'Typ dania', 'go4taste-recipes-plugin' ),
            'required' => true,
        ),
        G4T_PREP_TIME_TAXONOMY => array(
            'field'    => 'g4t_prep_time_term_ids',
            'label'    => __( 'Czas przygotowania', 'go4taste-recipes-plugin' ),
            'required' => true,
        ),
        G4T_FEATURE_TAXONOMY   => array(
            'field'    => 'g4t_feature_term_ids',
            'label'    => __( 'Cechy przepisu', 'go4taste-recipes-plugin' ),
            'required' => false,
        ),
    );

    $errors = array();

    foreach ( $rules as $taxonomy_key => $rule ) {
        $selected_term_ids = $submitted_taxonomies[ $taxonomy_key ] ?? null;
        if ( null === $selected_term_ids ) {
            if ( ! empty( $rule['required'] ) ) {
                $errors[] = array(
                    'field'   => $rule['field'],
                    'message' => sprintf(
                        /* translators: %s: taxonomy label. */
                        __( 'Wybierz dokładnie jeden %s.', 'go4taste-recipes-plugin' ),
                        $rule['label']
                    ),
                );
            }

            continue;
        }

        $available_terms = get_terms(
            array(
                'taxonomy'   => $taxonomy_key,
                'hide_empty' => false,
                'number'     => 1,
                'fields'     => 'ids',
            )
        );

        if ( is_wp_error( $available_terms ) || empty( $available_terms ) ) {
            if ( ! empty( $rule['required'] ) ) {
                $errors[] = array(
                    'field'   => $rule['field'],
                    'message' => sprintf(
                        /* translators: %s: taxonomy label. */
                        __( 'Nie dodano jeszcze termów do taksonomii %s. Dodaj termy i spróbuj ponownie.', 'go4taste-recipes-plugin' ),
                        $rule['label']
                    ),
                );
            }

            continue;
        }

        if ( ! empty( $rule['required'] ) && 1 !== count( $selected_term_ids ) ) {
            $errors[] = array(
                'field'   => $rule['field'],
                'message' => sprintf(
                    /* translators: %s: taxonomy label. */
                    __( 'Wybierz dokładnie jeden %s.', 'go4taste-recipes-plugin' ),
                    $rule['label']
                ),
            );
        }
    }

    return $errors;
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
