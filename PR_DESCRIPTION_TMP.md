# TMP: Backfill Migration — ACF Repeater → `_g4t_a_recipe_bottom_product_ids`

> Roboczy dokument do przepisania na właściwy PR po stworzeniu osobnego brancha.

---

## Problem

140 produkcyjnych przepisów ma produkty zapisane wyłącznie w starym formacie ACF repeatera
(`recipe_ads_products_N_product_id`). Nowy kontrakt (`_g4t_a_recipe_bottom_product_ids`) jest
dla nich pusty. Frontend działa przez fallback w `view-model-builder.php`, ale:

- go4taste-ads czyta wyłącznie `_g4t_a_recipe_bottom_product_ids` przez własną ścieżkę renderowania
- fallback w `view-model-builder.php` może zostać usunięty przy przyszłych zmianach architektury
- ACF metabox w wp-admin jest jedynym UI które nadal zapisuje do starego formatu

---

## Stan bazy przed migracją

### Query 1 — przegląd: ile przepisów używa której ścieżki

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

Wynik przed migracją:

```
+---------------+---------------+--------------------+----------------+
| total_recipes | nowy_kontrakt | tylko_acf_repeater | brak_produktow |
+---------------+---------------+--------------------+----------------+
|           144 |             1 |                140 |            143 |
+---------------+---------------+--------------------+----------------+
```

Obraz jest czytelny:

- **144** przepisów łącznie
- **1** ma nowy kontrakt — post 2299 (testowy)
- **140** ma tylko stary ACF repeater — przepisy produkcyjne (np. 1635–2299)
- Przepis 1743 to typowy przykład — 3 produkty (IDs: 91, 219, 66) w starym formacie, zero w nowym

Przepisy z grupy `tylko_acf_repeater` działają na froncie przez fallback:

```
g4t_recipe_bottom_strip_get_products_for_post()  →  puste (_g4t_a_recipe_bottom_product_ids brak)
    ↓ fallback w view-model-builder.php
g4t_fetch_ads_products( $ads_product_ids )        →  czyta recipe_ads_products_N_product_id  ✓
```

Działają, ale zależą od fallbacku który może zniknąć przy przyszłej zmianie architektury.

### Query 2 — lista przepisów do migracji

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

### Query 3 — weryfikacja konkretnego przepisu

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

## Migracja

### Skrypt PHP (WP-CLI)

```php
<?php
// migrate-ads-products.php
// Uruchomienie: wp eval-file migrate-ads-products.php

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

### Dry run (tylko podgląd, bez zapisu)

```php
<?php
// migrate-ads-products-dryrun.php
// Uruchomienie: wp eval-file migrate-ads-products-dryrun.php

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

---

## Stan bazy po migracji

Query 1 powinno zwrócić:

```
+---------------+---------------+--------------------+----------------+
| total_recipes | nowy_kontrakt | tylko_acf_repeater | brak_produktow |
+---------------+---------------+--------------------+----------------+
|           144 |           141 |                  0 |              3 |
+---------------+---------------+--------------------+----------------+
```

(3 przepisy bez produktów to przepisy gdzie ACF repeater miał count > 0 ale puste `_product_id` — zostaną pominięte przez skrypt.)

Query 2 po migracji powinno zwrócić 0 wierszy.

---

## Uwagi

- Skrypt jest **idempotentny** — pomija przepisy które już mają `_g4t_a_recipe_bottom_product_ids`.
- Stary ACF repeater (`recipe_ads_products_N_product_id`) nie jest usuwany — pozostaje jako backup.
- Demo IDs (`p1`–`p10`) przejdą przez skrypt ale zostaną odrzucone przez `g4t_normalize_recipe_bottom_strip_product_ids()` na poziomie odczytu, jeśli Algolia jest skonfigurowana.
- Jeśli któryś przepis ma demo IDs w ACF repeaterze (jak post 2299 z `p1`, `p2`) — po migracji nowy kontrakt będzie miał `[]` (puste po normalizacji). To jest oczekiwane zachowanie.

---

## Scope brancha i PR

- Nowy branch: `feature/migrate-ads-products-contract`
- Zakres: tylko skrypt migracyjny + ewentualna aktualizacja SCHEMA_CONTRACT_V2.md
- Bez zmian w logice wtyczki — migracja jest jednorazowym data fix
