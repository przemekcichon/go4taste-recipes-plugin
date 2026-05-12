# /add-cpt — Change the CPT or Block Template

Ask the developer for the following before doing anything:

1. **What to change** — CPT key/labels, source mode, block template, or all three?
2. **New CPT key** (if changing) — `snake_case` ≤ 20 chars
3. **New labels** (if changing) — singular, plural
4. **Source mode** — `post` / `existing_cpt` / `recipes_cpt`
5. **Block template changes** — which blocks to add, remove, or reorder?

---

## Execute these steps in order

### Step 1 — `includes/registration/post-types.php`

**To change the CPT key**: update the value of `G4T_RECIPES_POST_TYPE` constant and update labels/rewrite slug in `g4t_register_recipe_post_type()`.

**To change the block template**: update `$args['template']` in `g4t_apply_recipe_block_template()`. Order defines editor layout:

```php
$args['template'] = array(
    array( 'go4taste-recipes/hero' ),
    array( 'go4taste-recipes/ingredients' ),
    array( 'go4taste-recipes/steps' ),
    array( 'go4taste-recipes/tips' ),
    array( 'go4taste-recipes/source' ),
    array( 'go4taste-ads/inline-banner' ),
);
$args['template_lock'] = 'contentOnly';
```

Use `'contentOnly'` to prevent add/remove/reorder. Use `'insert'` to allow additions. Use `false` for no lock.

### Step 2 — `includes/registration/taxonomies.php`

Taxonomy functions already read from `$plugin->resolve_source_strategy()` — no change needed unless you are moving to a new CPT key. If `G4T_RECIPES_POST_TYPE` changes, verify no hardcoded `'recipe'` strings remain in taxonomy registrations.

### Step 3 — `includes/admin/settings-page.php`

If adding a new source mode: add the option to `g4t_render_source_mode_field()` and validate it in `g4t_sanitize_options()`.

### Step 4 — `includes/timber/context-filters.php`

If the CPT needs different listing query args: update `g4t_filter_home_query_args()`. If the archive slug differs from `G4T_RECIPES_POST_TYPE`, update `g4t_should_use_archive_recipe_template()`.

### Step 5 — `SCHEMA_CONTRACT_V1.md`

Update Admin Settings section: source mode, CPT key, rewrite slug.

### Step 6 — tests

- PHPUnit: `g4t_register_recipe_post_type()` registers with correct args.
- Integration: block template applied to correct post type; `g4t_apply_recipe_block_template()` is no-op for other types.
