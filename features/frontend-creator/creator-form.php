<?php
/**
 * GO4TASTE Recipes Plugin — Recipe Creator Front-End Page Template Registration
 *
 * Registers a WordPress page template for the recipe creator form, loads it on front-end,
 * enqueues CSS/JS, and manages access control based on user role settings.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the recipe creator page template for WordPress.
 *
 * This filter allows selecting a custom page template in the page editor.
 * The template name is 'g4t-recipe-creator'.
 *
 * @param array<string,string> $post_templates Array of page templates indexed by file path.
 * @return array<string,string> Modified templates array.
 */
function g4t_register_recipe_creator_template( array $post_templates ): array {
    $post_templates['g4t-recipe-creator'] = __( 'Recipe Creator (Kreator Przepisu)', 'go4taste-recipes-plugin' );
    return $post_templates;
}

/**
 * Load the recipe creator page template when it's selected for a page.
 *
 * This hook runs during template selection and returns the path to our custom template.
 * Must return before get_header() is called.
 *
 * @param string $template Path to the currently selected template.
 * @return string Path to the recipe creator template, or original template.
 */
function g4t_load_recipe_creator_template( string $template ): string {
    if ( is_page() ) {
        $page_template = get_page_template_slug( get_queried_object_id() );
        if ( 'g4t-recipe-creator' === $page_template ) {
            /**
             * Return path to our custom PHP template.
             * This file calls acf_form_head() before get_header().
             */
            return g4t_get_recipe_creator_page_path();
        }
    }
    return $template;
}

/**
 * Get the absolute file path to the recipe creator page template.
 *
 * @return string Absolute path to recipe-creator-page.php
 */
function g4t_get_recipe_creator_page_path(): string {
    return GO4TASTE_RECIPES_PLUGIN_PATH . 'features/frontend-creator/creator-page.php';
}

/**
 * Enqueue CSS and JS assets for the recipe creator form.
 *
 * Only enqueues when:
 * - We're on a page
 * - The page has the recipe creator template
 * - User may or may not have permission (permission gate also needs styles/scripts)
 *
 * @return void
 */
function g4t_enqueue_recipe_creator_assets(): void {
    if ( ! is_page() ) {
        return;
    }

    $page_template = get_page_template_slug( get_queried_object_id() );
    if ( 'g4t-recipe-creator' !== $page_template ) {
        return;
    }

    $plugin_url = GO4TASTE_RECIPES_PLUGIN_URL;
    $version    = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : '1.0.0';
    $ads_picker = g4t_get_recipe_creator_ads_picker_config();

    if ( ! empty( $ads_picker['enabled'] ) ) {
        g4t_enqueue_recipe_creator_ads_picker_assets();
    }

    wp_enqueue_style(
        'g4t-acf-front-form-fonts',
        'https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:wght@400;500;600;700&family=Open+Sans:wght@400;500;600;700&display=swap',
        array(),
        null
    );

    // CSS: Main form styles (fonts, layout, components)
    wp_enqueue_style(
        'g4t-acf-front-form',
        $plugin_url . 'features/frontend-creator/creator.css',
        array( 'g4t-acf-front-form-fonts' ),
        $version
    );

    // Custom element for image upload (must be loaded before vanilla app script).
    wp_enqueue_script(
        'g4t-image-slot',
        $plugin_url . 'features/frontend-creator/image-slot.js',
        array(),
        $version,
        true
    );

    // Static data used by the vanilla prototype app.
    wp_enqueue_script(
        'g4t-acf-front-form-data',
        $plugin_url . 'features/frontend-creator/creator-data.js',
        array(),
        $version,
        true
    );

    $creator_data = array(
        'ingredientTaxonomy' => g4t_get_ingredient_taxonomy_for_js(),
        'mealTypeTaxonomy'   => g4t_get_classification_taxonomy_for_js( G4T_MEAL_TYPE_TAXONOMY ),
        'prepTimeTaxonomy'   => g4t_get_classification_taxonomy_for_js( G4T_PREP_TIME_TAXONOMY ),
        'featureTaxonomy'    => g4t_get_classification_taxonomy_for_js( G4T_FEATURE_TAXONOMY ),
        'adsPicker'          => $ads_picker,
        'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
        'homeUrl'            => home_url( '/' ),
        'uploadNonce'        => wp_create_nonce( 'g4t_recipe_creator' ),
        'heartbeatEnabled'   => g4t_is_creator_heartbeat_enabled_for_current_user(),
        'heartbeatNonce'     => wp_create_nonce( 'g4t_recipe_creator_heartbeat' ),
        'heartbeatInterval'  => 30,
    );

    if ( ! empty( $creator_data['heartbeatEnabled'] ) ) {
        wp_enqueue_script( 'heartbeat' );
    }

    $edit_post_id = isset( $_GET['post_id'] ) && is_numeric( (string) $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
    if ( $edit_post_id > 0 && g4t_can_user_edit_recipe( $edit_post_id ) ) {
        $edit_post = get_post( $edit_post_id );
        if ( $edit_post instanceof WP_Post ) {
            $creator_data['initialState']   = g4t_get_recipe_creator_initial_state( $edit_post_id );
            $creator_data['editMode']       = true;
            $creator_data['editPostId']     = $edit_post_id;
            $creator_data['editPostStatus'] = sanitize_key( (string) $edit_post->post_status );
        }
    }

    wp_add_inline_script(
        'g4t-acf-front-form-data',
        'window.G4T_RECIPE_CREATOR_DATA = ' . wp_json_encode( $creator_data ) . ';',
        'before'
    );

    // Main vanilla app logic.
    wp_enqueue_script(
        'g4t-acf-front-form',
        $plugin_url . 'features/frontend-creator/creator.js',
        array( 'g4t-image-slot', 'g4t-acf-front-form-data' ),
        $version,
        true
    );
}

/**
 * Check if current user can create recipes based on min role setting.
 *
 * Always returns true for 'manage_options' capability (administrators).
 *
 * @global Go4Taste_Recipes_Plugin|null $g4t_recipes_plugin
 * @return bool
 */
function g4t_current_user_can_create_recipe(): bool {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }

    global $g4t_recipes_plugin;
    if ( ! isset( $g4t_recipes_plugin ) ) {
        return false;
    }

    $options = g4t_get_recipe_options();
    $min_role = isset( $options['creator_min_role'] ) ? (string) $options['creator_min_role'] : 'editor';

    return current_user_can( $min_role );
}

