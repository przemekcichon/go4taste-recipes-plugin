# GO4TASTE Recipes Plugin — Schema Contract v1

**Frozen as of:** Phase 0
**Status:** Stable — no changes until Phase 2+ with major version bump

---

## Overview

Schema v1 defines the recipe data model contract between:
- Plugin data layer (`go4taste-recipes-plugin`)
- Theme rendering layer (Timber `single-recipe.twig`, `archive-recipe.twig`, `home.twig`)
- API layer (WPGraphQL future Phase 4)
- Static site generation (future Phase 4+)

All downstream systems consume the **single recipe view model** shape. Changes to this contract require version bump and coordinated migration across all consumers.

---

## Single Recipe View Model

**PHP Type:** `array<string, mixed>`  
**ACF Fields Required:** Yes (fallback to post_meta if ACF missing)  
**Source:** `Go4Taste_Recipes_Plugin::build_single_recipe_view_model( WP_Post $post ): array`

### Structure

```php
$recipe = [
    'id'         => (int),           // WP_Post->ID
    
    'hero' => [
        'title'   => (string),        // get_the_title()
        'excerpt' => (string),        // get_the_excerpt() — nullable, can be empty string
        'image'   => (string|null),   // get_the_post_thumbnail_url( 'full' ) — nullable
    ],
    
    'times' => [
        'prep'    => (string),        // ACF field 'recipe_time_prep' — human readable (e.g., "15 min")
        'cook'    => (string),        // ACF field 'recipe_time_cook'
        'total'   => (string),        // ACF field 'recipe_time_total'
    ],
    
    'difficulty' => (string),        // ACF field 'recipe_difficulty' — e.g., "easy", "medium", "hard"
    
    'servings' => [
        'value'   => (float|null),    // ACF field 'recipe_servings_value' — nullable, numeric
        'unit'    => (string),        // ACF field 'recipe_servings_unit' — e.g., "persons", "cups"
    ],
    
    'ingredients' => [               // Array of normalized ingredient objects
        [
            'ingredient_term_id' => (int|null), // taxonomy term ID from flat taxonomy `recipe_ingredient`
            'name'               => (string),    // ingredient name (fallback/import label)
            'amount'             => (float|null),// numeric amount, nullable
            'unit'               => (string),    // unit of measurement — empty string OK
        ],
        // ... more ingredients
    ],
    
    'steps' => [                      // Array of step strings, normalized (empty items removed)
        '0' => (string),              // first step text
        '1' => (string),              // second step text, etc.
    ],
    
    'tips'  => (string),              // ACF field 'recipe_tips' — NULLABLE, can be empty string
    
    'source' => [
        'label' => (string),          // ACF field 'recipe_source_label' — REQUIRED, fallback to site name
        'url'   => (string),          // ACF field 'recipe_source_url' — REQUIRED, fallback to post permalink
    ],
    
    'ads' => [
        'enabled'           => (bool),           // true if product IDs present
        'bottom_product_ids' => [                // Array of product ID strings
            '0' => (string),                     // product ID from PrestaShop
        ],
        'products'          => [                 // Array of fetched product data (Phase future)
            // Populated by Go4Taste_Ads API fetcher when available
        ],
    ],
];
```

### Nullability Rules (Critical)

| Field | Nullable | Default |
|-------|----------|---------|
| `hero.excerpt` | ✅ YES | `""` |
| `hero.image` | ✅ YES | `null` |
| `servings.value` | ✅ YES | `null` |
| `tips` | ✅ YES | `""` |
| `source.label` | ❌ NO | site name |
| `source.url` | ❌ NO | post permalink |
| All others | ❌ NO | type-appropriate default |

### Data Normalization

- **Ingredients:** Empty items removed; `amount` coerced to float or null
- **Ingredients taxonomy binding:** Each ingredient row maps to flat taxonomy `recipe_ingredient`.
    - If `ingredient_term_id` exists, it is used as canonical reference.
    - If only `name` exists, plugin resolves existing term by name/slug or creates missing term.
    - Synchronization stores both repeater row data and post term assignment.
- **Steps:** Extracted from repeater; empty items removed; order preserved
- **Times:** Human-readable strings (e.g., "15 minutes", "1 hour") — NOT parsed
- **Ingredients repeater:** Fields are `ingredient_term_id`, `name`, `amount`, `unit` (lowercase)
- **Steps repeater:** Field is `text` (lowercase)
- **Ads products:** Stored as array of product ID strings in repeater called `recipe_ads_products`

### Ingredient Taxonomy Contract

- **Taxonomy key:** `recipe_ingredient`
- **Type:** flat taxonomy (`hierarchical = false`)
- **REST exposure:** `show_in_rest = true`
- **Post assignment:** terms synchronized from ingredients repeater rows on post save
- **Import behavior:** importer may provide either `ingredient_term_id` or `name`; missing terms are auto-created from `name`

### Additional Recipe Taxonomies

Three additional flat taxonomies are registered for recipe classification and filtering:

- **recipe_meal_type** — Typ dania (śniadanie, danie główne, napój), slug `/typ-dania/`
- **recipe_prep_time_range** — Zakres czasu przygotowania (do 15 min, 16-30 min, itd.), slug `/czas-przygotowania/`
- **recipe_feature** — Cechy przepisu (Bez glutenu, Dania z orzechami, itd.), slug `/cechy/`

