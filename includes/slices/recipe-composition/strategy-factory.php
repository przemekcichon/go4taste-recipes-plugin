<?php
/**
 * Recipe source strategy factory.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @return array{mode:string,post_type:string,fallback_used:bool}
 */
function g4t_get_source_strategy(): array {
    $options = g4t_get_recipe_options();
    $mode    = isset( $options['source_mode'] ) ? sanitize_key( (string) $options['source_mode'] ) : Go4Taste_Recipes_Plugin::MODE_POST;

    if ( ! in_array( $mode, array( Go4Taste_Recipes_Plugin::MODE_POST, Go4Taste_Recipes_Plugin::MODE_EXISTING_CPT, Go4Taste_Recipes_Plugin::MODE_RECIPES_CPT ), true ) ) {
        $mode = Go4Taste_Recipes_Plugin::MODE_POST;
    }

    $fallback_used = false;
    $post_type     = 'post';

    if ( Go4Taste_Recipes_Plugin::MODE_RECIPES_CPT === $mode ) {
        $post_type = G4T_RECIPES_POST_TYPE;
    } elseif ( Go4Taste_Recipes_Plugin::MODE_EXISTING_CPT === $mode ) {
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
