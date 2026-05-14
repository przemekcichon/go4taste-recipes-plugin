# GO4TASTE Recipes Plugin — Schema Contract v1

**Last Updated:** Phase 1-3 (ACF field groups & sync flows added)  
**Status:** Stable — dual UI support complete (Block Editor + ACF Meta Boxes)

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
        'soak'    => (string),        // ACF field 'recipe_time_soak' — NULLABLE, soaking time (e.g., "48 hours")
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
            'ingredient_note'    => (string),    // optional comment/tip — empty string if not set
        ],
        // ... more ingredients
    ],
    
    'steps' => [                      // Array of step objects, normalized (empty items removed)
        [
            'heading'     => (string),    // step title/heading
            'description' => (string),    // step instructions text
            'tip'         => (string),    // optional cooking tip (empty string if none)
            'imageUrl'    => (string),    // optional step image URL (empty string if none)
        ],
        // ... more steps
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
| `times.soak` | ✅ YES | `""` |
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
- **Steps:** Extracted from repeater; empty items removed; order preserved; each step has `heading`, `description`, `tip` (nullable), and `imageUrl` (nullable)
- **Times:** Human-readable strings (e.g., "15 minutes", "1 hour") — NOT parsed. `soak` is NULLABLE (empty string allowed), used for ingredient soaking time (e.g., "48 hours" for chickpea soaking).
- **Ingredients repeater:** Fields are `ingredient_term_id`, `name`, `amount`, `unit`, `ingredient_note` (lowercase)
- **Steps repeater:** Fields are `heading`, `description`, `tip`, `imageUrl` (camelCase, matching block editor)
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
        'hero'        => [ /* taxonomy term hero data, see below */ ], // ONLY for taxonomy archives
    ],
];
```

### Taxonomy Term Hero Data (Archive Context Only)

For taxonomy archive pages (e.g., `/typ-dania/sniadanie/`), `$context['recipe_archive']['hero']` contains custom hero area settings from ACF term meta fields.

**PHP Type:** `array<string, mixed>`  
**ACF Fields:** Optional (if ACF not active or fields not set, returns safe defaults)  
**Source:** `g4t_get_taxonomy_term_hero_data( WP_Term $term ): array`

**Structure:**

```php
$hero = [
    'icon'              => (array|null),    // ACF image field (array with 'url', 'alt', 'width', 'height') or null
    'title_suffix'      => (string),        // Text to append to "Przepisy" (e.g., "na śniadanie")
    'short_description' => (string),        // Brief intro text (textarea, nullable, can be empty string)
    'hero_image'        => (array|null),    // ACF image field (array) or null; has default_value placeholder URL
    'short_claim'       => (string),        // Tagline (e.g., "Dobry start dnia"), nullable, can be empty string
    'blog_posts'        => [                // Array of related blog posts (repeater)
        [
            'title' => (string),            // Blog post title (required, non-empty)
            'url'   => (string),            // Blog post URL (validatedesc_url_raw, required, non-empty)
        ],
        // ... more blog posts
    ],
];
```

**Nullability Rules:**

| Field | Nullable | Default if empty |
|-------|----------|------------------|
| `icon` | ✅ YES | `null` |
| `title_suffix` | ❌ NO | `""` (empty string) |
| `short_description` | ❌ NO | `""` (empty string) |
| `hero_image` | ✅ YES | `null` (ACF has default_value for placeholder) |
| `short_claim` | ❌ NO | `""` (empty string) |
| `blog_posts` | ❌ NO | `[]` (empty array) |

**Data Normalization:**

- **Blog posts repeater:** Invalid entries (empty title or invalid URL) are filtered out via `g4t_normalize_term_blog_posts()`
- **Image fields:** If ACF returns non-array (e.g., false when field empty), coerced to `null`
- **Text fields:** Empty values coerced to `""` (empty string) for consistent type

**Availability:**

- Only present in `$context['recipe_archive']['hero']` when `is_tax()` is true
- For post-type archives (e.g., `/recipes/`), hero data is not populated (hero index not present)
- If ACF not active, all fields return safe defaults (nulls and empty strings)

**Taxonomies covered:**

Hero data is available for all four recipe taxonomies:
- `recipe_ingredient` (Składniki)
- `recipe_meal_type` (Typy dania)
- `recipe_prep_time_range` (Czas przygotowania)
- `recipe_feature` (Cechy)

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

## ACF Field Groups & Sync Flows

**Added:** Phase 1-3 of ACF field groups implementation  
**Status:** Stable — dual UI support (Block Editor + ACF Meta Boxes)

### Overview

The plugin supports **two alternative UIs** for editing recipe data:

1. **Block Editor** (Gutenberg blocks) — Primary, source of truth
2. **ACF Meta Boxes** — Alternative for users preferring field-based UI

Both UIs are **bidirectionally synchronized:**
- **ACF save → blocks**: ACF field edits rebuild `post_content` with Gutenberg block markup
- **Block save → meta**: Block edits sync attributes to `post_meta` for ACF read/fallback

### ACF Field Groups

Six ACF field groups are registered for recipe content:

#### 1. Recipe Hero (`group_recipe_hero`)

**Meta Box Title:** Recipe Hero  
**Fields:** 8 simple fields (text, number, select)

| Field Name | Type | Meta Key | Description |
|------------|------|----------|-------------|
| `recipe_lead` | textarea | `recipe_lead` | Lead/excerpt text |
| `recipe_difficulty` | select | `recipe_difficulty` | Difficulty level (easy/medium/hard) |
| `recipe_servings_value` | number | `recipe_servings_value` | Number of servings (numeric) |
| `recipe_servings_unit` | text | `recipe_servings_unit` | Servings unit (e.g., "persons", "cups") |
| `recipe_time_prep` | text | `recipe_time_prep` | Preparation time (human-readable, e.g., "15 min") |
| `recipe_time_cook` | text | `recipe_time_cook` | Cooking time |
| `recipe_time_total` | text | `recipe_time_total` | Total time |
| `recipe_time_soak` | text | `recipe_time_soak` | Soaking time (nullable, e.g., "48 hours") |

**Sync:** Builds `go4taste-recipes/hero` block with all 8 attributes.

#### 2. Recipe Ingredients (`group_recipe_ingredients`)

**Meta Box Title:** Recipe Ingredients  
**Fields:** 1 repeater (table layout) + 1 image field

**Repeater (`recipe_ingredients`):**

| Sub-Field Name | Type | Meta Key Pattern | Description |
|----------------|------|------------------|-------------|
| `ingredient_term_id` | taxonomy | `recipe_ingredients_{$i}_ingredient_term_id` | Links to `recipe_ingredient` taxonomy term |
| `name` | text | `recipe_ingredients_{$i}_name` | Fallback name if term not selected |
| `amount` | number | `recipe_ingredients_{$i}_amount` | Quantity (nullable) |
| `unit` | text | `recipe_ingredients_{$i}_unit` | Unit of measurement |
| `ingredient_note` | text | `recipe_ingredients_{$i}_ingredient_note` | Optional comment/tip/substitute suggestion |

**Photo field:**
- `recipe_ingredients_photo_url` (image, return URL) — Ingredients photo (optional)

**Meta keys written:**
- `recipe_ingredients` (count, integer)
- `recipe_ingredients_{$i}_ingredient_term_id` (term ID, integer)
- `recipe_ingredients_{$i}_name` (text)
- `recipe_ingredients_{$i}_amount` (float)
- `recipe_ingredients_{$i}_unit` (text)
- `recipe_ingredients_{$i}_ingredient_note` (text, optional)
- `recipe_ingredients_photo_url` (URL)

**Sync:** Builds `go4taste-recipes/ingredients` block with `itemsList` array + `photoUrl`.

#### 4. Recipe Steps (`group_recipe_steps`)

**Meta Box Title:** Recipe Steps  
**Fields:** 1 flat repeater (block layout) with 4 sub-fields

**Repeater (`recipe_steps`):**

| Sub-Field Name | Type | Meta Key Pattern | Description |
|----------------|------|------------------|-------------|
| `step_header` | text | `recipe_steps_{$i}_step_header` | Step title/heading |
| `step_description` | textarea | `recipe_steps_{$i}_step_description` | Detailed step instructions |
| `step_tip` | textarea | `recipe_steps_{$i}_step_tip` | Optional cooking tip (nullable) |
| `step_image_url` | image | `recipe_steps_{$i}_step_image_url` | Optional step photo URL (nullable) |

**Meta keys written:**
- `recipe_steps` (count, integer)
- `recipe_steps_{$i}_step_header` (text)
- `recipe_steps_{$i}_step_description` (textarea)
- `recipe_steps_{$i}_step_tip` (textarea, nullable)
- `recipe_steps_{$i}_step_image_url` (URL, nullable)

**Example structure:**
```
Step 0:
  - Header: "Przygotuj składniki"
  - Description: "Zmierz mąkę, cukier i jajka"
  - Tip: "Użyj ciepłych jajek" (optional)
  - Image: "https://example.com/step1.jpg" (optional)
