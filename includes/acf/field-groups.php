<?php
/**
 * GO4TASTE Recipes Plugin — ACF Field Group Registration
 *
 * WHY register ACF fields in PHP rather than using ACF's JSON export:
 *
 * ACF offers two ways to version-control field definitions:
 *  a) PHP registration via acf_add_local_field_group() — field definitions live in
 *     code, are tracked by git, and deploy with the plugin. No database state to
 *     synchronise across environments.
 *  b) JSON export — ACF writes .json files to an /acf-json/ directory which you
 *     check into git. ACF imports them on activation. Works well for content-editor
 *     defined fields but requires a manual export step after every change, which
 *     is fragile in automated CI/CD pipelines.
 *
 * For plugin-owned fields (fields this plugin knows about at design time) PHP
 * registration is the safer choice. Use JSON export only for fields that content
 * editors define themselves and that the plugin does not need to know about.
 *
 * All field keys in this file must be globally unique across all ACF field groups
 * on the site. Prefix them with 'field_recipe_' to avoid collisions.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register all ACF field groups for recipe posts.
 *
 * Called on the 'acf/init' hook, which fires after ACF itself has initialised.
 * Calling acf_add_local_field_group() earlier (e.g. on 'init') is safe in
 * practice but 'acf/init' is the documented correct hook.
 *
 * WHY only one field group (ads products):
 *
 * Recipe content fields (ingredients, steps, times, difficulty, etc.) are stored
 * in block attributes (post_content). They do NOT use ACF fields. The sync module
 * writes them to post_meta keys in ACF-compatible format so that:
 *  - Legacy code querying ACF directly still works
 *  - Importers can write to post_meta and have it render correctly
 *  - The view model builder can fall back to meta when block data is absent
 *
 * The only ACF field group defined here is for ads integration, which is not
 * part of the recipe schema contract and is managed separately from recipe data.
 *
 * @param Go4Taste_Recipes_Plugin $plugin Plugin instance for context.
 */
function g4t_register_acf_fields( Go4Taste_Recipes_Plugin $plugin ): void {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    $strategy = $plugin->resolve_source_strategy();

    // ─────────────────────────────────────────────────────────────────────────
    // Ads products repeater field group
    //
    // This repeater stores product IDs for the bottom banner slot on single
    // recipe pages. The view model builder (view-model/builder.php) reads this
    // repeater and fetches product data from the Go4Taste_Ads plugin.
    //
    // WHY a repeater instead of a simple text field:
    //  - Supports multiple products per recipe (carousel/slider)
    //  - Table layout makes bulk entry easier in wp-admin
    //  - Future-proof for adding product-specific settings (e.g. custom labels)
    //
    // Location rule follows $strategy['post_type'] so the field group appears
    // on the correct post type regardless of source_mode setting.
    // ─────────────────────────────────────────────────────────────────────────
    acf_add_local_field_group(
        array(
            'key'    => 'group_go4taste_recipe_ads',
            'title'  => 'GO4TASTE Recipe Ads',
            'fields' => array(
                array(
                    'key'          => 'field_recipe_ads_products',
                    'label'        => 'Ads Products (Bottom Single Banner)',
                    'name'         => 'recipe_ads_products',
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => 'Add product',
                    'sub_fields'   => array(
                        array(
                            'key'   => 'field_recipe_ads_product_id',
                            'label' => 'Product ID',
                            'name'  => 'product_id',
                            'type'  => 'text',
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => $strategy['post_type'],
                    ),
                ),
            ),
            'position' => 'normal',
            'style'    => 'default',
        )
    );
}