**Common properties:**
- **Type:** flat taxonomy (`hierarchical = false`)
- **REST exposure:** `show_in_rest = true`, `show_admin_column = true`
- **Post assignment:** manual via admin UI or programmatic via `wp_set_object_terms`
- **Use case:** filtering, categorization, and WPGraphQL queries

---

## Context Filters Contract

### Signature & Lifecycle

Three filters pass enriched `$context` array with recipe metadata:

```php
// Single recipe page
apply_filters( 'go4taste/recipes/context/single', array $context, array $meta ): array;

// Archive (CPT archive, taxonomy, date) page
apply_filters( 'go4taste/recipes/context/archive', array $context, array $meta ): array;

// Home / main listing (options → query override)
apply_filters( 'go4taste/recipes/context/home', array $context, array $meta ): array;
```

### Single Context Payload

```php
$context = [
    // ... existing Timber context ...
    
    'recipes' => [
        'enabled'              => (bool),         // true = plugin active
        'source_mode'          => 'post'|'existing_cpt'|'recipes_cpt',
        'source_post_type'     => (string),       // 'post' | 'recipe' | custom CPT key
        'fallback_used'        => (bool),         // true if configured CPT doesn't exist
        'acf_available'        => (bool),         // function_exists( 'get_field' )
        'wpgraphql_available'  => (bool),         // WPGraphQL plugin active
        'scope'                => 'single',
    ],
    
    'is_recipe'  => (bool),         // true if queried post matches source_post_type
    'recipe'     => [ /* single view model above */ ],
];
```

### Archive Context Payload

```php
$context = [
    // ... existing Timber context ...
    
    'recipes' => [
        'enabled'                     => (bool),
        'source_mode'                 => 'post'|'existing_cpt'|'recipes_cpt',
        'source_post_type'            => (string),
        'fallback_used'               => (bool),
        'acf_available'               => (bool),
        'wpgraphql_available'         => (bool),
        'scope'                       => 'archive',
        'use_archive_recipe_template' => (bool),   // true = use archive-recipe.twig
    ],
    
    'is_recipe_archive' => (bool),  // true if post-type archive or related taxonomy
    'recipe_archive'    => [
        'title'       => (string),   // archive title (category name, taxonomy label, etc.)
        'description' => (string),   // archive description (term description)
        'post_type'   => (string),   // source post type key
    ],
];
```

### Home Context Payload

```php
$context = [
    // ... existing Timber context ...
    
    'recipes' => [
        'enabled'             => (bool),
        'source_mode'         => 'post'|'existing_cpt'|'recipes_cpt',
        'source_post_type'    => (string),
        'fallback_used'       => (bool),
        'acf_available'       => (bool),
        'wpgraphql_available' => (bool),
        'scope'               => 'home',
    ],
    
    'is_recipe_home' => (bool),  // true
    'recipe_home'    => [
        'post_type' => (string), // source post type key
    ],
];
```

### Fallback Behavior

- If plugin not active: `context['recipes']['enabled'] = false`; all flag fields present but `false`
- If configured post_type doesn't exist: `context['recipes']['fallback_used'] = true`; fallback to `post`
- If ACF not installed: `context['recipes']['acf_available'] = false`; recipe data unavailable but plugin silent

---

## Admin Settings (Phase 0 Gate)

Settings page: **Settings → GO4TASTE Recipes**

### Options Storage

```php
get_option( 'go4taste_recipes_options' ) => [
    'source_mode'        => 'post'|'existing_cpt'|'recipes_cpt',
    'existing_post_type' => (string),  // CPT key if mode = 'existing_cpt'
];
```

### Configuration Flow

1. Admin selects source mode (post / existing CPT / recipes CPT)
2. If existing CPT, admin enters post_type key to validate
3. On save, options sanitized; cached opts cleared
4. Next filter call uses new strategy
5. If configured CPT doesn't exist → fallback to post + admin notice

---

## Version Bump Decision Tree

**When to change schema version:**

- ❌ Adding optional nullable fields (v1 → v1.1 patch)
- ✅ Adding required non-nullable fields (v1 → v2 major)
- ✅ Changing field name (v1 → v2 major)
- ✅ Changing data type (v1 → v2 major)
- ✅ Changing nullability rule (v1 → v2 major)

**Procedure for major bump:**
1. Create new schema file (SCHEMA_CONTRACT_v2.md)
2. Create migration path document (SCHEMA_MIGRATION_v1_to_v2.md)
3. Bump plugin version in .php main file
4. Update GraphQL type definition (Phase 4)
5. Coordinate with all dependent systems (theme, static site, etc.)

---

## Compliance Checklist (Phase 0 Gate)

- [ ] Plugin builds view model with all fields from schema v1
- [ ] All three context filters (`single`, `archive`, `home`) pass correct payload shape
- [ ] Fallback handling works: ACF missing → silent fail; CPT doesn't exist → use post
- [ ] Admin settings page functional: mode selector + existing_post_type input
- [ ] Sanitization applied to all options
- [ ] No hardcoding of 'post' type — always read from strategy
- [ ] ACF field group location respects configured post_type
- [ ] Dependencies (ACF required; WPGraphQL optional) correctly detected
- [ ] Plugin silent if not all deps present (no fatal errors)
- [ ] Schema documented in this file BEFORE moving to Phase 1

---

**Frozen:** This document is meant to be frozen as-is for Phase 1 & 2 work. Review and approve before Phase 1 starts.
