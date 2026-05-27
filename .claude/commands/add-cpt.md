# /add-cpt — Change the CPT or Settings

Ask the developer for the following before doing anything:

1. **What to change** — CPT key/labels, source mode, or settings?
2. **New CPT key** (if changing) — `snake_case` ≤ 20 chars
3. **New labels** (if changing) — singular, plural
4. **Source mode** — `post` / `existing_cpt` / `recipes_cpt`

---

## Execute these steps in order

### Step 1 — `core/domain/recipe-pt.php`

**To change the CPT key**: update the value of `G4T_RECIPES_POST_TYPE` constant and update labels/rewrite slug in `g4t_register_recipe_post_type()`.

**To change visibility/admin settings**: update post type args (supports, taxonomies, etc.) in `g4t_register_recipe_post_type()`.

### Step 2 — `core/domain/recipe-taxonomies.php`

Taxonomy functions already read from `$plugin->resolve_source_strategy()` — no change needed unless you are moving to a new CPT key. If `G4T_RECIPES_POST_TYPE` changes, verify no hardcoded `'recipe'` strings remain in taxonomy registrations.

### Step 3 — `features/admin-settings/settings-page.php`

If adding a new source mode: add the option to `g4t_render_source_mode_field()` and validate it in `g4t_sanitize_options()`.

### Step 4 — `features/recipe-display/timber-context.php`

If the CPT needs different listing query args: update `g4t_filter_home_query_args()`. If the archive slug differs from `G4T_RECIPES_POST_TYPE`, update `g4t_should_use_archive_recipe_template()`.

### Step 5 — `SCHEMA_CONTRACT_V2.md`

Update Admin Settings section: source mode, CPT key, rewrite slug.

### Step 6 — tests

- PHPUnit: `g4t_register_recipe_post_type()` registers with correct args and source_mode handling.
- Integration: CPT appears correctly in wp-admin and theme templates.
