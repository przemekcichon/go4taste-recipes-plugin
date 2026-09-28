# go4taste-recipes-plugin

WordPress plugin that provides recipe domain data, a frontend creator workflow, and normalized read models for theme rendering.

## 🏗️ Architecture Philosophy: True Vertical Slices (VSA) & Co-location

This project strictly follows a **True Vertical Slices Architecture** with **Co-location** of assets. 
We prioritize *business features* over *technical layers*. 

- **No Horizontal Layers:** We do not group files by their technical purpose (no global `controllers`, `models`, or `assets/js` folders).
- **Co-location:** PHP, JS, CSS, and Test files that belong to the same business feature live side-by-side in the exact same folder.
- **Autonomy:** Each feature slice is 100% self-contained. It registers its own hooks, handles its own validations, and manages its own assets.

## 📂 Directory Structure

```text
go4taste-recipes-plugin/
├── core/                                    # Shared foundations (Read-Only for Features)
│   ├── class-plugin-bootstrap.php           # Initializes core runtime and plugin instance
│   └── domain/                              # Global domain definitions
│       ├── recipe-pt.php                    # Reads settings & registers target Post Type (or uses default 'post')
│       └── recipe-taxonomies.php            # Registers global taxonomies (ingredients, prep time)
│
├── features/                                # TRUE VERTICAL SLICES (Business Functions)
│   ├── frontend-creator/                    # Feature: User adding a recipe via frontend
│   │   ├── acf-fields.php                   # Local ACF field definitions for the creator
│   │   ├── creator-form.php                 # UI rendering and save logic
│   │   ├── creator.js                       # JS strictly for this form
│   │   ├── creator.css                      # CSS strictly for this form
│   │   └── creator-meta-sync.test.php       # Tests for creator meta/taxonomy sync
│   │
│   ├── recipe-display/                      # Feature: Rendering a single recipe
│   │   ├── view-model-builder.php           # Logic for reading/normalizing recipe data
│   │   └── timber-context.php               # Timber context injection
│   │
│   ├── recipe-schema/                       # Feature: schema.org Recipe JSON-LD on single recipes
│   │   ├── schema-builder.php               # Pure mapping view model → Recipe node
│   │   ├── schema-builder.test.php          # Tests for the pure mapping
│   │   ├── schema-context.php               # Captures view model from context/single filter
│   │   ├── standalone-output.php            # <script type="application/ld+json"> when no Yoast graph
│   │   ├── yoast-piece.php                  # wpseo_schema_graph_pieces callback + Yoast graph detection
│   │   └── class-yoast-recipe-piece.php     # Recipe piece extending Yoast Abstract_Schema_Piece
│   │
│   ├── archive-filtering/                   # Feature: Filtering recipes on archives
│   │   └── facetwp-integration.php          # FacetWP specific logic
│   │
│   ├── admin-settings/                      # Feature: Plugin configuration in wp-admin
│   │   ├── settings-page.php                # UI for selecting target Post Type
│   │   └── settings-logic.php               # Saving/reading options from wp_options
│   │
│   └── admin-promoted-products/             # Isolated Algolia/promoted products parking-plan slice
│       └── algolia-products.php             # AJAX save handler for selected promoted product IDs
│
├── .claude/                                 # AI assistant architecture guardrails
├── tests/                                   # Global test bootstrap (Unit tests live inside features/)
└── go4taste-recipes-plugin.php              # Plugin entrypoint (loads feature slices + core bootstrap)
```
## **🤖 Hard Constraints & Golden Rules for AI Agents**

When editing or refactoring this project, **AI agents must strictly adhere to the following rules:**

1. **The Core Dependency Rule:** Files inside `features/` can import/use functions from `core/`. However, `core/` must **never** depend on anything inside `features/`.  
2. **Feature Isolation:** A feature (e.g., `frontend-creator`) must **never** import or directly call code from another feature (e.g., `recipe-display`). If two features need the same logic, it belongs in `core/` or should be duplicated locally (AHA \- Avoid Hasty Abstractions).  
3. **No Central Orchestrator for Logic:** Do not use `class-plugin-bootstrap.php` to register business logic hooks (`add_action`, `add_filter`). Each feature must hook into WordPress from within its own directory (e.g., inside `creator-form.php`).  
4. **Asset Co-location:** If a feature requires specific JavaScript or CSS, create it inside the feature's folder. Enqueue it using `plugins_url( 'filename.js', __FILE__ )` from the feature's PHP file. Do not put it in a global `assets/` folder.  
5. **Algolia Exclusion:** Legacy Algolia integration for promoted products is scoped out of the main recipe workflow. If implemented, it must reside in its own isolated slice (e.g., `features/admin-promoted-products/`).

