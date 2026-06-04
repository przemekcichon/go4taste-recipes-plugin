# PR: Data Migration — ACF Repeater → `_g4t_a_recipe_bottom_product_ids`

**Branch:** feature/migrate-ads-products-contract -> main

**Depends on:** go4taste-ads `feature/use-ads-data-provider` merged to main (provider must be on main before migration runs)

## Implementation status

| Stage | Status | Description |
|---|---|---|
| Stage 1: Dry run | ✅ Done | `migrate-ads-products-dryrun.php` run — list verified |
| Stage 2: Migration | ✅ Done | `migrate-ads-products.php` run — 140 recipes migrated |
| Stage 3: Verify | ✅ Done | Query 1 confirmed `tylko_acf_repeater` = 0 |
| Stage 4: Cleanup | ✅ Done | Fallback path removed from `view-model-builder.php` |

---

## Context

Two previous PRs ship the new product storage contract:

- `feature/banner-extensibility` (go4taste-ads) — PHP foundation: placement registry, payload resolver, `g4t_recipe_bottom_strip_get_products_for_post()`
- `feature/use-ads-data-provider` (both plugins) — JS shared provider; save path via `_g4t_a_recipe_bottom_product_ids`

140 production recipes still have products stored only in the old ACF repeater format (`recipe_ads_products_N_product_id`). The new key (`_g4t_a_recipe_bottom_product_ids`) is empty for them. The frontend works via a fallback in `view-model-builder.php`, but the fallback is a liability.

---

## Problem

- `go4taste-ads` reads only `_g4t_a_recipe_bottom_product_ids` through its own rendering path — no fallback
- The fallback in `view-model-builder.php` can be removed in future architecture changes
- The ACF metabox in wp-admin is the only UI that still writes to the old format

---

## Database state before migration

### Query 1 — overview: how many recipes use which path

```sql
SELECT
  COUNT(DISTINCT p.ID)                                                         AS total_recipes,
  COUNT(DISTINCT CASE WHEN pm_new.meta_value != '' THEN p.ID END)              AS nowy_kontrakt,
  COUNT(DISTINCT CASE WHEN CAST(pm_old.meta_value AS UNSIGNED) > 0
                        AND (pm_new.meta_value IS NULL OR pm_new.meta_value = '')
                       THEN p.ID END)                                          AS tylko_acf_repeater,
  COUNT(DISTINCT CASE WHEN pm_new.meta_value IS NULL OR pm_new.meta_value = ''
                        AND (pm_old.meta_value IS NULL OR pm_old.meta_value = '0' OR pm_old.meta_value = '')
                       THEN p.ID END)                                          AS brak_produktow
FROM wp_posts p
LEFT JOIN wp_postmeta pm_new ON p.ID = pm_new.post_id
  AND pm_new.meta_key = '_g4t_a_recipe_bottom_product_ids'
LEFT JOIN wp_postmeta pm_old ON p.ID = pm_old.post_id
  AND pm_old.meta_key = 'recipe_ads_products'
WHERE p.post_type = 'post'
  AND p.post_status = 'publish';
```

Expected result before migration:

```
+---------------+---------------+--------------------+----------------+
| total_recipes | nowy_kontrakt | tylko_acf_repeater | brak_produktow |
+---------------+---------------+--------------------+----------------+
|           144 |             1 |                140 |            143 |
+---------------+---------------+--------------------+----------------+
```

- **1** recipe has the new contract — post 2299 (test)
- **140** have only the old ACF repeater — production recipes
- These work via fallback but depend on it staying in place

### Query 2 — list of recipes to migrate

```sql
SELECT
  p.ID,
  p.post_title,
  pm_old.meta_value        AS acf_liczba_produktow,
  pm_new.meta_value        AS nowy_kontrakt
FROM wp_posts p
INNER JOIN wp_postmeta pm_old ON p.ID = pm_old.post_id
  AND pm_old.meta_key = 'recipe_ads_products'
  AND CAST(pm_old.meta_value AS UNSIGNED) > 0
LEFT JOIN wp_postmeta pm_new ON p.ID = pm_new.post_id
  AND pm_new.meta_key = '_g4t_a_recipe_bottom_product_ids'
WHERE p.post_type = 'post'
  AND p.post_status = 'publish'
  AND (pm_new.meta_value IS NULL OR pm_new.meta_value = '')
ORDER BY p.ID;
```

### Query 3 — verify a single recipe

```sql
SELECT meta_key, meta_value
FROM wp_postmeta
WHERE post_id = 1635
AND meta_key IN (
  '_g4t_a_recipe_bottom_product_ids',
  'recipe_ads_products',
  'recipe_ads_products_0_product_id',
  'recipe_ads_products_1_product_id',
  'recipe_ads_products_2_product_id'
)
ORDER BY meta_key;
```

---

## Migration scripts

### Dry run (read-only preview)

File: `migrate-ads-products-dryrun.php`
Run: `wp eval-file migrate-ads-products-dryrun.php`

