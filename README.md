# go4taste-recipes-plugin

WordPress plugin for recipe content structure and editor experience used in go4taste.pl.

## Features

- Structured recipe data via ACF/meta keys (hero, ingredients, steps, tips, source)
- Frontend Recipe Creator as the primary authoring surface
- ACF meta boxes available in wp-admin for inspection and admin edits
- Gutenberg disabled for the configured recipe post type
- Ingredient taxonomy auto-sync from recipe ingredient repeater rows
- Additional classification taxonomies (meal type, prep time, features)
- Timber view model integration (meta-only runtime)
- Configurable source post type (post / existing CPT / dedicated recipe CPT)

## Testing

### Requirements

- PHPUnit 9+
- WordPress test suite

### Setup

```bash
# Install WordPress test suite
bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Run tests
phpunit
```

### Running specific test suites

```bash
# Test normalizers
phpunit tests/unit/test-normalizers.php

# Test meta sync
phpunit tests/unit/test-meta-sync.php

# Test view model builder
phpunit tests/unit/test-builder.php

# Test all modules
phpunit tests/unit
```

## Architecture

```
includes/
├── class-go4taste-recipes-plugin.php   # Orchestrator (proxy methods only)
├── registration/
│   ├── post-types.php                  # CPT registration + block editor disable rule
│   ├── taxonomies.php                  # Taxonomy registration
├── acf/
│   └── field-groups.php                # ACF field group definitions
├── front/
│   ├── recipe-creator.php              # Frontend creator logic (save/load meta)
│   ├── recipe-creator-helpers.php      # Creator helper utilities
│   ├── recipe-creator-page.php         # Template loader + acf_form bridge
│   └── recipe-creator-template.php     # Creator markup shell
├── sync/
│   └── meta-sync.php                   # Repeater/taxonomy utilities
├── view-model/
│   ├── normalizers.php                 # Data normalization helpers
│   └── builder.php                     # View model builder (meta-first)
├── timber/
│   └── context-filters.php             # Timber context filters
└── admin/
    └── settings-page.php               # Admin settings UI

tests/
├── bootstrap.php                       # PHPUnit bootstrap
└── unit/
    ├── test-normalizers.php            # Normalizers tests
    ├── test-post-types.php             # CPT registration tests
    ├── test-taxonomies.php             # Taxonomy tests
    ├── test-meta-sync.php              # Legacy parser + repeater utilities tests
    ├── test-builder.php                # View model builder tests
    ├── test-context-filters.php        # Timber integration tests
    └── test-settings-page.php          # Settings page tests
```

## Editing Model

The plugin now uses a single canonical editing model:

1. **Frontend Recipe Creator** — primary authoring surface
2. **ACF Meta Boxes in wp-admin** — backend field view/editor for administrators

Gutenberg blocks are not used for recipe authoring. The block editor is disabled
for the configured recipe post type (derived from plugin source strategy).

### Data Flow

- Creator save path writes directly to `post_meta`.
- Featured image is set directly via `set_post_thumbnail()`.
- Ingredient repeater writes immediately sync ingredient taxonomy terms.
- View model builder reads ACF/meta only.

### ACF Field Groups

Four ACF field groups registered for recipe content:

- **group_recipe_hero**: 8 simple fields (lead, difficulty, servings, times)
- **group_recipe_notes**: 3 fields (tips, source label, source URL)
- **group_recipe_ingredients**: Flat repeater (ingredient_term_id, name, amount, unit) + photo URL
- **group_recipe_steps**: Nested repeater (groups > steps > step_text)

See [SCHEMA_CONTRACT_V2.md](SCHEMA_CONTRACT_V2.md) for the active view-model contract.

### Testing Current Editing Flow

1. Open frontend recipe creator page.
2. Save a new draft and verify values are present in ACF/meta keys.
3. Verify featured image appears as post thumbnail.
4. Verify ingredient taxonomy terms reflect ingredient repeater rows.
5. Open single recipe page and verify rendering from recipe view model.

## Release Checklist

Before each production deployment:

1. Confirm repository is clean: `git status`.
2. Pull latest changes: `git pull --ff-only`.
3. **Run test suite**: `phpunit` to verify all modules pass.
4. Smoke test critical flows in local WP:
  - recipe creator save/edit,
  - wp-admin ACF metabox visibility,
   - single recipe rendering,
   - archive listing rendering.
5. Push release commit to `main`.
6. Deploy plugin directory to `wp-content/plugins/go4taste-recipes-plugin/`.
7. Activate or refresh plugin on target WordPress instance.
8. Verify one recipe in wp-admin editor and one recipe page on frontend.

## Release Notes Template

Use this template for each new tag/release:

```md
# go4taste-recipes-plugin vX.Y.Z

Release date: YYYY-MM-DD
Tag: vX.Y.Z

## Summary
- Short description of the release purpose.

## Added
- New feature A

## Changed
- Updated behavior A

## Fixed
- Bug fix A

## Deployment Notes
- Plugin path: wp-content/plugins/go4taste-recipes-plugin/
- Smoke test: editor blocks, single recipe page, archive page.

## Rollback
- Roll back to previous tag: `git checkout <previous-tag>`
```

## Release Notes

### go4taste-recipes-plugin v1.1.0

Release date: 2026-05-26
Tag: v1.1.0

#### Summary
- Migrated recipe editing from block-based dual UI to meta-first creator workflow.

#### Changed
- Frontend creator now saves directly to ACF/meta keys.
- Featured image is written directly as post thumbnail.
- Ingredient taxonomy sync now runs from meta-only save path.
- View model builder switched to meta-only reads.
- Gutenberg is disabled for the configured recipe post type.

#### Removed
- Recipe block registration and block template lock workflow.
- ACF→blocks and blocks→meta sync modules.
- Reverse-sync admin action and related legacy sync helpers.

### go4taste-recipes-plugin v1.0.0

Release date: 2026-05-10
Tag: v1.0.0

#### Summary
- First public repository baseline for the plugin.

#### Added
- Initial plugin codebase import (`assets/`, `includes/`, main plugin bootstrap).
- Schema contract documentation in `SCHEMA_CONTRACT_V1.md`.
- Repository defaults for line ending normalization via `.gitattributes`.
- Operational documentation in `README.md` with release checklist and template.

#### Deployment Notes
- Plugin path: `wp-content/plugins/go4taste-recipes-plugin/`
- Smoke test: editor blocks, single recipe page, archive page.

#### Rollback
- Roll back to pre-release baseline by checking out commit `867ae1f`.
