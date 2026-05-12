---
mode: agent
description: "Change or reconfigure the GO4TASTE Recipes Plugin CPT — key, labels, block template, source mode."
---

# Change the Custom Post Type

Ask the developer for:

1. **What to change** — CPT key/labels, source mode, block template, or all three?
2. **New CPT key** (if changing) — `snake_case` ≤ 20 chars
3. **New labels** (if changing) — singular, plural
4. **Source mode** — `post` / `existing_cpt` / `recipes_cpt`
5. **Block template changes** — which blocks to add, remove, or reorder?

---

## Steps to perform

### 1. `includes/registration/post-types.php`

To change the CPT key: update `G4T_RECIPES_POST_TYPE` constant value and update `g4t_register_recipe_post_type()` labels/rewrite slug.

To change the block template: update `$args['template']` in `g4t_apply_recipe_block_template()`. Block order in this array defines the visual order in the editor.

```php
$args['template'] = array(
    array( 'go4taste-recipes/hero' ),
    array( 'go4taste-recipes/ingredients' ),
    array( 'go4taste-recipes/steps' ),
    array( 'go4taste-recipes/tips' ),
    array( 'go4taste-recipes/source' ),
    array( 'go4taste-ads/inline-banner' ),
    // add new blocks here
);
$args['template_lock'] = 'contentOnly';
```

### 2. `includes/registration/taxonomies.php`

Taxonomy functions already read `$plugin->resolve_source_strategy()` — no change needed unless the CPT key is hardcoded anywhere. Verify `G4T_RECIPES_POST_TYPE` is used consistently.

### 3. `includes/admin/settings-page.php`

If adding a new source mode: add the new option to `g4t_render_source_mode_field()` and validate it in `g4t_sanitize_options()` alongside `MODE_POST`, `MODE_EXISTING_CPT`, `MODE_RECIPES_CPT`.

If renaming a mode constant: update `Go4Taste_Recipes_Plugin::MODE_RECIPES_CPT` in the class and all references in `g4t_should_use_archive_recipe_template()`.

### 4. `includes/timber/context-filters.php`

If the CPT needs different listing behavior: update `g4t_filter_home_query_args()` (posts per page, order). If the archive slug differs from `G4T_RECIPES_POST_TYPE`, update `g4t_should_use_archive_recipe_template()`.

### 5. `SCHEMA_CONTRACT_V1.md`

Update the Admin Settings section: document the active source mode, CPT key, and rewrite slug.

### 6. Tests

- PHPUnit: `g4t_register_recipe_post_type()` registers the CPT with correct args.
- Integration: block template is applied on the correct post type; `g4t_apply_recipe_block_template()` is a no-op for unrelated post types.
