# PR: Ingredient Sections — Grouped Ingredients Support

**Branch:** `feature/ingredient-sections` → `main`

**Affects repos:** `go4taste-recipes-plugin` + `go4taste-recipes-theme`

---

## Goal

Add support for dividing recipe ingredients into named sections (e.g., "Ciasto" + "Farsz") while keeping the existing flat-list approach as the default. Zero migration required for the existing ~130 recipes.

---

## Design decisions

| Decision | Choice | Reason |
|---|---|---|
| Old repeater | Kept intact | Zero migration for ~130 existing recipes |
| New structure | `recipe_ingredient_sections` nested repeater | ACF Pro supports nested repeaters natively; clean separation |
| Mode toggle | `recipe_ingredients_mode` (`single` \| `sections`) | One source of truth, explicit intent stored in data |
| View model output | Always `ingredient_sections[]` | One Twig template, no branching — single-mode wraps in one unnamed section |
| Single-mode wrapping | Flat list wrapped as `[['title' => null, 'items' => [...]]]` | Unified shape regardless of mode |
| Frontend creator | Both modes supported | Redaktorzy only access creator, not wp-admin |
| Old `ingredients` key | Removed from view model | Replaced by `ingredient_sections`; template updated accordingly |

---

## New meta keys

```
# Mode toggle (new field)
recipe_ingredients_mode                         = "single" | "sections"   (default: "single")

# Sections repeater (new — only written when mode = "sections")
recipe_ingredient_sections                      = N   (section count)
recipe_ingredient_sections_0_title              = "Ciasto"
recipe_ingredient_sections_0_items              = M   (item count in section 0)
recipe_ingredient_sections_0_items_0_ingredient_term_id = 5
recipe_ingredient_sections_0_items_0_name       = "Mąka"
recipe_ingredient_sections_0_items_0_amount     = "500"
recipe_ingredient_sections_0_items_0_unit       = "g"
recipe_ingredient_sections_0_items_0_ingredient_note = ""
recipe_ingredient_sections_1_title              = "Farsz"
recipe_ingredient_sections_1_items              = K
...

# Existing keys — unchanged, still used when mode = "single"
recipe_ingredients                              = N   (flat count)
recipe_ingredients_0_name                       = ...
recipe_ingredients_0_amount                     = ...
recipe_ingredients_0_unit                       = ...
recipe_ingredients_0_ingredient_term_id         = ...
recipe_ingredients_0_ingredient_note            = ...
recipe_ingredients_photo_url                    = ...   (used in both modes)
```

---

## New view model shape (SCHEMA_CONTRACT_V3)

Replaces the existing `ingredients` flat array. Always present, always same structure:

```php
'ingredient_sections' => [
  [
    'title' => null | string,   // null = no heading (single-mode), string = section title
    'items' => [
      [
        'ingredient_term_id' => int|null,
        'name'               => string,    // required, non-empty
        'amount'             => string|null,
        'unit'               => string,
        'ingredient_note'    => string,
      ],
      // ...
    ],
  ],
  // single-mode: exactly 1 section with title = null
  // sections-mode: 1..N sections each with non-empty string title
]
```

---

## Implementation stages

