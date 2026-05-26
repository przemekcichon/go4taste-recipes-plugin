---
applyTo: "**"
---

# GO4TASTE Recipes Plugin — GitHub Copilot Instructions

This file is automatically read by GitHub Copilot for every file in this repository.

---

## What this plugin is

**GO4TASTE Recipes Plugin** is a WordPress plugin that provides a structured recipe data model using frontend creator + ACF/meta storage. It supports a dedicated `recipe` CPT or configured source post type, registers ingredient and classification taxonomies, and exposes a normalized view model to Timber-based theme templates via context filters.

---

## Current token values

| Token role | Value |
|---|---|
| Plugin slug | `go4taste-recipes-plugin` |
| PHP prefix | `go4taste_recipes` |
| Short function prefix | `g4t_` |
| Short constant prefix | `G4T_` |
| Class name | `Go4Taste_Recipes_Plugin` |
| Filter namespace | `go4taste/recipes/` |
| CPT key | `recipe` |
| Primary taxonomy | `recipe_ingredient` |
| Option key | `go4taste_recipes_options` |
| Text domain | `go4taste-recipes-plugin` |

---

## Architecture — which file owns what

```
includes/class-go4taste-recipes-plugin.php   Orchestrator: constants, options cache, proxy methods only
includes/registration/post-types.php         CPT registration + Gutenberg disable for recipe post type
includes/registration/taxonomies.php         Taxonomy registration + G4T_*_TAXONOMY constants
includes/acf/field-groups.php                All acf_add_local_field_group() calls
includes/front/recipe-creator.php            Frontend creator save/load + state→meta mapping
includes/front/recipe-creator-helpers.php    Creator helpers aligned to resolve_source_strategy()
includes/sync/meta-sync.php                  Repeater/taxonomy utilities
includes/view-model/normalizers.php          Pure data normalisation helpers
includes/view-model/builder.php              View model builder (meta-first)
includes/admin/settings-page.php             Admin settings page
includes/timber/context-filters.php          Timber context filters + debug helpers
```

### Hard constraints

- Creator save → recipe meta writes only in `includes/front/recipe-creator.php`
- Repeater/taxonomy sync helpers in `includes/sync/meta-sync.php`
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

## How to add a new recipe field

1. `includes/acf/field-groups.php` — add/update ACF field definition
2. `includes/front/recipe-creator.php` — map creator state save/load for the field
3. `includes/view-model/builder.php` — include field in recipe view model mapping
4. `SCHEMA_CONTRACT_V2.md` — document shape changes when applicable

## How to add a taxonomy

See `.github/prompts/add-taxonomy.prompt.md`.

1. `includes/registration/taxonomies.php` — add entry + PHP constant
2. If term-synced: add `g4t_ensure_*_term()` in `sync/meta-sync.php` + proxy + action in orchestrator
3. `SCHEMA_CONTRACT_V2.md` — document
