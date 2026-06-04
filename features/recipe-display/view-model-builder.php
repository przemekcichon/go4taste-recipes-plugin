<?php
/**
 * GO4TASTE Recipes Plugin — View Model Builder
 *
 * Builds the canonical view model for a single recipe post. This is the data shape
 * consumed by Timber templates, WPGraphQL resolvers, and any other downstream
 * system. The shape is documented in SCHEMA_CONTRACT_V2.md.
 *
 * Data source:
 *  1. ACF / post_meta — the canonical and only runtime source of truth for recipe data.
 *     Written by the frontend creator (g4t_creator_state_to_meta) and by ACF metaboxes.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Build the full view model for a single recipe post.
 *
 * This function orchestrates:
 *  1. Reading ACF/post_meta data (g4t_get_recipe_acf_data)
 *  2. Normalizing repeater arrays (g4t_normalize_ingredients_list, g4t_normalize_steps_list)
 *  3. Assembling the final view model array matching SCHEMA_CONTRACT_V2.md
 *
 * The returned array is frozen in structure: adding/removing/renaming keys requires
 * a schema version bump and migration plan. See SCHEMA_CONTRACT_V2.md for details.
 *
 * @param WP_Post $post Post object.
 * @param Go4Taste_Recipes_Plugin $plugin Plugin instance for context.
 * @return array<string,mixed> View model matching SCHEMA_CONTRACT_V2.md shape.
 */
function g4t_build_single_recipe_view_model( WP_Post $post, Go4Taste_Recipes_Plugin $plugin ): array {
    $post_id  = (int) $post->ID;
    $acf_data = g4t_get_recipe_acf_data( $post_id );
    $data     = $acf_data;

    $created_timestamp  = (int) get_post_time( 'U', false, $post_id );
    $modified_timestamp = (int) get_post_modified_time( 'U', false, $post_id );

    $date_label = 'Data dodania';
    $date_value = $created_timestamp > 0 ? wp_date( 'j F Y', $created_timestamp ) : '';

    if ( $modified_timestamp > 0 && $created_timestamp > 0 && $modified_timestamp > $created_timestamp ) {
        $date_label = 'Data aktualizacji';
        $date_value = wp_date( 'j F Y', $modified_timestamp );
    }

    $servings_value = $data['servings_value'] ?? null;
    if ( null !== $servings_value && '' !== $servings_value ) {
        $servings_value = is_numeric( (string) $servings_value ) ? (float) $servings_value : null;
    }

    $normalized_ingredients = ( ! empty( $data['ingredients'] ) && is_array( $data['ingredients'] ) ) ? $data['ingredients'] : array();

    $ads_product_ids = g4t_get_ads_product_ids_from_acf( $post_id );
    $ads_products    = function_exists( 'g4t_recipe_bottom_strip_get_products_for_post' )
        ? g4t_recipe_bottom_strip_get_products_for_post( $post_id )
        : array();

    $hero_image_url = get_the_post_thumbnail_url( $post_id, 'full' );

    $difficulty_key   = (string) ( $data['difficulty'] ?? '' );
    $difficulty_label = g4t_get_difficulty_label( $difficulty_key );

    return array(
        'id'    => $post_id,
        'hero'  => array(
            'title'   => get_the_title( $post_id ),
            'excerpt' => (string) ( $data['lead'] ?? '' ),
            'image'   => is_string( $hero_image_url ) ? $hero_image_url : '',
        ),
        'times' => array(
            'prep'  => (string) ( $data['time_prep'] ?? '' ),
            'cook'  => (string) ( $data['time_cook'] ?? '' ),
            'total' => (string) ( $data['time_total'] ?? '' ),
            'soak'  => (string) ( $data['time_soak'] ?? '' ),
        ),
        'difficulty'       => $difficulty_label,
        'difficulty_key'   => $difficulty_key,
        'servings'   => array(
            'value' => $servings_value,
            'unit'  => (string) ( $data['servings_unit'] ?? '' ),
        ),
        'date' => array(
            'label' => $date_label,
            'value' => $date_value,
        ),
        'ingredients'           => $normalized_ingredients,
        'ingredients_photo_url' => (string) ( $data['ingredients_photo_url'] ?? '' ),
        'steps'                 => ( ! empty( $data['steps'] ) && is_array( $data['steps'] ) ) ? $data['steps'] : array(),
        'tips'                  => (string) ( $data['tips'] ?? '' ),
        'source'                => array(
            'label' => (string) ( $data['source_label'] ?? '' ),
            'url'   => (string) ( $data['source_url'] ?? '' ),
        ),
        'ads' => array(
            'bottom_product_ids' => $ads_product_ids,
            'products'           => $ads_products,
            'enabled'            => ! empty( $ads_products ),
        ),
    );
}

/**
 * Extract ads product IDs from legacy ACF/meta repeater.
 *
 * @param int $post_id Post ID.
 * @return array<int,string>
 */
function g4t_get_ads_product_ids_from_acf( int $post_id ): array {
    $ads_products_raw = g4t_acf_field( 'recipe_ads_products', $post_id, array() );
    $raw_ids          = array();

    if ( is_array( $ads_products_raw ) ) {
        foreach ( $ads_products_raw as $row ) {
            if ( is_array( $row ) && isset( $row['product_id'] ) ) {
                $raw_ids[] = $row['product_id'];
            }
        }
    }

    return g4t_normalize_ads_product_ids( $raw_ids );
}

/**
 * Normalize ads product ID values to a unique, ordered list of strings.
 *
 * @param array<int,mixed> $raw_ids Raw ids from blocks/meta.
 * @return array<int,string>
 */