/**
 * Get recipe creator top bar branding config.
 *
 * @global Go4Taste_Recipes_Plugin|null $g4t_recipes_plugin
 * @return array{mark_image_url:string,name:string}
 */
function g4t_get_recipe_creator_branding(): array {
    $result = array(
        'mark_image_url' => '',
        'name'           => '',
    );

    global $g4t_recipes_plugin;
    if ( ! isset( $g4t_recipes_plugin ) ) {
        return $result;
    }

    $options       = g4t_get_recipe_options();
    $attachment_id = isset( $options['creator_brand_mark'] ) ? absint( $options['creator_brand_mark'] ) : 0;
    $image_url     = $attachment_id > 0 ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';

    $result['mark_image_url'] = $image_url ? esc_url_raw( $image_url ) : '';
    $result['name']           = isset( $options['creator_brand_name'] ) ? sanitize_text_field( (string) $options['creator_brand_name'] ) : '';

    return $result;
}

/**
 * Get ingredient taxonomy as JS-friendly array for autocomplete.
 *
 * @return array<int,array{id:string,name:string,termId:int,category:string}>
 */
function g4t_get_ingredient_taxonomy_for_js(): array {
    $terms = get_terms(
        array(
            'taxonomy'   => 'recipe_ingredient',
            'hide_empty' => false,
            'number'     => 9999,
        )
    );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return array();
    }

    $result = array();
    foreach ( $terms as $term ) {
        $category = '';
        if ( isset( $term->parent ) && $term->parent > 0 ) {
            $parent    = get_term( $term->parent );
            $category  = is_wp_error( $parent ) ? '' : $parent->name;
        }

        $result[] = array(
            'id'      => sanitize_key( $term->slug ),
            'name'    => sanitize_text_field( $term->name ),
            'termId'  => (int) $term->term_id,
            'category' => sanitize_text_field( $category ),
        );
    }

    return $result;
}

/**
 * Get classification taxonomy terms for JS-driven selector cards.
 *
 * @param string $taxonomy_key Taxonomy key.
 * @return array<int,array{id:string,name:string,termId:int,heroIcon:array{id:int,url:string,alt:string}|null}>
 */
function g4t_get_classification_taxonomy_for_js( string $taxonomy_key ): array {
    static $cache = array();

    if ( '' === $taxonomy_key || ! taxonomy_exists( $taxonomy_key ) ) {
        return array();
    }

    if ( isset( $cache[ $taxonomy_key ] ) && is_array( $cache[ $taxonomy_key ] ) ) {
        return $cache[ $taxonomy_key ];
    }

    $terms = get_terms(
        array(
            'taxonomy'   => $taxonomy_key,
            'hide_empty' => false,
            'number'     => 9999,
        )
    );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        $cache[ $taxonomy_key ] = array();
        return $cache[ $taxonomy_key ];
    }

    $result = array();

    foreach ( $terms as $term ) {
        if ( ! ( $term instanceof WP_Term ) || (int) $term->term_id <= 0 ) {
            continue;
        }

        $result[] = array(
            'id'       => sanitize_key( (string) $term->slug ),
            'name'     => sanitize_text_field( (string) $term->name ),
            'termId'   => (int) $term->term_id,
            'heroIcon' => g4t_get_taxonomy_hero_icon_for_js( (int) $term->term_id, (string) $term->name ),
        );
    }

    $cache[ $taxonomy_key ] = $result;
    return $cache[ $taxonomy_key ];
}

/**
 * Get hero icon payload for taxonomy card rendering in creator UI.
 *
 * @param int $term_id Term ID.
 * @param string $term_name Term name for alt fallback.
 * @return array{id:int,url:string,alt:string}|null
 */
function g4t_get_taxonomy_hero_icon_for_js( int $term_id, string $term_name ): ?array {
    static $icon_cache = array();

    if ( $term_id <= 0 ) {
        return null;
    }

    if ( isset( $icon_cache[ $term_id ] ) ) {
        return is_array( $icon_cache[ $term_id ] ) ? $icon_cache[ $term_id ] : null;
    }

    if ( ! function_exists( 'get_field' ) ) {
        $icon_cache[ $term_id ] = null;
        return null;
    }

    $icon = get_field( 'hero_icon', 'term_' . $term_id );
    if ( ! is_array( $icon ) ) {
        $icon_cache[ $term_id ] = null;
        return null;
    }

    $url = isset( $icon['url'] ) ? esc_url_raw( (string) $icon['url'] ) : '';
    if ( '' === $url ) {
        $icon_cache[ $term_id ] = null;
        return null;
    }

    $payload = array(
        'id'  => isset( $icon['ID'] ) && is_numeric( (string) $icon['ID'] ) ? (int) $icon['ID'] : 0,
        'url' => $url,
        'alt' => sanitize_text_field( $term_name ),
    );

    $icon_cache[ $term_id ] = $payload;
    return $payload;
}

/**
 * Get assigned term IDs for a post/taxonomy in a JS-friendly numeric array.
 *
 * @param int $post_id Post ID.
 * @param string $taxonomy_key Taxonomy key.
 * @return array<int,int>
 */
function g4t_get_post_term_ids_for_js( int $post_id, string $taxonomy_key ): array {
    if ( $post_id <= 0 || '' === $taxonomy_key || ! taxonomy_exists( $taxonomy_key ) ) {
        return array();
    }

    $terms = get_the_terms( $post_id, $taxonomy_key );
    if ( is_wp_error( $terms ) || empty( $terms ) || ! is_array( $terms ) ) {
        return array();
    }

    $term_ids = array();
    foreach ( $terms as $term ) {
        if ( ! ( $term instanceof WP_Term ) ) {
            continue;
        }

        $id = (int) $term->term_id;
        if ( $id > 0 ) {
            $term_ids[] = $id;
        }
    }

    return array_values( array_unique( $term_ids ) );
}

/**
 * Get Algolia credentials for front-end product search.
 *
 * Fetches from go4taste-ads plugin options if plugin is active.
 *
 * @return array<string,string|int>
 */
/**
 * Build picker configuration for the recipe creator step 6 adapter.
 *
 * Credentials are intentionally absent — G4TAdsProvider (go4taste-ads) owns
 * Algolia credentials and initializes the shared JS client on wp_enqueue_scripts.
 * Creator.js calls G4TAdsProvider.getInstance() to get search access.
 *
 * @return array<string,mixed>
 */
