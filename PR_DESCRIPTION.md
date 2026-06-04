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
| Prep-time 1: Backfill script | ✅ Done | `bin/backfill-prep-time-terms.php` run — existing recipes backfilled |
| Prep-time 2: Auto-select in creator | ✅ Done | `creator.js` auto-selects term on time field change |

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

## Part 3: Backfill prep-time taxonomy from `recipe_time_total`

### Problem

Recipes created before the `recipe_prep_time_range` taxonomy was introduced have no term assigned. The FacetWP "Czas przygotowania" filter shows no results for them. The fix is a one-time WP-CLI script that reads `recipe_time_total` and assigns the matching term.

### Known before implementation

| Fact | Source |
|---|---|
| Field: `recipe_time_total` stores integer minutes as a plain string (`"35"`) | `creator-form.php:1321` — `g4t_recipe_creator_format_minutes()` returns `(string) $total_minutes` |
| Taxonomy slug: `recipe_prep_time_range` (`G4T_PREP_TIME_TAXONOMY`) | `recipe-taxonomies.php:19` |
| Single-select: max 1 term per recipe | `creator-meta-sync.php:212` |
| Post type from: `g4t_get_source_strategy()['post_type']` | plugin source strategy |
| Skip if already has a term: yes — idempotent backfill only | design decision |
| Skip if `recipe_time_total` empty: yes — log to output | design decision |

### Mapping: minutes → term

| Zakres | Slug | term_id |
|---|---|---|
| ≤ 15 min | `do-15-min` | 19 |
| 16–30 min | `do-30-min` | 980 |
| 31–45 min | `do-45-min` | 981 |
| > 45 min | `powyzej-45-min` | 11 |

### Script (`bin/backfill-prep-time-terms.php`)

```
# dry run (domyślnie):
wp eval-file wp-content/plugins/go4taste-recipes-plugin/bin/backfill-prep-time-terms.php

# realny run:
wp eval-file wp-content/plugins/go4taste-recipes-plugin/bin/backfill-prep-time-terms.php run
```

Logika: dla każdego przepisu bez termu — czyta `recipe_time_total`, mapuje minuty na zakres, wywołuje `wp_set_object_terms()`. Pomija przepisy z już przypisanym termem (idempotentny). Wypisuje podsumowanie: `Przypisano / Pominięto / Brak czasu / Błędy`.

Zabezpieczenia: `WP_Query` z `no_found_rows + update_*_cache = false`, `wp_get_object_terms()` (nie cached), weryfikacja slugów na starcie.

### Auto-select w kreatorze

**`features/frontend-creator/creator.js`**

Dodano `getAutoTermIdForTotalMinutes(totalMinutes)` — mapuje sumę minut na slug termu i zwraca `termId` z `window.PREP_TIME_TAXONOMY`. `updateTotalTime()` wywołuje tę funkcję po każdej zmianie pola czasu i aktualizuje `state.data.prepTimeTermIds`, po czym wywołuje `renderTaxonomySelectors()`. Selector prep-time w formularzu aktualizuje się w czasie rzeczywistym — zawsze nadpisuje poprzedni wybór. Warunek `state.data.prepTimeTermIds[0] !== termId` zapobiega zbędnemu re-renderowi gdy zakres się nie zmienił.

### Verification query (po uruchomieniu backfill)

```sql
SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN tt.term_id IS NOT NULL THEN 1 ELSE 0 END) AS has_prep_time_term,
    SUM(CASE WHEN tt.term_id IS NULL THEN 1 ELSE 0 END) AS missing_prep_time_term
FROM wp_posts p
LEFT JOIN wp_term_relationships tr ON tr.object_id = p.ID
LEFT JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
    AND tt.taxonomy = 'recipe_prep_time_range'
WHERE p.post_type = 'post'
AND p.post_status = 'publish';
```

Oczekiwany wynik: `missing_prep_time_term = 0` (lub równy liczbie przepisów bez `recipe_time_total`).

---

## Merge readiness

All stages done. No regressions expected:
- Migration is idempotent — safe to verify by re-running dry run script
- Old ACF repeater data untouched — can be used for rollback verification
- Taxonomy unregister is opt-in via settings — disabled by default, no change for installs that don't enable it
