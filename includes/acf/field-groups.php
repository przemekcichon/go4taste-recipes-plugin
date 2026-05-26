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
 * Get ACF location rules based on current source strategy
 *
 * Returns dynamic location rules that adapt to the plugin's source_mode:
 * - MODE_POST → post_type='post'
 * - MODE_EXISTING_CPT → post_type from options
 * - MODE_RECIPES_CPT → post_type='recipe'
 *
 * This ensures ACF field groups appear on the correct post type regardless
 * of how the plugin is configured.
 *
 * @param Go4Taste_Recipes_Plugin $plugin Plugin instance.
 * @return array<int,array<int,array{param:string,operator:string,value:string}>> ACF location rules.
 */
function g4t_get_acf_location_rules( Go4Taste_Recipes_Plugin $plugin ): array {
    $strategy = $plugin->resolve_source_strategy();

    return array(
        array(
            array(
                'param'    => 'post_type',
                'operator' => '==',
                'value'    => $strategy['post_type'],
            ),
        ),
    );
}

/**
 * Register all ACF field groups for recipe posts.
 *
 * Called on the 'acf/init' hook, which fires after ACF itself has initialised.
 * Calling acf_add_local_field_group() earlier (e.g. on 'init') is safe in
 * practice but 'acf/init' is the documented correct hook.
 *
 * WHY ACF field groups for recipe content:
 *
 * Recipe fields can be edited in TWO ways:
 *  1. Block Editor (primary, preferred) — data in block attributes (post_content)
 *  2. ACF Meta Boxes (alternative, fallback) — data in post_meta
 *
 * Dual UI support allows:
 *  - Admin users who prefer form-based editing over blocks
 *  - WP All Import to map to ACF fields (then auto-sync to blocks)
 *  - Legacy integrations that query ACF directly
 *
 * Sync flow:
 *  - ACF save → acf/save_post priority 5 → builds blocks → post_content updated
 *  - Block save → save_post priority 20 → syncs to meta → post_meta updated
 *
 * Field groups defined here:
 *  - Recipe Hero (lead, difficulty, servings, times)
 *  - Recipe Ingredients (repeater with taxonomy linking)
 *  - Recipe Steps (flat repeater with heading, description, tip, image)
 *  - Recipe Tips (general tips/notes)
 *  - Recipe Source (attribution label + URL)
 *  - Recipe Ads (product IDs for bottom banner)
 *  - Taxonomy Hero (archive page customization)
 *
 * @param Go4Taste_Recipes_Plugin $plugin Plugin instance for context.
 */
