---
mode: agent
description: "Change or reconfigure the GO4TASTE Recipes Plugin CPT — key, labels, source mode."
---

# Change the Custom Post Type

Ask the developer for:

1. **What to change** — CPT key/labels, source mode, or settings?
2. **New CPT key** (if changing) — `snake_case` ≤ 20 chars
3. **New labels** (if changing) — singular, plural
4. **Source mode** — `post` / `existing_cpt` / `recipes_cpt`

---

## Steps to perform

### 1. `core/domain/recipe-pt.php`

To change the CPT key: update `G4T_RECIPES_POST_TYPE` constant value and update `g4t_register_recipe_post_type()` labels/rewrite slug.

To change admin settings: update post type args (supports, taxonomies, menu position, etc.) in `g4t_register_recipe_post_type()`.

### 2. `core/domain/recipe-taxonomies.php`

Taxonomy functions already read `$plugin->resolve_source_strategy()` — no change needed unless the CPT key is hardcoded anywhere. Verify `G4T_RECIPES_POST_TYPE` is used consistently.

### 3. `features/admin-settings/settings-page.php`

If adding a new source mode: add the new option to `g4t_render_source_mode_field()` and validate it in `g4t_sanitize_options()` alongside `MODE_POST`, `MODE_EXISTING_CPT`, `MODE_RECIPES_CPT`.

If renaming a mode constant: update `Go4Taste_Recipes_Plugin::MODE_RECIPES_CPT` in the class and all references in `g4t_should_use_archive_recipe_template()`.

### 4. `features/recipe-display/timber-context.php`

If the CPT needs different listing behavior: update `g4t_filter_home_query_args()` (posts per page, order). If the archive slug differs from `G4T_RECIPES_POST_TYPE`, update `g4t_should_use_archive_recipe_template()`.

### 5. `SCHEMA_CONTRACT_V2.md`

Update the Admin Settings section: document the active source mode, CPT key, and rewrite slug.

### 6. Tests

- PHPUnit: `g4t_register_recipe_post_type()` registers the CPT with correct args and source_mode handling.
- Integration: CPT appears correctly in wp-admin and theme templates.
