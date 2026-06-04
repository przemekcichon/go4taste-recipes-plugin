# PR: Migration + Post Type Taxonomy Cleanup

**Branch:** feature/migrate-ads-products-contract -> main

**Depends on:** go4taste-ads `feature/use-ads-data-provider` merged to main

## Implementation status

| Stage | Status | Description |
|---|---|---|
| Migration 1: Dry run | ✅ Done | `bin/migrate-ads-products-dryrun.php` run — list verified |
| Migration 2: Migration | ✅ Done | `bin/migrate-ads-products.php` run — 140 recipes migrated |
| Migration 3: Verify | ✅ Done | Query 1 confirmed `tylko_acf_repeater` = 0 |
| Migration 4: Cleanup | ✅ Done | Fallback removed from `view-model-builder.php` |
| Taxonomy 1: Settings | ✅ Done | Two checkboxes added to admin settings page |
| Taxonomy 2: Logic | ✅ Done | Runtime unregister on `init` priority 11 |
| Taxonomy 3: Data cleanup | ✅ Done | Existing Uncategorized removed from 6 posts via WP-CLI |

---

## Part 1: Data Migration — ACF Repeater → `_g4t_a_recipe_bottom_product_ids`

### Problem

140 production recipes had products stored only in the old ACF repeater format (`recipe_ads_products_N_product_id`). The new key (`_g4t_a_recipe_bottom_product_ids`) was empty for them. The frontend worked via a fallback in `view-model-builder.php`, but:

- `go4taste-ads` reads only `_g4t_a_recipe_bottom_product_ids` through its own rendering path — no fallback there
- The fallback in `view-model-builder.php` was a liability for future architecture changes

### What was done

Ran `bin/migrate-ads-products.php` via `wp eval-file` — for each recipe with ACF repeater data and no new key, read product IDs from `recipe_ads_products_{N}_product_id` and wrote them as JSON to `_g4t_a_recipe_bottom_product_ids`.

Result:

```
+---------------+---------------+--------------------+----------------+
| total_recipes | nowy_kontrakt | tylko_acf_repeater | brak_produktow |
+---------------+---------------+--------------------+----------------+
|           144 |           141 |                  0 |              3 |
+---------------+---------------+--------------------+----------------+
```

Old ACF keys left intact as backup.

### Files changed

- `features/recipe-display/view-model-builder.php` — removed `if/else` fallback block; replaced with single call to `g4t_recipe_bottom_strip_get_products_for_post()`

---

## Part 2: Unregister Built-in Taxonomies from Post Type

### Problem

When the recipe source post type is `post`, WordPress automatically assigns the "Uncategorized" category to every post saved without a category. This results in unwanted category assignments on recipe posts. Additionally, the Category and Tag metaboxes clutter the editor for content that doesn't use them.

### Solution

New admin settings section "Post Type Configuration" with two independent checkboxes:

- **Odepnij Kategorie od wpisów** (`unregister_category`) — calls `unregister_taxonomy_for_object_type('category', post_type)` on `init` priority 11; WordPress stops forcing Uncategorized on save; Category metabox disappears from editor
- **Odepnij Tagi od wpisów** (`unregister_post_tag`) — calls `unregister_taxonomy_for_object_type('post_tag', post_type)` on `init` priority 11; Tag metabox disappears from editor

Hook runs at priority 11 because WordPress registers built-in taxonomies in `create_initial_taxonomies()` at `init` priority 0.

The unregister applies to whatever post type is resolved by `g4t_get_source_strategy()` — works for `post`, existing CPT, or dedicated CPT.

### One-off data cleanup

6 production recipes had the Uncategorized term already assigned. Removed via `bin/remove-uncategorized.php` (WP-CLI script, deleted after use).

### Files changed

- `features/admin-settings/settings-page.php` — new section, two `add_settings_field` calls, named callbacks, `g4t_render_unregister_taxonomy_field()`, sanitize support, updated defaults
- `features/admin-settings/settings-logic.php` — `g4t_maybe_unregister_recipe_taxonomies()` function
- `features/admin-settings/index.php` — `add_action('init', 'g4t_maybe_unregister_recipe_taxonomies', 11)`

---

## Merge readiness

All stages done. No regressions expected:
- Migration is idempotent — safe to verify by re-running dry run script
- Old ACF repeater data untouched — can be used for rollback verification
- Taxonomy unregister is opt-in via settings — disabled by default, no change for installs that don't enable it
