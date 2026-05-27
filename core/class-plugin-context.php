<?php
/**
 * GO4TASTE Recipes Plugin — Compatibility Context Class
 *
 * This class keeps option constants and lightweight accessors used by migrated
 * core/feature modules while legacy wrapper paths remain available.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Go4Taste_Recipes_Plugin {
    public const OPTION_KEY = 'go4taste_recipes_options';

    public const MODE_POST         = 'post';
    public const MODE_EXISTING_CPT = 'existing_cpt';
    public const MODE_RECIPES_CPT  = 'recipes_cpt';

    public function __construct() {
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
}