# go4taste-recipes-plugin

WordPress plugin for recipe content structure and editor experience used in go4taste.pl.

## Features

- Structured recipe data via ACF Blocks (hero, ingredients, steps, tips, source)
- Ingredient taxonomy with auto-sync from block editor
- Additional classification taxonomies (meal type, prep time, features)
- Block → ACF/post_meta synchronization
- Timber view model integration
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
│   ├── post-types.php                  # CPT registration + block template
│   ├── taxonomies.php                  # Taxonomy registration
│   └── blocks.php                      # Block type registration
├── acf/
│   └── field-groups.php                # ACF field group definitions
├── sync/
│   └── meta-sync.php                   # Block → meta sync + taxonomy binding
├── view-model/
│   ├── normalizers.php                 # Data normalization helpers
│   └── builder.php                     # View model builder + ACF fallback
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
    ├── test-blocks.php                 # Block registration tests
    ├── test-meta-sync.php              # Meta sync tests
    ├── test-builder.php                # View model builder tests
    ├── test-context-filters.php        # Timber integration tests
    └── test-settings-page.php          # Settings page tests
```

## Release Checklist

Before each production deployment:

1. Confirm repository is clean: `git status`.
2. Pull latest changes: `git pull --ff-only`.
3. **Run test suite**: `phpunit` to verify all modules pass.
4. Smoke test critical flows in local WP:
   - recipe editor blocks,
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
