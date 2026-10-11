<?php
/**
 * GO4TASTE Recipes Plugin — Recipe display image sizes
 *
 * Registers an intermediate width for recipe photos (hero, home feature,
 * listing cards). Migrated originals are ~600 px wide and the photos display
 * at ~390 px on desktop, so without a size in between `srcset` can only offer
 * 300w (too small) or the 600w original.
 *
 * The size keeps the original aspect ratio (no crop), so WordPress adds it to
 * `srcset` of the 'full' size automatically.
 *
 * Existing attachments need
 * `wp media regenerate --image_size=g4t-recipe-400 --only-missing`.
 *
 * @package Go4Taste_Recipes_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * `after_setup_theme` callback: register the recipe display image sizes.
 */
function g4t_recipe_display_register_image_sizes(): void {
    add_image_size( 'g4t-recipe-400', 400, 0, false );
}