function g4t_normalize_ads_product_ids( array $raw_ids ): array {
    $normalized = array();

    foreach ( $raw_ids as $raw_id ) {
        if ( is_scalar( $raw_id ) ) {
            $product_id = trim( (string) $raw_id );
        } elseif ( is_array( $raw_id ) && isset( $raw_id['id'] ) && is_scalar( $raw_id['id'] ) ) {
            $product_id = trim( (string) $raw_id['id'] );
        } elseif ( is_array( $raw_id ) && isset( $raw_id['product_id'] ) && is_scalar( $raw_id['product_id'] ) ) {
            $product_id = trim( (string) $raw_id['product_id'] );
        } else {
            $product_id = '';
        }

        if ( '' !== $product_id ) {
            $normalized[] = $product_id;
        }
    }

    return array_values( array_unique( $normalized ) );
}

/**
 * Load ACF / post-meta fields for a recipe post.
 *
 * @return array<string,mixed>
 */
function g4t_get_recipe_acf_data( int $post_id ): array {
    $ingredients_raw = g4t_acf_field( 'recipe_ingredients', $post_id, array() );
    $steps_raw       = g4t_acf_field( 'recipe_steps', $post_id, array() );

    $ingredients = array();
    if ( is_array( $ingredients_raw ) ) {
        $ingredients = g4t_normalize_ingredients_list( $ingredients_raw );
    } elseif ( is_numeric( $ingredients_raw ) ) {
        $ingredients = g4t_load_ingredients_repeater_meta( $post_id, (int) $ingredients_raw );
    }

    $steps = array();
    if ( is_array( $steps_raw ) ) {
        $steps = g4t_normalize_steps_list( $steps_raw );
    } elseif ( is_numeric( $steps_raw ) ) {
        $steps = g4t_load_steps_repeater_meta( $post_id, (int) $steps_raw );
    }

    return array(
        'lead'                  => (string) g4t_acf_field( 'recipe_lead', $post_id, '' ),
        'difficulty'            => g4t_normalize_difficulty_key( (string) g4t_acf_field( 'recipe_difficulty', $post_id, '' ) ),
        'servings_value'        => g4t_acf_field( 'recipe_servings_value', $post_id, null ),
        'servings_unit'         => (string) g4t_acf_field( 'recipe_servings_unit', $post_id, '' ),
        'time_prep'             => (string) g4t_acf_field( 'recipe_time_prep', $post_id, '' ),
        'time_cook'             => (string) g4t_acf_field( 'recipe_time_cook', $post_id, '' ),
        'time_total'            => (string) g4t_acf_field( 'recipe_time_total', $post_id, '' ),
        'time_soak'             => (string) g4t_acf_field( 'recipe_time_soak', $post_id, '' ),
        'ingredients'           => $ingredients,
        'ingredients_photo_url' => (string) g4t_acf_field( 'recipe_ingredients_photo_url', $post_id, '' ),
        'steps'                 => $steps,
        'tips'                  => (string) g4t_acf_field( 'recipe_tips', $post_id, '' ),
        'source_label'          => (string) g4t_acf_field( 'recipe_source_label', $post_id, '' ),
        'source_url'            => (string) g4t_acf_field( 'recipe_source_url', $post_id, '' ),
    );
}

/**
 * Safe ACF field reader with automatic fallback to post_meta.
 *
 * WHY this wrapper exists:
 *
 * ACF's get_field() returns false when ACF is inactive OR when the field doesn't
 * exist OR when the field is empty. We need to distinguish "ACF not loaded" from
 * "field is empty". This function:
 *  1. Checks if ACF is available (function_exists('get_field')).
 *  2. Calls get_field() if available.
 *  3. Falls back to get post_meta() if ACF returns false.
 *  4. Returns $default if both sources return empty.
 *
 * This ensures recipes render correctly even if ACF is temporarily disabled or
 * if data was written directly to post_meta by an importer.
 *
 * @param string $field_key ACF field key or post_meta key.
 * @param int $post_id Post ID.
 * @param mixed $default Default value if field is not found.
 * @return mixed Field value, post_meta value, or $default.
 */
function g4t_acf_field( string $field_key, int $post_id, $default = null ) {
    if ( function_exists( 'get_field' ) ) {
        $value = get_field( $field_key, $post_id );
        if ( g4t_has_value( $value ) ) {
            return $value;
        }
    }

    $meta = get_post_meta( $post_id, $field_key, true );

    return g4t_has_value( $meta ) ? $meta : $default;
}

/**
 * Fetch ads product data from the Go4Taste_Ads plugin for a list of product IDs.
 *
 * @param array<int,string> $product_ids
 * @param int               $post_id
 * @return array<int,mixed>
 */
function g4t_fetch_ads_products( array $product_ids, int $post_id ): array {
    if ( empty( $product_ids ) || ! class_exists( 'Go4Taste_Ads' ) ) {
        return array();
    }

    $options  = get_option( 'go4taste_ads_options' );
    $p_value  = isset( $options['p_parameter_value'] ) ? (string) $options['p_parameter_value'] : '';
    $lang     = isset( $options['prestashop_lang'] ) ? (string) $options['prestashop_lang'] : 'pl';
    $api_type = isset( $options['api_type'] ) ? (string) $options['api_type'] : 'prestashop_1_5';
    $api_url  = isset( $options['api_url'] ) ? (string) $options['api_url'] : '';

    $plugin_instance = Go4Taste_Ads::get_instance();
    $products        = array();

    foreach ( $product_ids as $product_id ) {
        $product_id = trim( (string) $product_id );
        if ( '' === $product_id ) {
            continue;
        }

        $product_data = $plugin_instance->fetch_product_data( $product_id, $p_value, $lang, $api_type, $api_url );
        if ( $product_data ) {
            $products[] = $product_data;
        }
    }

    return $products;
}