| Stage | Status | Description |
|---|---|---|
| Schema 1: SCHEMA_CONTRACT_V3 | ✅ Done | Document new meta keys, new view model shape, note deprecation of `ingredients` |
| ACF 1: Mode toggle field | ✅ Done | `recipe_ingredients_mode` radio field added to `acf-fields.php` (group_recipe_ingredients) |
| ACF 2: Sections repeater | ✅ Done | `recipe_ingredient_sections` nested repeater with `section_title` + `section_items` sub-repeater |
| Plugin 1: Normalizer | ✅ Done | New `g4t_normalize_ingredient_sections()` in `normalizers.php` |
| Plugin 2: View model builder | ✅ Done | Read mode toggle, branch to correct loader, always output `ingredient_sections` |
| Plugin 3: Meta sync — read | ✅ Done | `g4t_load_ingredient_sections_meta()` in `creator-meta-sync.php` |
| Plugin 4: Meta sync — write | ✅ Done | `g4t_sync_ingredient_sections_meta()` + taxonomy term sync for sections items |
| Creator 1: State shape | ✅ Done | Add `ingredientsMode` + `ingredientSections` to `state.data` in `creator.js` |
| Creator 2: Mode selector UI | ✅ Done | UI toggle on ingredient screen: "Jedna lista składników / Kilka sekcji" |
| Creator 3: Sections UI | ✅ Done | Add/remove/rename sections; move-up/down sections; ingredient rows within each section |
| Creator 4: Payload builder | ✅ Done | `buildAcfPayload()` — emit mode field + correct repeater based on active mode |
| Creator 5: Edit-mode load | ✅ Done | Hydrate `ingredientsMode` + `ingredientSections` from `initialState` on edit |
| Creator 6: Validation | ✅ Done | ≥1 section with ≥1 ingredient (name + amount) in sections mode |
| Theme 1: Template | ✅ Done | Replace `recipe.ingredients` loop with `recipe.ingredient_sections` loop in Twig |
| Theme 2: Styles | ✅ Done | `.ingredients-section-title` styles in theme `single-recipe.css` (see theme PR) |
| Migration: Manual | 📋 Post-merge | ~5 recipes converted to sections mode manually via creator — no code changes needed |

---

## Part 1: SCHEMA_CONTRACT_V3

New file `SCHEMA_CONTRACT_V3.md` in plugin root.

Key changes vs V2:
- `ingredients` flat array → **deprecated, removed**
- New: `ingredient_sections[]` — unified array, always present
- New meta key: `recipe_ingredients_mode`
- New meta key group: `recipe_ingredient_sections_*`

---

## Part 2: ACF Fields (`features/frontend-creator/acf-fields.php`)

### Mode toggle

```php
[
  'key'           => 'field_recipe_ingredients_mode',
  'name'          => 'recipe_ingredients_mode',
  'label'         => 'Tryb składników',
  'type'          => 'radio',
  'choices'       => [ 'single' => 'Jedna lista', 'sections' => 'Kilka sekcji' ],
  'default_value' => 'single',
  'layout'        => 'horizontal',
]
```

### Sections nested repeater

```php
[
  'key'        => 'field_recipe_ingredient_sections',
  'name'       => 'recipe_ingredient_sections',
  'label'      => 'Sekcje składników',
  'type'       => 'repeater',
  'layout'     => 'block',
  'sub_fields' => [
    // section_title (text)
    // section_items (repeater — same sub-fields as existing recipe_ingredients:
    //   ingredient_term_id, name, amount, unit, ingredient_note)
  ],
]
```

Both field groups live in `group_recipe_ingredients`. The sections repeater is conditionally shown in ACF UI when mode = "sections" (ACF conditional logic).

---

## Part 3: Plugin — Normalizer (`features/recipe-display/normalizers.php`)

### New function

```php
/**
 * Normalizes raw ACF sections repeater data into the unified ingredient_sections shape.
 * For single-mode: wraps existing flat g4t_normalize_ingredients_list() output.
 *
 * @param string $mode   'single' | 'sections'
 * @param array  $data   Raw ACF field value for the active repeater
 * @return array         ingredient_sections[]
 */
function g4t_normalize_ingredient_sections( string $mode, array $data ): array
```

- `mode = 'single'` → calls existing `g4t_normalize_ingredients_list($data)`, wraps in `[['title' => null, 'items' => [...]]]`
- `mode = 'sections'` → iterates sections, normalizes each `section_items` via `g4t_normalize_ingredients_list()`, returns `[['title' => string, 'items' => [...]]]`
- Skips sections with empty title or zero valid items

---

## Part 4: Plugin — View Model Builder (`features/recipe-display/view-model-builder.php`)

`g4t_build_single_recipe_view_model()` updated:

1. Read `recipe_ingredients_mode` (default `'single'` if missing — backward compat for all existing recipes)
2. Branch:
   - `single` → load `recipe_ingredients` repeater (existing path)
   - `sections` → load `recipe_ingredient_sections` repeater (new path)
3. Pass both `$mode` and raw data to `g4t_normalize_ingredient_sections()`
4. Write result to `ingredient_sections` key
5. Remove old `ingredients` key from returned array

