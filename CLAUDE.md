# GO4TASTE Recipes Plugin — Claude Code Context

This file is loaded automatically by Claude Code for every session in this directory.

---

## What this plugin is

**GO4TASTE Recipes Plugin** is a WordPress plugin that provides a structured recipe data model using frontend creator + ACF/meta storage. It supports a dedicated CPT (`recipe`) or configured source post type, registers ingredient and classification taxonomies, and exposes a normalized view model to Timber-based theme templates via context filters.

---

## Current token values

| Token role | Value |
|---|---|
| Plugin slug | `go4taste-recipes-plugin` |
| PHP prefix | `go4taste_recipes` |
| Short function prefix | `g4t_` |
| Short CSS/handle prefix | `g4t-` |
| Short constant prefix | `G4T_` |
| Class name | `Go4Taste_Recipes_Plugin` |
| Filter namespace | `go4taste/recipes/` |
| CPT key | `recipe` |
| Primary taxonomy | `recipe_ingredient` |
| Class. taxonomy 1 | `recipe_meal_type` |
| Class. taxonomy 2 | `recipe_prep_time_range` |
| Class. taxonomy 3 | `recipe_feature` |
| Option key | `go4taste_recipes_options` |
| Text domain | `go4taste-recipes-plugin` |
| Debug query param | `?g4t_recipe_debug` |

---

## Architecture — which file owns what

```
includes/class-go4taste-recipes-plugin.php   Orchestrator: constants, options cache, resolve_source_strategy(), proxy methods
includes/registration/post-types.php         g4t_register_recipe_post_type(), g4t_disable_block_editor_for_recipe_post_type()
includes/registration/taxonomies.php         g4t_register_ingredient_taxonomy(), g4t_register_flat_taxonomies() + taxonomy constants
includes/acf/field-groups.php                g4t_register_acf_fields() — all acf_add_local_field_group() calls
includes/front/recipe-creator.php            Frontend creator save/load + g4t_creator_state_to_meta()
includes/front/recipe-creator-helpers.php    Creator helpers aligned to resolve_source_strategy()
includes/sync/meta-sync.php                  Repeater/taxonomy utilities
includes/view-model/normalizers.php          g4t_normalize_ingredients_list(), steps, images, g4t_has_value()
includes/view-model/builder.php              g4t_build_single_recipe_view_model(), g4t_get_recipe_acf_data() (meta-only runtime)
includes/admin/settings-page.php             g4t_register_settings(), g4t_render_options_page(), g4t_sanitize_options()
includes/timber/context-filters.php          g4t_filter_single_context(), archive, home, debug helpers
```

### Hard constraints — never violate these

| Concern | Only in this file |
|---|---|
| Creator save → recipe meta writes | `includes/front/recipe-creator.php` |
| Repeater/taxonomy sync helpers | `includes/sync/meta-sync.php` |
| `acf_add_local_field_group()` | `includes/acf/field-groups.php` |
| `register_taxonomy()` | `includes/registration/taxonomies.php` |
| `add_action()` / `add_filter()` | `includes/class-go4taste-recipes-plugin.php` |
| View model shape | `includes/view-model/builder.php` |
| Data normalisation | `includes/view-model/normalizers.php` |
| Timber context enrichment | `includes/timber/context-filters.php` |

---

## Editing Model

- Primary authoring surface: frontend recipe creator.
- Backend surface: ACF metaboxes for the configured recipe post type.
- Gutenberg: disabled for configured recipe post type.
- Source of truth: ACF/meta.
- Legacy block data: ignored by runtime rendering.

---

## Coding conventions

- PHP 8.0+; prefer typed parameters and return types on new functions.
- All user-facing strings: `__( 'text', 'go4taste-recipes-plugin' )`.
- Input sanitisation: `sanitize_text_field()`, `sanitize_key()`, `esc_url_raw()`.
- Output escaping: `esc_html()`, `esc_attr()`, `esc_url()`.
- No anonymous functions in `add_action()` / `add_filter()` calls — use proxy methods on the class.
- No logic in the orchestrator class — only hook registration and `resolve_source_strategy()`.
- New hook registrations go in `__construct()` as `add_action('hook', array($this, 'proxy_method'))`.

---

## How to add a new recipe field

1. `includes/acf/field-groups.php` — add/update ACF field definition.
2. `includes/front/recipe-creator.php` — map creator state ↔ meta save/load.
3. `includes/view-model/builder.php` — include field in view model mapping.
4. `SCHEMA_CONTRACT_V2.md` — update contract docs if view-model shape changes.

## How to add a taxonomy

See `.claude/commands/add-taxonomy.md`. Summary:

1. `includes/registration/taxonomies.php` — add entry + PHP constant
2. If term-synced: `includes/sync/meta-sync.php` + `includes/class-go4taste-recipes-plugin.php`
3. `SCHEMA_CONTRACT_V1.md` — document

---

## Context filters

```php
apply_filters( 'go4taste/recipes/context/single',  $context, $meta ): array
apply_filters( 'go4taste/recipes/context/archive', $context, $meta ): array
apply_filters( 'go4taste/recipes/context/home',    $context, $meta ): array
apply_filters( 'go4taste/recipes/use_archive_template', $bool, $queried_object ): bool
apply_filters( 'go4taste/recipes/home_query_args', $args ): array
apply_filters( 'go4taste/recipes/source_post_type', $post_type ): string
```

---

## Debug mode

Add `?g4t_recipe_debug=1` to any single recipe URL. Works on local env (`wp_get_environment_type() === 'local'`) or for `manage_options` users. Adds `recipes_debug_json` to Timber context with full strategy, block parse, and view model diagnostics.

---

## Schema contract

View model shape is documented in `SCHEMA_CONTRACT_V2.md`. Do not change the view model without updating that file. Non-additive changes (rename, type change, nullability change) require a major version bump and migration document.