function g4t_register_acf_fields( Go4Taste_Recipes_Plugin $plugin ): void {
    // DEBUG: Function called
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'GO4TASTE: g4t_register_acf_fields() CALLED' );
    }
    
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        // ACF not active - early return
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'GO4TASTE Recipes: ACF not active, field groups not registered' );
        }
        return;
    }

    $strategy = $plugin->resolve_source_strategy();

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( sprintf(
            'GO4TASTE Recipes: Registering ACF field groups for post_type=%s',
            $strategy['post_type']
        ) );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Recipe Hero field group (simple fields)
    //
    // Core recipe metadata displayed in the hero area of single recipe pages.
    // These fields provide an alternative to Block Editor for users who prefer
    // form-based editing.
    //
    // Sync: ACF save → builds hero block → post_content updated
    // ─────────────────────────────────────────────────────────────────────────
    acf_add_local_field_group(
        array(
            'key'      => 'group_recipe_hero',
            'title'    => 'Recipe Hero',
            'fields'   => array(
                array(
                    'key'          => 'field_recipe_lead',
                    'label'        => 'Lead / Excerpt',
                    'name'         => 'recipe_lead',
                    'type'         => 'textarea',
                    'rows'         => 3,
                    'instructions' => 'Krótki opis przepisu (1-2 zdania)',
                ),
                array(
                    'key'           => 'field_recipe_featured_image',
                    'label'         => 'Featured Image (Main Recipe Photo)',
                    'name'          => 'recipe_featured_image',
                    'type'          => 'image',
                    'return_format' => 'url',
                    'instructions'  => 'Główne zdjęcie do przepisu (wyświetlane w hero)',
                ),
                array(
                    'key'           => 'field_recipe_difficulty',
                    'label'         => 'Difficulty',
                    'name'          => 'recipe_difficulty',
                    'type'          => 'select',
                    'choices'       => array(
                        'easy'   => __( 'Łatwy', 'go4taste-recipes-plugin' ),
                        'medium' => __( 'Średni', 'go4taste-recipes-plugin' ),
                        'hard'   => __( 'Trudny', 'go4taste-recipes-plugin' ),
                    ),
                    'default_value' => 'medium',
                ),
                array(
                    'key'   => 'field_recipe_servings_value',
                    'label' => 'Servings (number)',
                    'name'  => 'recipe_servings_value',
                    'type'  => 'number',
                    'min'   => 1,
                    'step'  => 1,
                ),
                array(
                    'key'           => 'field_recipe_servings_unit',
                    'label'         => 'Servings (unit)',
                    'name'          => 'recipe_servings_unit',
                    'type'          => 'text',
                    'placeholder'   => 'porcje, osoby, sztuk',
                    'default_value' => 'porcje',
                ),
                array(
                    'key'          => 'field_recipe_time_prep',
                    'label'        => 'Prep Time',
                    'name'         => 'recipe_time_prep',
                    'type'         => 'text',
                    'placeholder'  => '15 min',
                    'instructions' => 'Czas przygotowania (forma tekstowa)',
                ),
                array(
                    'key'         => 'field_recipe_time_cook',
                    'label'       => 'Cook Time',
                    'name'        => 'recipe_time_cook',
                    'type'        => 'text',
                    'placeholder' => '30 min',
                ),
                array(
                    'key'         => 'field_recipe_time_total',
                    'label'       => 'Total Time',
                    'name'        => 'recipe_time_total',
                    'type'        => 'text',
                    'placeholder' => '45 min',
                ),
                array(
                    'key'          => 'field_recipe_time_soak',
                    'label'        => 'Soak Time (optional)',
                    'name'         => 'recipe_time_soak',
                    'type'         => 'text',
                    'placeholder'  => '48 godzin',
                    'instructions' => 'Czas namaczania składników (opcjonalnie)',
                ),
            ),
            'location'   => g4t_get_acf_location_rules( $plugin ),
            'position'   => 'normal',
            'style'      => 'default',
            'menu_order' => 10,
        )
    );

    // ─────────────────────────────────────────────────────────────────────────
    // Recipe Ingredients field group (flat repeater)
    //
    // Ingredients list with taxonomy term linking and photo URL.
    // Repeater layout: table for easy bulk entry.
    //
    // Fields:
    //  - ingredient_term_id (taxonomy select) - links to recipe_ingredient taxonomy
    //  - name (text) - fallback if term not selected
    //  - amount (number) - quantity
    //  - unit (text) - measurement unit
    //  - ingredient_note (text) - optional comment/tip for ingredient
    //  - photo_url (image) - ingredients photo (outside repeater)
    //
    // Sync: ACF save → builds ingredients block with itemsList array → post_content
    // ─────────────────────────────────────────────────────────────────────────
    acf_add_local_field_group(
        array(
            'key'      => 'group_recipe_ingredients',
            'title'    => 'Recipe Ingredients',
            'fields'   => array(
                array(
                    'key'          => 'field_recipe_ingredients',
                    'label'        => 'Ingredients List',
                    'name'         => 'recipe_ingredients',
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => __( 'Add Ingredient', 'go4taste-recipes-plugin' ),
                    'sub_fields'   => array(
                        array(
                            'key'          => 'field_ingredient_term_id',
                            'label'        => 'Ingredient',
                            'name'         => 'ingredient_term_id',
                            'type'         => 'taxonomy',
                            'taxonomy'     => G4T_INGREDIENT_TAXONOMY,
                            'field_type'   => 'select',
                            'allow_null'   => 1,
                            'instructions' => 'Wybierz z listy lub wpisz nazwę poniżej',
                        ),
                        array(
                            'key'          => 'field_ingredient_name',
                            'label'        => 'Name (fallback)',
                            'name'         => 'name',
                            'type'         => 'text',
                            'instructions' => 'Jeśli składnika nie ma w taksonomii',
                        ),
                        array(
                            'key'  => 'field_ingredient_amount',
                            'label' => 'Amount',
                            'name' => 'amount',
                            'type' => 'text',
                            'placeholder' => 'np. 1/3, 1.5, szczypta',
                        ),
                        array(
                            'key'         => 'field_ingredient_unit',
                            'label'       => 'Unit',
                            'name'        => 'unit',
                            'type'        => 'text',
                            'placeholder' => 'g, ml, łyżka, szczypta',
                        ),
                        array(
                            'key'          => 'field_ingredient_note',
                            'label'        => 'Wskazówka',
                            'name'         => 'ingredient_note',
                            'type'         => 'text',
                            'placeholder'  => 'Opcjonalny komentarz do składnika',
                            'instructions' => 'Np. substytuty, wskazówki zakupowe',
                        ),
                    ),
                ),
                array(
                    'key'           => 'field_recipe_ingredients_photo_url',
                    'label'         => 'Ingredients Photo (optional)',
                    'name'          => 'recipe_ingredients_photo_url',
                    'type'          => 'image',
                    'return_format' => 'url',
                    'instructions'  => 'Zdjęcie przygotowanych składników',
                ),
            ),
            'location'   => g4t_get_acf_location_rules( $plugin ),
            'position'   => 'normal',
            'style'      => 'default',
            'menu_order' => 20,
        )
    );

    // ─────────────────────────────────────────────────────────────────────────
    // Recipe Steps
    //
    // Flat repeater for recipe preparation steps.
    // Each step has: heading, description, optional tip, and optional image.
    //
    // Fields:
    //  - recipe_steps (repeater)
    //      ├─ step_header (text) - step title/heading
    //      ├─ step_description (textarea) - step instructions
    //      ├─ step_tip (textarea, nullable) - optional cooking tip
    //      └─ step_image_url (image, nullable) - optional step photo
    //
    // Example structure:
    //  Step 1:
    //    - Header: "Przygotuj składniki"
    //    - Description: "Zmierz mąkę, cukier i jajka"
    //    - Tip: "Użyj ciepłych jajek"
    //    - Image: (optional)
    //
    // Meta keys written: recipe_steps (count),
    //                    recipe_steps_{$i}_step_header (text),
    //                    recipe_steps_{$i}_step_description (textarea),
    //                    recipe_steps_{$i}_step_tip (textarea),
    //                    recipe_steps_{$i}_step_image_url (URL)
    //
    // Sync: ACF save → builds steps block with stepsList array → post_content
    // ─────────────────────────────────────────────────────────────────────────
    
    // DEBUG: Check if this code is reached
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'GO4TASTE: Attempting to register group_recipe_steps' );
    }
    
    acf_add_local_field_group(
        array(
            'key'      => 'group_recipe_steps',
            'title'    => 'Recipe Steps',
            'fields'   => array(
                array(
                    'key'          => 'field_recipe_steps',
                    'label'        => 'Steps',
                    'name'         => 'recipe_steps',
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'button_label' => __( 'Add Step', 'go4taste-recipes-plugin' ),
                    'sub_fields'   => array(
                        array(
                            'key'          => 'field_step_header',
                            'label'        => 'Nagłówek kroku',
                            'name'         => 'step_header',
                            'type'         => 'text',
                            'placeholder'  => 'np. Przygotuj składniki',
                            'instructions' => 'Krótki tytuł kroku',
                        ),
                        array(
                            'key'   => 'field_step_description',
                            'label' => 'Opis kroku',
                            'name'  => 'step_description',
                            'type'  => 'textarea',
                            'rows'  => 3,
                            'instructions' => 'Szczegółowy opis',
                        ),
                        array(
                            'key'          => 'field_step_tip',
                            'label'        => 'Wskazówka (opcjonalnie)',
                            'name'         => 'step_tip',
                            'type'         => 'textarea',
                            'rows'         => 2,
                            'instructions' => 'Dodatkowa porada dla tego kroku',
                        ),
                        array(
                            'key'           => 'field_step_image_url',
                            'label'         => 'Zdjęcie kroku (opcjonalnie)',
                            'name'          => 'step_image_url',
                            'type'          => 'image',
                            'return_format' => 'url',
                            'instructions'  => 'Zdjęcie ilustrujące ten krok',
                        ),
                    ),
                ),
            ),
            'location'   => g4t_get_acf_location_rules( $plugin ),
            'position'   => 'normal',
            'style'      => 'default',
            'menu_order' => 30,
        )
    );

    // ─────────────────────────────────────────────────────────────────────────
    // Recipe Tips field group
    //
    // General tips and notes for the recipe (not step-specific).
    // Displayed as a separate block after preparation steps.
    //
    // Sync: ACF save → builds tips block → post_content updated
    // ─────────────────────────────────────────────────────────────────────────
    acf_add_local_field_group(
        array(
            'key'      => 'group_recipe_tips',
            'title'    => 'Recipe Tips',
            'fields'   => array(
                array(
                    'key'          => 'field_recipe_tips',
                    'label'        => 'Tips',
                    'name'         => 'recipe_tips',
                    'type'         => 'textarea',
                    'rows'         => 4,
                    'instructions' => 'Ogólne wskazówki do przepisu, warianty, porady',
                ),
            ),
            'location'   => g4t_get_acf_location_rules( $plugin ),
            'position'   => 'normal',
            'style'      => 'default',
            'menu_order' => 40,
        )
    );

    // ─────────────────────────────────────────────────────────────────────────
    // Recipe Source field group
    //
    // Source attribution for the recipe (label + URL).
    // Allows proper crediting of original recipe authors/websites.
    //
    // Sync: ACF save → builds source block → post_content updated
    // ─────────────────────────────────────────────────────────────────────────
    acf_add_local_field_group(
        array(
            'key'      => 'group_recipe_source',
            'title'    => 'Recipe Source',
            'fields'   => array(
                array(
                    'key'          => 'field_recipe_source_label',
                    'label'        => 'Source Label',
                    'name'         => 'recipe_source_label',
                    'type'         => 'text',
                    'placeholder'  => 'Adapted from..., Oryginalny przepis:',
                    'instructions' => 'Nazwa źródła przepisu',
                ),
                array(
                    'key'          => 'field_recipe_source_url',
                    'label'        => 'Source URL',
                    'name'         => 'recipe_source_url',
                    'type'         => 'url',
                    'instructions' => 'Link do oryginalnego przepisu',
                ),
            ),
            'location'   => g4t_get_acf_location_rules( $plugin ),
            'position'   => 'normal',
            'style'      => 'default',
            'menu_order' => 50,
        )
    );

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

    // ─────────────────────────────────────────────────────────────────────────
    // Taxonomy term meta fields for hero area on archive pages
    //
    // This group applies to all four recipe taxonomies: ingredient, meal type,
    // prep time range, and feature. Fields are used to customize the hero area
    // on taxonomy archive pages (e.g. /typ-dania/sniadanie/).
    //
    // WHY one group for all taxonomies instead of four separate groups:
    //  - All taxonomies share the same hero structure
    //  - Reduces code duplication and maintenance burden
    //  - Consistent UX across all taxonomy archives
    //
    // Field usage in theme:
    // These fields are read via ACF's get_field( $key, 'term_' . $term_id )
    // in the Timber context filters (includes/timber/context-filters.php)
    // for archive pages. The view model is built in g4t_filter_archive_context().
    // ─────────────────────────────────────────────────────────────────────────
    acf_add_local_field_group(
        array(
            'key'    => 'group_go4taste_taxonomy_hero',
            'title'  => 'Archive Hero Settings',
            'fields' => array(
                array(
                    'key'   => 'field_taxonomy_hero_icon',
                    'label' => 'Icon',
                    'name'  => 'hero_icon',
                    'type'  => 'image',
                    'instructions' => 'Icon displayed in the hero area (recommended: SVG or PNG, max 200x200px)',
                    'return_format' => 'array',
                    'preview_size' => 'thumbnail',
                ),
                array(
                    'key'          => 'field_taxonomy_title_suffix',
                    'label'        => 'Title Suffix',
                    'name'         => 'title_suffix',
                    'type'         => 'text',
                    'instructions' => 'Text appended to "Przepisy" (e.g. "na śniadanie" → "Przepisy na śniadanie")',
                    'prepend'      => 'Przepisy',
                    'placeholder'  => 'na śniadanie',
                ),
                array(
                    'key'           => 'field_taxonomy_hero_image',
                    'label'         => 'Hero Image',
                    'name'          => 'hero_image',
                    'type'          => 'image',
                    'instructions'  => 'Background image for the hero area (recommended: 1920x600px)',
                    'return_format' => 'array',
                    'preview_size'  => 'medium',
                    'default_value' => '/wp-content/uploads/placeholders/default-hero.jpg',
                ),
                array(
                    'key'          => 'field_taxonomy_short_claim',
                    'label'        => 'Short Claim',
                    'name'         => 'short_claim',
                    'type'         => 'text',
                    'instructions' => 'Short tagline (e.g. "Dobry start dnia" for Śniadanie)',
                    'maxlength'    => 60,
                    'placeholder'  => 'Dobry start dnia',
                ),
                array(
                    'key'          => 'field_taxonomy_blog_posts',
                    'label'        => 'Related Blog Posts',
                    'name'         => 'blog_posts',
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => 'Add blog post',
                    'instructions' => 'Manually curated blog posts to display in the hero area',
                    'sub_fields'   => array(
                        array(
                            'key'   => 'field_taxonomy_blog_post_title',
                            'label' => 'Title',
                            'name'  => 'title',
                            'type'  => 'text',
                        ),
                        array(
                            'key'   => 'field_taxonomy_blog_post_url',
                            'label' => 'URL',
                            'name'  => 'url',
                            'type'  => 'url',
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'taxonomy',
                        'operator' => '==',
                        'value'    => G4T_INGREDIENT_TAXONOMY,
                    ),
                ),
                array(
                    array(
                        'param'    => 'taxonomy',
                        'operator' => '==',
                        'value'    => G4T_MEAL_TYPE_TAXONOMY,
                    ),
                ),
                array(
                    array(
                        'param'    => 'taxonomy',
                        'operator' => '==',
                        'value'    => G4T_PREP_TIME_TAXONOMY,
                    ),
                ),
                array(
                    array(
                        'param'    => 'taxonomy',
                        'operator' => '==',
                        'value'    => G4T_FEATURE_TAXONOMY,
                    ),
                ),
            ),
            'position' => 'normal',
            'style'    => 'default',
        )
    );
}
