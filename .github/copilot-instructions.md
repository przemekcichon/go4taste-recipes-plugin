---
applyTo: "**"
---

# GO4TASTE Recipes Plugin — GitHub Copilot Instructions

This file is automatically read by GitHub Copilot for every file in this repository.

---

## What this plugin is

**GO4TASTE Recipes Plugin** is a WordPress plugin that provides a structured block-editor form for recipe data. It registers ACF Blocks, a `recipe` CPT, ingredient and classification taxonomies, and exposes a normalized view model to Timber-based theme templates via context filters.

---

## Current token values

| Token role | Value |
|---|---|
| Plugin slug | `go4taste-recipes-plugin` |
| PHP prefix | `go4taste_recipes` |
| Short function prefix | `g4t_` |
| Short constant prefix | `G4T_` |
| Class name | `Go4Taste_Recipes_Plugin` |
| Block namespace | `go4taste-recipes/` |
| Filter namespace | `go4taste/recipes/` |
| CPT key | `recipe` |
| Primary taxonomy | `recipe_ingredient` |
| Option key | `go4taste_recipes_options` |
| Text domain | `go4taste-recipes-plugin` |

---

## Architecture — which file owns what

```
includes/class-go4taste-recipes-plugin.php   Orchestrator: constants, options cache, proxy methods only
includes/registration/post-types.php         CPT registration + block template lock
includes/registration/taxonomies.php         Taxonomy registration + G4T_*_TAXONOMY constants
includes/registration/blocks.php             G4T_RECIPE_BLOCK_NAMES, block type registration, editor script
includes/acf/field-groups.php                All acf_add_local_field_group() calls
includes/sync/meta-sync.php                  save_post → block parse → post_meta
includes/sync/acf-to-blocks.php              acf/save_post → rebuild blocks in post_content
includes/sync/converters.php                 post_meta → block attributes conversion helpers
includes/sync/detection.php                  Save source detection + UI tracking
includes/admin/reverse-sync.php              Meta→blocks batch conversion admin action
includes/view-model/normalizers.php          Pure data normalisation helpers
includes/view-model/builder.php              View model builder + ACF fallback
includes/admin/settings-page.php             Admin settings page
includes/timber/context-filters.php          Timber context filters + debug helpers
assets/js/editor-blocks-new.js               Block editor UI (no build step required)
assets/css/editor-blocks.css                 Editor-only styles
```

### Hard constraints

- `update_post_meta()` only in `includes/sync/meta-sync.php`
- `register_block_type()` only in `includes/registration/blocks.php`
- `acf_add_local_field_group()` only in `includes/acf/field-groups.php`
- `register_taxonomy()` only in `includes/registration/taxonomies.php`
- `add_action()` / `add_filter()` only in `includes/class-go4taste-recipes-plugin.php`

---

## Coding conventions

- PHP 8.0+; typed parameters and return types on new functions.
- All user-facing strings: `__( 'text', 'go4taste-recipes-plugin' )`.
- Input sanitisation: `sanitize_text_field()`, `sanitize_key()`, `esc_url_raw()`.
- Output escaping: `esc_html()`, `esc_attr()`, `esc_url()`.
- No anonymous functions in `add_action()` / `add_filter()` hooks — use proxy methods on the class.
- No logic in `class-go4taste-recipes-plugin.php` — orchestrator only.

---

## How to add a new block

See `.github/prompts/add-block.prompt.md` for the full agent prompt.

1. `includes/registration/blocks.php` — add to `G4T_RECIPE_BLOCK_NAMES`
2. `assets/js/editor-blocks-new.js` — add `registerBlockType()` call
3. `includes/acf/field-groups.php` — add ACF group inside `g4t_register_acf_fields()`
4. `includes/sync/meta-sync.php` — add case in `g4t_get_recipe_blocks_data()`, payload key in `g4t_sync_recipe_meta_from_blocks()`
5. `includes/view-model/builder.php` — add to view model, ACF data, and merge keys
6. `includes/registration/post-types.php` — add to template in `g4t_apply_recipe_block_template()`
7. `SCHEMA_CONTRACT_V1.md` — document

## How to add a taxonomy

See `.github/prompts/add-taxonomy.prompt.md`.

1. `includes/registration/taxonomies.php` — add entry + PHP constant
2. If term-synced: add `g4t_ensure_*_term()` in `sync/meta-sync.php` + proxy + action in orchestrator
3. `SCHEMA_CONTRACT_V1.md` — document
