# PR: Creator Algolia Integration — Use Shared Ads Data Provider

**Branch:** feature/use-ads-data-provider -> main

**Depends on:** go4taste-ads `feature/picker-ui-extensibility` (Stage 1: `G4TAdsProvider`) merged to main

## Implementation status

| Stage | Status | Description |
|---|---|---|
| Stage 1: Replace direct algoliasearch init | ✅ Done | `G4TAdsProvider.getInstance()` used; `algoliaIndex` variable removed |
| Stage 2: Remove `normalizeProductRecord()` | ✅ Done | Kept as internal state normalizer; provider hits pass through it (handles both shapes) |
| Stage 3: Replace `algoliaIndex.*` calls | ✅ Done | `provider.search()` and `provider.getById()` in `fetchAlgoliaProducts` and `hydrateSelectedProductsFromAlgolia` |
| Stage 4: Update PHP credential passing | ✅ Done | `appId/apiKey/indexName` removed from `G4T_RECIPE_CREATOR_DATA.adsPicker`; provider owns credentials |
| Stage 5: QA + regression | 🔲 Next | Full creator flow — search, select, save, edit, publish |

---

## Context

The previous branch (`feature/picker-ui-extensibility`, merged) delivered:
- Step 6 Algolia picker in the recipe creator — search, select, save, edit
- Save path via `g4t_recipe_ads_product_ids_json` flat POST field (bypasses ACF field filtering)
- Read path via `g4t_recipe_bottom_strip_get_products_for_post()` (from go4taste-ads)
- Bug fixes: ACF field filtering, new recipe redirect to `post_id=0`, orphaned heartbeat draft

That branch is complete and merge-ready. This PR is a follow-up refactor only — no user-visible behavior changes.

---

## Problem

`creator.js` currently initializes its own Algolia client independently:

```javascript
// creator.js — direct SDK usage
const searchClient = window.algoliasearch(pickerConfig.appId, pickerConfig.apiKey);
algoliaIndex = searchClient.initIndex(pickerConfig.indexName || 'go4taste_product_list');
const response = await algoliaIndex.search(query, { hitsPerPage: 12 });
```

And has its own hit normalization:

```javascript
function normalizeProductRecord(raw) {
  return {
    id:      String(raw.id || raw.objectID || ''),
    name:    raw.name || raw.title || '',
    picture: raw.picture || raw.image || '',
    // ...
  };
}
```

After go4taste-ads ships `G4TAdsProvider`, this is duplication. Both the admin picker and the creator would maintain separate clients, separate normalization, and risk diverging product shapes.

---

## Motivation

- One Algolia client instance per page — no duplicate init.
- One canonical product shape — `NormalizedProduct` from the provider, used by all surfaces.
- Creator data layer shrinks to a thin consumer; all data logic lives in the ads provider.
- Future changes to normalization or API version propagate to all surfaces automatically.

---

## Desired outcome

After this implementation:
1. Creator no longer initializes its own `algoliasearch` client.
2. `normalizeProductRecord()` is deleted from `creator.js`.
3. `algoliaIndex` variable is removed; search and fetch go through `G4TAdsProvider`.
4. PHP passes only what the provider needs (or nothing if provider init is handled by ads plugin exclusively).
5. No visible behavior change for the user — same search results, same save/edit flow.

---

## Architecture

### Stage 1 + 3: Replace SDK calls

```javascript
// Before
const searchClient = window.algoliasearch(pickerConfig.appId, pickerConfig.apiKey);
algoliaIndex = searchClient.initIndex(pickerConfig.indexName);
const response = await algoliaIndex.search(query, { hitsPerPage: 12 });
const products = response.hits.map(normalizeProductRecord);

// After
const provider = window.G4TAdsProvider && window.G4TAdsProvider.getInstance();
if (!provider) return [];
const products = await provider.search(query, { hitsPerPage: 12 });
```

```javascript
// Before — hydrate selected from Algolia
const requests = ids.map(id => algoliaIndex.getObject(id));

// After
const products = await provider.getByIds(ids);
```

### Stage 2: Remove `normalizeProductRecord()`

The function is deleted. Provider returns `NormalizedProduct` directly. Any call site that mapped hits through `normalizeProductRecord()` maps through provider instead.

### Stage 4: PHP credential passing

**go4taste-ads inicjalizuje provider wszędzie** — to jest właściwe rozwiązanie. Ads plugin jest właścicielem credentials i providera; enqueue `algolia-provider.js` z `wp_localize_script` nie tylko na stronach admina ale też na frontendzie gdy plugin jest aktywny. Provider inicjalizuje się sam na `DOMContentLoaded`.

`creator.js` wywołuje wyłącznie `G4TAdsProvider.getInstance()` — nigdy `init()`. Nie zna credentials.

Konsekwencja po stronie ads plugin (mała zmiana w zakresie tego PR): `g4t-ads-provider` script enqueued warunkowo na frontend gdy strona zawiera kreator (np. `is_page_template('recipe-creator')`) lub bezwarunkowo gdy plugin aktywny — do decyzji przy implementacji.

Wynikowa zmiana w recipes plugin: `G4T_RECIPE_CREATOR_DATA.adsPicker.appId/apiKey/indexName` można usunąć z PHP. `adsPicker.enabled` i `adsPicker.hitsPerPage` zostają jeśli kreator nadal potrzebuje własnej konfiguracji UI (np. liczby wyników).

---

## Validation checklist

### Must pass (no regressions)

1. ✅ Step 6 loads Algolia products — same results as before.
2. ✅ Search input filters results in real time.
3. ✅ Selecting products updates `selected-strip` correctly.
4. ✅ Save persists product IDs to `_g4t_a_recipe_bottom_product_ids`.
5. ✅ Edit mode loads saved IDs back into `selected-strip`.
6. ✅ New recipe publishes with correct redirect (post_id not 0).
7. ✅ Heartbeat draft reused on publish.
8. ✅ Title changes save correctly.

### New (provider integration)

9. Single Algolia client init per page — confirm in DevTools Network tab (one Algolia request per search, not two).
10. `normalizeProductRecord` not present in creator.js source.
11. `algoliaIndex` variable not present in creator.js source.

---

## Risks and mitigations

- Risk: `G4TAdsProvider` not available when creator page loads (ads plugin inactive or provider not yet enqueued on frontend).
  Mitigation: guard with `window.G4TAdsProvider && G4TAdsProvider.getInstance()` — falls back to local catalog gracefully, same as current `algoliaIndex = null` path.

- Risk: `NormalizedProduct` shape from provider differs from `normalizeProductRecord()` output.
  Mitigation: compare field names before removing local function; provider shape is source of truth.

- Risk: this PR merges before ads Stage 1 is on main.
  Mitigation: hard dependency — merge order enforced: ads Stage 1 first, this PR second.

---

## Merge readiness

- Blocked on: go4taste-ads `feature/picker-ui-extensibility` Stage 1 (`G4TAdsProvider`) merged to main.
- Planning scope until then. Implementation starts after ads Stage 1 is available.