function g4t_get_recipe_creator_ads_picker_config(): array {
    $base = array(
        'enabled'          => false,
        'context'          => 'recipe_creator',
        'variant'          => 'creator',
        'surfaceLabel'     => __( 'Kreator przepisu', 'go4taste-recipes-plugin' ),
        'fallbackMessage'  => __( 'Wyszukiwarka produktów jest chwilowo niedostępna. Możesz korzystać z lokalnej listy produktów poniżej.', 'go4taste-recipes-plugin' ),
        'hitsPerPage'      => 12,
        'searchDebounceMs' => 260,
    );

    if ( ! file_exists( WP_PLUGIN_DIR . '/go4taste-ads/go4taste-ads.php' ) ) {
        return $base;
    }

    return array_merge( $base, array( 'enabled' => true ) );
}

/**
 * Enqueue shared Ads picker assets used by the creator adapter.
 *
 * @return void
 */
function g4t_enqueue_recipe_creator_ads_picker_assets(): void {
    // G4TAdsProvider (algolia-search-client + algolia-provider.js) is enqueued
    // by go4taste-ads on wp_enqueue_scripts. No additional assets needed here.
}

/**
 * Check if user can edit a specific recipe.
 *
 * User can edit if:
 * - They are the post author, OR
 * - They have 'manage_options' capability
 *
 * @param int $post_id Recipe post ID.
 * @return bool
 */
function g4t_can_user_edit_recipe( int $post_id ): bool {
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
        return false;
    }

    return (int) $post->post_author === get_current_user_id();
}

/**
 * Handle AJAX image upload to WordPress Media Library.
 *
 * Endpoint: wp_ajax_g4t_upload_image
 *
 * Expects:
 *   - $_FILES['file'] — image file
 *   - $_POST['nonce'] — nonce for validation
 *
 * Returns JSON:
 *   - success (bool)
 *   - data:
 *     - url (string) — attachment URL
 *     - id (int) — attachment post ID
 *     OR
 *     - message (string) — error message
 *
 * @wp-ajax g4t_upload_image
 */
function g4t_handle_upload_image(): void {
    // Verify user is logged in
    if ( ! is_user_logged_in() ) {
        wp_send_json_error(
            array( 'message' => __( 'You must be logged in to upload images.', 'go4taste-recipes-plugin' ) ),
            403
        );
    }

    // Verify nonce
    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'g4t_recipe_creator' ) ) {
        wp_send_json_error(
            array( 'message' => __( 'Security check failed.', 'go4taste-recipes-plugin' ) ),
            403
        );
    }

    // Check user has upload capability
    if ( ! current_user_can( 'upload_files' ) ) {
        wp_send_json_error(
            array( 'message' => __( 'You do not have permission to upload files.', 'go4taste-recipes-plugin' ) ),
            403
        );
    }

    // Verify file was uploaded
    if ( empty( $_FILES['file'] ) ) {
        wp_send_json_error(
            array( 'message' => __( 'No file was provided.', 'go4taste-recipes-plugin' ) )
        );
    }

    // Require media library functions
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    // Handle the file upload
    $attachment_id = media_handle_upload(
        'file',
        0  // post_id = 0 (not attached to post yet)
    );

    // Check for errors
    if ( is_wp_error( $attachment_id ) ) {
        wp_send_json_error(
            array( 'message' => $attachment_id->get_error_message() )
        );
    }

    // Get attachment URL
    $attachment_url = wp_get_attachment_url( $attachment_id );

    // Return success response
    wp_send_json_success(
        array(
            'url' => $attachment_url,
            'id'  => $attachment_id,
        )
    );
}

/**
 * Build initial state for recipe creator edit mode.
 *
 * @param int $post_id Recipe post ID.
 * @return array<string,mixed>
 */
