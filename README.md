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
│   │   └── creator.test.php                 # Tests for the creator flow
│   │
│   ├── recipe-display/                      # Feature: Rendering a single recipe
│   │   ├── view-model-builder.php           # Logic for reading/normalizing recipe data
│   │   └── timber-context.php               # Timber context injection
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

Domyślny `phpunit` uruchamia testy z `features/` i `core/domain/` (zgodnie z `phpunit.xml`).

Unit tests are co-located with their features. Example focused test runs:
```bash
phpunit
phpunit features/frontend-creator/creator-meta-sync.test.php
phpunit features/recipe-display/view-model-builder.test.php
phpunit features/archive-filtering/facetwp-integration.test.php
phpunit features/admin-settings/settings-page.test.php
```
