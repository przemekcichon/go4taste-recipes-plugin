# go4taste-recipes-plugin

WordPress plugin for recipe content structure and editor experience used in go4taste.pl.

## Features

- Structured recipe data via ACF Blocks (hero, ingredients, steps, tips, source)
- **Dual UI support**: Block Editor (Gutenberg blocks) + ACF Meta Boxes
- **Bidirectional sync**: ACF edits → blocks, block edits → meta
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

## Dual UI & Sync Flows

The plugin supports **two alternative UIs** for editing recipe data:

1. **Block Editor** (Gutenberg blocks) — Primary, source of truth
2. **ACF Meta Boxes** — Alternative for users preferring field-based UI

### Bidirectional Sync

Both UIs are automatically synchronized:

- **ACF save → blocks** (priority 5): ACF field edits rebuild `post_content` with Gutenberg block markup
  - File: `includes/sync/acf-to-blocks.php`
  - Hook: `acf/save_post` priority 5
  - Uses shared converters from `includes/sync/converters.php`
  
- **Block save → meta** (priority 20): Block edits sync attributes to `post_meta` for ACF read/fallback
  - File: `includes/sync/meta-sync.php`
  - Hook: `save_post` priority 20
  - Parses blocks, writes meta, syncs taxonomy terms

### Architecture Files (Sync-Related)

```
includes/
├── sync/
│   ├── acf-to-blocks.php      # ACF → blocks sync (priority 5)
│   ├── meta-sync.php          # Blocks → meta sync (priority 20)
│   ├── converters.php         # Shared meta↔block converters (DRY)
│   └── detection.php          # Save source detection + UI switch tracking
└── admin/
    └── reverse-sync.php       # Manual blocks rebuild (WP All Import compat)
```

### ACF Field Groups

Four ACF field groups registered for recipe content:

- **group_recipe_hero**: 8 simple fields (lead, difficulty, servings, times)
- **group_recipe_notes**: 3 fields (tips, source label, source URL)
- **group_recipe_ingredients**: Flat repeater (ingredient_term_id, name, amount, unit) + photo URL
- **group_recipe_steps**: Nested repeater (groups > steps > step_text)

See [SCHEMA_CONTRACT_V1.md](SCHEMA_CONTRACT_V1.md#acf-field-groups--sync-flows) for detailed field structure and sync documentation.

### Testing Dual UI Sync

**Test ACF → Blocks:**
1. Create or edit a post in WordPress admin
2. Scroll to ACF meta boxes (Recipe Hero, etc.)
3. Fill in fields, click "Update"
4. Switch to Block Editor → verify blocks appear with correct data

**Test Blocks → ACF:**
1. Edit a post in Block Editor
2. Add/edit recipe blocks (hero, ingredients, steps)
3. Click "Update"
4. Scroll to ACF meta boxes → verify fields match block data

**Verify no infinite loops:**
- Edit via ACF → save → reload → blocks match
- Edit via blocks → save → reload → ACF fields match
- No performance degradation (transient flag skips redundant parses)

**Check admin notice:**
- Edit via ACF → save → wait 5min → open Block Editor
- Should see admin notice: "You recently edited this recipe in ACF Meta Boxes"
- Notice disappears after 24 hours or if dismissed

### Troubleshooting Sync Issues

**ACF edits don't update blocks:**
- Check transient `g4t_acf_save_{POST_ID}` (expires in 60s)
- Clear transient: `wp transient delete g4t_acf_save_{POST_ID}`

**Block edits don't update ACF fields:**
- Verify `save_post` hook at priority 20 is registered
- Check for plugin conflicts (other plugins hooking `save_post`)

**Empty blocks or fields after save:**
- Verify ACF field names match meta key patterns (`recipe_*`)
- Check PHP error log for parse_blocks() errors

**Infinite loop on save:**
- Check static `$running` flag in acf-to-blocks.php
- Verify re-entry guards not bypassed

See full troubleshooting guide in [SCHEMA_CONTRACT_V1.md](SCHEMA_CONTRACT_V1.md#troubleshooting).

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
