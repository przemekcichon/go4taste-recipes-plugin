# SCHEMA_CONTRACT_V3 — Ingredient Sections

> **Status:** Active (feature/ingredient-sections branch)
> **Supersedes:** SCHEMA_CONTRACT_V2.md (archived, flat `ingredients` array removed from view model)

---

## What changed vs V2

| Field | V2 | V3 |
|---|---|---|
| `recipe.ingredients` | `array<IngredientItem>` | **Removed** |
| `recipe.ingredient_sections` | — | `array<IngredientSection>` (**New**, always present) |
| `recipe_ingredients_mode` meta | — | `"single"` \| `"sections"` (new meta key) |
| `recipe_ingredient_sections_*` meta | — | Nested repeater meta keys (new) |

All other V2 fields are unchanged.

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

## View model shape (returned by `g4t_build_single_recipe_view_model()`)

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
    // sections-mode: 1..N sections, each title is a non-empty string
  ],
  'ingredients_photo_url' => string,  // unchanged
]
```

### Invariants

- `ingredient_sections` is **always present** and always an array.
- Single-mode recipes (mode = `"single"` or no mode stored): exactly **1 section** with `title = null`.
- Sections-mode recipes: 1 or more sections, each with a string `title`.
- A section with zero valid `items` may appear only when data is malformed; normalizer skips empty-name items.

---

## Backward compatibility

- All existing recipes have no `recipe_ingredients_mode` in meta → treated as `"single"` → wrapped in one unnamed section.
- No data migration required.
- Twig templates **must** use `recipe.ingredient_sections` (not `recipe.ingredients`).

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