Backward compat: any recipe with no `recipe_ingredients_mode` stored treats as `'single'` → zero behavior change for all existing recipes.

---

## Part 5: Plugin — Meta Sync (`features/frontend-creator/creator-meta-sync.php`)

### Read (for edit mode in creator)

New `g4t_load_ingredient_sections_meta(int $post_id, int $section_count): array`

Mirrors existing `g4t_load_ingredients_repeater_meta()` but reads the nested structure:
```
recipe_ingredient_sections_{i}_title
recipe_ingredient_sections_{i}_items       (count)
recipe_ingredient_sections_{i}_items_{j}_* (per-item fields)
```

### Write

New `g4t_sync_ingredient_sections_meta(int $post_id, array $sections): void`

- Deletes old `recipe_ingredient_sections_*` keys
- Writes new nested structure to post_meta
- Collects all ingredient term IDs across ALL sections
- Calls `wp_set_object_terms()` with merged term list (single taxonomy sync call)

### Mode toggle

`g4t_sync_ingredients_mode(int $post_id, string $mode): void` — writes `recipe_ingredients_mode`.

Existing `g4t_sync_ingredients_repeater_meta()` unchanged — still called when mode = `'single'`.

---

## Part 6: Frontend Creator (`features/frontend-creator/creator.js`)

### State shape additions

```javascript
state.data.ingredientsMode = 'single';          // 'single' | 'sections'
state.data.ingredientSections = [               // only used when mode = 'sections'
  {
    id: 'sec-xxxx',
    title: '',
    items: [
      { id: 'ing-xxxx', name: '', termId: null, amount: '', unit: '', hint: '' }
    ]
  }
];
// Existing state.data.ingredients unchanged — used when mode = 'single'
```

### Mode selector UI (Screen 10, above ingredient list)

Radio/toggle: "Jedna lista składników" | "Kilka sekcji (np. ciasto i farsz)"

Switching mode:
- `single → sections`: auto-creates Section 1 pre-populated with current `state.data.ingredients`
- `sections → single`: flattens all section items into `state.data.ingredients`, confirms if >1 section

### Sections UI (mode = 'sections')

- Render each section as a titled block with its own ingredient rows table
- "Dodaj sekcję" button appends new empty section
- Section title: inline text input, required (non-empty)
- Remove section: only if ≥2 sections; confirms if section has items
- Drag-reorder sections (same mechanism as existing ingredient row reorder)
- Ingredient row UI within each section: identical to existing single-mode rows

### `buildAcfPayload()` additions

```javascript
// Mode field
acf[field_recipe_ingredients_mode] = state.data.ingredientsMode

// Sections (only emitted when mode = 'sections')
acf[field_recipe_ingredient_sections][0][field_section_title] = 'Ciasto'
acf[field_recipe_ingredient_sections][0][field_section_items][0][field_ingredient_name] = 'Mąka'
// ...
```

Existing ingredients payload logic unchanged — still emitted when mode = `'single'`.

### Edit-mode hydration

`window.G4T_RECIPE_CREATOR_DATA.initialState` extended server-side:
```javascript
ingredientsMode: 'single' | 'sections',
ingredientSections: [ { id, title, items: [...] } ]  // populated for sections mode
```

`creator.js` reads these on init, populates correct state branch.

---

## Part 7: Theme (`go4taste-recipes-theme`)

### Template change

Replace current:
```twig
{% for ingredient in recipe.ingredients %}
  ...
{% endfor %}
```

With:
```twig
{% for section in recipe.ingredient_sections %}
  {% if section.title %}
    <h3 class="ingredients__section-title">{{ section.title }}</h3>
  {% endif %}
  {% for ingredient in section.items %}
    ...
  {% endfor %}
{% endfor %}
```

### Styles

New `.ingredients__section-title` rule — visual weight between recipe heading and ingredient rows. Exact styling TBD during implementation.

---

## Part 8: Manual migration

Identify recipes that need sections (user-driven, ~5 recipes). For each: open in creator, switch mode to "Kilka sekcji", arrange ingredients into sections, save. No script needed.

---

## Merge readiness

✅ Ready to merge — all implementation stages complete. Manual recipe migration (~5 recipes) is a post-merge task done via the creator UI, requires no code changes.