Step 1:
  - Header: "Piecz ciasto"
  - Description: "Wstaw do piekarnika na 30 minut"
  - Tip: "" (empty)
  - Image: "" (empty)
```

**Sync:** Builds `go4taste-recipes/steps` block with `stepsList` array:
```json
[
  {
    "heading": "Przygotuj składniki",
    "description": "Zmierz mąkę, cukier i jajka",
    "tip": "Użyj ciepłych jajek",
    "imageUrl": "https://example.com/step1.jpg"
  },
  {
    "heading": "Piecz ciasto",
    "description": "Wstaw do piekarnika na 30 minut",
    "tip": "",
    "imageUrl": ""
  }
]
```

#### 5. Recipe Tips (`group_recipe_tips`)

**Meta Box Title:** Recipe Tips  
**Fields:** 1 simple field (textarea)

| Field Name | Type | Meta Key | Description |
|------------|------|----------|-------------|
| `recipe_tips` | textarea | `recipe_tips` | General cooking tips/notes for the recipe |

**Sync:** Builds `go4taste-recipes/tips` block with `tips` attribute.

#### 6. Recipe Source (`group_recipe_source`)

**Meta Box Title:** Recipe Source  
**Fields:** 2 simple fields (text, url)

| Field Name | Type | Meta Key | Description |
|------------|------|----------|-------------|
| `recipe_source_label` | text | `recipe_source_label` | Source attribution label |
| `recipe_source_url` | url | `recipe_source_url` | Source attribution URL |

**Sync:** Builds `go4taste-recipes/source` block with `sourceLabel` and `sourceUrl` attributes.

### Sync Flows

#### Flow 1: ACF → Blocks (Priority 5)

**Hook:** `acf/save_post` priority 5 (before default 10)  
**File:** `includes/sync/acf-to-blocks.php`  
**Function:** `g4t_sync_acf_to_blocks( int $post_id )`

**Process:**
1. Detect if save came from ACF UI (`$_POST['acf']` present)
2. Read all ACF field values using `get_post_meta()`
3. Call block builder functions:
   - `g4t_build_hero_block_from_acf( $post_id )`
   - `g4t_build_ingredients_block_from_acf( $post_id )`
   - `g4t_build_steps_block_from_acf( $post_id )`
   - `g4t_build_tips_block_from_acf( $post_id )`
   - `g4t_build_source_block_from_acf( $post_id )`
4. Combine blocks into `post_content` (with blank lines + inline-banner block)
5. Set transient flag `g4t_acf_save_{$post_id}` (60s TTL) to skip block→meta parse
6. Disable block→meta sync hook temporarily (`remove_action`)
7. Call `wp_update_post()` to write `post_content`
8. Re-enable block→meta sync hook (`add_action`)
9. Track edit source as `'acf'` via `g4t_track_edit_source()`

**Block builders** use **shared converters** from `includes/sync/converters.php`:
- `g4t_convert_hero_meta_to_attrs( $post_id )`
- `g4t_convert_ingredients_meta_to_array( $post_id )`
- `g4t_convert_steps_meta_to_array( $post_id )`

**Re-entry guard:** Static `$running` flag prevents infinite loops if ACF triggers another save.

#### Flow 2: Blocks → Meta (Priority 20)

**Hook:** `save_post` priority 20 (after block editor saves)  
**File:** `includes/sync/meta-sync.php`  
**Function:** `g4t_sync_recipe_meta_from_blocks( int $post_id, WP_Post $post, bool $update, $post_before )`

**Process:**
1. Check transient flag `g4t_acf_save_{$post_id}` — if set, skip parse (performance)
2. Parse `post_content` using `parse_blocks()` to extract block attributes
3. Write block attributes to `post_meta` using `update_post_meta()`
4. Sync ingredient terms to `recipe_ingredient` taxonomy via `g4t_ensure_ingredient_term()`
5. Track edit source as `'blocks'` via `g4t_track_edit_source()`

**Why priority 20?** Ensures block editor has finished writing `post_content` before we parse it.

**Performance optimization:** Transient flag skips expensive `parse_blocks()` call when ACF already wrote meta directly.

### Save Source Detection

**File:** `includes/sync/detection.php`  
**Function:** `g4t_detect_save_source( int $post_id ): string`

Detects which UI triggered the save:

| Save Source | Detection Method | Returned Value |
|-------------|------------------|----------------|
| ACF Meta Boxes | `$_POST['acf']` present | `'acf'` |
| Block Editor | `defined('REST_REQUEST') && REST_REQUEST` | `'blocks'` |
| Reverse Sync | `doing_action('admin_post_g4t_reverse_sync')` | `'reverse_sync'` |
| Unknown | None of above | `'unknown'` |

**Edit source tracking:**
- Stored in post meta `g4t_last_edit_source` (string) + `g4t_last_edit_timestamp` (UNIX timestamp)
- Used to show admin notice when user switches UIs within 24 hours

**Admin notice:**
If user edited in ACF yesterday and opens Block Editor today → admin notice warns about potential conflicts.

### Shared Converters

**File:** `includes/sync/converters.php`

Three converter functions ensure **DRY principle** (single source of truth for meta↔block transformations):

#### `g4t_convert_hero_meta_to_attrs( int $post_id ): array`

Reads 8 hero meta keys, returns block attributes array.

**Output:**
```php
[
    'lead'          => (string),
    'difficulty'    => (string),
    'servingsValue' => (float|null),
    'servingsUnit'  => (string),
    'timePrep'      => (string),
    'timeCook'      => (string),
    'timeTotal'     => (string),
    'timeSoak'      => (string),
]
```

#### `g4t_convert_ingredients_meta_to_array( int $post_id ): array`

Reads repeater meta keys (`recipe_ingredients`, `recipe_ingredients_{$i}_*`), returns flat array.

**Output:**
```php
[
    [
        'ingredient_term_id' => (int|null),
        'name'               => (string),
        'amount'             => (float|null),
        'unit'               => (string),
    ],
    // ... more ingredients
]
```

#### `g4t_convert_steps_meta_to_array( int $post_id ): array`

Reads nested repeater meta keys (`recipe_steps`, `recipe_steps_{$i}_steps_group_{$j}_step_text`), returns nested array.

**Output:**
```php
[
    [
        ['step' => 'Przygotuj składniki'],
        ['step' => 'Wymieszaj w misce'],
    ],
    [
        ['step' => 'Piecz przez 30 minut'],
    ],
]
```

**Used by:**
- `includes/sync/acf-to-blocks.php` (ACF→blocks builders)
- `includes/admin/reverse-sync.php` (WP All Import compatibility)

### Troubleshooting

#### Issue: ACF edits don't appear in Block Editor

**Cause:** Transient flag still set from previous save  
**Solution:** Wait 60 seconds or clear transient via WP-CLI:
```bash
wp transient delete g4t_acf_save_{POST_ID}
```

#### Issue: Block edits don't appear in ACF fields

**Cause:** Block→meta sync not running (priority conflict or disabled)  
**Solution:** Check `save_post` hooks registered at priority 20

#### Issue: Infinite loop when saving

**Cause:** Re-entry guard bypassed or hook priority conflict  
**Solution:** Check for custom `save_post` hooks interfering with priority system

#### Issue: Empty blocks after ACF save

**Cause:** ACF fields empty or field names mismatched  
**Solution:** Verify ACF field group `name` keys match meta key patterns (`recipe_*`)

#### Issue: Admin notice "You recently edited this recipe in [other UI]"

**Cause:** User switched between ACF ↔ Block Editor within 24 hours  
**Solution:** Expected behavior — notice is informational, safe to ignore if intentional switch

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