## **🔄 Dynamic Post Type Strategy**

The plugin does not force a specific Custom Post Type.

* `features/admin-settings/` provides the UI to select the desired Post Type (e.g., standard 'post' or custom 'recipe').  
* `core/domain/recipe-pt.php` reads this setting, registers the CPT if necessary, and provides a global helper `get_target_recipe_post_type()` for all other features to use.

## **🧪 Testing**

Tests are co-located with their features as `*.test.php` and run on the WordPress test suite (`WP_UnitTestCase`). `phpunit.xml` collects every `*.test.php` under `features/` and `core/domain/`.

**One-time setup** (Local by Flywheel, run from the plugin folder in Git Bash):

1. The CLI PHP needs the `mysqli` extension (`extension=mysqli` in `php.ini`).
2. Create an **empty, dedicated** database in Local's MySQL, e.g. `go4taste_tests`. The suite drops and recreates its tables, so never point it at the site database (`local`). Local's MySQL port is in Local → Database (e.g. `10157`).
3. `composer install`
4. Install the WP test library and a matching WP core into the temp dir:
   ```bash
   bash bin/install-wp-tests.sh go4taste_tests root root 127.0.0.1:<port> <wp-version> true
   ```
   The last argument skips DB creation (step 2). Without `svn` the script downloads the tests from the GitHub `wordpress-develop` mirror (needs `curl` and GNU `tar`, as in Git Bash). Custom locations: set `WP_TESTS_DIR` / `WP_CORE_DIR`.

**Run** (the Local site must be running, so MySQL is up):

```bash
composer test
vendor/bin/phpunit features/recipe-display/view-model-builder.test.php
vendor/bin/phpunit --filter Test_Timber_Context
```

## 🍲 schema.org Recipe (JSON-LD)

`features/recipe-schema/` prints a `Recipe` node on the single view of the configured recipe post type. The node needs a name, a featured image, and at least one ingredient or step.

- **Data source:** the slice reads `$context['recipe']` from the `go4taste/recipes/context/single` filter (priority 20). It never calls `recipe-display` directly, so the ads products are not fetched a second time. The theme must apply that filter before rendering, which `single.php` already does.
- **Yoast SEO active:** the node is added to Yoast's `@graph` via `wpseo_schema_graph_pieces`. `mainEntityOfPage` points to Yoast's WebPage, and `author` points to Yoast's `#organization` when the site represents a company.
- **No Yoast graph:** a standalone `<script type="application/ld+json" class="g4t-recipe-schema">` is printed in `wp_head`.
- **Extending:** `apply_filters( 'go4taste/recipes/schema/recipe', $data, $post )`.
- **Times:** stored values (`"30"`, `"30 min"`, `"1 h 20 min"`) become ISO 8601 durations. `prepTime` and `cookTime` are emitted only together. `totalTime` falls back to their sum.
- **Images:** `Recipe.image` lists the 1:1, 4:3 and 16:9 crops (`g4t-recipe-1x1`, `g4t-recipe-4x3`, `g4t-recipe-16x9`, 1200 px wide) that exist for the featured image, then the original. Originals smaller than a size are cropped to its ratio without upscaling. After activation run `wp media regenerate` for existing attachments.

### ItemList on recipe listings

The recipe home and recipe archives get an `ItemList` with one `ListItem` (position, URL) per recipe shown on the current page. The recipes come from `$context['posts']` in the `go4taste/recipes/context/home` and `/archive` filters (priority 20). Pages with active FacetWP filters get no list. With Yoast the node joins its graph, otherwise it is printed standalone.

## 🧭 Breadcrumbs

`features/breadcrumbs/` adds `breadcrumbs` (list of `{text, url}`, the last item with an empty `url`) to the `single` and `archive` contexts. The trail comes from Yoast's breadcrumbs generator, the same one that builds the `BreadcrumbList` in the schema graph, so the visible trail matches the structured data. Without Yoast: "Przepisy → current page".

Yoast settings that define the trail (Yoast SEO → Settings → Advanced → Breadcrumbs):

| Setting | Value | `wpseo_titles` key |
|---|---|---|
| Anchor text for the homepage | `Przepisy` | `breadcrumbs-home` |
| Breadcrumbs for post types → Posts | `Typ dania` (`recipe_meal_type`) | `post_types-post-maintax` |