```php
<?php
global $wpdb;

$posts = $wpdb->get_results(
    "SELECT p.ID, p.post_title, pm.meta_value AS count
     FROM wp_posts p
     INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
         AND pm.meta_key = 'recipe_ads_products'
         AND CAST(pm.meta_value AS UNSIGNED) > 0
     LEFT JOIN wp_postmeta pm_new ON p.ID = pm_new.post_id
         AND pm_new.meta_key = '_g4t_a_recipe_bottom_product_ids'
     WHERE p.post_type = 'post'
         AND p.post_status = 'publish'
         AND (pm_new.meta_value IS NULL OR pm_new.meta_value = '')
     ORDER BY p.ID"
);

echo "DRY RUN — brak zapisu do bazy\n\n";

foreach ( $posts as $post ) {
    $ids = [];

    for ( $i = 0; $i < (int) $post->count; $i++ ) {
        $id = get_post_meta( $post->ID, "recipe_ads_products_{$i}_product_id", true );
        if ( $id !== '' && $id !== false ) {
            $ids[] = (string) $id;
        }
    }

    $label = empty( $ids ) ? 'BRAK ID' : implode( ', ', $ids );
    echo "post {$post->ID} ({$post->post_title}): {$label}\n";
}

echo "\nLiczba przepisów do migracji: " . count( $posts ) . "\n";
```

### Migration script (writes to DB)

File: `migrate-ads-products.php`
Run: `wp eval-file migrate-ads-products.php`

```php
<?php
global $wpdb;

$posts = $wpdb->get_results(
    "SELECT p.ID, pm.meta_value AS count
     FROM wp_posts p
     INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
         AND pm.meta_key = 'recipe_ads_products'
         AND CAST(pm.meta_value AS UNSIGNED) > 0
     LEFT JOIN wp_postmeta pm_new ON p.ID = pm_new.post_id
         AND pm_new.meta_key = '_g4t_a_recipe_bottom_product_ids'
     WHERE p.post_type = 'post'
         AND p.post_status = 'publish'
         AND (pm_new.meta_value IS NULL OR pm_new.meta_value = '')"
);

$migrated = 0;
$skipped  = 0;

foreach ( $posts as $post ) {
    $ids = [];

    for ( $i = 0; $i < (int) $post->count; $i++ ) {
        $id = get_post_meta( $post->ID, "recipe_ads_products_{$i}_product_id", true );
        if ( $id !== '' && $id !== false ) {
            $ids[] = (string) $id;
        }
    }

    if ( empty( $ids ) ) {
        echo "SKIP post {$post->ID} — brak product_id w repeaterze\n";
        $skipped++;
        continue;
    }

    update_post_meta( $post->ID, '_g4t_a_recipe_bottom_product_ids', wp_json_encode( $ids ) );
    $migrated++;
    echo "OK post {$post->ID}: " . implode( ', ', $ids ) . "\n";
}

echo "\nGotowe. Zmigrowano: {$migrated}, pominięto: {$skipped}\n";
```

---

## Expected database state after migration

Query 1 should return:

```
+---------------+---------------+--------------------+----------------+
| total_recipes | nowy_kontrakt | tylko_acf_repeater | brak_produktow |
+---------------+---------------+--------------------+----------------+
|           144 |           141 |                  0 |              3 |
+---------------+---------------+--------------------+----------------+
```

Query 2 should return 0 rows.

(3 recipes without products = ACF repeater had `count > 0` but empty `_product_id` rows — skipped by the script.)

---

## Notes

- Scripts are **idempotent** — recipes that already have `_g4t_a_recipe_bottom_product_ids` are skipped by the SQL WHERE clause.
- Old ACF repeater keys (`recipe_ads_products_N_product_id`) are **not deleted** — they remain as a backup.
- Demo IDs (`p1`–`p10`) will pass through the script but are rejected by `g4t_normalize_recipe_bottom_strip_product_ids()` at read time when Algolia is configured — expected behavior.

---

## Stage 4: Remove fallback from view-model-builder.php

After migration is confirmed (Query 1 shows `tylko_acf_repeater = 0`), remove the legacy fallback from `features/recipe-display/view-model-builder.php`:

```php
// Remove this entire block — replace with single call:
if ( function_exists( 'g4t_recipe_bottom_strip_get_products_for_post' ) ) {
    $ads_products = g4t_recipe_bottom_strip_get_products_for_post( $post_id );
    if ( empty( $ads_products ) && ! empty( $ads_product_ids ) ) {
        $ads_products = g4t_fetch_ads_products( $ads_product_ids, $post_id );  // ← remove fallback
    }
} else {
    $ads_products = g4t_fetch_ads_products( $ads_product_ids, $post_id );      // ← remove else branch
}

// Keep only:
$ads_products = g4t_recipe_bottom_strip_get_products_for_post( $post_id );
```

---

## Merge readiness

- Blocked on: go4taste-ads `feature/use-ads-data-provider` merged to main.
- Scripts are ready — run dry run first, verify output, then run migration.
- Stage 4 (fallback removal) can be a follow-up commit on the same branch or a separate tiny PR.
