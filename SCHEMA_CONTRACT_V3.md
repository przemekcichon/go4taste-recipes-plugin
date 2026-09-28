# SCHEMA_CONTRACT_V3 — Ingredient Sections

> **Status:** Active (current contract)
> **Supersedes:** SCHEMA_CONTRACT_V2.md (archived, flat `ingredients` array removed from view model)
> **Last updated:** 2026-09-28 — additive: `breadcrumbs` context key (outside the view model)

---

## Full view model shape (returned by `g4t_build_single_recipe_view_model()`)

Built in `features/recipe-display/view-model-builder.php` and exposed to Timber
as `recipe` by `go4taste/recipes/context/single`. Every key below is **always
present**; "empty" values are `''`, `null` or `[]` as listed, never a missing key.

```php
[
  'id'   => int,                       // post ID
  'hero' => [
    'title'   => string,               // get_the_title()
    'excerpt' => string,               // meta recipe_lead (not post_excerpt); '' when empty
    'image'   => string,               // featured image URL, size 'full'; '' when none
  ],
  'times' => [                         // raw meta strings, '' when empty
    'prep'  => string,                 // recipe_time_prep
    'cook'  => string,                 // recipe_time_cook
    'total' => string,                 // recipe_time_total
    'soak'  => string,                 // recipe_time_soak
  ],
  'difficulty'     => string,          // 'Łatwy' | 'Średni' | 'Trudny' | ''  (translated label)
  'difficulty_key' => string,          // 'easy' | 'medium' | 'hard' | ''
  'servings' => [
    'value' => float|null,             // recipe_servings_value; null when empty or non-numeric
    'unit'  => string,                 // recipe_servings_unit; '' when empty
  ],
  'date' => [
    'label' => string,                 // 'Data dodania' | 'Data aktualizacji'
    'value' => string,                 // wp_date( 'j F Y' ); '' when no post date
  ],
  'ingredient_sections'   => array<IngredientSection>,  // see "Ingredient sections shape" below
  'ingredients_photo_url' => string,   // recipe_ingredients_photo_url; '' when empty
  'steps' => [
    [
      'heading'     => string,         // step_header; 'Krok N' when empty (N = 1-based row index)
      'description' => string,         // step_description; required, rows without it are skipped
      'tip'         => string,         // step_tip; '' when empty
      'imageUrl'    => string,         // step_image_url; '' when none
    ],
    // ...
  ],                                   // [] when no steps
  'tips'   => string,                  // recipe_tips; '' when empty
  'source' => [
    'label' => string,                 // recipe_source_label; '' when empty
    'url'   => string,                 // recipe_source_url; '' when empty
  ],
  'ads' => [
    'bottom_product_ids' => array<int,string>, // legacy ACF repeater recipe_ads_products, unique, ordered
    'products'           => array,     // from go4taste-ads g4t_recipe_bottom_strip_get_products_for_post();
                                       // shape owned by go4taste-ads; [] when the plugin is inactive
    'enabled'            => bool,      // true when 'products' is non-empty
  ],
]
```

### Field notes

- **`times.*`** — stored as entered, no unit normalization: the creator stores
  bare minutes (`"30"`), migrated posts store `"30 min"`. Consumers that need
  minutes must parse (see `g4t_recipe_schema_parse_minutes()` in
  `features/recipe-schema/schema-builder.php`).
- **`difficulty` vs `difficulty_key`** — use `difficulty` for display and
  `difficulty_key` for CSS classes and logic (introduced in V2.1). Legacy values
  (`"łatwy"`, `"latwy"`, …) are normalized to a key; unrecognized values give
  `''` in both fields.
- **`date`** — `"Data aktualizacji"` with the modified date when the post was
  modified after publishing, otherwise `"Data dodania"` with the publish date.
- **`steps[].imageUrl`** — camelCase is historical (block-editor era) and part
  of the contract; renaming it is a non-additive change.
- **`ads.bottom_product_ids` vs `ads.products`** — independent sources:
  `bottom_product_ids` reads the legacy ACF repeater in this plugin, while
  `products` is resolved by go4taste-ads from its own meta
  (`_g4t_a_recipe_bottom_product_ids`, legacy fallback `_g4t_a_recipe_bottom_ps_product_ids`). `enabled` depends on `products` only.

---

## What changed vs V2