function g4t_get_recipe_creator_initial_state( int $post_id ): array {
    $draft_state = g4t_get_recipe_creator_user_draft_state( $post_id );
    if ( ! empty( $draft_state ) ) {
        if ( ! isset( $draft_state['products'] ) || ! is_array( $draft_state['products'] ) || empty( $draft_state['products'] ) ) {
            $draft_state['products'] = array_map(
                static function( string $product_id ): array {
                    return array( 'id' => $product_id );
                },
                g4t_get_recipe_bottom_strip_product_ids_for_post( $post_id )
            );
        }

        return $draft_state;
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
        return array();
    }

    $featured_image_id = get_post_thumbnail_id( $post_id );
    $featured_image    = $featured_image_id ? g4t_get_recipe_creator_photo_payload( (string) $featured_image_id ) : null;

    $ingredients_mode  = (string) get_post_meta( $post_id, 'recipe_ingredients_mode', true );
    if ( '' === $ingredients_mode ) {
        $ingredients_mode = 'single';
    }

    $ingredients = array();
    $ingredients_count = (int) get_post_meta( $post_id, 'recipe_ingredients', true );
    for ( $i = 0; $i < $ingredients_count; $i++ ) {
        $ingredients[] = array(
            'id'     => 'ing-' . $i,
            'name'   => (string) get_post_meta( $post_id, 'recipe_ingredients_' . $i . '_name', true ),
            'termId' => (string) get_post_meta( $post_id, 'recipe_ingredients_' . $i . '_ingredient_term_id', true ),
            'amount' => (string) get_post_meta( $post_id, 'recipe_ingredients_' . $i . '_amount', true ),
            'unit'   => (string) get_post_meta( $post_id, 'recipe_ingredients_' . $i . '_unit', true ),
            'hint'   => (string) get_post_meta( $post_id, 'recipe_ingredients_' . $i . '_ingredient_note', true ),
        );
    }

    $ingredient_sections   = array();
    $section_count = (int) get_post_meta( $post_id, 'recipe_ingredient_sections', true );
    for ( $i = 0; $i < $section_count; $i++ ) {
        $item_count = (int) get_post_meta( $post_id, 'recipe_ingredient_sections_' . $i . '_section_items', true );
        $items      = array();
        for ( $j = 0; $j < $item_count; $j++ ) {
            $items[] = array(
                'id'     => 'ing-' . $i . '-' . $j,
                'name'   => (string) get_post_meta( $post_id, 'recipe_ingredient_sections_' . $i . '_section_items_' . $j . '_name', true ),
                'termId' => (string) get_post_meta( $post_id, 'recipe_ingredient_sections_' . $i . '_section_items_' . $j . '_ingredient_term_id', true ),
                'amount' => (string) get_post_meta( $post_id, 'recipe_ingredient_sections_' . $i . '_section_items_' . $j . '_amount', true ),
                'unit'   => (string) get_post_meta( $post_id, 'recipe_ingredient_sections_' . $i . '_section_items_' . $j . '_unit', true ),
                'hint'   => (string) get_post_meta( $post_id, 'recipe_ingredient_sections_' . $i . '_section_items_' . $j . '_ingredient_note', true ),
            );
        }
        $ingredient_sections[] = array(
            'id'    => 'sec-' . $i,
            'title' => (string) get_post_meta( $post_id, 'recipe_ingredient_sections_' . $i . '_section_title', true ),
            'items' => $items,
        );
    }

    $steps = array();
    $steps_count = (int) get_post_meta( $post_id, 'recipe_steps', true );
    for ( $i = 0; $i < $steps_count; $i++ ) {
        $steps[] = array(
            'id'      => 'prep-' . $i,
            'heading' => (string) get_post_meta( $post_id, 'recipe_steps_' . $i . '_step_header', true ),
            'body'    => (string) get_post_meta( $post_id, 'recipe_steps_' . $i . '_step_description', true ),
            'hint'    => (string) get_post_meta( $post_id, 'recipe_steps_' . $i . '_step_tip', true ),
            'photo'   => g4t_get_recipe_creator_photo_payload( get_post_meta( $post_id, 'recipe_steps_' . $i . '_step_image_url', true ) ),
        );
    }

    $products = array_map(
        static function( string $product_id ): array {
            return array( 'id' => $product_id );
        },
        g4t_get_recipe_bottom_strip_product_ids_for_post( $post_id )
    );

    return array(
        'title'            => (string) get_the_title( $post_id ),
        'lead'             => (string) get_post_meta( $post_id, 'recipe_lead', true ),
        'image'            => $featured_image,
        'difficulty'       => g4t_normalize_difficulty_key( (string) get_post_meta( $post_id, 'recipe_difficulty', true ) ),
        'portions'         => (int) get_post_meta( $post_id, 'recipe_servings_value', true ),
        'portionUnit'      => (string) get_post_meta( $post_id, 'recipe_servings_unit', true ),
        'timePrep'         => g4t_recipe_creator_parse_minutes( (string) get_post_meta( $post_id, 'recipe_time_prep', true ) ),
        'timeCook'         => g4t_recipe_creator_parse_minutes( (string) get_post_meta( $post_id, 'recipe_time_cook', true ) ),
        'timeSoak'         => g4t_recipe_creator_parse_minutes( (string) get_post_meta( $post_id, 'recipe_time_soak', true ) ),
        'ingredientsPhoto'   => g4t_get_recipe_creator_photo_payload( get_post_meta( $post_id, 'recipe_ingredients_photo_url', true ) ),
        'ingredientsMode'    => $ingredients_mode,
        'ingredients'        => $ingredients,
        'ingredientSections' => $ingredient_sections,
        'steps'              => $steps,
        'tips'             => (string) get_post_meta( $post_id, 'recipe_tips', true ),
        'sourceName'       => (string) get_post_meta( $post_id, 'recipe_source_label', true ),
        'sourceUrl'        => (string) get_post_meta( $post_id, 'recipe_source_url', true ),
        'mealTypeTermIds'  => g4t_get_post_term_ids_for_js( $post_id, G4T_MEAL_TYPE_TAXONOMY ),
        'prepTimeTermIds'  => g4t_get_post_term_ids_for_js( $post_id, G4T_PREP_TIME_TAXONOMY ),
        'featureTermIds'   => g4t_get_post_term_ids_for_js( $post_id, G4T_FEATURE_TAXONOMY ),
        'products'         => $products,
    );
}

/**
 * Convert stored image value (attachment ID or URL) to JS payload.
 *
 * @param mixed $value Raw meta value.
 * @return array<string,mixed>|null
 */
function g4t_get_recipe_creator_photo_payload( $value ): ?array {
    $raw = trim( (string) $value );
    if ( '' === $raw ) {
        return null;
    }

    if ( ctype_digit( $raw ) ) {
        $attachment_id  = (int) $raw;
        $attachment_url = wp_get_attachment_url( $attachment_id );
        return array(
            'id'  => $attachment_id,
            'url' => is_string( $attachment_url ) ? $attachment_url : '',
        );
    }

    return array(
        'id'  => 0,
        'url' => esc_url_raw( $raw ),
    );
}

/**
 * Parse common recipe time strings into minutes.
 *
 * @param string $value Time value (e.g. "1 h 20 min", "45 min", "2 h").
 * @return int
 */
function g4t_recipe_creator_parse_minutes( string $value ): int {
    $input = trim( strtolower( $value ) );
    if ( '' === $input ) {
        return 0;
    }

    $hours = 0;
    $mins  = 0;
    if ( preg_match( '/(\d+)\s*h/', $input, $hours_match ) ) {
        $hours = (int) $hours_match[1];
    }
    if ( preg_match( '/(\d+)\s*min/', $input, $mins_match ) ) {
        $mins = (int) $mins_match[1];
    }

    $total = ( $hours * 60 ) + $mins;
    if ( $total > 0 ) {
        return $total;
    }

    return ctype_digit( $input ) ? (int) $input : 0;
}

/**
 * Check if heartbeat autosave is enabled for the current user role.
 */
