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
go4taste-recipes-plugin.php                       Entry point: loads feature indexes and core bootstrap
core/class-plugin-bootstrap.php                   Core bootstrap + plugin context instance
core/domain/index.php                             Core domain hook registration (init + block editor filter)
core/domain/recipe-pt.php                         g4t_register_recipe_post_type(), g4t_disable_block_editor_for_recipe_post_type()
core/domain/recipe-taxonomies.php                 g4t_register_ingredient_taxonomy(), g4t_register_flat_taxonomies() + taxonomy constants
core/domain/recipe-options.php                    options cache helpers
core/domain/recipe-source-strategy.php            resolve source strategy
features/frontend-creator/index.php               feature-local hook registration for creator flow
features/frontend-creator/creator-form.php        creator form rendering + save draft/upload/heartbeat logic
features/frontend-creator/creator-meta-sync.php   repeater/taxonomy utilities
features/frontend-creator/acf-fields.php          g4t_register_acf_fields() and ACF groups
features/recipe-display/index.php                 context filter hook registration
features/recipe-display/view-model-builder.php    g4t_build_single_recipe_view_model(), g4t_get_recipe_acf_data()
features/recipe-display/normalizers.php           g4t_normalize_ingredients_list(), steps, images, g4t_has_value()
features/recipe-display/timber-context.php        g4t_filter_single_context(), archive, home, debug helpers
features/recipe-schema/index.php                  schema.org Recipe hook registration (context capture, wp_head, Yoast graph)
features/recipe-schema/schema-builder.php         g4t_recipe_schema_build() — pure view model → Recipe node, time → ISO 8601
features/recipe-schema/schema-context.php         captures $context['recipe'] from context/single, resolves post data, memoized node
features/recipe-schema/standalone-output.php      standalone <script type="application/ld+json"> in wp_head (no Yoast graph)
features/recipe-schema/yoast-piece.php            wpseo_schema_graph_pieces callback (loads class lazily)
features/recipe-schema/class-yoast-recipe-piece.php Recipe piece extending Yoast Abstract_Schema_Piece
features/archive-filtering/index.php              FacetWP hook registration
features/archive-filtering/facetwp-integration.php FacetWP integration callbacks
features/admin-settings/index.php                 admin settings hook registration
features/admin-settings/settings-page.php         g4t_register_settings(), g4t_render_options_page(), g4t_sanitize_options()
features/admin-promoted-products/index.php        promoted products AJAX hook registration
features/admin-promoted-products/algolia-products.php promoted products save handler
```

### Hard constraints — never violate these

| Concern | Only in this file |
|---|---|
| Creator save → recipe meta writes | `features/frontend-creator/creator-form.php` |
| Repeater/taxonomy sync helpers | `features/frontend-creator/creator-meta-sync.php` |
| `acf_add_local_field_group()` | `features/frontend-creator/acf-fields.php` |
| `register_taxonomy()` | `core/domain/recipe-taxonomies.php` |
| Core domain hooks (`init` / block editor filter) | `core/domain/index.php` |
| Feature business hooks (`add_action()` / `add_filter()`) | `features/*/index.php` |
| View model shape | `features/recipe-display/view-model-builder.php` |
| Data normalisation | `features/recipe-display/normalizers.php` |
| Timber context enrichment | `features/recipe-display/timber-context.php` |

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
- No anonymous functions in `add_action()` / `add_filter()` calls.
- `Go4Taste_Recipes_Plugin` is a compatibility context class (constants + options/strategy accessors), not a hook orchestrator.
- New business hooks must be registered in feature indexes (`features/*/index.php`) or `core/domain/index.php` for domain-level hooks.

---

## How to add a new recipe field

1. `features/frontend-creator/acf-fields.php` — add/update ACF field definition.
2. `features/frontend-creator/creator-form.php` — map creator state ↔ meta save/load.
3. `features/recipe-display/view-model-builder.php` — include field in view model mapping.
4. `SCHEMA_CONTRACT_V3.md` — update contract docs if view-model shape changes.

## How to add a taxonomy

See `.claude/commands/add-taxonomy.md`. Summary:

1. `core/domain/recipe-taxonomies.php` — add entry + PHP constant
2. If term-synced: `features/frontend-creator/creator-meta-sync.php` + relevant feature index registration
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
apply_filters( 'go4taste/recipes/schema/recipe', $data, $post ): array   // Recipe JSON-LD node before output
```

`recipe-schema` reads the view model from `go4taste/recipes/context/single` (priority 20), never by calling `recipe-display` directly. With Yoast SEO active, the `Recipe` node joins Yoast's graph; otherwise it prints as a standalone script. There is always exactly one graph per page.

---

## Debug mode

Add `?g4t_recipe_debug=1` to any single recipe URL. Works on local env (`wp_get_environment_type() === 'local'`) or for `manage_options` users. Adds `recipes_debug_json` to Timber context with full strategy, block parse, and view model diagnostics.

---

## Tests

`composer test` runs every `*.test.php` (co-located with its feature) on the WP test suite against a dedicated test DB in Local's MySQL — never the site DB. Local must be running. One-time setup: README → Testing.

---

## Schema contract

View model shape is documented in `SCHEMA_CONTRACT_V3.md`. Do not change the view model without updating that file. Non-additive changes (rename, type change, nullability change) require a major version bump and migration document.