| Field | V2 | V3 |
|---|---|---|
| `recipe.ingredients` | `array<IngredientItem>` | **Removed** |
| `recipe.ingredient_sections` | — | `array<IngredientSection>` (**New**, always present) |
| `recipe_ingredients_mode` meta | — | `"single"` \| `"sections"` (new meta key) |
| `recipe_ingredient_sections_*` meta | — | Nested repeater meta keys (new) |

All other V2 fields are unchanged. The full current shape is in "Full view model shape" above.

---

## New meta keys

```
# Mode toggle (default: "single" when key absent — backward compat)
recipe_ingredients_mode                                         string   "single" | "sections"

# Sections nested repeater (only written when mode = "sections")
recipe_ingredient_sections                                      int      section count
recipe_ingredient_sections_{i}_section_title                   string   section heading (e.g. "Ciasto")
recipe_ingredient_sections_{i}_section_items                   int      item count in section i
recipe_ingredient_sections_{i}_section_items_{j}_name          string   ingredient name
recipe_ingredient_sections_{i}_section_items_{j}_amount        string   quantity (preserved as string, e.g. "1/3")
recipe_ingredient_sections_{i}_section_items_{j}_unit          string   unit (e.g. "g", "ml")
recipe_ingredient_sections_{i}_section_items_{j}_ingredient_term_id  int|""  recipe_ingredient term ID
recipe_ingredient_sections_{i}_section_items_{j}_ingredient_note     string  optional tip / substitution note
```

Existing `recipe_ingredients_*` keys unchanged — still written in `single` mode.
`recipe_ingredients_photo_url` unchanged — used in both modes.

---

## Ingredient sections shape (`ingredient_sections`)

```php
[
  'ingredient_sections' => [
    [
      'title' => null | string,    // null = no heading (single-mode wrap); string = section title
      'items' => [
        [
          'ingredient_term_id' => int|null,
          'name'               => string,     // required, non-empty
          'amount'             => string|null, // null when empty; preserved as string ("1/3", "szczypta")
          'unit'               => string,
          'ingredient_note'    => string,
        ],
        // ...
      ],
    ],
    // single-mode: exactly 1 section, title = null
    // sections-mode: 1..N sections, title = string, or null when the section title is empty
  ],
  'ingredients_photo_url' => string,  // unchanged
]
```

### Invariants

- `ingredient_sections` is **always present** and always an array.
- Single-mode recipes (mode = `"single"` or no mode stored): exactly **1 section** with `title = null`.
- Sections-mode recipes: 1 or more sections. `title` is the trimmed section title, or `null` when it is empty but the section has items. Sections with neither a title nor valid items are dropped; when none remain, the result is the single fallback section `{ title: null, items: [] }`.
- A titled section may have zero `items` (empty-name items are skipped).

---

## Backward compatibility

- All existing recipes have no `recipe_ingredients_mode` in meta → treated as `"single"` → wrapped in one unnamed section.
- No data migration required.
- Twig templates **must** use `recipe.ingredient_sections` (not `recipe.ingredients`).

---

## Context keys outside the view model

Additive in V3 (no version bump). Set by `features/breadcrumbs/` on
`go4taste/recipes/context/single` and `go4taste/recipes/context/archive`
(priority 10).

```
breadcrumbs: array<int, {
  text: string,   // plain text, never empty
  url:  string    // absolute URL; always "" for the last item (current page)
}>
```

- Always set on those two contexts and never empty: the trail has at least the current page.
- Source: Yoast SEO breadcrumbs generator (same trail as the `BreadcrumbList` in the schema graph). Without Yoast: `Przepisy` (`home_url('/')`) → current page.
- Theme renders it and escapes `text`/`url` on output.

---

## Creator JS state shape additions

```js
state.data.ingredientsMode     // 'single' | 'sections'
state.data.ingredientSections  // Array<{ id: string, title: string, items: Array<IngItem> }>
// Existing state.data.ingredients unchanged — used only in single mode
```

## ACF field keys (PHP registration)

| Purpose | Key | Name (meta suffix) |
|---|---|---|
| Mode toggle | `field_recipe_ingredients_mode` | `recipe_ingredients_mode` |
| Sections repeater | `field_recipe_ingredient_sections` | `recipe_ingredient_sections` |
| Section title | `field_ingredient_section_title` | `section_title` |
| Section items repeater | `field_ingredient_section_items` | `section_items` |
| Item term | `field_section_item_term_id` | `ingredient_term_id` |
| Item name | `field_section_item_name` | `name` |
| Item amount | `field_section_item_amount` | `amount` |
| Item unit | `field_section_item_unit` | `unit` |
| Item note | `field_section_item_note` | `ingredient_note` |