function g4t_is_creator_heartbeat_enabled_for_current_user(): bool {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    global $g4t_recipes_plugin;
    if ( ! isset( $g4t_recipes_plugin ) ) {
        return false;
    }

    $options = g4t_get_recipe_options();
    $roles   = isset( $options['creator_heartbeat_roles'] ) && is_array( $options['creator_heartbeat_roles'] )
        ? array_map( 'sanitize_key', $options['creator_heartbeat_roles'] )
        : array();

    if ( empty( $roles ) ) {
        return false;
    }

    $user = wp_get_current_user();
    if ( ! $user || empty( $user->roles ) || ! is_array( $user->roles ) ) {
        return false;
    }

    foreach ( $user->roles as $role ) {
        if ( in_array( sanitize_key( (string) $role ), $roles, true ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Handle recipe creator heartbeat payload and autosave draft state.
 *
 * @param array<string,mixed> $response
 * @param array<string,mixed> $data
 * @param Go4Taste_Recipes_Plugin $plugin
 * @return array<string,mixed>
 */
function g4t_recipe_creator_handle_heartbeat( array $response, array $data, Go4Taste_Recipes_Plugin $plugin ): array {
    if ( empty( $data['g4t_recipe_creator_autosave'] ) || ! is_array( $data['g4t_recipe_creator_autosave'] ) ) {
        return $response;
    }

    if ( ! g4t_is_creator_heartbeat_enabled_for_current_user() ) {
        $response['g4t_recipe_creator_autosave'] = array(
            'ok'      => false,
            'message' => 'Heartbeat autosave is disabled for this role.',
        );
        return $response;
    }

    $payload = $data['g4t_recipe_creator_autosave'];
    $nonce   = isset( $payload['nonce'] ) ? sanitize_text_field( (string) $payload['nonce'] ) : '';
    if ( ! wp_verify_nonce( $nonce, 'g4t_recipe_creator_heartbeat' ) ) {
        $response['g4t_recipe_creator_autosave'] = array(
            'ok'      => false,
            'message' => 'Invalid nonce.',
        );
        return $response;
    }

    $state = isset( $payload['state'] ) && is_array( $payload['state'] ) ? $payload['state'] : array();
    if ( empty( $state ) ) {
        return $response;
    }

    $requested_post_id                   = isset( $payload['postId'] ) ? (int) $payload['postId'] : 0;
    $response['g4t_recipe_creator_autosave'] = g4t_recipe_creator_save_state_to_draft( $state, $requested_post_id, $plugin );

    return $response;
}

/**
 * AJAX: save current recipe creator state as draft.
 *
 * @param Go4Taste_Recipes_Plugin $plugin
 * @return void
 */
function g4t_handle_recipe_creator_save_draft( Go4Taste_Recipes_Plugin $plugin ): void {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error(
            array(
                'message' => __( 'Musisz być zalogowany.', 'go4taste-recipes-plugin' ),
            ),
            401
        );
    }

    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
    if ( ! wp_verify_nonce( $nonce, 'g4t_recipe_creator_heartbeat' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Nieprawidłowy nonce.', 'go4taste-recipes-plugin' ),
            ),
            403
        );
    }

    $raw_state = isset( $_POST['state'] ) ? wp_unslash( $_POST['state'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification
    $state     = is_string( $raw_state ) ? json_decode( $raw_state, true ) : array();
    if ( ! is_array( $state ) || empty( $state ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Brak danych do zapisu szkicu.', 'go4taste-recipes-plugin' ),
            ),
            400
        );
    }

    $post_id = isset( $_POST['postId'] ) ? (int) $_POST['postId'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
    $result  = g4t_recipe_creator_save_state_to_draft( $state, $post_id, $plugin );

    if ( empty( $result['ok'] ) ) {
        wp_send_json_error( $result, 400 );
    }

    wp_send_json_success( $result );
}

/**
 * Persist recipe creator state into a draft post.
 *
 * @param array<string,mixed>       $state
 * @param int                       $requested_post_id
 * @param Go4Taste_Recipes_Plugin   $plugin
 * @return array<string,mixed>
 */
function g4t_recipe_creator_save_state_to_draft( array $state, int $requested_post_id, Go4Taste_Recipes_Plugin $plugin ): array {
    $post_id = 0;
    $post    = null;

    if ( $requested_post_id > 0 ) {
        if ( ! g4t_can_user_edit_recipe( $requested_post_id ) ) {
            return array(
                'ok'      => false,
                'message' => 'You cannot edit this post.',
            );
        }

        $post = get_post( $requested_post_id );
        if ( ! $post instanceof WP_Post ) {
            return array(
                'ok'      => false,
                'message' => 'Post not found.',
            );
        }

        $post_id = (int) $post->ID;

        if ( 'publish' === $post->post_status ) {
            $saved = g4t_save_recipe_creator_user_draft_state( $post_id, $state );
            if ( ! $saved ) {
                return array(
                    'ok'      => false,
                    'message' => 'Failed to save draft changes.',
                    'postId'  => $post_id,
                );
            }

            return array(
                'ok'      => true,
                'postId'  => $post_id,
                'savedAt' => current_time( 'H:i' ),
                'status'  => 'draft-buffer',
            );
        }
    }

    $title = isset( $state['title'] ) ? sanitize_text_field( (string) $state['title'] ) : '';
    if ( 0 === $post_id ) {
        if ( ! g4t_current_user_can_create_recipe() ) {
            return array(
                'ok'      => false,
                'message' => 'You cannot create recipe drafts.',
            );
        }

        $strategy = g4t_get_source_strategy();
        $inserted = wp_insert_post(
            array(
                'post_type'   => $strategy['post_type'],
                'post_status' => 'draft',
                'post_title'  => '' !== $title ? $title : __( 'Nowy szkic przepisu', 'go4taste-recipes-plugin' ),
                'post_author' => get_current_user_id(),
            ),
            true
        );

        if ( is_wp_error( $inserted ) || ! is_numeric( $inserted ) ) {
            return array(
                'ok'      => false,
                'message' => is_wp_error( $inserted ) ? $inserted->get_error_message() : 'Insert failed.',
            );
        }

        $post_id = (int) $inserted;
    }

    $result = wp_update_post(
        array(
            'ID'          => $post_id,
            'post_title'  => '' !== $title ? $title : get_the_title( $post_id ),
            'post_status' => 'draft',
        ),
        true
    );

    if ( is_wp_error( $result ) ) {
        return array(
            'ok'      => false,
            'message' => $result->get_error_message(),
            'postId'  => $post_id,
        );
    }

    g4t_creator_state_to_meta( $state, $post_id );

    return array(
        'ok'      => true,
        'postId'  => $post_id,
        'savedAt' => current_time( 'H:i' ),
        'status'  => 'draft',
    );
}

/**
 * Build user-meta key for creator draft state.
 */
function g4t_recipe_creator_user_draft_state_key( int $post_id ): string {
    return 'g4t_recipe_creator_draft_state_' . $post_id;
}

/**
 * Save user-scoped draft state for published post editing.
 *
 * @param int                 $post_id
 * @param array<string,mixed> $state
 */
function g4t_save_recipe_creator_user_draft_state( int $post_id, array $state ): bool {
    $user_id = get_current_user_id();
    if ( $user_id <= 0 || $post_id <= 0 ) {
        return false;
    }

    $payload = array(
        'state'   => $state,
        'savedAt' => current_time( 'mysql' ),
    );

    return (bool) update_user_meta( $user_id, g4t_recipe_creator_user_draft_state_key( $post_id ), $payload );
}

/**
 * Load user-scoped draft state for published post editing.
 *
 * @param int $post_id
 * @return array<string,mixed>
 */
function g4t_get_recipe_creator_user_draft_state( int $post_id ): array {
    $post = get_post( $post_id );
    if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
        return array();
    }

    $user_id = get_current_user_id();
    if ( $user_id <= 0 ) {
        return array();
    }

    $stored = get_user_meta( $user_id, g4t_recipe_creator_user_draft_state_key( $post_id ), true );
    if ( ! is_array( $stored ) || empty( $stored['state'] ) || ! is_array( $stored['state'] ) ) {
        return array();
    }

    return $stored['state'];
}

/**
 * Clear user-scoped draft state.
 */
function g4t_clear_recipe_creator_user_draft_state( int $post_id ): void {
    $user_id = get_current_user_id();
    if ( $user_id <= 0 || $post_id <= 0 ) {
        return;
    }

    delete_user_meta( $user_id, g4t_recipe_creator_user_draft_state_key( $post_id ) );
}

/**
 * Write recipe creator state directly to post meta (meta-only save path).
 *
 * Replaces the old block-build → post_content → meta-sync round-trip.
 * Ingredient taxonomy terms are synced via g4t_sync_ingredients_repeater_meta().
 * Featured image is set directly as the post thumbnail — no ACF intermediary needed.
 *
 * @param array<string,mixed> $state  Creator state payload.
 * @param int                 $post_id Post ID to write to.
 */
function g4t_creator_state_to_meta( array $state, int $post_id ): void {
    $lead       = isset( $state['lead'] ) ? sanitize_textarea_field( (string) $state['lead'] ) : '';
    $difficulty = isset( $state['difficulty'] ) ? g4t_normalize_difficulty_key( (string) $state['difficulty'] ) : '';
    $servings   = isset( $state['portions'] ) && is_numeric( (string) $state['portions'] ) ? (int) $state['portions'] : 0;
    $servings_u = isset( $state['portionUnit'] ) ? sanitize_text_field( (string) $state['portionUnit'] ) : '';
    $prep_mins  = isset( $state['timePrep'] ) && is_numeric( (string) $state['timePrep'] ) ? (int) $state['timePrep'] : 0;
    $cook_mins  = isset( $state['timeCook'] ) && is_numeric( (string) $state['timeCook'] ) ? (int) $state['timeCook'] : 0;
    $soak_mins  = isset( $state['timeSoak'] ) && is_numeric( (string) $state['timeSoak'] ) ? (int) $state['timeSoak'] : 0;
    $total_mins = $prep_mins + $cook_mins + $soak_mins;

    update_post_meta( $post_id, 'recipe_lead', $lead );
    update_post_meta( $post_id, 'recipe_difficulty', g4t_normalize_difficulty_for_meta( $difficulty ) );
    update_post_meta( $post_id, 'recipe_servings_value', $servings > 0 ? $servings : '' );
    update_post_meta( $post_id, 'recipe_servings_unit', $servings_u );
    update_post_meta( $post_id, 'recipe_time_prep', $prep_mins > 0 ? g4t_recipe_creator_format_minutes( $prep_mins ) : '' );
    update_post_meta( $post_id, 'recipe_time_cook', $cook_mins > 0 ? g4t_recipe_creator_format_minutes( $cook_mins ) : '' );
    update_post_meta( $post_id, 'recipe_time_soak', $soak_mins > 0 ? g4t_recipe_creator_format_minutes( $soak_mins ) : '' );
    update_post_meta( $post_id, 'recipe_time_total', $total_mins > 0 ? g4t_recipe_creator_format_minutes( $total_mins ) : '' );
    update_post_meta( $post_id, 'recipe_tips', wp_kses_post( (string) ( $state['tips'] ?? '' ) ) );
    update_post_meta( $post_id, 'recipe_source_label', sanitize_text_field( (string) ( $state['sourceName'] ?? '' ) ) );
    update_post_meta( $post_id, 'recipe_source_url', esc_url_raw( (string) ( $state['sourceUrl'] ?? '' ) ) );

    // Featured image — set directly as post thumbnail, no ACF field needed as intermediary.
    if ( isset( $state['image'] ) && is_array( $state['image'] ) && ! empty( $state['image']['id'] ) ) {
        $attachment_id = (int) $state['image']['id'];
        if ( $attachment_id > 0 ) {
            set_post_thumbnail( $post_id, $attachment_id );
        }
    } elseif ( array_key_exists( 'image', $state ) && null === $state['image'] ) {
        delete_post_thumbnail( $post_id );
    }

    // Ingredients photo URL.
    $ingredients_photo_url = '';
    if ( isset( $state['ingredientsPhoto'] ) ) {
        $ingredients_photo_url = g4t_recipe_creator_extract_photo_url( $state['ingredientsPhoto'] );
    }
    update_post_meta( $post_id, 'recipe_ingredients_photo_url', $ingredients_photo_url );

    // Ingredients mode toggle.
    $ingredients_mode = 'single';
    if ( isset( $state['ingredientsMode'] ) && 'sections' === (string) $state['ingredientsMode'] ) {
        $ingredients_mode = 'sections';
    }
    update_post_meta( $post_id, 'recipe_ingredients_mode', $ingredients_mode );

    if ( 'sections' === $ingredients_mode ) {
        // Sections mode — map creator section rows to meta-sync shape and persist.
        $sections = array();
        if ( isset( $state['ingredientSections'] ) && is_array( $state['ingredientSections'] ) ) {
            foreach ( $state['ingredientSections'] as $section_row ) {
                if ( ! is_array( $section_row ) ) {
                    continue;
                }
                $section_title = sanitize_text_field( (string) ( $section_row['title'] ?? '' ) );
                $section_items = array();
                if ( isset( $section_row['items'] ) && is_array( $section_row['items'] ) ) {
                    foreach ( $section_row['items'] as $row ) {
                        if ( ! is_array( $row ) ) {
                            continue;
                        }
                        $name   = sanitize_text_field( (string) ( $row['name'] ?? '' ) );
                        $amount = sanitize_text_field( trim( (string) ( $row['amount'] ?? '' ) ) );
                        if ( '' === $name && '' === $amount ) {
                            continue;
                        }
                        $term_id = null;
                        if ( isset( $row['termId'] ) && is_numeric( (string) $row['termId'] ) && (int) $row['termId'] > 0 ) {
                            $term_id = (int) $row['termId'];
                        }
                        $section_items[] = array(
                            'name'               => $name,
                            'amount'             => '' !== $amount ? $amount : null,
                            'unit'               => sanitize_text_field( (string) ( $row['unit'] ?? '' ) ),
                            'ingredient_note'    => sanitize_text_field( (string) ( $row['hint'] ?? '' ) ),
                            'ingredient_term_id' => $term_id,
                        );
                    }
                }
                if ( '' === $section_title && empty( $section_items ) ) {
                    continue;
                }
                $sections[] = array(
                    'section_title' => $section_title,
                    'section_items' => $section_items,
                );
            }
        }
        g4t_sync_ingredient_sections_meta( $post_id, $sections );
    } else {
        // Single mode — flat ingredients repeater. Writes meta rows AND syncs taxonomy terms.
        $ingredients = array();
        if ( isset( $state['ingredients'] ) && is_array( $state['ingredients'] ) ) {
            foreach ( $state['ingredients'] as $row ) {
                if ( ! is_array( $row ) ) {
                    continue;
                }
                $name   = sanitize_text_field( (string) ( $row['name'] ?? '' ) );
                $amount = sanitize_text_field( trim( (string) ( $row['amount'] ?? '' ) ) );
                if ( '' === $name && '' === $amount ) {
                    continue;
                }
                $term_id = null;
                if ( isset( $row['termId'] ) && is_numeric( (string) $row['termId'] ) && (int) $row['termId'] > 0 ) {
                    $term_id = (int) $row['termId'];
                }
                $ingredients[] = array(
                    'name'               => $name,
                    'amount'             => '' !== $amount ? $amount : null,
                    'unit'               => sanitize_text_field( (string) ( $row['unit'] ?? '' ) ),
                    'ingredient_note'    => sanitize_text_field( (string) ( $row['hint'] ?? '' ) ),
                    'ingredient_term_id' => $term_id,
                );
            }
        }
        g4t_sync_ingredients_repeater_meta( $post_id, $ingredients );
    }

    // Steps repeater — maps creator field names to meta-sync field names.
    $steps = array();
    if ( isset( $state['steps'] ) && is_array( $state['steps'] ) ) {
        foreach ( $state['steps'] as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            $heading = sanitize_text_field( (string) ( $row['heading'] ?? '' ) );
            $body    = wp_kses_post( (string) ( $row['body'] ?? '' ) );
            if ( '' === $heading && '' === trim( wp_strip_all_tags( $body ) ) ) {
                continue;
            }
            $step_photo_url = '';
            if ( isset( $row['photo'] ) ) {
                $step_photo_url = g4t_recipe_creator_extract_photo_url( $row['photo'] );
            }
            $steps[] = array(
                'heading'     => $heading,
                'description' => $body,
                'tip'         => sanitize_text_field( (string) ( $row['hint'] ?? '' ) ),
                'imageUrl'    => $step_photo_url,
            );
        }
    }
    g4t_sync_steps_repeater_meta( $post_id, $steps );

    g4t_sync_recipe_classification_taxonomies(
        $post_id,
        array(
            G4T_MEAL_TYPE_TAXONOMY => is_array( $state['mealTypeTermIds'] ?? null ) ? $state['mealTypeTermIds'] : array(),
            G4T_PREP_TIME_TAXONOMY => is_array( $state['prepTimeTermIds'] ?? null ) ? $state['prepTimeTermIds'] : array(),
            G4T_FEATURE_TAXONOMY   => is_array( $state['featureTermIds'] ?? null ) ? $state['featureTermIds'] : array(),
        )
    );

    $raw_products = isset( $state['products'] ) && is_array( $state['products'] ) ? $state['products'] : array();
    $product_ids  = array();

    foreach ( $raw_products as $product ) {
        if ( is_array( $product ) ) {
            $product_ids[] = $product['id'] ?? '';
            continue;
        }

        $product_ids[] = $product;
    }

    g4t_update_recipe_bottom_strip_product_ids_meta( $post_id, $product_ids );
}

/**
 * Normalize mixed product ID payload into ordered unique string IDs.
 *
 * @param array<int,mixed> $raw_ids Raw IDs from creator state or repeater/meta.
 * @return array<int,string>
 */
function g4t_normalize_recipe_bottom_strip_product_ids( array $raw_ids ): array {
    $normalized = array();
    $reject_demo_ids = g4t_recipe_creator_should_reject_demo_product_ids();

    foreach ( $raw_ids as $raw_id ) {
        $id = trim( (string) $raw_id );
        if ( '' === $id ) {
            continue;
        }

        if ( $reject_demo_ids && g4t_recipe_creator_is_static_demo_product_id( $id ) ) {
            continue;
        }

        $normalized[ $id ] = $id;
    }

    return array_values( $normalized );
}

/**
 * Check whether static demo product IDs must be rejected.
 *
 * @return bool
 */
function g4t_recipe_creator_should_reject_demo_product_ids(): bool {
    return file_exists( WP_PLUGIN_DIR . '/go4taste-ads/go4taste-ads.php' );
}

/**
 * Identify legacy demo IDs from creator-data.js sample catalog.
 *
 * @param string $product_id Raw product ID.
 * @return bool
 */
function g4t_recipe_creator_is_static_demo_product_id( string $product_id ): bool {
    $normalized = strtolower( trim( $product_id ) );

    return (bool) preg_match( '/^p([1-9]|10)$/', $normalized );
}

/**
 * Persist recipe bottom strip product IDs using the shared ads JSON contract.
 *
 * @param int              $post_id Post ID.
 * @param array<int,mixed> $raw_ids Raw product IDs.
 */
function g4t_update_recipe_bottom_strip_product_ids_meta( int $post_id, array $raw_ids ): void {
    if ( $post_id <= 0 ) {
        return;
    }

    $product_ids = g4t_normalize_recipe_bottom_strip_product_ids( $raw_ids );
    if ( empty( $product_ids ) ) {
        update_post_meta( $post_id, '_g4t_a_recipe_bottom_product_ids', '' );
        return;
    }

    $encoded = wp_json_encode( $product_ids );
    if ( is_string( $encoded ) ) {
        update_post_meta( $post_id, '_g4t_a_recipe_bottom_product_ids', $encoded );
    }
}

/**
 * Read recipe bottom strip product IDs for creator edit mode.
 *
 * Reads new JSON contract first, then falls back to legacy creator repeater.
 *
 * @param int $post_id Post ID.
 * @return array<int,string>
 */
function g4t_get_recipe_bottom_strip_product_ids_for_post( int $post_id ): array {
    if ( $post_id <= 0 ) {
        return array();
    }

    $raw_json = get_post_meta( $post_id, '_g4t_a_recipe_bottom_product_ids', true );
    if ( is_string( $raw_json ) && '' !== trim( $raw_json ) ) {
        $decoded = json_decode( $raw_json, true );
        if ( is_array( $decoded ) ) {
            $normalized = g4t_normalize_recipe_bottom_strip_product_ids( $decoded );
            if ( ! empty( $normalized ) ) {
                return $normalized;
            }
        }
    }

    $legacy_product_ids = array();
    $products_count     = (int) get_post_meta( $post_id, 'recipe_ads_products', true );
    for ( $i = 0; $i < $products_count; $i++ ) {
        $primary_key_value = get_post_meta( $post_id, 'recipe_ads_products_' . $i . '_product_id', true );
        if ( '' !== trim( (string) $primary_key_value ) ) {
            $legacy_product_ids[] = $primary_key_value;
            continue;
        }

        // Compatibility with older payload shape used by previous creator adapters.
        $legacy_product_ids[] = get_post_meta( $post_id, 'recipe_ads_products_' . $i . '_recipe_ads_product_id', true );
    }

    return g4t_normalize_recipe_bottom_strip_product_ids( $legacy_product_ids );
}

/**
 * Sync recipe bottom strip JSON contract after ACF form save.
 *
 * @param int $post_id Post ID saved by ACF.
 */
function g4t_recipe_creator_sync_recipe_bottom_strip_meta_after_acf_save( int $post_id ): void {
    if ( $post_id <= 0 ) {
        return;
    }

    $requested_action = g4t_get_recipe_creator_requested_action();
    if ( ! in_array( $requested_action, array( 'publish', 'update' ), true ) ) {
        return;
    }

    // Update post title from the flat `post_title` field submitted early in the POST
    // body — before the large ACF payload that may be truncated by max_input_vars.
    // acf[_post_title] is not relied on because it lands late in the POST body and
    // gets silently dropped on servers with a low max_input_vars limit.
    $submitted_title = isset( $_POST['post_title'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
        ? sanitize_text_field( wp_unslash( (string) $_POST['post_title'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
        : '';
    if ( '' !== $submitted_title ) {
        wp_update_post(
            array(
                'ID'         => $post_id,
                'post_title' => $submitted_title,
            )
        );
    }

    $posted_product_ids = g4t_recipe_creator_extract_product_ids_from_request();
    if ( ! empty( $posted_product_ids ) ) {
        g4t_update_recipe_bottom_strip_product_ids_meta( $post_id, $posted_product_ids );
        return;
    }

    g4t_update_recipe_bottom_strip_product_ids_meta(
        $post_id,
        g4t_get_recipe_bottom_strip_product_ids_for_post( $post_id )
    );
}

/**
 * Extract promoted product IDs from posted ACF payload.
 *
 * @return array<int,string>
 */
function g4t_recipe_creator_extract_product_ids_from_request(): array {
    // Primary path: flat JSON field submitted early in the POST body, before the large
    // ACF repeater payload. This avoids silent truncation caused by PHP max_input_vars
    // when the server limit is reached by ingredients/steps repeater inputs.
    if ( isset( $_POST['g4t_recipe_ads_product_ids_json'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
        $json_string = sanitize_text_field( wp_unslash( (string) $_POST['g4t_recipe_ads_product_ids_json'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        if ( '' !== $json_string ) {
            $decoded = json_decode( $json_string, true );
            if ( is_array( $decoded ) ) {
                return g4t_normalize_recipe_bottom_strip_product_ids( $decoded );
            }
        }
    }

    // Fallback: nested ACF payload (may be truncated on servers with low max_input_vars).
    if ( ! isset( $_POST['acf'] ) || ! is_array( $_POST['acf'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
        return array();
    }

    $acf_payload = wp_unslash( $_POST['acf'] ); // phpcs:ignore WordPress.Security.NonceVerification
    if ( ! is_array( $acf_payload ) ) {
        return array();
    }

    $raw_rows = $acf_payload['field_recipe_ads_products'] ?? ( $acf_payload['recipe_ads_products'] ?? null );
    if ( ! is_array( $raw_rows ) ) {
        return array();
    }

    $product_ids = array();
    foreach ( $raw_rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $product_ids[] = $row['field_recipe_ads_product_id'] ?? ( $row['product_id'] ?? '' );
    }

    return g4t_normalize_recipe_bottom_strip_product_ids( $product_ids );
}

/**
 * @param mixed $photo
 */
function g4t_recipe_creator_extract_photo_url( $photo ): string {
    if ( is_string( $photo ) ) {
        return esc_url_raw( $photo );
    }

    if ( ! is_array( $photo ) ) {
        return '';
    }

    if ( isset( $photo['id'] ) && is_numeric( (string) $photo['id'] ) ) {
        $attachment_url = wp_get_attachment_url( (int) $photo['id'] );
        if ( is_string( $attachment_url ) && '' !== $attachment_url ) {
            return esc_url_raw( $attachment_url );
        }
    }

    if ( isset( $photo['url'] ) && is_string( $photo['url'] ) ) {
        return esc_url_raw( $photo['url'] );
    }

    return '';
}

function g4t_recipe_creator_format_minutes( int $total_minutes ): string {
    if ( $total_minutes <= 0 ) {
        return '';
    }

    return (string) $total_minutes;
}
