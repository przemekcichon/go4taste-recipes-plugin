<?php
/**
 * GO4TASTE Recipes Plugin — Block Type Registration
 *
 * Registers each custom recipe block type with WordPress and enqueues the editor
 * script and stylesheet.
 *
 * WHY the render_callback returns an empty string:
 *
 * These blocks use the "data-only" pattern: the block editor (JavaScript) stores
 * data as block attributes in post_content. The front-end rendering is done
 * entirely by the theme — in this plugin via Timber templates that receive the
 * data through context filters (see includes/timber/context-filters.php).
 *
 * A render_callback must exist for WordPress to accept the block registration
 * (otherwise the block type is considered "static" and expects a save() function
 * that produces HTML). The empty-string callback is the correct no-op for this
 * pattern.
 *
 * WHY no block.json files:
 *
 * WordPress 5.8+ supports block.json manifests. This plugin deliberately avoids
 * them to stay build-tool-free: everything runs from a single enqueued JS file with
 * no compilation step. If your project has a Node.js build pipeline, migrating to
 * block.json is straightforward — register_block_type() accepts a directory path
 * that contains block.json.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The block names registered by this plugin.
 *
 * The namespace 'go4taste-recipes/' must match the registerBlockType() calls
 * in assets/js/editor-blocks-new.js.
 *
 * When adding a new block:
 *  1. Add the block name to this array.
 *  2. Add a registerBlockType() call in assets/js/editor-blocks-new.js.
 *  3. Add the block to the template array in includes/registration/post-types.php.
 *  4. Add a case in the block-parsing loop in includes/sync/meta-sync.php.
 *  5. Add view model keys in includes/view-model/builder.php.
 *  6. Document in SCHEMA_CONTRACT_V2.md.
 *
 * @var array<int,string>
 */
define( 'G4T_RECIPE_BLOCK_NAMES', array(
    'go4taste-recipes/hero',
    'go4taste-recipes/ingredients',
    'go4taste-recipes/steps',
    'go4taste-recipes/tips',
    'go4taste-recipes/source',
) );

/**
 * Register all recipe block types and the editor script handle.
 *
 * @param Go4Taste_Recipes_Plugin $plugin Plugin instance for context.
 */
function g4t_register_recipe_blocks( Go4Taste_Recipes_Plugin $plugin ): void {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }

    g4t_register_editor_blocks_script();

    foreach ( G4T_RECIPE_BLOCK_NAMES as $block_name ) {
        register_block_type(
            $block_name,
            array(
                'api_version'     => 2,
                'editor_script'   => 'go4taste-recipes-editor-blocks',
                'render_callback' => 'g4t_render_recipe_block_placeholder',
                'show_in_rest'    => true,
            )
        );
    }
}

/**
 * Register (and enqueue) the editor blocks JS/CSS assets.
 *
 * WHY the file_exists() guard:
 *
 * In some deployment workflows (especially CI/CD pipelines or partial deploys)
 * the assets directory may be absent when the plugin is first activated. The guard
 * prevents a fatal error from wp_register_script() receiving a URL to a
 * nonexistent file — it degrades silently instead of crashing the admin.
 *
 * WHY wp-api-fetch dependency:
 *
 * The ingredients block uses apiFetch() to query the recipe_ingredient taxonomy
 * via REST API for autocomplete functionality. This dependency must be declared
 * here to ensure apiFetch is available when editor-blocks-new.js runs.
 */
function g4t_register_editor_blocks_script(): void {
    $script_path = GO4TASTE_RECIPES_PLUGIN_PATH . 'assets/js/editor-blocks-new.js';
    $css_path    = GO4TASTE_RECIPES_PLUGIN_PATH . 'assets/css/editor-blocks.css';

    if ( ! file_exists( $script_path ) ) {
        return;
    }

    wp_register_script(
        'go4taste-recipes-editor-blocks',
        GO4TASTE_RECIPES_PLUGIN_URL . 'assets/js/editor-blocks-new.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-hooks', 'wp-api-fetch' ),
        (string) filemtime( $script_path ),
        true
    );

    if ( file_exists( $css_path ) ) {
        wp_register_style(
            'go4taste-recipes-editor-blocks',
            GO4TASTE_RECIPES_PLUGIN_URL . 'assets/css/editor-blocks.css',
            array(),
            (string) filemtime( $css_path )
        );
        wp_enqueue_style( 'go4taste-recipes-editor-blocks' );
    }
}

/**
 * Server-side render callback for recipe blocks (returns empty string — blocks render client-side).
 *
 * @param array<string,mixed> $attributes
 */
function g4t_render_recipe_block_placeholder( array $attributes = array() ): string {
    return '';
}
