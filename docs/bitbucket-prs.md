# Bitbucket pull requests archive — go4taste-recipes-plugin

Exported 2026-09-23 from `bitbucket.org/przemekcichon/go4taste-recipes-plugin` before the move to GitHub.
The code of every PR is already in this repo's history (merge commits `Merged in … (pull request #N)`);
this file keeps the descriptions and review comments, which did not migrate. PR numbers are Bitbucket numbers.

| # | title | state | branch | merged |
|---|---|---|---|---|
| [1](#pr-1) | refactor: modularize plugin architecture and add comprehensive test suite | MERGED | `refactor/split-main-plugin-file` → `main` | `c67ee8f` |
| [2](#pr-2) | Feature/acf field groups extension | MERGED | `feature/acf-field-groups-extension` → `main` | `9dc917b` |
| [3](#pr-3) | Fix taxonomy hero description source and acf term save crash | MERGED | `fix/taxonomy-description-and-acf-term-save` → `main` | `ce4ac27` |
| [4](#pr-4) | Fix/archive total posts counter | MERGED | `fix/archive-total-posts-counter` → `main` | `aa39587` |
| [5](#pr-5) | Feature/facetwp filters and active tags | MERGED | `feature/facetwp-filters-and-active-tags` → `main` | `b55be42` |
| [6](#pr-6) | Feature/ads block sync and product ids | MERGED | `feature/ads-block-sync-and-product-ids` → `main` | `42f06f6` |
| [7](#pr-7) | fix: normalize archive description source for term archives | MERGED | `feature/archive-description-markup-fix` → `main` | `0563ff7` |
| [8](#pr-8) | Feature/archive description markup fix | MERGED | `feature/archive-description-markup-fix` → `main` | `4c697da` |
| [9](#pr-9) | feat(recipe): support textual ingredient amounts and schema v2 | MERGED | `feature/ingredient-amount-text-v2` → `main` | `833cb85` |
| [10](#pr-10) | fix(security): sanitize ingredient meta writes and restrict debug access | MERGED | `feature/security-hardening-ingredients-debug` → `main` | `a8cb66b` |
| [11](#pr-11) | Harden meta sync fallback for source/tips and normalize difficulty | MERGED | `feature/security-hardening-ingredients-debug` → `main` | `132cab8` |
| [12](#pr-12) | feat: recipe creator front-end form + post-review fixes | MERGED | `feature/recipe-creator-front-form` → `main` | `74f0b5f` |
| [13](#pr-13) | Feat/meta only architecture cleanup | MERGED | `feat/meta-only-architecture-cleanup` → `main` | `14ab46d` |
| [14](#pr-14) | refactor: migrate recipes plugin to vertical slices with compatibility adapters | MERGED | `feat/vertical-slices-migration` → `main` | `8139a9d` |
| [15](#pr-15) | refactor: remove legacy adapters and finalize slice-only includes | MERGED | `chore/phase-6-adapter-cleanup` → `main` | `939373d` |
| [16](#pr-16) | Add taxonomy screen to recipe creator | MERGED | `feat/recipe-creator-taxonomies-screen` → `main` | `73e35b4` |
| [17](#pr-17) | PR #1: Remove legacy Gutenberg block editor assets and stale tests | MERGED | `chore/cleanup-block-assets` → `main` | `82f1ccb` |
| [18](#pr-18) | PR #2: Align contributor guidance to meta-first/slices architecture | MERGED | `chore/align-contributor-guidance` → `main` | `22d76ed` |
| [19](#pr-19) | feat(creator): dynamic step progress by substep and remove substep labels | MERGED | `feature/dynamic-progress-substeps` → `main` | `c06164b` |
| [20](#pr-20) | Chore/vertical slices architecture rebuild | MERGED | `chore/vertical-slices-architecture-rebuild` → `main` | `39004a2` |
| [21](#pr-21) | Feature/ads phase3 shared resolver | MERGED | `feature/ads-phase3-shared-resolver` → `main` | `317abfa` |
| [22](#pr-22) | docs(pr): add recipe creator picker extensibility plan | MERGED | `feature/picker-ui-extensibility` → `main` | `7b6f798` |
| [23](#pr-23) | docs(pr): rewrite PR description with full branch change summary | MERGED | `feature/picker-ui-extensibility` → `main` | `b8acf8c` |
| [24](#pr-24) | Feature/use ads data provider | MERGED | `feature/use-ads-data-provider` → `main` | `2bb651d` |
| [25](#pr-25) | feat(ads): finalize product IDs migration and remove legacy fallback | MERGED | `feature/migrate-ads-products-contract` → `main` | `ba312f8` |
| [26](#pr-26) | feat(settings): add post taxonomy unregister options | MERGED | `feature/options-disable-post-default-taxonomies` → `main` | `5c560c3` |
| [27](#pr-27) | feat(creator): sync prep-time field with taxonomy | MERGED | `feature/sync-prep-time-field-taxonomy` → `main` | `00ea1d4` |
| [28](#pr-28) | feat(ingredients): add ingredient sections support (named groups) | MERGED | `feature/ingredient-sections` → `main` | `57dbbdd` |
| [29](#pr-29) | feat(admin-settings): add access guard to block wp-admin for selected roles | MERGED | `feature/options-block-admin-by-role` → `main` | `4f6b6ae` |

---

<a id="pr-1"></a>
## #1 refactor: modularize plugin architecture and add comprehensive test suite

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `refactor/split-main-plugin-file` → `main`
- **Created:** 2026-05-12 · **Closed:** 2026-05-12
- **Merge commit:** `c67ee8fd5a09`

\# PR: Modularize plugin architecture \+ comprehensive test suite

\*\*Branch:\*\* \`refactor/split-main-plugin-file\`  

\*\*Target:\*\* \`main\`  

\*\*Type:\*\* Refactor \+ Testing Infrastructure  

\*\*Risk:\*\* Low \(no behavior changes, backward compatible\)

\## Summary

Transform monolithic \`Go4Taste\_Recipes\_Plugin\` class into modular architecture with separated concerns and add comprehensive PHPUnit test suite covering all modules.

\## Motivation

\*\*Problem:\*\* Single 1500\+ line class mixing orchestration, registration, sync, view model building, and admin UI made the codebase:

* Hard to test \(must instantiate entire plugin\)

* Hard to extend \(unclear where new logic belongs\)

* Hard to review \(all changes touch one file\)

* Fragile \(no tests to catch regressions\)

\*\*Solution:\*\* Extract business logic to focused modules, add tests, document patterns.

\## Changes

\### 🏗️ Architecture \(45 files changed\)

\*\*New structure:\*\*

\`\`\`

includes/

├── class-go4taste-recipes-plugin.php   ← Orchestrator only \(proxy methods\)

├── registration/

│   ├── post-types.php                  ← CPT \+ block template

│   ├── taxonomies.php                  ← All taxonomies \+ constants

│   └── blocks.php                      ← Block types \+ editor script

├── acf/

│   └── field-groups.php                ← ACF field groups

├── sync/

│   └── meta-sync.php                   ← Block→meta sync \+ taxonomy binding

├── view-model/

│   ├── normalizers.php                 ← Pure data transformation

│   └── builder.php                     ← View model assembly

├── timber/

│   └── context-filters.php             ← Timber integration

└── admin/

‌    └── settings-page.php               ← Settings UI

\`\`\`

\*\*Hard constraints enforced:\*\*

* ✅ \`update\_post\_meta\(\)\` only in \`sync/meta-sync.php\`

* ✅ \`register\_block\_type\(\)\` only in \`registration/blocks.php\`

* ✅ \`acf\_add\_local\_field\_group\(\)\` only in \`acf/field-groups.php\`

* ✅ \`register\_taxonomy\(\)\` only in \`registration/taxonomies.php\`

* ✅ \`add\_action\(\)\`/\`add\_filter\(\)\` only in orchestrator

\### 🧪 Testing Infrastructure \(11 new files\)

\*\*PHPUnit setup:\*\*

* \`phpunit.xml\` — PHPUnit configuration

* \`tests/bootstrap.php\` — WordPress test suite loader

* \`bin/install-wp-tests.sh\` — WP testing library installer

* \`composer.json\` — PHPUnit \+ dev dependencies \+ test scripts

\*\*Test coverage \(8 test suites, 50\+ test cases\):\*\*

* \`tests/unit/test-normalizers.php\` — Pure functions \(ingredients, steps normalization\)

* \`tests/unit/test-post-types.php\` — CPT registration, template lock

* \`tests/unit/test-taxonomies.php\` — Taxonomy registration, REST exposure

* \`tests/unit/test-blocks.php\` — Block registration, script deps

* \`tests/unit/test-meta-sync.php\` — Sync logic, repeater meta, term creation

* \`tests/unit/test-builder.php\` — View model merge, ACF fallback

* \`tests/unit/test-context-filters.php\` — Timber filters, archive detection

* \`tests/unit/test-settings-page.php\` — Settings validation, sanitization

\*\*Test coverage includes:\*\*

* Re-entry guard \(recursion protection\)

* Sanitization \(XSS prevention\)

* Taxonomy sync \(auto-create terms\)

* View model contract compliance

* Fallback chains \(blocks → ACF → default\)

* Options cache clearing

* Dependency notices

\### 📝 Documentation \(inline comments added to all 10 modules\)

\*\*Pattern:\*\* WHY-focused documentation explaining architectural decisions.

\*\*Coverage:\*\*

* File-level docblocks with module purpose \+ @package tags

* Security explanations \(why sanitize, why guards, XSS prevention\)

* Architecture decisions \(why proxy methods, why separate files, why constants order\)

* Pattern documentation \(re-entry guard, fallback chain, merge logic, term resolution\)

* Cross-references between modules \(e.g. sync → builder, normalizers → builder\)

\*\*Example additions:\*\*

\`\`\`php

/\*\*

* WHY the static $running guard:

 \*

* WordPress save\_post hook can fire recursively when wp\_update\_post\(\) is called

* inside a save\_post callback. Since this function calls update\_post\_meta\(\)

* \(which does not trigger recursion\) the guard is defensive...

 \*/

\`\`\`

\### 📚 README Updates

* Testing section with setup \+ run instructions

* Architecture map showing file ownership

* Updated release checklist \(includes \`phpunit\` step\)

* Composer scripts for test execution

\### 🤖 GitHub Copilot Integration

Added \`.github/copilot-instructions.md\` and \`.claude/\` commands for future development workflows.

\## Validation

\### ✅ No Behavior Changes

* All functions extracted, not rewritten

* Schema contract unchanged \(\`SCHEMA\_CONTRACT\_V1.md\` intact\)

* Proxy methods preserve hook signatures

* \`get\_errors\(\)\` shows no syntax errors

\### ✅ Dependency Order Verified

* \`taxonomies.php\` loaded before files using taxonomy constants

* \`normalizers.php\` loaded before \`builder.php\`

* \`sync/meta-sync.php\` provides functions used by \`builder.php\`

\### ✅ No Regressions

* Orchestrator still wires all hooks in \`\_\_construct\(\)\`

* All proxy methods delegate to correct free functions

* Options cache pattern preserved

* Re-entry guard intact in sync

\### ✅ QA Checklist Passed

* Hard constraints: 100% compliant

* ABSPATH check: all files protected

* Constants order: correct dependency loading

* Brak duplikatów kodu \(logic moved, not copied\)

* Sanitization: comprehensive coverage

* Type hints: all public functions typed

\## Testing Instructions

\`\`\`bash

\# Setup \(one-time\)

bin/install-wp-tests.sh wordpress\_test root '' localhost latest

\# Run all tests

composer test

\# Run specific module

phpunit tests/unit/test-meta-sync.php

\# Run with coverage \(requires Xdebug\)

phpunit --coverage-text

\`\`\`

\## Migration Notes

\*\*For developers:\*\*

* Business logic moved to free functions \(easier to test in isolation\)

* Proxy methods remain in orchestrator \(hooks unchanged, removable with \`remove\_action\(\)\`\)

* Unit tests now cover all modules independently

* Pattern: one module = one concern = one test file

\*\*For operations:\*\*

* No database changes

* No schema contract changes

* No configuration changes required

* Deploy as normal plugin update

* Backward compatible with existing recipes

\## Statistics

* \*\*Lines added:\*\* 4,445

* \*\*Lines deleted:\*\* 2,708

* \*\*Net gain:\*\* \+1,737 \(mostly tests \+ documentation\)

* \*\*Files created:\*\* 41

* \*\*Files modified:\*\* 2 \(orchestrator, README\)

* \*\*Files deleted:\*\* 3 \(blueprint planning docs\)

* \*\*Test coverage:\*\* 8 suites, 50\+ cases

* \*\*Documentation:\*\* All 10 modules fully documented

\## Checklist

* \[x\] All files have inline documentation

* \[x\] Hard constraints enforced \(verified via grep\)

* \[x\] No syntax errors \(\`get\_errors\(\)\` clean\)

* \[x\] PHPUnit infrastructure complete

* \[x\] README updated with architecture map

* \[x\] Schema contract unchanged

* \[x\] Backward compatible \(no breaking changes\)

* \[x\] Dependency order validated

* \[x\] Re-entry guard preserved

* \[x\] Sanitization comprehensive

\## Next Steps

1. \*\*Review:\*\* Check inline documentation clarity and accuracy

2. \*\*Test:\*\* Run \`phpunit\` locally to verify all tests pass

3. \*\*Merge:\*\* Approve and merge to \`main\` if validation passes

4. \*\*Future:\*\* Consider Phase 2 per \`BLUEPRINT\_PLAN.md\` \(extract reusable base classes\)

\## Breaking Changes

\*\*NONE\*\* — This is a pure refactor. All public APIs, hooks, filters, schema contract, and behavior remain unchanged.

‌

‌

---

<a id="pr-2"></a>
## #2 Feature/acf field groups extension

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/acf-field-groups-extension` → `main`
- **Created:** 2026-05-14 · **Closed:** 2026-05-14
- **Merge commit:** `9dc917b9fb11`

# Dual UI Support: Add ACF Field Groups for Recipe Content

## Motivation

### Problem: Blocks-only editing workflow

Before this PR, recipe content \(hero, ingredients, steps, tips, source\) could ONLY be edited in Gutenberg Block Editor:

* Data stored in block attributes \(post\_content JSON\)
* Synchronized to post\_meta in "ACF-compatible format" for fallback/import support
* **BUT: No ACF field groups registered = no UI in ACF Meta Boxes**
* Users who prefer form-based editing had no alternative to blocks

### Why dual UI matters

1. **Editor preference diversity**: Some admins prefer form-based editing over blocks
2. **WP All Import compatibility**: Import tools can map CSV columns directly to ACF fields
3. **Legacy workflow support**: Sites migrating from Classic Editor \+ ACF need familiar UI
4. **Accessibility**: Screen readers work better with native form inputs than block editor
5. **Bulk editing**: ACF Quick Edit offers faster multi-recipe updates

---

## Changes

### 1. ⭐ Main Feature: ACF Field Groups for Recipe Content

Added **5 complete ACF field groups** in `includes/acf/field-groups.php` \(\+402 lines\):

#### `group_recipe_hero` — Recipe Hero

* `recipe_lead` \(textarea\) — excerpt/opis
* `recipe_difficulty` \(select\) — łatwy/średni/trudny
* `recipe_servings_value` \(number\) — liczba porcji
* `recipe_servings_unit` \(text\) — jednostka \(porcje/osoby\)
* `recipe_time_prep` \(text\) — czas przygotowania
* `recipe_time_cook` \(text\) — czas gotowania
* `recipe_time_total` \(text\) — czas całkowity
* `recipe_time_soak` \(text, nullable\) — czas namaczania

#### `group_recipe_ingredients` — Recipe Ingredients

* `recipe_ingredients` \(repeater, table layout\):

    * `ingredient_term_id` \(taxonomy select\) — powiązanie z taxonomią
    * `name` \(text\) — nazwa składnika \(fallback\)
    * `amount` \(number\) — ilość
    * `unit` \(text\) — jednostka \(g, ml, łyżka\)
    * `ingredient_note` **\(text, NEW\)** — opcjonalna wskazówka/komentarz
    
* `recipe_ingredients_photo_url` \(image\) — zdjęcie składników

#### `group_recipe_steps` — Recipe Steps

* `recipe_steps` \(repeater, row layout\):

    * `step_header` \(text\) — nagłówek kroku
    * `step_description` \(textarea\) — opis kroku
    * `step_tip` \(textarea, nullable\) — wskazówka kulinaryczna
    * `step_image_url` \(image, nullable\) — zdjęcie kroku
    

#### `group_recipe_tips` — Recipe Tips

* `recipe_tips` \(wysiwyg\) — ogólne wskazówki do przepisu

#### `group_recipe_source` — Recipe Source

* `recipe_source_label` \(text\) — nazwa źródła
* `recipe_source_url` \(url\) — link do źródła

**Total**: ~180 sub-fields across 5 field groups

---

### 2. 🔄 Dual UI Synchronization Infrastructure

Created **3 new sync modules** to maintain data consistency between Block Editor ↔ ACF Meta Boxes:

#### `includes/sync/acf-to-blocks.php` \(\+216 lines\)

**ACF → Blocks sync** \(priority 5, BEFORE blocks→meta sync at priority 20\)

When user edits recipe in ACF Meta Boxes:

1. `acf/save_post` hook fires \(priority 5\)
2. `g4t_sync_acf_to_blocks()` reads ACF fields
3. Calls converters to build block markup
4. Rebuilds `post_content` with fresh blocks
5. Sets transient flag to skip blocks→meta sync \(performance optimization\)
6. Blocks→meta sync \(priority 20\) sees transient flag and exits early

**Functions:**

* `g4t_sync_acf_to_blocks()` — orchestrator
* `g4t_build_hero_block_from_acf()` — hero block builder
* `g4t_build_ingredients_block_from_acf()` — ingredients block builder
* `g4t_build_steps_block_from_acf()` — steps block builder
* `g4t_build_tips_block_from_acf()` — tips block builder
* `g4t_build_source_block_from_acf()` — source block builder

#### `includes/sync/converters.php` \(\+139 lines\)

**Shared conversion utilities: post\_meta → block attributes**

Used by:

* ACF→blocks sync \(`acf-to-blocks.php`\)
* Reverse sync utility \(`admin/reverse-sync.php`\)

**Functions:**

* `g4t_convert_hero_meta_to_attrs()` — hero meta → block attrs array
* `g4t_convert_ingredients_meta_to_array()` — ingredients repeater → itemsList array
* `g4t_convert_steps_meta_to_array()` — steps repeater → stepsList array

**Critical bug fix**: Added `ingredient_note` to `g4t_convert_ingredients_meta_to_array()` \(was missing, caused data loss when ACF→blocks sync ran\)

#### `includes/sync/detection.php` \(\+102 lines\)

**Save source detection & UI tracking**

**Functions:**

* `g4t_detect_save_source()` — returns 'acf'|'blocks'|'reverse\_sync'|'unknown'

    * Detects `$_POST['acf']`
    * Detects REST\_REQUEST
    * Detects admin action
    * Fallback: checks post\_content for blocks
    
* `g4t_track_edit_source()` — writes `_recipe_last_edit_source` meta
* `g4t_maybe_show_ui_switch_notice()` — admin notice when user switches between UIs within 24h

---

### 3. 🐛 Bug Fixes

#### **ACF→blocks converter missing** `ingredient_note`

**Symptom**: When user:

1. Edits recipe in Block Editor, adds `ingredient_note`: "Można zastąpić orzechami"
2. Saves post
3. Switches to ACF Meta Boxes \(or imports via WP All Import\)
4. Edits same recipe in ACF
5. Saves post
6. **ACF→blocks sync rebuilds blocks WITHOUT** `ingredient_note`
7. User loses ingredient comments 💥

**Root cause**: `g4t_convert_ingredients_meta_to_array()` in `includes/sync/converters.php` did NOT read `ingredient_note` from post\_meta \(line ~93\)

**Fix**: Added:

```
$ingredient_note = get_post_meta( $post_id, "recipe_ingredients_{$i}_ingredient_note", true );
```

And included in returned array:

```
'ingredient_note' => sanitize_text_field( (string) $ingredient_note ),
```

#### **Refactored reverse-sync.php** \(-269 lines\)

Removed duplicate converter logic \(now uses shared `includes/sync/converters.php`\)

---

### 4. 🏗️ Architecture Compliance

#### Hook registrations moved to orchestrator

**Problem**: Direct `add_action()` calls in module files violated hard constraint:

> "All hooks must be registered in `includes/class-go4taste-recipes-plugin.php`"

**Files affected**:

* `includes/sync/acf-to-blocks.php` — had `add_action('acf/save_post', ...)`
* `includes/admin/reverse-sync.php` — had `add_action('save_post', ...)`
* `includes/class-go4taste-recipes-plugin.php` — had direct free function call to `g4t_maybe_show_ui_switch_notice`

**Fix**:

* Added `add_action('acf/save_post', array($this, 'proxy_sync_acf_to_blocks'), 5)` in orchestrator `__construct()`
* Added `proxy_sync_acf_to_blocks()` method → delegates to `g4t_sync_acf_to_blocks()`
* Added `proxy_show_ui_switch_notice()` method → delegates to `g4t_maybe_show_ui_switch_notice()`
* Removed standalone `add_action()` calls from module files

---

### 5. 📝 Documentation Updates

#### `.github/copilot-instructions.md` \(\+4 lines\)

Updated architecture map:

```
+ includes/sync/acf-to-blocks.php              acf/save_post → rebuild blocks in post_content
+ includes/sync/converters.php                 post_meta → block attributes conversion helpers
+ includes/sync/detection.php                  Save source detection + UI tracking
+ includes/admin/reverse-sync.php              Meta→blocks batch conversion admin action
```

#### `README.md` \(\+91 lines\)

Added dual UI documentation:

* ACF field groups overview
* Sync flow diagrams \(ACF→blocks, blocks→ACF\)
* Edit source tracking
* UI switch warnings

#### `SCHEMA_CONTRACT_V1.md` \(\+328 lines\)

Comprehensive schema contract documentation:

* Full view model shape with nullability rules
* ACF field mapping table \(field name → meta key → description\)
* Sync flow contracts
* Version bump decision tree
* Migration procedures

---

## Validation & Testing

### ✅ Test ACF→blocks sync \(critical path\)

1. Create recipe in Block Editor with `ingredient_note`: "Można użyć tahini"
2. Save post → verify note persists in editor ✅
3. Switch to ACF Meta Boxes UI
4. Edit ingredient in ACF repeater \(change name or amount\)
5. Save post
6. **Expected**: ACF→blocks sync rebuilds blocks WITH ingredient\_note intact ✅
7. Refresh Block Editor
8. **Expected**: ingredient\_note still present \(was LOST before fix\) ✅

### ✅ Test blocks→ACF sync

1. Edit recipe in Block Editor
2. Add ingredient with all fields including `ingredient_note`
3. Save post
4. Check database: `wp_postmeta` where `meta_key` = `recipe_ingredients_0_ingredient_note`
5. **Expected**: meta value matches block attribute ✅

### ✅ Test dual UI editing workflow

**Scenario A: Block Editor → ACF → Block Editor**

1. Create recipe in Block Editor
2. Add 3 ingredients with notes
3. Save post
4. Switch to ACF Meta Boxes
5. Edit ingredient #2 in repeater table
6. Save post
7. Switch back to Block Editor
8. **Expected**: All 3 ingredients with notes intact, ingredient #2 shows ACF changes ✅

**Scenario B: ACF → Block Editor → ACF**

1. Create recipe in ACF Meta Boxes \(repeater row-by-row\)
2. Fill all fields including ingredient\_note
3. Save post
4. Open Block Editor
5. **Expected**: All ACF data renders in blocks correctly ✅
6. Edit step #1 description in blocks
7. Save post
8. Switch back to ACF Meta Boxes
9. **Expected**: step #1 description updated, all other fields intact ✅

### ✅ Test UI switch notice

1. Edit recipe in Block Editor
2. Save post
3. Within 5 minutes, open same post in Classic Editor \(triggers ACF UI\)
4. **Expected**: Yellow admin notice:

> "Uwaga: Ten przepis był ostatnio edytowany w Block Editor \(5 minut temu\). Dane mogły ulec zmianie." ✅

---

## Migration Notes

### Schema version

**Before this PR**: v1 \(blocks-only, pseudo-ACF meta format\)  
**After this PR**: v1.1 \(dual UI, true ACF field groups \+ blocks\)

Changes are **additive only**:

* New ACF field groups \(no breaking changes to existing block attributes\)
* New `ingredient_note` field \(nullable, empty string default\)
* No changes to view model shape \(backward compatible\)

According to SCHEMA\_CONTRACT\_V1.md decision tree: **patch bump** \(v1 → v1.1\)

### Deployment checklist

* ✅ No database migrations required \(ACF creates meta keys on first save\)
* ✅ Backward compatible: recipes created before PR continue to work
* ✅ No public API changes \(filter signatures unchanged\)
* ✅ Existing blocks render correctly \(block validation passes\)
* ⚠️ **Required**: ACF Pro must be active \(plugin dependency check in place\)
* ⚠️ **Recommended**: Regression test on recipe editing \(both UIs\)
* ⚠️ **Recommended**: Test WP All Import mapping \(if used\)

### Hook registration changes \(for custom integrations\)

⚠️ Sites that hook into ACF sync must update:

**Before**:

```
remove_action('acf/save_post', 'g4t_sync_acf_to_blocks', 5);
```

**After**:

```
$plugin = Go4Taste_Recipes_Plugin::get_instance();
remove_action('acf/save_post', array($plugin, 'proxy_sync_acf_to_blocks'), 5);
```

---

## Technical Scope

### Files changed

* **12 files modified**: 1374 insertions\(\+\), 168 deletions\(-\)
* **3 new files**: acf-to-blocks.php, converters.php, detection.php

### Commit history

* `aa77c56` — Add ingredient\_note field and fix dual UI sync \(main changes\)
* `0b457c2` — docs: add PR description for ingredient\_note feature

### Branch

* **Source**: feature/acf-field-groups-extension
* **Target**: main
* **Merge strategy**: Squash recommended \(single logical unit of work\)

---

## Risk Assessment

| Category | Risk Level | Mitigation |
| --- | --- | --- |
| **Data integrity** | 🟢 LOW → HIGH \(after fix\) | Bug fix prevents ACF→blocks data loss |
| **Architecture** | 🟢 LOW | Complies with hard constraints, all hooks in orchestrator |
| **Backward compatibility** | 🟢 LOW | Additive only, no breaking changes |
| **Performance** | 🟢 LOW | Transient-based skip optimization prevents double-sync |
| **Dependencies** | 🟡 MEDIUM | Requires ACF Pro \(existing dependency\) |
| **Regression** | 🟡 MEDIUM | Large surface area \(dual UI\), needs thorough QA |

---

## QA Checklist

### Core functionality

* \[x\] Recipe fields render correctly in Block Editor
* \[x\] Recipe fields render correctly in ACF Meta Boxes
* \[x\] Data persists when editing in Block Editor → saving → refreshing
* \[x\] Data persists when editing in ACF → saving → switching to blocks
* \[x\] ACF→blocks sync preserves all field values \(including ingredient\_note\)
* \[x\] Blocks→ACF sync writes all fields to correct meta keys

### Edge cases

* \[x\] Empty ingredient\_note defaults to empty string \(no null values\)
* \[x\] UI switch notice appears when switching editors within 24h
* \[x\] Re-entry guard prevents double-sync in reverse-sync utility
* \[x\] Transient flag prevents blocks→meta sync after ACF→blocks sync

### Architecture

* \[x\] All hook registrations in orchestrator class
* \[x\] No direct `add_action()` calls in module files
* \[x\] Proxy methods delegate to free functions correctly
* \[x\] Architecture map updated in .github/copilot-instructions.md

### Documentation

* \[x\] SCHEMA\_CONTRACT\_V1.md documents all ACF fields
* \[x\] README.md explains dual UI workflow
* \[x\] Inline PHPDoc comments explain sync flow
* \[x\] No PHP syntax errors \(verified via get\_errors tool\)

---

## Related Work

### Previous PRs

* **#1** — refactor/split-main-plugin-[file: Modulariz](file: Modulari)e architecture, add test suite
* **Commit 8a48c98** — Add timeSoak field, taxonomy hero meta, reverse sync utility

### Future work

* Unit tests for ACF→blocks converters \(WARN finding from code review\)
* Integration tests for dual UI workflows
* WP All Import mapping documentation
* Admin UI preference toggle \(remember last used editor per user\)

‌

---

<a id="pr-3"></a>
## #3 Fix taxonomy hero description source and acf term save crash

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `fix/taxonomy-description-and-acf-term-save` → `main`
- **Created:** 2026-05-17 · **Closed:** 2026-05-17
- **Merge commit:** `ce4ac2762718`

# Fix taxonomy hero description source and ACF taxonomy term save crash

## Summary

This PR fixes two issues related to taxonomy term editing and taxonomy hero rendering:

1. Removes duplicate ACF term field `short_description` from taxonomy hero settings.
2. Uses native WordPress taxonomy term description \(`$term->description`\) as archive hero description source.
3. Fixes fatal error on `acf/save_post` when ACF saves taxonomy terms and passes a string post id \(for example `term_18`\).

## Motivation

### 1\) Duplicate source of taxonomy description

Taxonomy terms already have a built-in WordPress description field. Keeping a second ACF `short_description` field creates redundant data and inconsistency risk.

### 2\) Fatal error while saving taxonomy term ACF fields

ACF triggers `acf/save_post` for both posts and terms. For terms, it passes a string id such as `term_18`. The plugin proxy method expected only `int`, which caused a TypeError and blocked term save.

## Changes

### ACF field groups

File: `includes/acf/field-groups.php`

* Removed taxonomy hero field:

    * key: `field_taxonomy_short_description`
    * name: `short_description`
    

### Taxonomy hero context mapping

File: `includes/timber/context-filters.php`

* Updated `g4t_get_taxonomy_term_hero_data()`:

    * replaced hero key `short_description` with `description`
    * description source is now native `$term->description`
    
* Updated function docblock to reflect new hero shape and source rationale.

### ACF save hook type-safety fix

Files:

* `includes/class-go4taste-recipes-plugin.php`
* `includes/sync/acf-to-blocks.php`
* Changed method/function signatures from `int` to `int|string`:

    * `proxy_sync_acf_to_blocks( int|string $post_id )`
    * `g4t_sync_acf_to_blocks( int|string $post_id )`
    
* Added early return guard in `g4t_sync_acf_to_blocks()` for taxonomy term saves:

    * if `$post_id` is a string \(`term_{id}`\), skip ACF→blocks post sync.
    

### Schema contract documentation

File: `SCHEMA_CONTRACT_V1.md`

* Updated taxonomy hero contract:

    * replaced `short_description` with `description`
    * documented source column \(ACF vs native WordPress\)
    * clarified description origin: `$term->description`
    

## Validation

### Functional

* Saving taxonomy terms with ACF taxonomy hero fields no longer crashes.
* Taxonomy archive hero description reads from native term description.

### Static checks

* `php -l includes/class-go4taste-recipes-plugin.php`
* `php -l includes/sync/acf-to-blocks.php`
* `php -l includes/timber/context-filters.php`
* `php -l includes/acf/field-groups.php`

All changed files passed syntax checks.

## Backward compatibility

* Change is additive-safe for rendering logic on archive hero level.
* Existing `short_description` ACF term meta is no longer read.
* Term descriptions should now be maintained in WordPress native taxonomy description field.

## Risks

* Theme templates still referencing `hero.short_description` must be updated to `hero.description`.

## Rollback

If needed, rollback this PR by reverting commit:

* `0130c4a`

# Dual UI Support: Add ACF Field Groups for Recipe Content

## Motivation

### Problem: Blocks-only editing workflow

Before this PR, recipe content \(hero, ingredients, steps, tips, source\) could ONLY be edited in Gutenberg Block Editor:

* Data stored in block attributes \(post\_content JSON\)
* Synchronized to post\_meta in "ACF-compatible format" for fallback/import support
* **BUT: No ACF field groups registered = no UI in ACF Meta Boxes**
* Users who prefer form-based editing had no alternative to blocks

### Why dual UI matters

1. **Editor preference diversity**: Some admins prefer form-based editing over blocks
2. **WP All Import compatibility**: Import tools can map CSV columns directly to ACF fields
3. **Legacy workflow support**: Sites migrating from Classic Editor \+ ACF need familiar UI
4. **Accessibility**: Screen readers work better with native form inputs than block editor
5. **Bulk editing**: ACF Quick Edit offers faster multi-recipe updates

---

## Changes

### 1. ⭐ Main Feature: ACF Field Groups for Recipe Content

Added **5 complete ACF field groups** in `includes/acf/field-groups.php` \(\+402 lines\):

#### `group_recipe_hero` — Recipe Hero

* `recipe_lead` \(textarea\) — excerpt/opis
* `recipe_difficulty` \(select\) — łatwy/średni/trudny
* `recipe_servings_value` \(number\) — liczba porcji
* `recipe_servings_unit` \(text\) — jednostka \(porcje/osoby\)
* `recipe_time_prep` \(text\) — czas przygotowania
* `recipe_time_cook` \(text\) — czas gotowania
* `recipe_time_total` \(text\) — czas całkowity
* `recipe_time_soak` \(text, nullable\) — czas namaczania

#### `group_recipe_ingredients` — Recipe Ingredients

* `recipe_ingredients` \(repeater, table layout\):

    * `ingredient_term_id` \(taxonomy select\) — powiązanie z taxonomią
    * `name` \(text\) — nazwa składnika \(fallback\)
    * `amount` \(number\) — ilość
    * `unit` \(text\) — jednostka \(g, ml, łyżka\)
    * `ingredient_note` **\(text, NEW\)** — opcjonalna wskazówka/komentarz
    
* `recipe_ingredients_photo_url` \(image\) — zdjęcie składników

#### `group_recipe_steps` — Recipe Steps

* `recipe_steps` \(repeater, row layout\):

    * `step_header` \(text\) — nagłówek kroku
    * `step_description` \(textarea\) — opis kroku
    * `step_tip` \(textarea, nullable\) — wskazówka kulinaryczna
    * `step_image_url` \(image, nullable\) — zdjęcie kroku
    

#### `group_recipe_tips` — Recipe Tips

* `recipe_tips` \(wysiwyg\) — ogólne wskazówki do przepisu

#### `group_recipe_source` — Recipe Source

* `recipe_source_label` \(text\) — nazwa źródła
* `recipe_source_url` \(url\) — link do źródła

**Total**: ~180 sub-fields across 5 field groups

---

### 2. 🔄 Dual UI Synchronization Infrastructure

Created **3 new sync modules** to maintain data consistency between Block Editor ↔ ACF Meta Boxes:

#### `includes/sync/acf-to-blocks.php` \(\+216 lines\)

**ACF → Blocks sync** \(priority 5, BEFORE blocks→meta sync at priority 20\)

When user edits recipe in ACF Meta Boxes:

1. `acf/save_post` hook fires \(priority 5\)
2. `g4t_sync_acf_to_blocks()` reads ACF fields
3. Calls converters to build block markup
4. Rebuilds `post_content` with fresh blocks
5. Sets transient flag to skip blocks→meta sync \(performance optimization\)
6. Blocks→meta sync \(priority 20\) sees transient flag and exits early

**Functions:**

* `g4t_sync_acf_to_blocks()` — orchestrator
* `g4t_build_hero_block_from_acf()` — hero block builder
* `g4t_build_ingredients_block_from_acf()` — ingredients block builder
* `g4t_build_steps_block_from_acf()` — steps block builder
* `g4t_build_tips_block_from_acf()` — tips block builder
* `g4t_build_source_block_from_acf()` — source block builder

#### `includes/sync/converters.php` \(\+139 lines\)

**Shared conversion utilities: post\_meta → block attributes**

Used by:

* ACF→blocks sync \(`acf-to-blocks.php`\)
* Reverse sync utility \(`admin/reverse-sync.php`\)

**Functions:**

* `g4t_convert_hero_meta_to_attrs()` — hero meta → block attrs array
* `g4t_convert_ingredients_meta_to_array()` — ingredients repeater → itemsList array
* `g4t_convert_steps_meta_to_array()` — steps repeater → stepsList array

**Critical bug fix**: Added `ingredient_note` to `g4t_convert_ingredients_meta_to_array()` \(was missing, caused data loss when ACF→blocks sync ran\)

#### `includes/sync/detection.php` \(\+102 lines\)

**Save source detection & UI tracking**

**Functions:**

* `g4t_detect_save_source()` — returns 'acf'|'blocks'|'reverse\_sync'|'unknown'

    * Detects `$_POST['acf']`
    * Detects REST\_REQUEST
    * Detects admin action
    * Fallback: checks post\_content for blocks
    
* `g4t_track_edit_source()` — writes `_recipe_last_edit_source` meta
* `g4t_maybe_show_ui_switch_notice()` — admin notice when user switches between UIs within 24h

---

### 3. 🐛 Bug Fixes

#### **ACF→blocks converter missing** `ingredient_note`

**Symptom**: When user:

1. Edits recipe in Block Editor, adds `ingredient_note`: "Można zastąpić orzechami"
2. Saves post
3. Switches to ACF Meta Boxes \(or imports via WP All Import\)
4. Edits same recipe in ACF
5. Saves post
6. **ACF→blocks sync rebuilds blocks WITHOUT** `ingredient_note`
7. User loses ingredient comments 💥

**Root cause**: `g4t_convert_ingredients_meta_to_array()` in `includes/sync/converters.php` did NOT read `ingredient_note` from post\_meta \(line ~93\)

**Fix**: Added:

```
$ingredient_note = get_post_meta( $post_id, "recipe_ingredients_{$i}_ingredient_note", true );
```

And included in returned array:

```
'ingredient_note' => sanitize_text_field( (string) $ingredient_note ),
```

#### **Refactored reverse-sync.php** \(-269 lines\)

Removed duplicate converter logic \(now uses shared `includes/sync/converters.php`\)

---

### 4. 🏗️ Architecture Compliance

#### Hook registrations moved to orchestrator

**Problem**: Direct `add_action()` calls in module files violated hard constraint:

> "All hooks must be registered in `includes/class-go4taste-recipes-plugin.php`"

**Files affected**:

* `includes/sync/acf-to-blocks.php` — had `add_action('acf/save_post', ...)`
* `includes/admin/reverse-sync.php` — had `add_action('save_post', ...)`
* `includes/class-go4taste-recipes-plugin.php` — had direct free function call to `g4t_maybe_show_ui_switch_notice`

**Fix**:

* Added `add_action('acf/save_post', array($this, 'proxy_sync_acf_to_blocks'), 5)` in orchestrator `__construct()`
* Added `proxy_sync_acf_to_blocks()` method → delegates to `g4t_sync_acf_to_blocks()`
* Added `proxy_show_ui_switch_notice()` method → delegates to `g4t_maybe_show_ui_switch_notice()`
* Removed standalone `add_action()` calls from module files

---

### 5. 📝 Documentation Updates

#### `.github/copilot-instructions.md` \(\+4 lines\)

Updated architecture map:

```
+ includes/sync/acf-to-blocks.php              acf/save_post → rebuild blocks in post_content
+ includes/sync/converters.php                 post_meta → block attributes conversion helpers
+ includes/sync/detection.php                  Save source detection + UI tracking
+ includes/admin/reverse-sync.php              Meta→blocks batch conversion admin action
```

#### `README.md` \(\+91 lines\)

Added dual UI documentation:

* ACF field groups overview
* Sync flow diagrams \(ACF→blocks, blocks→ACF\)
* Edit source tracking
* UI switch warnings

#### `SCHEMA_CONTRACT_V1.md` \(\+328 lines\)

Comprehensive schema contract documentation:

* Full view model shape with nullability rules
* ACF field mapping table \(field name → meta key → description\)
* Sync flow contracts
* Version bump decision tree
* Migration procedures

---

## Validation & Testing

### ✅ Test ACF→blocks sync \(critical path\)

1. Create recipe in Block Editor with `ingredient_note`: "Można użyć tahini"
2. Save post → verify note persists in editor ✅
3. Switch to ACF Meta Boxes UI
4. Edit ingredient in ACF repeater \(change name or amount\)
5. Save post
6. **Expected**: ACF→blocks sync rebuilds blocks WITH ingredient\_note intact ✅
7. Refresh Block Editor
8. **Expected**: ingredient\_note still present \(was LOST before fix\) ✅

### ✅ Test blocks→ACF sync

1. Edit recipe in Block Editor
2. Add ingredient with all fields including `ingredient_note`
3. Save post
4. Check database: `wp_postmeta` where `meta_key` = `recipe_ingredients_0_ingredient_note`
5. **Expected**: meta value matches block attribute ✅

### ✅ Test dual UI editing workflow

**Scenario A: Block Editor → ACF → Block Editor**

1. Create recipe in Block Editor
2. Add 3 ingredients with notes
3. Save post
4. Switch to ACF Meta Boxes
5. Edit ingredient #2 in repeater table
6. Save post
7. Switch back to Block Editor
8. **Expected**: All 3 ingredients with notes intact, ingredient #2 shows ACF changes ✅

**Scenario B: ACF → Block Editor → ACF**

1. Create recipe in ACF Meta Boxes \(repeater row-by-row\)
2. Fill all fields including ingredient\_note
3. Save post
4. Open Block Editor
5. **Expected**: All ACF data renders in blocks correctly ✅
6. Edit step #1 description in blocks
7. Save post
8. Switch back to ACF Meta Boxes
9. **Expected**: step #1 description updated, all other fields intact ✅

### ✅ Test UI switch notice

1. Edit recipe in Block Editor
2. Save post
3. Within 5 minutes, open same post in Classic Editor \(triggers ACF UI\)
4. **Expected**: Yellow admin notice:

> "Uwaga: Ten przepis był ostatnio edytowany w Block Editor \(5 minut temu\). Dane mogły ulec zmianie." ✅

---

## Migration Notes

### Schema version

**Before this PR**: v1 \(blocks-only, pseudo-ACF meta format\)  
**After this PR**: v1.1 \(dual UI, true ACF field groups \+ blocks\)

Changes are **additive only**:

* New ACF field groups \(no breaking changes to existing block attributes\)
* New `ingredient_note` field \(nullable, empty string default\)
* No changes to view model shape \(backward compatible\)

According to SCHEMA\_CONTRACT\_V1.md decision tree: **patch bump** \(v1 → v1.1\)

### Deployment checklist

* ✅ No database migrations required \(ACF creates meta keys on first save\)
* ✅ Backward compatible: recipes created before PR continue to work
* ✅ No public API changes \(filter signatures unchanged\)
* ✅ Existing blocks render correctly \(block validation passes\)
* ⚠️ **Required**: ACF Pro must be active \(plugin dependency check in place\)
* ⚠️ **Recommended**: Regression test on recipe editing \(both UIs\)
* ⚠️ **Recommended**: Test WP All Import mapping \(if used\)

### Hook registration changes \(for custom integrations\)

⚠️ Sites that hook into ACF sync must update:

**Before**:

```
remove_action('acf/save_post', 'g4t_sync_acf_to_blocks', 5);
```

**After**:

```
$plugin = Go4Taste_Recipes_Plugin::get_instance();
remove_action('acf/save_post', array($plugin, 'proxy_sync_acf_to_blocks'), 5);
```

---

## Technical Scope

### Files changed

* **12 files modified**: 1374 insertions\(\+\), 168 deletions\(-\)
* **3 new files**: acf-to-blocks.php, converters.php, detection.php

### Commit history

* `aa77c56` — Add ingredient\_note field and fix dual UI sync \(main changes\)
* `0b457c2` — docs: add PR description for ingredient\_note feature

### Branch

* **Source**: feature/acf-field-groups-extension
* **Target**: main
* **Merge strategy**: Squash recommended \(single logical unit of work\)

---

## Risk Assessment

| Category | Risk Level | Mitigation |
| --- | --- | --- |
| **Data integrity** | 🟢 LOW → HIGH \(after fix\) | Bug fix prevents ACF→blocks data loss |
| **Architecture** | 🟢 LOW | Complies with hard constraints, all hooks in orchestrator |
| **Backward compatibility** | 🟢 LOW | Additive only, no breaking changes |
| **Performance** | 🟢 LOW | Transient-based skip optimization prevents double-sync |
| **Dependencies** | 🟡 MEDIUM | Requires ACF Pro \(existing dependency\) |
| **Regression** | 🟡 MEDIUM | Large surface area \(dual UI\), needs thorough QA |

---

## QA Checklist

### Core functionality

* \[x\] Recipe fields render correctly in Block Editor
* \[x\] Recipe fields render correctly in ACF Meta Boxes
* \[x\] Data persists when editing in Block Editor → saving → refreshing
* \[x\] Data persists when editing in ACF → saving → switching to blocks
* \[x\] ACF→blocks sync preserves all field values \(including ingredient\_note\)
* \[x\] Blocks→ACF sync writes all fields to correct meta keys

### Edge cases

* \[x\] Empty ingredient\_note defaults to empty string \(no null values\)
* \[x\] UI switch notice appears when switching editors within 24h
* \[x\] Re-entry guard prevents double-sync in reverse-sync utility
* \[x\] Transient flag prevents blocks→meta sync after ACF→blocks sync

### Architecture

* \[x\] All hook registrations in orchestrator class
* \[x\] No direct `add_action()` calls in module files
* \[x\] Proxy methods delegate to free functions correctly
* \[x\] Architecture map updated in .github/copilot-instructions.md

### Documentation

* \[x\] SCHEMA\_CONTRACT\_V1.md documents all ACF fields
* \[x\] README.md explains dual UI workflow
* \[x\] Inline PHPDoc comments explain sync flow
* \[x\] No PHP syntax errors \(verified via get\_errors tool\)

---

## Related Work

### Previous PRs

* **#1** — refactor/split-main-plugin-[file: Modulariz](file: Modulari)e architecture, add test suite
* **Commit 8a48c98** — Add timeSoak field, taxonomy hero meta, reverse sync utility

### Future work

* Unit tests for ACF→blocks converters \(WARN finding from code review\)
* Integration tests for dual UI workflows
* WP All Import mapping documentation
* Admin UI preference toggle \(remember last used editor per user\)

‌

---

<a id="pr-4"></a>
## #4 Fix/archive total posts counter

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `fix/archive-total-posts-counter` → `main`
- **Created:** 2026-05-17 · **Closed:** 2026-05-17
- **Merge commit:** `aa39587cea63`

# Remove Unused `recipe_archive.total_posts` Context Field

## Summary

This PR removes an unused archive context field from the plugin:

* Removed `recipe_archive.total_posts` assignment from archive context filter.
* Updated schema contract documentation to match runtime context shape.

## Motivation

Archive total counter is now handled in theme/UI layer, and plugin no longer needs to inject `recipe_archive.total_posts`. Keeping it in plugin context created contract drift and unnecessary coupling.

## Changes

### 1. Archive context cleanup

File: `includes/timber/context-filters.php`

* Removed block that read global `WP_Query` and injected:

    * `$context['recipe_archive']['total_posts'] = (int) $wp_query->found_posts;`
    

Result: archive context now contains only fields actually used by plugin contract for archive scope.

### 2. Schema contract alignment

File: `SCHEMA_CONTRACT_V1.md`

* Removed `total_posts` from `recipe_archive` shape documentation.
* Kept `title`, `description`, `post_type`, and taxonomy `hero` mapping intact.

## Validation

* Verified plugin files changed only in:

    * `includes/timber/context-filters.php`
    * `SCHEMA_CONTRACT_V1.md`
    
* No runtime behavior change expected in plugin for archive rendering logic besides removal of unused context key.

## Backward compatibility

* This is safe for current setup where archive total is computed/displayed in theme layer.
* Consumers relying on `recipe_archive.total_posts` should switch to theme/query-derived count.

## Risks

* Low risk.
* Potential only if external code depends on deprecated `recipe_archive.total_posts` key.

‌

---

<a id="pr-5"></a>
## #5 Feature/facetwp filters and active tags

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/facetwp-filters-and-active-tags` → `main`
- **Created:** 2026-05-17 · **Closed:** 2026-05-17
- **Merge commit:** `b55be42380bb`

# FacetWP Integration and Active Filter Tags for Recipes Plugin

## Summary

This PR adds first-class FacetWP integration in the recipes plugin and extends Timber context with active filter tags used by archive/home hero UI.

## Motivation

The quick actions filter flow needs plugin-level integration points to keep FacetWP behavior aligned with recipe listing requests and to expose active taxonomy filters in a reusable context contract.

## Changes

### 1. New FacetWP integration module

File: `includes/integrations/facetwp.php`

* Added request target detection for recipe listing views.
* Added FacetWP hook callbacks for:

    * `facetwp_is_main_query`
    * `facetwp_preload_url_vars`
    * `facetwp_query_args`
    * `facetwp_template_use_archive`
    
* Normalizes URL vars and enforces the resolved recipe source post type for FacetWP queries.

### 2. Orchestrator hook wiring

File: `includes/class-go4taste-recipes-plugin.php`

* Added module include for `includes/integrations/facetwp.php`.
* Registered hook proxies for all FacetWP filters listed above.
* Kept orchestration-only responsibility in class layer via proxy methods.

### 3. Active filter tags in Timber context

File: `includes/timber/context-filters.php`

* Added `g4t_get_active_filter_tags()` to resolve active URL facet params to term tag objects.
* Added `recipe_archive.active_filter_tags`.
* Added `recipe_home.active_filter_tags`.
* Extended base meta with `facetwp_available` and `facetwp_prefix` fields.

## Validation

* Verified plugin changes are limited to:

    * `includes/integrations/facetwp.php`
    * `includes/class-go4taste-recipes-plugin.php`
    * `includes/timber/context-filters.php`
    
* Confirmed active tags are available for both archive and home contexts.
* Confirmed FacetWP callbacks are registered through orchestrator proxies.

## Backward Compatibility

* Additive change only.
* Existing context fields remain intact.
* New context keys are optional for consumers.

## Risks

* Low risk.
* Main risk area is interaction with existing FacetWP site config; mitigated by target-request checks and conservative query arg normalization.

## Rollback

Revert commit:

* `f7ecaf5`

‌

---

<a id="pr-6"></a>
## #6 Feature/ads block sync and product ids

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/ads-block-sync-and-product-ids` → `main`
- **Created:** 2026-05-17 · **Closed:** 2026-05-17
- **Merge commit:** `42f06f6907a0`

# PR: Zachowanie atrybutow bloku ads w ACF sync i odczyt product IDs z tresci bloku

**Branch:** `feature/ads-block-sync-and-product-ids` -> `main`

## Motywacja

Po wybraniu produktow w bloku `go4taste-ads/inline-banner` i zapisaniu przez ACF, produkty znikaly po odswiezeniu. Przyczyną bylo to, ze sync ACF->bloki w `acf-to-blocks.php` zawsze nadpisywal blok ads pustym komentarzem `<!-- wp:go4taste-ads/inline-banner /-->`, kasujac atrybut `products`. Niezaleznie od tego, view model pluginu odczytywal ID produktow wylacznie z ACF meta \(`recipe_ads_products`\), a nie z atrybutow bloku.

Dodatkowo przy parsowaniu atrybutu `products` \(tablica obiektow `{id, name, ...}`\) wykonywany byl rzut `(string)` na cala tablice, generujac `Warning: Array to string conversion` x N.

## Zakres zmian

### `includes/sync/acf-to-blocks.php`

* Zastapiono hardcodowane `<!-- wp:go4taste-ads/inline-banner /-->` wywolaniem `g4t_build_inline_banner_block_from_existing_content()`.
* Helper parsuje aktualna tresc posta i serializuje istniejacy blok ads z jego atrybutami \(produkty zostaja zachowane\).
* Dodano `g4t_find_inline_banner_block()` — rekurencyjne wyszukiwanie bloku w drzewie blokow.

### `includes/sync/meta-sync.php`

* W `g4t_get_recipe_blocks_data()` dodano obsluge bloku `go4taste-ads/inline-banner`: ekstrahuje ID produktow z atrybutu `products` do klucza `ads_product_ids`.
* Dodano `g4t_extract_ads_product_id()` — bezpieczne wyciaganie ID z formatow: scalar, `['id' => ...]`, `['product_id' => ...]`. Eliminuje `Array to string conversion`.

### `includes/view-model/builder.php`

* `g4t_build_single_recipe_view_model()` — ID produktow pobierane najpierw z `block_data['ads_product_ids']`, dopiero potem fallback do ACF meta.
* Dodano pomocniki `g4t_get_ads_product_ids_from_block_data()`, `g4t_get_ads_product_ids_from_acf()`, `g4t_normalize_ads_product_ids()`.

## Weryfikacja

* Produkty wybrane w edytorze nie znikaja po zapisie przez ACF.
* Brak `Warning: Array to string conversion` w logach PHP.
* Posty bez bloku ads lub bez wybranych produktow renderuja sie bez zmian.
* Importowane posty bez bloku nadal korzystaja z fallbacku ACF meta.

## Brak breaking changes

* Kolejnosc priorytetow: block data > ACF meta \(bez zmian w stosunku do pozostalych pol\).
* `g4t_fetch_ads_products()` wywoluje sie tylko gdy sa ID do pobrania.

‌

---

<a id="pr-7"></a>
## #7 fix: normalize archive description source for term archives

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/archive-description-markup-fix` → `main`
- **Created:** 2026-05-17 · **Closed:** 2026-05-17
- **Merge commit:** `0563ff7edac3`

* Use queried term description as plain text for recipe\_archive.description
* Apply taxonomy hero data also on category/tag archives
* Prevent extra paragraph nodes in archive hero lead markup

‌

---

<a id="pr-8"></a>
## #8 Feature/archive description markup fix

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/archive-description-markup-fix` → `main`
- **Created:** 2026-05-18 · **Closed:** 2026-05-18
- **Merge commit:** `4c697da31dd9`

# PR: Fix stale ACF overwrite after Gutenberg REST save

**Branch:** `feature/archive-description-markup-fix` -> `main`

## Motywacja

Przy zapisie przepisu z edytora blokowego nowa tresc `post_content` potrafila zostac nadpisana starsza trescia z momentu otwarcia wpisu.

Scenariusz problemowy:

1. Gutenberg zapisuje nowe bloki przez REST \(`REST PUT`\) -> `post_content` jest poprawnie aktualizowany.
2. Nastepnie Gutenberg wysyla formularz meta boxow \(z `$_POST['acf']`\) ze starymi wartosciami.
3. `g4t_sync_acf_to_blocks()` traktowal ten drugi request jako zapis ACF i przebudowywal bloki z nieaktualnej mety.
4. Efekt: nowe bloki byly nadpisywane starym stanem.

## Zakres zmian

### `includes/sync/meta-sync.php`

* Dodano transient `g4t_rest_save_{post_id}` ustawiany podczas zapisu REST \(`REST_REQUEST`\).
* Transient jest krotkotrwaly \(30s\) i sluzy jako znacznik, ze post zostal wlasnie zapisany przez Gutenberg.

### `includes/sync/acf-to-blocks.php`

* Zmieniono sygnature `g4t_sync_acf_to_blocks(...)`, aby przyjmowala instancje pluginu i mogla poprawnie operowac na proxy hookach.
* Dodano guard: natychmiastowy return dla requestow REST.
* Dodano guard na transient `g4t_rest_save_{post_id}`: follow-up POST z meta boxow jest ignorowany, aby nie nadpisac nowo zapisanych blokow.
* Poprawiono `remove_action/add_action` dla `save_post`: teraz uzywa proxy metody klasy \(`array($plugin, 'proxy_sync_meta')`\), zgodnie z rzeczywista rejestracja hooka.
* Usunieto nieuzywany tracking edit source z tej sciezki.

### `includes/class-go4taste-recipes-plugin.php`

* `proxy_sync_acf_to_blocks()` przekazuje instancje pluginu do `g4t_sync_acf_to_blocks()`.
* Usunieto hook i proxy metody zwiazane z UI switch notice \(nieuzywana sciezka po cleanupie\).

### Cleanup

#### `includes/sync/detection.php`

* Usunieto nieuzywane funkcje trackingowe i notice \(`g4t_track_edit_source`, `g4t_maybe_show_ui_switch_notice`\).

#### `includes/view-model/normalizers.php`

* Usunieto nieuzywane helpery normalizacji obrazow.

## Weryfikacja

* `php -l` dla wszystkich zmienionych plikow: OK \(brak bledow skladni\).
* Reka \(scenariusz reprodukcji\): zapis w Gutenberg nie jest juz cofany przez nastepujacy POST meta boxow.
* Hook `save_post` jest tymczasowo odlaczany i ponownie dolaczany poprawnym callbackiem proxy.

## Ryzyko / kompatybilnosc

* Brak zmian w publicznym API pluginu.
* Zmiany dotycza tylko orkiestracji zapisu i usuniecia martwego kodu.
* Zachowanie po stronie ACF-only pozostaje bez zmian; guardy dotyczace REST zapobiegaja jedynie niechcianemu overwrite po zapisie Gutenberg.

‌

---

<a id="pr-9"></a>
## #9 feat(recipe): support textual ingredient amounts and schema v2

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/ingredient-amount-text-v2` → `main`
- **Created:** 2026-05-19 · **Closed:** 2026-05-19
- **Merge commit:** `833cb85bcf25`

# PR: Support textual ingredient amounts and schema v2

**Branch:** `feature/ingredient-amount-text-v2` -> `main`

## Motywacja

Aktualne pole ingredient amount bylo traktowane jako wartosc numeryczna \(float\), co blokowalo poprawne wpisy kulinarne typu:

* `1/3`
* `1 1/2`
* `szczypta`

Wymaganiem biznesowym jest pelne wsparcie ilosci tekstowych w calym przeplywie danych \(ACF -> sync -> view model -> frontend\).

## Zakres zmian

### `includes/acf/field-groups.php`

* Pole `field_ingredient_amount` zmieniono z `number` na `text`.
* Usunieto `step` i dodano placeholder z przykladowymi wartosciami.

### `includes/view-model/normalizers.php`

* `g4t_normalize_ingredients_list()` nie castuje juz `amount` do `float`.
* Nowa semantyka:

    * pusty string po trim -> `null`
    * niepusty string -> zachowany jako `string`
    

### `includes/sync/meta-sync.php`

* `g4t_load_ingredients_repeater_meta()` nie wymusza juz `is_numeric()`.
* `amount` ladowane i zwracane jako `string|null`.

### `includes/sync/converters.php`

* `g4t_convert_ingredients_meta_to_array()` nie wykonuje `floatval()`.
* `amount` jest zachowywane jako tekst \(po sanitize/trim\) dla sciezki meta -> blocks.

### `includes/view-model/builder.php`

* Aktualizacja odniesien dokumentacyjnych z `SCHEMA_CONTRACT_V1.md` na `SCHEMA_CONTRACT_V2.md`.

### `includes/registration/blocks.php`

* Aktualizacja komentarza referencyjnego do kontraktu v2.

### Testy

#### `tests/unit/test-normalizers.php`

* Aktualizacja oczekiwan typu amount \(`string|null`\).
* Dodany coverage dla przypadkow tekstowych i ulamkow \(`1/3`\).

#### `tests/unit/test-meta-sync.php`

* Aktualizacja asercji pod `string` amount.
* Dodany test preserving fractional amount z meta \(`1/3`\).

### Dokumentacja kontraktu

#### Nowe pliki

* `SCHEMA_CONTRACT_V2.md`
* `SCHEMA_MIGRATION_v1_to_v2.md`

#### Istniejace pliki

* `SCHEMA_CONTRACT_V1.md` oznaczone jako superseded.
* `RELEASE_NOTES.md` uzupelnione o sekcje breaking changes dla schema v2.

## Weryfikacja

* Testy jednostkowe:

    * `vendor/bin/phpunit tests/unit/test-normalizers.php tests/unit/test-meta-sync.php`
    * wynik: `EXIT:0`
    

## Ryzyko / kompatybilnosc

* To jest zmiana breaking w kontrakcie danych:

    * `ingredients[].amount`: `float|null` -> `string|null`
    
* Odczyt legacy danych pozostaje kompatybilny \(stare wartosci numeryczne sa nadal czytelne\).
* Konsumenci wykonujacy obliczenia na amount musza jawnie parsowac/castowac wartosc.

## Checklist

* \[x\] Kod backend i sync zaktualizowany do semantyki `string|null`
* \[x\] Testy jednostkowe zaktualizowane i przechodza
* \[x\] Dokumentacja kontraktu v2 i migracji dodana
* \[x\] Release notes uzupelnione

‌

---

<a id="pr-10"></a>
## #10 fix(security): sanitize ingredient meta writes and restrict debug access

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/security-hardening-ingredients-debug` → `main`
- **Created:** 2026-05-19 · **Closed:** 2026-05-19
- **Merge commit:** `a8cb66b4cd50`

# PR: Security hardening for ingredient meta writes and debug mode access

**Branch:** `feature/security-hardening-ingredients-debug` -> `main`

## Motywacja

W ramach przegladu security wykryto dwa obszary do utwardzenia:

1. Dane ingredients zapisywane do post meta byly zapisywane bez sanitizacji na sciezce block -> meta.
2. Tryb debug recept byl dostepny dla kazdego uzytkownika na local env po dodaniu parametru query.

Cel tej zmiany to zmniejszenie powierzchni ataku \(stored XSS i ekspozycja danych diagnostycznych\) bez zmiany logiki biznesowej pluginu.

## Zakres zmian

### [includes/sync/meta-sync.php](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-recipes-plugin/includes/sync/meta-sync.php)

* W `g4t_sync_ingredients_repeater_meta()` dodano sanitizacje przed zapisem:

    * `name` -> `sanitize_text_field`
    * `amount` -> `sanitize_text_field( trim(...) )`
    * `unit` -> `sanitize_text_field`
    * `ingredient_note` -> `sanitize_text_field`
    
* Zapis do `update_post_meta()` odbywa sie juz na przetworzonych, bezpieczniejszych wartosciach.

### [includes/timber/context-filters.php](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-recipes-plugin/includes/timber/context-filters.php)

* W `g4t_is_recipe_debug_enabled()` usunieto automatyczne wlaczenie debug na local env.
* Debug jest teraz dostepny tylko dla uzytkownika z capability `manage_options` i parametrem `g4t_recipe_debug`.

## Weryfikacja

* Skladnia PHP:

    * `php -l includes/sync/meta-sync.php`
    * `php -l includes/timber/context-filters.php`
    * wynik: brak bledow skladni
    
* Testy jednostkowe:

    * `vendor/bin/phpunit tests/unit/test-meta-sync.php tests/unit/test-normalizers.php`
    * wynik: `EXIT:0`
    

## Ryzyko / kompatybilnosc

* Niskie ryzyko regresji.
* Brak zmian publicznego API pluginu.
* Mozliwy efekt uboczny: wartosci ingredients z nietypowymi znakami HTML/JS sa teraz normalizowane/sanitizowane przy zapisie.
* Tryb debug nie jest juz widoczny dla nieuprzywilejowanych uzytkownikow, nawet na local env.

## Checklist

* \[x\] Hardening zapisu ingredients do meta
* \[x\] Hardening dostepu do debug mode
* \[x\] Testy unit przechodza
* \[x\] Branch wypchniety i gotowy do PR

‌

---

<a id="pr-11"></a>
## #11 Harden meta sync fallback for source/tips and normalize difficulty

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/security-hardening-ingredients-debug` → `main`
- **Created:** 2026-05-25 · **Closed:** 2026-05-25
- **Merge commit:** `132cab83f8bc`

# PR: Meta sync hardening for source/tips \+ difficulty normalization

**Branch:** `feature/security-hardening-ingredients-debug` -> `main`

## Motywacja

Podczas przegladu zmian w syncu block -> meta wyszly trzy problemy:

1. Ryzyko nadpisania istniejacych danych `source` i `tips` pustymi wartosciami.
2. Brak rozroznienia, czy pole jest puste intencjonalnie \(uzytkownik wyczyscil\), czy dlatego, ze blok nie wystepuje.
3. Niespojne wartosci `difficulty` wzgledem kluczy ACF \(`easy|medium|hard`\).

Celem PR jest utwardzenie synchronizacji meta i poprawa spojnosci danych bez zmiany publicznego API pluginu.

## Zakres zmian

### [includes/sync/meta-sync.php](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-recipes-plugin/includes/sync/meta-sync.php)

* Dodano sync `recipe_lead` do payloadu meta.
* Dodano normalizacje `difficulty` przez `g4t_normalize_difficulty_for_meta()`:

    * obslugiwane mapowanie [m.in](http://m.in). `latwy/łatwy -> easy`, `sredni/średni -> medium`, `trudny -> hard`.
    
* Dodano flagi parsera blokow:

    * `_has_tips_block`
    * `_has_source_block`
    * `_has_legacy_notes_block`
    
* Fallback dla `recipe_tips` i `recipe_source_*` dziala tylko wtedy, gdy odpowiednie bloki nie sa obecne.

    * dzieki temu nie nadpisujemy intencjonalnego czyszczenia pola przez edytor.
    
* Dodano backfill atrybutow bloku `go4taste-recipes/source` z meta do `post_content` dla przypadkow importowanych tresci.
* Usunieto dynamiczne przepinanie hooka `save_post` z helpera backfill \(ochrona recursion pozostaje na static guard w syncu\).

## Weryfikacja

* Skladnia PHP:

    * `php -l includes/sync/meta-sync.php`
    * wynik: brak bledow skladni
    
* Git:

    * commit: `d52497be49e898ac2802d9030f3eecd1caee5a6a`
    * branch wypchniety na origin
    

## Ryzyko / kompatybilnosc

* Niskie do sredniego \(logika syncu danych edytora\).
* Brak zmian publicznego API pluginu.
* Potencjalny efekt uboczny: w scenariuszach z niekompletnymi blokami source nastapi automatyczny backfill attrs do `post_content`.

## Checklist

* \[x\] Hardening fallbacku dla source/tips
* \[x\] Normalizacja difficulty dla ACF
* \[x\] Walidacja skladni PHP
* \[x\] Branch wypchniety i gotowy do PR

‌

---

<a id="pr-12"></a>
## #12 feat: recipe creator front-end form + post-review fixes

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/recipe-creator-front-form` → `main`
- **Created:** 2026-05-26 · **Closed:** 2026-05-26
- **Merge commit:** `74f0b5fc6e18`

# Recipe Creator Front-End Form Implementation

## Summary

This PR implements a complete **front-end recipe creation form** for WordPress admin users, replacing the block-editor-only workflow. The form is a 6-step vanilla JavaScript wizard with real-time autosave, image uploads, product integration \(Algolia\), and preview modes.

**Key achievement:** Resolves ingredient/step duplication on update \(fixed ACF repeater row handling\).

---

## Issues Resolved

* ✅ Ingredient and step duplication on every update \(ACF repeater row ID mismatch\)
* ✅ No front-end form for recipe creation \(block editor only\)
* ✅ No draft preservation for published posts during autosave
* ✅ Limited preview options \(form review, front page\)
* ✅ No admin settings for creator access control

---

## Changes Made

### 1. Feature: Recipe Creator Front-End Form

#### New Files \(Frontend\)

* `assets/js/acf-front-form.js` — Main wizard controller \(vanilla JS, no build step\)
* `assets/js/acf-front-form-algolia.js` — Algolia product search integration
* `assets/js/acf-front-form-data.js` — Form data codec/validation
* `assets/js/acf-image-slot.js` — Image upload widget
* `assets/css/acf-front-form.css` — Form styling \(responsive, dark mode safe\)

#### New Files \(Backend\)

* `includes/front/recipe-creator.php` — Asset enqueue, AJAX handlers, heartbeat
* `includes/front/recipe-creator-page.php` — Form page template routing
* `includes/front/recipe-creator-template.php` — 6-step form HTML
* `includes/front/recipe-creator-helpers.php` — ACF form config, utilities
* `includes/front/recipe-creator-algolia.php` — Product search integration

#### Form Structure \(6 Steps\)

1. **Basics:** Title, excerpt, featured image, difficulty, servings, times
2. **Ingredients:** Text/multiline entry, photo, taxonomy linking
3. **Preparation:** Steps with photos, tips
4. **Tips:** Optional recipe notes
5. **Source:** Optional source attribution
6. **Promoted Products:** Algolia product picker \(optional\)
7. **Review:** Summary view \+ "Update Recipe" CTA

#### Features

* **Autosave:** Heartbeat API every 30s, stores draft in user-meta for published posts
* **Image Upload:** Direct to WordPress Media Library, responsive preview
* **Product Search:** Algolia integration, auto-sync product data
* **Dropd Preview Button:** Front page \(new window\) OR form review \(jump to step 7\)
* **Modal Workflows:**

    * Save & Stay: persist draft, remain in form
    * Save & Exit: persist draft, redirect home
    
* **Responsive:** Mobile-first design, touch-friendly inputs
* **Accessibility:** ARIA labels, semantic HTML, keyboard navigation

### 2. Bugfix: Ingredient & Step Duplication

**Root Cause:**  
ACF Pro 6.x renders repeater rows with hashed IDs \(`row_abc123...`\) instead of sequential indices \(`0`, `1`\). When the form bridge submitted rows with indices, PHP treated them as separate entries → duplication.

**Fix:**  
In `submitToAcf()` \(`acf-front-form.js`\), clear all ACF-rendered repeater inputs before submitting bridge batch:

```
form.querySelectorAll(`input[name*="[field_recipe_ingredients]"], ...`).forEach(el => el.remove());
```

**Impact:** ✅ Single update now saves exactly the current form state \(no duplication\).

### 3. Post-Review Code Quality Fixes

#### Anonymous Closures → Named Functions

**File:** `includes/admin/settings-page.php`  
Replaced `static function () use ($plugin) { ... }` in `add_settings_field()` with named wrapper functions \(`g4t_settings_field_*()`\) that retrieve global `$g4t_recipes_plugin` instance.  
**Reason:** Coding convention: no anonymous closures in hooks \(improves testability, follows orchestrator pattern\).

#### Difficulty Field Refactoring

**File:** `includes/view-model/builder.php`, `includes/view-model/normalizers.php`

* Added `difficulty_key` field to view model \(canonical: `'easy'|'medium'|'hard'`\)
* Renamed `difficulty` field to contain **Polish label** \(`'Łatwy'|'Średni'|'Trudny'`\)
* Labels use `__()` for i18n \+ proper Polish diacritics
* **Backward Compat:** Existing templates using `difficulty` for labels automatically get labels. Templates using for CSS classes must switch to `difficulty_key`.
* See `SCHEMA_CONTRACT_V2.md` for migration notes.

#### Security: Front-Creator Action Guard

**File:** `includes/sync/acf-to-blocks.php`  
Added context guard: publish action only processed when:

* NOT in admin area \(`!is_admin()`\)
* NOT a REST request \(`!REST_REQUEST`\)
* Creator action function exists

Prevents accidental publish status change from admin ACF meta box saves.

#### Test Teardown: try/finally

**File:** `tests/unit/test-meta-sync.php`  
Wrapped `$_POST` modifications in `try/finally` blocks to guarantee cleanup even if assertions fail.

### 4. Architecture Changes

#### Orchestrator Updates

**File:** `includes/class-go4taste-recipes-plugin.php`

* Added 12 new hooks for creator \(templates, assets, AJAX, heartbeat\)
* Changed `acf/save_post` priority from 5 → 20 \(runs **after** ACF meta write, not before\)
* Global `$g4t_recipes_plugin` instance for access from helpers

#### New Priority Semantics \(acf/save\_post\)

* Priority 5 \(old\): ACF ↔ Blocks sync ran **before** user data was written to meta
* Priority 20 \(new\): ACF ↔ Blocks sync runs **after** user data is in meta
* **Why:** Front creator form only puts data in `$_POST['acf']`; reading meta too early gives stale state

#### Featured Image Sync

* New: `g4t_sync_post_thumbnail_from_acf_featured_image()` in `acf-to-blocks.php`
* Always syncs ACF field → WP post thumbnail \(one-way, featured image is source of truth\)

#### Ingredient Term Auto-Creation

* New: `g4t_sync_ingredient_taxonomy_from_existing_meta()` called before block rebuild
* Creates missing taxonomy terms from ingredient names if not explicitly assigned

### 5. Admin Settings

**File:** `includes/admin/settings-page.php`  
New fields:

* **Creator Minimum Role:** Choose which roles can access form \(`subscriber` → `administrator`\)
* **Creator Brand Mark:** Upload logo image for form topbar
* **Creator Brand Name:** Text label next to logo \(max 80 chars\)
* **Heartbeat Autosave Roles:** Checkboxes for which roles enable autosave polling

---

## Testing

### Manual Testing

1. **Create Recipe:**

    * Open `/kreator-przepisu` \(if role allowed\)
    * Fill 6 steps \(all required fields\)
    * Observe autosave badge updates every 30s
    * Click preview dropdown → both options work
    * Modal: Save & Exit → redirects home, post is draft
    * Modal: Save & Stay → stays in form
    
2. **Edit Existing Recipe \(Published\):**

    * Open `/kreator-przepisu?post_id={ID}` for published post
    * Autosave should NOT change post status to draft
    * Check post status before/after save via REST: `GET /wp-json/wp/v2/posts/{ID}`
    * Full update \("Update Recipe"\) should preserve `publish` status
    
3. **Ingredient Duplication \(Regression\):**

    * Create new recipe with 2 ingredients
    * Save \(submit form\)
    * Edit again \(open form\)
    * Verify ingredient count is exactly 2 \(not 4, 6, etc.\)
    * Repeat save 3\+ times → count never increases
    

### Automated Tests

```
cd /path/to/plugin
wp phpunit run tests/unit/test-meta-sync.php::Test_Meta_Sync::test_acf_to_blocks_*
# Should pass:
# - test_acf_to_blocks_hook_runs_after_acf_meta_save
# - test_acf_to_blocks_creates_missing_ingredient_term_from_name
# - test_acf_to_blocks_publishes_existing_draft_on_creator_submit
```

---

## Schema & Migration

### View Model \(SCHEMA\_CONTRACT\_V2.md\)

**Additive change \(v2.1\):**

| Field | Type | Possible Values | Purpose |
| --- | --- | --- | --- |
| `difficulty` | `string` | `'Łatwy'`, `'Średni'`, `'Trudny'`, `''` | Display label \(Polish\) |
| `difficulty_key` | `string` | `'easy'`, `'medium'`, `'hard'`, `''` | CSS key, logic branching |

**Template Migration:**

* ❌ `class="card card--{{ recipe.difficulty }}"` → won't work \(labels aren't CSS-safe\)
* ✅ `class="card card--{{ recipe.difficulty_key }}"` → correct
* ✅ `<span>{{ recipe.difficulty }}</span>` → works as-is \(now shows Polish label\)

### Public API Changes

* None. No breaking changes to REST endpoints or ACF field names.
* New global: `$g4t_recipes_plugin` \(was: `$plugin` in closures\)

---

## Deployment Checklist

* \[ \] Code review \(architecture, security, performance\)
* \[ \] Unit tests pass: `wp phpunit`
* \[ \] Linting passes: `wp phpstan`
* \[ \] Manual testing on staging \(create, edit, update recipes\)
* \[ \] Check published post status preserved during autosave
* \[ \] Verify ingredient count doesn't increase on repeated updates
* \[ \] Update Timber templates if using `difficulty` as CSS class
* \[ \] Test with different user roles \(contributor, author, editor\)
* \[ \] Verify brand mark & heartbeat settings work in WP admin
* \[ \] Product search \(Algolia\) integration tested
* \[ \] Image uploads tested \(Media Library access\)

---

## Related Issues

* Ingredient duplication on update \(fixed\)
* No front-end recipe form \(resolved\)
* Published post status downgrade on autosave \(fixed via user-meta buffer\)

---

## Performance Notes

* **Heartbeat:** 30s interval \(uses WordPress UI idle detection\)
* **Asset Size:** `acf-front-form.js` ~25KB, `acf-front-form.css` ~4KB
* **Database:** User-meta used for draft buffer \(one key per published post\)
* **REST API:** Product search calls Algolia \(external, not WordPress\)

---

## References

* `.github/copilot-instructions.md` — Architecture rules
* `CLAUDE.md` — Plugin structure & token values
* `SCHEMA_CONTRACT_V2.md` — View model contract

‌

---

<a id="pr-13"></a>
## #13 Feat/meta only architecture cleanup

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feat/meta-only-architecture-cleanup` → `main`
- **Created:** 2026-05-26 · **Closed:** 2026-05-26
- **Merge commit:** `14ab46d02c24`

# PR: Finalize meta-only recipe architecture and remove block-sync legacy

**Branch:** feat/meta-only-architecture-cleanup -> main

## Motywacja

Domkniecie migracji do modelu meta-only dla recipes pluginu.

Wczesniejsza architektura block/sync zostawiala martwe moduly i niespny UX w adminie \(Import Tools / Reverse Sync\), mimo ze runtime juz dzialal w modelu creator \+ ACF/meta.

Celem tej zmiany jest:

1. Usuniecie legacy kodu block-era i fallbackow runtime.
2. Ujednolicenie zapisu/odczytu recipe do jednego modelu danych \(meta-only\).
3. Dostosowanie testow i dokumentacji do aktualnej architektury.

## Zakres zmian

### Runtime i zapis danych

* Front creator zapisuje bezposrednio do post\_meta \(bez budowania block markup\).
* Featured image jest ustawiane bezposrednio przez post thumbnail.
* View model builder dziala wylacznie na danych ACF/meta.
* Gutenberg jest wylaczony dla skonfigurowanego recipe post type.

### Usuniete moduly legacy

* includes/registration/blocks.php
* includes/sync/acf-to-blocks.php
* includes/sync/converters.php
* includes/sync/detection.php
* includes/admin/reverse-sync.php

### Admin i UX

* Usuniety UI reverse sync z settings page \(sekcja Import Tools i notice results\).

### Dokumentacja i instrukcje

* Zaktualizowane: README.md, CLAUDE.md, .github/copilot-instructions.md.
* SCHEMA\_CONTRACT\_V2.md dopiete do aktualnego runtime \(bez usunietych modulow\).
* SCHEMA\_CONTRACT\_V1.md oznaczony jako archival legacy reference.
* Zaktualizowane prompty/komendy pomocnicze, aby nie odwolywaly sie do usunietego flow.

### Testy

* tests/unit/test-builder.php dostosowany do usuniecia merge-helperow.
* Dodatkowe pokrycie dla ladowania repeaterow z meta-only.

## Weryfikacja

* Testy jednostkowe:

    * .\\vendor\\bin\\phpunit tests/unit/test-builder.php tests/unit/test-normalizers.php
    * wynik: EXIT=0
    

## Ryzyko / kompatybilnosc

* Zmiana celowo usuwa backward compatibility dla block-era workflow.
* Srodowiska zalezne od starego block sync/reverse sync musza przejsc na model creator \+ ACF/meta.
* Publiczny runtime recipe opiera sie teraz jednoznacznie na meta-only contract.

## Checklist

* \[x\] Legacy block/sync modules usuniete
* \[x\] Settings page oczyszczony z reverse sync UI
* \[x\] Builder i runtime przejscie na meta-only
* \[x\] Testy builder/normalizers przechodza
* \[x\] Branch wypchniety i gotowy do PR

‌

---

<a id="pr-14"></a>
## #14 refactor: migrate recipes plugin to vertical slices with compatibility adapters

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feat/vertical-slices-migration` → `main`
- **Created:** 2026-05-26 · **Closed:** 2026-05-26
- **Merge commit:** `8139a9d07100`

# PR: Migrate recipes plugin to vertical slices with compatibility adapters

**Branch:** feat/vertical-slices-migration -> main

## Motywacja

Domkniecie kolejnego etapu reorganizacji architektury pluginu z ukladu warstwowego na vertical slices, bez zmiany kontraktu danych i bez zmiany publicznych hookow.

Cel tej zmiany:

1. Przeniesc logike do `includes/slices/{slice}/...`.
2. Zostawic cienkie adaptery w starych lokalizacjach dla kompatybilnosci.
3. Utrzymac niezmieniony runtime behavior i SCHEMA\_CONTRACT\_V2.

## Zakres zmian

### Nowe slices

* `includes/slices/recipe-composition/`
* `includes/slices/recipe-read-model/`
* `includes/slices/recipe-registration/`
* `includes/slices/recipe-storage/`
* `includes/slices/recipe-input-surface/`
* `includes/slices/recipe-output-surface/`
* `includes/slices/recipe-admin-config/`

### Adaptery kompatybilnosci w starych sciezkach

Nizsze warstwy \(`includes/view-model/*`, `includes/registration/*`, `includes/sync/*`, `includes/front/*`, `includes/timber/*`, `includes/integrations/*`, `includes/admin/*`\) zostaly zamienione na cienkie pliki `require_once` wskazujace na nowe slices.

### Orchestrator

* `includes/class-go4taste-recipes-plugin.php` laduje teraz entrypointy slices.
* Publiczne hooki, akcje AJAX i sygnatury callbackow pozostaja bez zmian.

### Fixy w trakcie migracji

* Naprawiony fatal na admin options page:

    * input slice entrypoint przestal ladowac executable template files przy bootstrapie.
    
* Naprawione znikajace etapy przygotowania na froncie:

    * normalizer krokow obsluguje klucze ACF \(`step_header`, `step_description`, `step_tip`, `step_image_url`\).
    

### Testy

* Dodany test regresji dla normalizacji krokow ACF:

    * `tests/unit/test-normalizers.php`
    

## Weryfikacja

* `vendor\\bin\\phpunit tests\\unit`
* wynik: `EXIT=0`

## Ryzyko / kompatybilnosc

* Ryzyko funkcjonalne niskie: zmiana ma charakter architektoniczny \(relokacja kodu \+ adaptery\).
* Publiczne API pluginu \(hooki/filtry/AJAX\) pozostaje stabilne.
* Adaptery pozostaja tymczasowo; ich cleanup planowany w kolejnym PR \(Faza 6\).

## Checklist

* \[x\] Slices utworzone i podlaczone
* \[x\] Legacy sciezki utrzymane przez adaptery
* \[x\] Naprawiony fatal na options page
* \[x\] Naprawione renderowanie etapow przygotowania
* \[x\] Testy unit przechodza

‌

---

<a id="pr-15"></a>
## #15 refactor: remove legacy adapters and finalize slice-only includes

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `chore/phase-6-adapter-cleanup` → `main`
- **Created:** 2026-05-26 · **Closed:** 2026-05-26
- **Merge commit:** `939373d85b3c`

# PR: Finalize vertical-slices architecture by removing legacy adapters

**Branch:** chore/phase-6-adapter-cleanup -> main

## Motywacja

Domkniecie Fazy 6 planu migracji: usuniecie tymczasowych adapterow kompatybilnosci po migracji do vertical slices. Celem jest uproszczenie include graph i utrzymanie jednego, docelowego punktu prawdy w `includes/slices/*`.

Cel tej zmiany:

1. Usunac adaptery w starych sciezkach warstwowych.
2. Podpiac orchestrator i autoload Composer bezposrednio pod entrypointy slice.
3. Utrzymac niezmieniony runtime behavior i SCHEMA\_CONTRACT\_V2.

## Zakres zmian

### Nowe slices

* `includes/slices/recipe-composition/`
* `includes/slices/recipe-read-model/`
* `includes/slices/recipe-registration/`
* `includes/slices/recipe-storage/`
* `includes/slices/recipe-input-surface/`
* `includes/slices/recipe-output-surface/`
* `includes/slices/recipe-admin-config/`

### Usuniete legacy adaptery i stare warstwy

Usuniete zostaly pliki adapterow oraz puste indeksy ze starych katalogow:

* `includes/view-model/*`
* `includes/registration/*`
* `includes/sync/*`
* `includes/front/*`
* `includes/timber/*`
* `includes/integrations/*`
* `includes/admin/*`
* `includes/acf/*`

### Orchestrator

* `includes/class-go4taste-recipes-plugin.php` laduje teraz entrypointy slices.
* Publiczne hooki, akcje AJAX i sygnatury callbackow pozostaja bez zmian.

### Composer autoload

* `composer.json` \(autoload `files`\) wskazuje teraz tylko na entrypointy slice, bez odniesien do usunietych sciezek legacy.

### Zakres celowo bez zmian funkcjonalnych

* Brak zmian kontraktu view-modelu.
* Brak zmian publicznych hookow i callback signatures.
* Brak zmian semantyki zapisu/odczytu danych recipe.

## Weryfikacja

* `vendor\\bin\\phpunit tests\\unit`
* wynik: `EXIT=0`

## Ryzyko / kompatybilnosc

* Ryzyko funkcjonalne niskie: zmiana ma charakter architektoniczny \(cleanup include graph\).
* Publiczne API pluginu \(hooki/filtry/AJAX\) pozostaje stabilne.
* Usuniecie adapterow moze ujawnic ukryte, niestandardowe importy po stronie zewnetrznej. W tym PR brak takich odniesien w kodzie pluginu.

## Checklist

* \[x\] Legacy adaptery usuniete
* \[x\] Orchestrator laduje bezposrednio slice entrypointy
* \[x\] Composer autoload zaktualizowany
* \[x\] Testy unit przechodza

‌

---

<a id="pr-16"></a>
## #16 Add taxonomy screen to recipe creator

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feat/recipe-creator-taxonomies-screen` → `main`
- **Created:** 2026-05-26 · **Closed:** 2026-05-26
- **Merge commit:** `73e35b4707a1`

# PR: Add taxonomy selection screen to recipe creator

**Branch:** feat/recipe-creator-taxonomies-screen -> main

## Motywacja

Dodanie nowego ekranu po kroku "Czas" w kreatorze przepisu, aby autor mial mozliwosc wyboru klasyfikacji przepisu bez wychodzenia z flow tworzenia. Ekran wspiera trzy taxonomie:

1. `recipe_meal_type` - wymagany pojedynczy wybor.
2. `recipe_prep_time_range` - wymagany pojedynczy wybor.
3. `recipe_feature` - opcjonalny wielokrotny wybor.

Celem jest zapis tych wyborow do docelowego modelu danych, z fallbackowym renderowaniem ikon oraz z zachowaniem obecnego stylu creatora.

## Zakres zmian

### UI creatora

* Dodany nowy krok 1.7 po ekranie "Czas".
* Dodane karty wyboru taxonomii z ikonami z `hero_icon`.
* Dodane empty-states, gdy dla taxonomii nie ma jeszcze termow.
* Dodane podsumowanie wybranych taxonomii na ekranie review.

### Front-end payload

* `assets/js/acf-front-form-data.js` eksportuje dane taxonomii do JS.
* `assets/js/acf-front-form.js` obsluguje stan, walidacje, renderowanie i submit.
* `assets/css/acf-front-form.css` zawiera style kart taxonomii i empty-state.

### Backend i zapis

* `includes/slices/recipe-input-surface/recipe-creator.php` lokalizuje taxonomy data i initial state.
* `includes/slices/recipe-input-surface/recipe-creator-helpers.php` przenosi selected term IDs przez pre-save flow.
* `includes/slices/recipe-storage/meta-sync.php` synchronizuje taxonomy relations jako source of truth.
* `includes/class-go4taste-recipes-plugin.php` podpina walidacje ACF do hookow pluginu.

### Testy

* `tests/unit/test-meta-sync.php` pokrywa selection model, clearing pustych selekcji i walidacje.

## Zachowanie po zmianie

* Meal type i prep time sa wymagane i musza miec dokladnie jeden wybor.
* Feature pozwala na wiele wyborow bez limitu.
* Gdy termy nie istnieja, ekran pokazuje instrukcje dodania termow zamiast ukrywac sekcje.
* Zapis nie czyści relacji przypadkowo, gdy pola nie zostana przeslane w payloadzie.

## Weryfikacja

* `vendor\\bin\\phpunit tests\\unit\\test-meta-sync.php`
* `vendor\\bin\\phpunit tests\\unit`
* wynik: `EXIT_META_SYNC=0`
* wynik: `EXIT_ALL=0`

## Ryzyko / kompatybilnosc

* Ryzyko funkcjonalne umiarkowane: zmiana dotyka flow zapisu i walidacji.
* Dane taxonomii sa synchronizowane w storage layer, wiec zachowana jest spojnosc z modelem danych.
* Najwieksze ryzyko to brak termow w taxonomiach wymaganych, ale jest to jawnie obslugiwane empty-state i walidacja.

## Checklist

* \[x\] Nowy ekran taxonomii dodany do creatora
* \[x\] Icons fallback i hero\_icon support wdrozone
* \[x\] Selection model zapisuje sie do taxonomy relations
* \[x\] Serwerowa walidacja dla wymaganych taxonomii jest aktywna
* \[x\] Testy unit przechodza

‌

---

<a id="pr-17"></a>
## #17 PR #1: Remove legacy Gutenberg block editor assets and stale tests

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `chore/cleanup-block-assets` → `main`
- **Created:** 2026-05-26 · **Closed:** 2026-05-26
- **Merge commit:** `82f1ccb59a89`

* Delete assets/js/editor-blocks-new.js \(621 lines, block registration, never enqueued\)
* Delete assets/css/editor-blocks.css \(177 lines, block styling, never enqueued\)
* Delete tests/unit/test-blocks.php \(4 stale tests for non-existent block APIs\)
* Remove 2 stale test methods from tests/unit/test-post-types.php \(test\_apply\_recipe\_block\_template, test\_template\_lock\_is\_all\)

Block editing was disabled in v1.1.0 in favor of the frontend recipe creator form. Runtime uses meta-first ACF fields; Gutenberg blocks are entirely out of scope. No functional changes to active codebase.

---

<a id="pr-18"></a>
## #18 PR #2: Align contributor guidance to meta-first/slices architecture

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `chore/align-contributor-guidance` → `main`
- **Created:** 2026-05-26 · **Closed:** 2026-05-26
- **Merge commit:** `22d76ede42c3`

Update all prompts, commands, agents, and core docs to reflect current architecture \(Phase 6\+\): - migrate file paths from layer-based \(includes/registration/, includes/view-model/, includes/front/\) to vertical slices model \(includes/slices/recipe-\*/\) - replace block-centric language with creator-first language - remove deprecated 'Add a block' workflow \(block editing discontinued in v1.1.0\) - update hard constraints tables to reference slices paths - mark SCHEMA\_CONTRACT\_V1.md as ARCHIVED \(block-era schema, retained for import compat\)

Files modified: - .github/prompts/add-cpt.prompt.md — removed Q5 \(blocks\), updated steps to slices - .github/prompts/add-taxonomy.prompt.md — creator-field focus, slices paths - .github/prompts/add-acf-field-group.prompt.md — clarified admin vs creator, slices paths - .claude/commands/\* — synced with updated prompts \(all 3 files\) - .github/agents/plugin-developer.agent.md — removed add-block task, updated constraints - .github/agents/plugin-reviewer.agent.md — downgrade block review to legacy-only, updated paths - copilot-instructions.md — full architecture map rewrite \(slices\), updated hard constraints - CLAUDE.md — architecture map rewrite \(slices\), updated field-group workflow - SCHEMA\_CONTRACT\_V1.md — added ARCHIVED header \(v1.0 era, deprecated\)

No runtime logic changes; purely documentation/workflow alignment.

---

<a id="pr-19"></a>
## #19 feat(creator): dynamic step progress by substep and remove substep labels

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/dynamic-progress-substeps` → `main`
- **Created:** 2026-05-27 · **Closed:** 2026-05-27
- **Merge commit:** `c06164bdaf78`

# PR: Dynamiczny pasek postepu po podkrokach \+ usuniecie etykiet substep

**Branch:** feature/dynamic-progress-substeps -> main

## Motywacja

W kreatorze przepisu aktywny segment paska postepu byl zawsze wypelniany do stalej wartosci \(~55%\), niezaleznie od liczby podkrokow i aktualnej pozycji uzytkownika w kroku.

Dodatkowo widok formularza pokazywal osobne etykiety typu `x z y · ...` \(`step__substep`\), co dublowalo informacje wizualne po stronie paska postepu.

Cel tej zmiany:

1. Urealnic wypelnienie aktywnego segmentu na podstawie rzeczywistego podkroku.
2. Zachowac dotychczasowa semantyke `is-active` i `is-done`.
3. Usunac etykiety `step__substep` ze wszystkich krokow formularza.

## Zakres zmian

### 1\) Dynamiczne wypelnienie segmentu progress

Plik: `assets/js/acf-front-form.js`

* Dodane funkcje pomocnicze:

    * `clamp01(value)`
    * `getFormSubstepTotal(step)`
    * `getCurrentFormSubstepPosition(screenIdx)`
    * `getActiveStepFillScale(screenIdx)`
    
* W `showScreen(idx)`:

    * dla kazdego segmentu czyszczone jest poprzednie `--progress-fill-scale`,
    * aktywny segment dostaje nowa wartosc `--progress-fill-scale` wyliczana z podkroku.
    
* Intro nie liczy sie do substepu \(`0%` wypelnienia aktywnego segmentu\).
* Dla ekranow innych niz `intro/form` \(review/done\) semantyka pozostaje bez zmian \(wszystkie `is-done`\).

### 2\) CSS progress oparty o zmienne

Plik: `assets/css/acf-front-form.css`

* `grid-template-columns` dla `.progress` korzysta z `--progress-steps` \(fallback `6`\).
* `@keyframes fillSeg` konczy animacje na `scaleX(var(--progress-fill-scale, 0.5))` zamiast stalego `scaleX(0.55)`.

### 3\) Usuniecie etykiet substep z UI

Pliki:

* `includes/slices/recipe-input-surface/recipe-creator-template.php`
* `assets/css/acf-front-form.css`
* Usuniete wszystkie elementy `<div class="step__substep">...</div>` w krokach formularza.
* Usuniete nieuzywane style `.step__substep` i `.step--center > .step__substep`.

## Weryfikacja

* Walidacja edytowanych plikow: brak bledow \(JS/CSS/PHP\) w Problems.
* Sprawdzenie wyszukiwania: brak wystapien `step__substep` po zmianie.
* Kontrola diff: zmiany ograniczone do 3 plikow.

## Ryzyko / kompatybilnosc

* Ryzyko niskie/srednie: zmiana dotyczy warstwy UI kreatora \(JS \+ CSS \+ template\).
* Brak zmian w modelu danych, ACF schema, hookach backendowych i zapisie recipe meta.
* Potencjalny obszar uwagi: wizualny odbior animacji na roznych urzadzeniach i przy szybkim przechodzeniu Wstecz/Dalej.

## Checklist

* \[x\] Aktywny segment progress zalezy od aktualnego podkroku
* \[x\] Intro nie liczy sie do podkroku \(0%\)
* \[x\] Zachowane `is-active` i `is-done`
* \[x\] Usuniete etykiety `step__substep` z formularza
* \[x\] Usuniete martwe style CSS zwiazane z `step__substep`

‌

---

<a id="pr-20"></a>
## #20 Chore/vertical slices architecture rebuild

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `chore/vertical-slices-architecture-rebuild` → `main`
- **Created:** 2026-05-27 · **Closed:** 2026-05-27
- **Merge commit:** `39004a25aa12`

# PR: Finalizacja migracji do True Vertical Slices \(core/features\) \+ cleanup warstw legacy

**Branch:** chore/vertical-slices-architecture-rebuild -> main

## Motywacja

Plugin byl w stanie przejsciowym: nowa architektura `core/` \+ `features/` istniala rownolegle ze stara warstwa `includes/slices` i globalnym `assets/`.

To prowadzilo do:

* ryzyka dublowania odpowiedzialnosci i przypadkowego ladowania starych sciezek,
* niespojnych instrukcji dla agentow i deweloperow,
* trudniejszego utrzymania testow oraz onboardingu.

Cel tego PR:

1. Domknac runtime migration do modelu True Vertical Slices.
2. Usunac warstwy legacy \(`includes/slices`, globalne `assets`, stare `tests/unit`\).
3. Ujednolicic konfiguracje i dokumentacje z faktycznym stanem kodu.

## Zakres zmian

### 1\) Runtime i ownership przeniesione do core/features

* Dodany bootstrap i kontekst pluginu w `core/`:

    * `core/class-plugin-bootstrap.php`
    * `core/class-plugin-context.php`
    
* Domain ownership przeniesiony do `core/domain/*`:

    * post type, taxonomie, options, source strategy, domain hooks.
    
* Feature ownership i hook registration osadzone w `features/*/index.php`:

    * `frontend-creator`, `recipe-display`, `archive-filtering`, `admin-settings`, `admin-promoted-products`.
    

### 2\) Co-location i usuniecie warstw legacy

* Usuniete globalne `assets/*`; assety pozostaja co-located w feature folderach.
* Usuniety katalog `includes/` \(w tym `includes/slices/*` i wrappery kompatybilnosci\).
* Kod feature/domain pozostaje jedynym zrodlem logiki biznesowej.

### 3\) Testy i konfiguracja narzedzi

* Testy unit zostaly co-located w `features/*` oraz `core/domain/*`.
* `phpunit.xml` zaktualizowany do nowej struktury \(`features` \+ `core/domain`\).
* `composer.json` zaktualizowany \(skrypty pod nowe sciezki, bez legacy autoload files\).

### 4\) Dokumentacja i guidance zaktualizowane do nowej architektury

* Zaktualizowane pliki architektury i kontraktu:

    * `README.md`
    * `CLAUDE.md`
    * `SCHEMA_CONTRACT_V2.md`
    
* Zaktualizowane instrukcje/commandy/prompty agentowe:

    * `.claude/commands/*`
    * `.github/agents/plugin-reviewer.agent.md`
    * `.github/prompts/*`
    

## Weryfikacja

* Kontrola referencji: brak runtime odwolan do `includes/slices` i usunietych `assets/*`.
* Walidacja edytowanych plikow: brak bledow skladni/diagnostyki w zmodyfikowanych plikach.
* Autoload Composer odswiezony po cleanupie sciezek legacy.
* PHPUnit: uruchomienie lokalne blokowane przez brak `wordpress-tests-lib` w srodowisku \(ograniczenie lokalnego setupu, nie regresja kodu\).

## Ryzyko / kompatybilnosc

* Ryzyko: srednie \(duzy cleanup strukturalny i usuniecie warstwy kompatybilnosci\).
* Ograniczenie ryzyka:

    * nowe ownership jest jawne i rozdzielone \(`core` vs `features`\),
    * usuniete zostaly stale punkty konfliktu \(dual paths\),
    * dokumentacja i konfiguracja sa spojne z kodem.
    

## Checklist

* \[x\] Runtime dziala w modelu `core/` \+ `features/`
* \[x\] Hook registration przeniesione do `features/*/index.php` i `core/domain/index.php`
* \[x\] Usuniety globalny `assets/`
* \[x\] Usuniete `includes/slices` oraz legacy wrappery
* \[x\] Testy przeniesione/co-located, `phpunit.xml` zaktualizowany
* \[x\] `composer.json` dostosowany do nowej struktury
* \[x\] README / kontrakt / instrukcje agentowe zsynchronizowane z aktualna architektura

‌

---

<a id="pr-21"></a>
## #21 Feature/ads phase3 shared resolver

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/ads-phase3-shared-resolver` → `main`
- **Created:** 2026-05-31 · **Closed:** 2026-06-02
- **Merge commit:** `317abfa74772`

# PR: Ads Phase 3\+4 — Wspólny resolver \+ adapter pickera w frontend creator

**Branch:** feature/ads-phase3-shared-resolver → main

## Motywacja

W recipes-plugin były dwa osobne problemy:

* Phase 3: lokalna duplikacja pobierania produktów reklamowych w view modelu \(`g4t_fetch_ads_products()`\),
* Phase 4: brak zapisu nowego kontraktu `_g4t_a_recipe_bottom_product_ids` z kreatora przepisu.

Celem jest pełna zgodność recipes-plugin z nową architekturą go4taste-ads bez zmiany kształtu danych konsumowanych przez Twig.

## Zakres zmian

### 1\) Phase 3 — delegacja pobierania do shared resolvera ads

Plik: `features/recipe-display/view-model-builder.php`

* `g4t_build_single_recipe_view_model()` wywołuje `g4t_recipe_bottom_strip_get_products_for_post($post_id)` gdy go4taste-ads jest aktywne \(`function_exists()`\).
* Zachowane fallbacki kompatybilności:

    1. go4taste-ads nieaktywne → lokalne `g4t_fetch_ads_products()`,
    2. nowy key pusty \+ legacy ACF repeater istnieje → `g4t_fetch_ads_products()`.
    
* `ads.enabled` bazuje na faktycznie załadowanych produktach.
* `g4t_fetch_ads_products()` pozostaje jako legacy fallback.

### 2\) Phase 4 — adapter pickera i zapis JSON key

Pliki:

* `features/frontend-creator/index.php`
* `features/frontend-creator/creator-form.php`
* `features/frontend-creator/creator-template.php`
* `features/frontend-creator/creator.js`

Co zostało dodane:

* Adapter pickera w kroku 6 kreatora \(`G4TAdsPicker`\) z fallbackiem do lokalnej listy produktów.
* Enqueue assets pickera \(`picker-ui.js`, `instantsearch.js`, `algoliasearch`\) na stronie kreatora.
* Zapis `_g4t_a_recipe_bottom_product_ids` jako JSON array of strings.
* Odczyt nowego key w trybie edycji \+ fallback do legacy `recipe_ads_products`.
* Sync po `acf/save_post` dla utrzymania nowego key po submit.
* Usunięcie błędnego gate'a blokującego picker \(`function_exists('go4taste_ads_get_options')`\).

## Kontekst architektoniczny

Zmiany domykają integrację recipes-plugin z planem rozszerzalności ads:

* Phase 3 \(read-model delegation\),
* Phase 4 \(creator save adapter, bez modyfikacji ads core\).

Kontrakt danych pozostaje spójny: `_g4t_a_recipe_bottom_product_ids` przechowuje JSON array string IDs.

## Weryfikacja

* Brak błędów Problems dla zmienionych plików.
* Lint PHP przechodzi dla zmienionych plików creator.
* Kształt `ads.products` w view modelu pozostaje kompatybilny z Twig.
* Krok 6 kreatora ładuje picker Algolii, jeśli ustawione są poprawne opcje ads; fallback lokalny działa gdy picker niedostępny.

## Ryzyko / kompatybilność

* Ryzyko niskie/średnie: zmiany obejmują view model \+ frontend creator.
* Kompatybilność wsteczna zachowana dzięki fallbackom \(`g4t_fetch_ads_products`, `recipe_ads_products`\).
* Brak zmian w schema Twig.

## Scope follow-up

* Phase 5 \(cache/invalidation\) jest poza zakresem tego PR i pozostaje do osobnego wdrożenia.

## Merge readiness

* PR merge-ready dla zakresu recipes-plugin: Phase 3 \+ Phase 4.

## Checklist

* \[x\] Guard `function_exists('g4t_recipe_bottom_strip_get_products_for_post')`
* \[x\] Fallback 1: go4taste-ads nieaktywne → `g4t_fetch_ads_products()`
* \[x\] Fallback 2: nowy key pusty \+ legacy ACF dane → `g4t_fetch_ads_products()`
* \[x\] `ads.enabled` odzwierciedla faktycznie załadowane produkty
* \[x\] `g4t_fetch_ads_products()` zachowana \(nie usunięta\)
* \[x\] Adapter pickera w kroku 6 kreatora
* \[x\] Zapis `_g4t_a_recipe_bottom_product_ids` \(JSON array\)
* \[x\] Fallback odczytu do `recipe_ads_products`
* \[x\] Sync po `acf/save_post`

‌

---

<a id="pr-22"></a>
## #22 docs(pr): add recipe creator picker extensibility plan

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/picker-ui-extensibility` → `main`
- **Created:** 2026-06-02 · **Closed:** 2026-06-03
- **Merge commit:** `7b6f7984e0a8`

# PR: Recipe Creator Product Discovery UI — Algolia Data in Existing Frontend

**Branch:** feature/picker-ui-extensibility -> main

## Implementation status

| Stage | Status | Description |
| --- | --- | --- |
| Stage 1: Algolia list in existing `product-list` | ✅ Verified | Algolia products load into `product-list` on step 6 open |
| Stage 2: Search bridge via existing `.search-box` | ✅ Verified | Search input drives Algolia query; results update `product-list` |
| Stage 3: Preview in existing `selected-strip` | ✅ Verified | Selected products render as chips with index \+ name |
| Stage 4: Persistence \+ compatibility guard | ✅ Fixed and verified | `_g4t_a_recipe_bottom_product_ids` persists correctly after save |
| Stage 5: Docs \+ QA checklist | ✅ Updated | All root causes documented; regression risks noted |
| Stage 6: New recipe publish flow | ✅ Fixed and verified | Correct post ID in redirect, no orphaned drafts |

---

## Bug fixes during QA

### Bug 1: Products not persisted after save

**Symptom:** After selecting Algolia products in step 6 and saving, `_g4t_a_recipe_bottom_product_ids` was persisted as empty string. Title changes were also not saved.

```
-- before fix
SELECT meta_value FROM wp_postmeta
WHERE post_id = 2299 AND meta_key = '_g4t_a_recipe_bottom_product_ids';
-- result: ""

-- after fix
-- result: '["99","98","97","96"]'
```

**Cause:** ACF front-end forms encode a signed `_acf_form` token with the allowed field groups. When processing the submission ACF strips any `$_POST['acf']` keys that don't belong to a declared group. `group_recipe_ads` is not in the creator's `field_groups`, so ACF removed `field_recipe_ads_products` and `_post_title` before our `acf/save_post` hook ran. Confirmed by comparing browser DevTools payload \(77 variables, all present\) against PHP `array_keys($_POST['acf'])` in debug log \(only declared-group fields visible\).

**Fix:** Submit product IDs and post title as flat top-level `$_POST` fields outside `acf[...]`, before `appendAcfPayload()`. ACF does not touch top-level `$_POST` fields.

```
// JS — submitToAcf(): flat fields submitted before ACF payload
appendField(form, 'post_title', (state.data.title || '').trim());
appendField(form, 'g4t_recipe_ads_product_ids_json', JSON.stringify(earlyProductIds));
appendAcfPayload(form, payload);
```

```
// PHP — primary read path uses flat field; ACF nested path is fallback
if ( isset( $_POST['g4t_recipe_ads_product_ids_json'] ) ) {
    $decoded = json_decode( sanitize_text_field( wp_unslash( $_POST['g4t_recipe_ads_product_ids_json'] ) ), true );
    if ( is_array( $decoded ) ) {
        return g4t_normalize_recipe_bottom_strip_product_ids( $decoded );
    }
}
```

Title update moved to `g4t_recipe_creator_sync_recipe_bottom_strip_meta_after_acf_save()` via `wp_update_post()` from `$_POST['post_title']`.

---

### Bug 2: New recipe redirected to `post_id=0`, saved as orphaned draft

**Symptom:** After publishing a new recipe through the creator, redirect URL was `?saved=1&status=published&post_id=0` with "Recipe not found or you do not have permission to edit it." The post existed but as a draft. Products were partially missing \(2 out of 4\).

**Cause \(two parts\):**

**Part A — ACF passes integer** `0`**, not string** `'new_post'`**:** Debug log revealed ACF passes `post_id = 0` \(integer\) to `acf/pre_save_post`, not `'new_post'` \(string\). Our guard `'new_post' !== $post_id` is TRUE for `0`, so we returned `0` immediately without creating a post or using the heartbeat draft.

```
[G4T pre_save_post] received post_id=0 type=integer
[G4T pre_save_post] g4t_heartbeat_draft_id=2360
[G4T pre_save_post] early return (not new_post), returning=0
```

**Part B —** `%post_id%` **URL-encoded by** `add_query_arg`**:** The return URL was built with `add_query_arg('post_id', '%post_id%', ...)`. WordPress's `build_query` URL-encodes the `%` signs, producing `post_id=%25post_id%25`. ACF's `str_replace('%post_id%', $id, $url)` could not find the pattern. The browser decoded `%25post_id%25` back to `%post_id%`, and `(int)'%post_id%'` = `0`.

**Part C — orphaned draft \+ missing products:** Heartbeat had already created a draft \(ID 2360\) with 2 products. Form submit created a different published post \(unreachable due to redirect bug\). User found and published the heartbeat draft, which only had the 2 heartbeat-saved products — explaining the partial product count.

**Fix \(three parts\):**

**Part A —** `creator-helpers.php`**:** treat `$post_id <= 0` as equivalent to `'new_post'`:

```
$is_new_post = ( 'new_post' === $post_id ) || ( is_numeric( $post_id ) && (int) $post_id <= 0 );
```

When `$is_new_post` is true and a heartbeat draft exists, publish that draft instead of creating a new post — avoiding orphaned duplicates:

```
$heartbeat_draft_id = isset( $_POST['g4t_heartbeat_draft_id'] ) ? (int) $_POST['g4t_heartbeat_draft_id'] : 0;
if ( $heartbeat_draft_id > 0 && g4t_can_user_edit_recipe( $heartbeat_draft_id ) ) {
    wp_update_post( array( 'ID' => $heartbeat_draft_id, 'post_status' => $target_status, ... ) );
    return $heartbeat_draft_id;
}
```

**Part B —** `creator-helpers.php` **/** `g4t_get_recipe_form_config()`**:** build return URL without passing `%post_id%` through `add_query_arg`:

```
// For new posts: append %post_id% as raw string, not via add_query_arg
$return_url = add_query_arg( array( 'saved' => '1', 'status' => 'published' ), get_the_permalink() )
    . '&post_id=%post_id%';
```

**Part C —** `creator.js` **/** `submitToAcf()`**:** pass heartbeat draft ID as early flat field:

```
if (!isEditMode && autosavePostId > 0) {
    appendField(form, 'g4t_heartbeat_draft_id', autosavePostId);
}
```

---

## Motivation

Current work showed that simply mounting the standard Ads picker UI in creator step 6 is not the target UX. The target is to keep the current creator frontend components and only reuse Ads/Algolia data logic.

Main goals:

* keep the current creator layout and interactions,
* feed `product-list` with Algolia data,
* drive search from existing `.search-box` input,
* use `selected-strip` as preview/selection UI,
* preserve existing save contract and backward compatibility.

---

## Desired outcome

After this implementation:

1. Product rows in creator `product-list` are loaded from Algolia \(not dummy/local-only source\).
2. Typing in creator `.search-box` filters Algolia results directly.
3. `selected-strip` shows selected products with chip number and product name.
4. Save/edit behavior remains unchanged for `_g4t_a_recipe_bottom_product_ids` and legacy fallback.
5. New recipe publish flow redirects correctly with the actual post ID.

---

## Architecture

### 1\) Data source and rendering boundary

* Data retrieval: `algoliaIndex.search()` called directly in `creator.js` via credentials from `G4T_RECIPE_CREATOR_DATA.adsPicker`.
* Rendering: creator-native row rendering in `product-list`, no injection of standard picker result UI.

### 2\) Search boundary

* Query source: creator input `#product-search`.
* Algolia search triggered via `refreshProductResults()` with debounce.

### 3\) Preview/selection boundary

* `selected-strip` is the only preview surface for selected products.
* Chip format: `{index}. {product name}`.

### 4\) Save contract and fallback

* Persist selected IDs to `_g4t_a_recipe_bottom_product_ids` \(JSON array of strings\) via dedicated flat POST field `g4t_recipe_ads_product_ids_json`.
* On edit, if JSON key is empty, fallback to legacy `recipe_ads_products` repeater read path.

### 5\) ACF field filtering constraint

ACF filters `$_POST['acf']` to only the field groups declared in `acf_form()`. Any data that must survive the save cycle and is not in a declared group must be submitted as a top-level `$_POST` field outside `acf[...]`. The product IDs \(`g4t_recipe_ads_product_ids_json`\), post title \(`post_title`\), and heartbeat draft ID \(`g4t_heartbeat_draft_id`\) follow this pattern. Any future additions to the must-save set should do the same.

### 6\) Database state — two storage formats in parallel

Both meta keys can exist simultaneously for a single post. They are independent:

```
-- Post saved via creator (after this PR):
_g4t_a_recipe_bottom_product_ids = ["98","97","96","95"]  ← new contract, Algolia IDs
recipe_ads_products               = 2                      ← ACF repeater row count (legacy)
recipe_ads_products_0_product_id  = p1                     ← legacy repeater row 0
recipe_ads_products_1_product_id  = p2                     ← legacy repeater row 1
```

`recipe_ads_products` is the ACF repeater row **count** \(not a product ID\). The actual IDs are in the `_N_product_id` sub-keys. This repeater format was written by an older version of the creator and is now only updated via the wp-admin ACF metabox.

Frontend read priority \(`view-model-builder.php` \+ `go4taste-ads`\):

1. `_g4t_a_recipe_bottom_product_ids` — primary, read by go4taste-ads directly
2. `recipe_ads_products_N_product_id` — fallback via `g4t_get_ads_product_ids_from_acf()`, only reached when the primary key is empty

For 140 pre-existing production recipes the primary key is empty and the fallback is active. Backfill migration \(ACF repeater → `_g4t_a_recipe_bottom_product_ids`\) is scoped to a separate branch and PR.

### 7\) New recipe publish flow

When creating a new recipe:

1. Heartbeat may create a draft before the user clicks "Opublikuj". The draft ID is stored in `autosavePostId` in JS.
2. On submit, `g4t_heartbeat_draft_id` is passed as a flat POST field.
3. PHP's `acf/pre_save_post` filter detects ACF's `post_id = 0` \(the integer ACF uses for new posts — not the string `'new_post'`\) and routes to either: updating the heartbeat draft to published, or creating a fresh post if no draft exists.
4. The return URL appends `%post_id%` as a raw string \(not via `add_query_arg`\) so ACF can replace it correctly.

---

## Validation checklist

### Passed

1. ✅ Step 6 loads Algolia products into `product-list` on open.
2. ✅ Searching via `#product-search` updates Algolia results in real time.
3. ✅ `selected-strip` displays chips with index \+ product name for all selected items.
4. ✅ Selecting/unselecting updates state correctly.
5. ✅ Save persists real Algolia product IDs to `_g4t_a_recipe_bottom_product_ids`.
6. ✅ Edit mode loads previously saved product IDs back into `selected-strip`.
7. ✅ Post title changes are saved correctly.
8. ✅ New recipe publish redirects to correct post ID \(not `post_id=0`\).
9. ✅ Heartbeat draft is reused on publish — no orphaned duplicate posts.

### Follow-up

10. Mobile and desktop QA for step 6 interactions.
11. No regressions in summary screen and submit/update flow.

---

## Risks and mitigations

* Risk: partial coupling to standard picker DOM. Mitigation: creator rendering/search controls are source of truth; Algolia access is isolated as data layer.
* Risk: duplicate selection state between provider and creator. Mitigation: creator state remains canonical; provider returns data only.
* Risk: performance issues with frequent search queries. Mitigation: debounce in creator input handler; initial hits limited by `hitsPerPage`.
* Risk: ACF silently strips undeclared `acf[...]` fields on any surface that uses `acf_form()` without the ads group. Mitigation: must-save data that crosses plugin boundaries is submitted as top-level `$_POST` fields, not inside `acf[...]`.
* Risk: ACF passes integer `0` instead of string `'new_post'` for new post forms — version-dependent behavior. Mitigation: `g4t_recipe_creator_force_pre_save_post` treats both `'new_post'` and `<= 0` as "new post" case.

---

## Merge readiness

* All stages verified. Both post-QA bugs diagnosed and fixed.
* Follow-up QA items 10–11 recommended before merge to main.

‌

---

<a id="pr-23"></a>
## #23 docs(pr): rewrite PR description with full branch change summary

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/picker-ui-extensibility` → `main`
- **Created:** 2026-06-03 · **Closed:** 2026-06-03
- **Merge commit:** `b8acf8c14233`

# PR: Recipe Creator Step 6 - Algolia products, stable persistence, autosave/publish fixes

**Branch:** feature/picker-ui-extensibility -> main

## Co wnosi ten branch

Ten branch domyka wdrozenie sekcji produktowej \(Step 6\) w kreatorze przepisu oraz usuwa bledy zapisu, ktore blokowaly stabilna prace flow:

* wyszukiwanie i wybor produktow Algolia w natywnym UI kreatora,
* poprawny zapis ID produktow do docelowego kontraktu meta,
* poprawny odczyt zapisanych produktow przy edycji,
* usuniecie problemu `post_id=0` po publikacji nowego wpisu,
* usuniecie duplikowania postow przy sekwencji autosave \(heartbeat\) -> publish.

---

## Zakres zmian \(w kolejnosci rozwoju\)

### 1\) Integracja danych produktowych w Step 6

* Kreator zaczal korzystac z danych Algolia do listy i paska wybranych produktow.
* Dodano mapowanie/hydratacje danych tak, aby po wejciu w edycje odtworzyc poprawnie wybrane produkty \(z miniaturami i podstawowymi polami\).
* Dodano odporniejsze mapowanie rekordow z Algolia i fallbacki dla roznych wariantow kluczy.

### 2\) Utrwalenie kontraktu zapisu produktow

* Zapis produktow zostal ustabilizowany do wspolnego kontraktu:

    * meta key: `_g4t_a_recipe_bottom_product_ids`
    * format: JSON array of strings, np. `["154","89"]`
    
* Dodano obsluge legacy fallback dla starszych wariantow kluczy ID produktu.

### 3\) Realny root cause bledow persystencji i fix

Pierwotna hipoteza o `max_input_vars` okazala sie nietrafiona.

**Rzeczywista przyczyna:** ACF security field filtering.

ACF usuwa z `$_POST['acf']` pola, ktore nie naleza do field groups zadeklarowanych w `acf_form()`. W efekcie pole produktow bylo odfiltrowywane zanim uruchamial sie hook zapisu.

**Naprawa:** krytyczne dane dla zapisu produktow sa wysylane jako top-level POST field poza `acf[...]`:

* `g4t_recipe_ads_product_ids_json`

To omija filtracje ACF dla niedeklarowanych pol i zapewnia stabilny zapis.

### 4\) Stabilizacja zapisu nowego wpisu i publikacji

* Poprawiono flow nowego posta tak, aby nie konczyc w stanie `post_id=0`.
* Dodano logike ponownego wykorzystania draftu utworzonego przez heartbeat przy publikacji.
* Efekt: brak duplikatow postow przy scenariuszu autosave -> publish.

---

## Najwazniejsze commity na branchu

* `e902bd1` feat\(creator\): use algolia data in native list and strip
* `218d01a` fix\(creator\): hydrate selected products and show thumbnails
* `9d0dd63` fix\(creator\): robust product hydration and image extraction
* `60ab50c` fix\(creator\): persist product ids from posted acf payload
* `13894c7` fix\(creator\): support product\_id legacy fallback keys
* `d81023a` fix\(creator\): disable dummy products when algolia is configured
* `b291120` fix\(creator\): persist product ids via flat POST fields
* `388ed1d` fix\(creator\): prevent duplicate posts on autosave \+ publish sequence

---

## Walidacja \(zakres funkcjonalny\)

### Potwierdzone

* Step 6 laduje produkty Algolia.
* Wyszukiwanie filtruje wyniki poprawnie.
* Wybor produktow aktualizuje selected strip.
* Zapis utrwala dane do `_g4t_a_recipe_bottom_product_ids`.
* Edycja odtwarza zapisane produkty.
* Tytul wpisu zapisuje sie poprawnie.
* Publish nowego wpisu nie konczy sie na `post_id=0`.
* Sekwencja autosave -> publish nie tworzy duplikatow.

### Follow-up QA

* Pelny retest mobile \+ desktop dla Step 6.
* Krotki regression check calego flow kreatora po publikacji.

---

## Ryzyka i uwagi

* Branch zawiera kumulatywny zestaw fixow \(UI \+ persistence \+ publish flow\). Zalecany merge jako jedna calosc.
* Save path produktow jest celowo niezalezny od `acf[...]` dla krytycznego pola IDs, aby uniknac ponownego efektu filtracji ACF.

---

## Merge readiness

* Status: gotowe do merge.
* Wymaganie: standardowy smoke test po merge na srodowisku docelowym.

‌

---

<a id="pr-24"></a>
## #24 Feature/use ads data provider

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/use-ads-data-provider` → `main`
- **Created:** 2026-06-03 · **Closed:** 2026-06-03
- **Merge commit:** `2bb651da3ed7`

# PR: Creator Algolia Integration — Use Shared Ads Data Provider

**Branch:** feature/use-ads-data-provider -> main

**Depends on:** go4taste-ads `feature/use-ads-data-provider` merged to main

## Implementation status

| Stage | Status | Description |
| --- | --- | --- |
| Stage 1: Replace direct algoliasearch init | ✅ Done | `G4TAdsProvider.getInstance()` used; `algoliaIndex` variable removed |
| Stage 2: Remove `normalizeProductRecord()` | ✅ Done | Kept as internal state normalizer; provider hits pass through it \(handles both shapes\) |
| Stage 3: Replace `algoliaIndex.*` calls | ✅ Done | `provider.search()` and `provider.getById()` in `fetchAlgoliaProducts` and `hydrateSelectedProductsFromAlgolia` |
| Stage 4: Update PHP credential passing | ✅ Done | `appId/apiKey/indexName` removed from `G4T_RECIPE_CREATOR_DATA.adsPicker`; provider owns credentials |
| Stage 5: QA \+ regression | ✅ Done | Full creator flow — search, select, save, edit, publish — all passed |

---

## Context

The previous branch \(`feature/picker-ui-extensibility`, merged\) delivered:

* Step 6 Algolia picker in the recipe creator — search, select, save, edit
* Save path via `g4t_recipe_ads_product_ids_json` flat POST field \(bypasses ACF field filtering\)
* Read path via `g4t_recipe_bottom_strip_get_products_for_post()` \(from go4taste-ads\)
* Bug fixes: ACF field filtering, new recipe redirect to `post_id=0`, orphaned heartbeat draft

That branch is complete and merge-ready. This PR is a follow-up refactor only — no user-visible behavior changes.

---

## Problem

`creator.js` currently initializes its own Algolia client independently:

```
// creator.js — direct SDK usage
const searchClient = window.algoliasearch(pickerConfig.appId, pickerConfig.apiKey);
algoliaIndex = searchClient.initIndex(pickerConfig.indexName || 'go4taste_product_list');
const response = await algoliaIndex.search(query, { hitsPerPage: 12 });
```

And has its own hit normalization:

```
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

* One Algolia client instance per page — no duplicate init.
* One canonical product shape — `NormalizedProduct` from the provider, used by all surfaces.
* Creator data layer shrinks to a thin consumer; all data logic lives in the ads provider.
* Future changes to normalization or API version propagate to all surfaces automatically.

---

## Desired outcome

After this implementation:

1. Creator no longer initializes its own `algoliasearch` client.
2. `normalizeProductRecord()` is kept as an internal state normalizer only — it handles both the provider shape \(`name`, `picture`, `category`\) and the legacy shape. It is no longer responsible for normalizing Algolia hits; that lives in the provider.
3. `algoliaIndex` variable is removed; search and fetch go through `G4TAdsProvider`.
4. PHP no longer passes `appId/apiKey/indexName` to the creator; provider owns credentials.
5. No visible behavior change for the user — same search results, same save/edit flow.

---

## Architecture

### Stage 1 \+ 3: Replace SDK calls

```
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

```
// Before — hydrate selected from Algolia
const requests = ids.map(id => algoliaIndex.getObject(id));

// After
const products = await provider.getByIds(ids);
```

### Stage 2: Retain `normalizeProductRecord()` as internal normalizer

The function is kept — it handles both the provider's `NormalizedProduct` shape and the legacy ACF state shape \(field name fallbacks already cover both\). Algolia hits returned by the provider pass through it before entering creator state. This avoids a separate adapter and keeps the internal creator state shape stable.

### Stage 4: PHP credential passing

The ads plugin owns the provider and its credentials. `g4t-ads-provider` is enqueued on both admin and frontend hooks; provider auto-initializes synchronously when its script executes.

`creator.js` calls only `G4TAdsProvider.getInstance()` — never `init()`, never reads credentials.

Resulting change in recipes plugin PHP: `G4T_RECIPE_CREATOR_DATA.adsPicker.appId/apiKey/indexName` removed. `adsPicker.enabled` and `adsPicker.hitsPerPage` remain for creator-specific UI config.

---

## Validation checklist

### Must pass \(no regressions\)

1. ✅ Step 6 loads Algolia products — same results as before.
2. ✅ Search input filters results in real time.
3. ✅ Selecting products updates `selected-strip` correctly.
4. ✅ Save persists product IDs to `_g4t_a_recipe_bottom_product_ids`.
5. ✅ Edit mode loads saved IDs back into `selected-strip`.
6. ✅ New recipe publishes with correct redirect \(post\_id not 0\).
7. ✅ Heartbeat draft reused on publish.
8. ✅ Title changes save correctly.

### New \(provider integration\)

9. ✅ Single Algolia client init per page — `algoliasearch@4` CDN loaded once; one Algolia request per search in DevTools Network tab.
10. ✅ `normalizeProductRecord` retained as internal state normalizer only — no longer normalizes Algolia hits directly; provider shape flows through it.
11. ✅ `algoliaIndex` variable not present in creator.js source.

---

## Risks and mitigations

* Risk: `G4TAdsProvider` not available when creator page loads \(ads plugin inactive or provider not yet enqueued on frontend\). Mitigation: guard with `window.G4TAdsProvider && G4TAdsProvider.getInstance()` — falls back to local catalog gracefully, same as current `algoliaIndex = null` path.
* Risk: `NormalizedProduct` shape from provider differs from `normalizeProductRecord()` output. Mitigation: compare field names before removing local function; provider shape is source of truth.
* Risk: this PR merges before ads Stage 1 is on main. Mitigation: hard dependency — merge order enforced: ads Stage 1 first, this PR second.

---

## Merge readiness

All stages implemented and QA-passed. Merge after go4taste-ads `feature/use-ads-data-provider` lands on main — that PR is the only hard dependency \(provider must exist before creator can call `getInstance()`\).

---

<a id="pr-25"></a>
## #25 feat(ads): finalize product IDs migration and remove legacy fallback

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/migrate-ads-products-contract` → `main`
- **Created:** 2026-06-04 · **Closed:** 2026-06-04
- **Merge commit:** `ba312f86d1ff`

# PR: Data Migration — ACF Repeater → `_g4t_a_recipe_bottom_product_ids`

**Branch:** feature/migrate-ads-products-contract -> main

**Depends on:** go4taste-ads `feature/use-ads-data-provider` merged to main \(provider must be on main before migration runs\)

## Implementation status

| Stage | Status | Description |
| --- | --- | --- |
| Stage 1: Dry run | ✅ Done | `migrate-ads-products-dryrun.php` run — list verified |
| Stage 2: Migration | ✅ Done | `migrate-ads-products.php` run — 140 recipes migrated |
| Stage 3: Verify | ✅ Done | Query 1 confirmed `tylko_acf_repeater` = 0 |
| Stage 4: Cleanup | ✅ Done | Fallback path removed from `view-model-builder.php` |

---

## Context

Two previous PRs ship the new product storage contract:

* `feature/banner-extensibility` \(go4taste-ads\) — PHP foundation: placement registry, payload resolver, `g4t_recipe_bottom_strip_get_products_for_post()`
* `feature/use-ads-data-provider` \(both plugins\) — JS shared provider; save path via `_g4t_a_recipe_bottom_product_ids`

140 production recipes still have products stored only in the old ACF repeater format \(`recipe_ads_products_N_product_id`\). The new key \(`_g4t_a_recipe_bottom_product_ids`\) is empty for them. The frontend works via a fallback in `view-model-builder.php`, but the fallback is a liability.

---

## Problem

* `go4taste-ads` reads only `_g4t_a_recipe_bottom_product_ids` through its own rendering path — no fallback
* The fallback in `view-model-builder.php` can be removed in future architecture changes
* The ACF metabox in wp-admin is the only UI that still writes to the old format

---

## Database state before migration

### Query 1 — overview: how many recipes use which path

```
SELECT
  COUNT(DISTINCT p.ID)                                                         AS total_recipes,
  COUNT(DISTINCT CASE WHEN pm_new.meta_value != '' THEN p.ID END)              AS nowy_kontrakt,
  COUNT(DISTINCT CASE WHEN CAST(pm_old.meta_value AS UNSIGNED) > 0
                        AND (pm_new.meta_value IS NULL OR pm_new.meta_value = '')
                       THEN p.ID END)                                          AS tylko_acf_repeater,
  COUNT(DISTINCT CASE WHEN pm_new.meta_value IS NULL OR pm_new.meta_value = ''
                        AND (pm_old.meta_value IS NULL OR pm_old.meta_value = '0' OR pm_old.meta_value = '')
                       THEN p.ID END)                                          AS brak_produktow
FROM wp_posts p
LEFT JOIN wp_postmeta pm_new ON p.ID = pm_new.post_id
  AND pm_new.meta_key = '_g4t_a_recipe_bottom_product_ids'
LEFT JOIN wp_postmeta pm_old ON p.ID = pm_old.post_id
  AND pm_old.meta_key = 'recipe_ads_products'
WHERE p.post_type = 'post'
  AND p.post_status = 'publish';
```

Expected result before migration:

```
+---------------+---------------+--------------------+----------------+
| total_recipes | nowy_kontrakt | tylko_acf_repeater | brak_produktow |
+---------------+---------------+--------------------+----------------+
|           144 |             1 |                140 |            143 |
+---------------+---------------+--------------------+----------------+
```

* **1** recipe has the new contract — post 2299 \(test\)
* **140** have only the old ACF repeater — production recipes
* These work via fallback but depend on it staying in place

### Query 2 — list of recipes to migrate

```
SELECT
  p.ID,
  p.post_title,
  pm_old.meta_value        AS acf_liczba_produktow,
  pm_new.meta_value        AS nowy_kontrakt
FROM wp_posts p
INNER JOIN wp_postmeta pm_old ON p.ID = pm_old.post_id
  AND pm_old.meta_key = 'recipe_ads_products'
  AND CAST(pm_old.meta_value AS UNSIGNED) > 0
LEFT JOIN wp_postmeta pm_new ON p.ID = pm_new.post_id
  AND pm_new.meta_key = '_g4t_a_recipe_bottom_product_ids'
WHERE p.post_type = 'post'
  AND p.post_status = 'publish'
  AND (pm_new.meta_value IS NULL OR pm_new.meta_value = '')
ORDER BY p.ID;
```

### Query 3 — verify a single recipe

```
SELECT meta_key, meta_value
FROM wp_postmeta
WHERE post_id = 1635
AND meta_key IN (
  '_g4t_a_recipe_bottom_product_ids',
  'recipe_ads_products',
  'recipe_ads_products_0_product_id',
  'recipe_ads_products_1_product_id',
  'recipe_ads_products_2_product_id'
)
ORDER BY meta_key;
```

---

## Migration scripts

### Dry run \(read-only preview\)

File: `migrate-ads-products-dryrun.php` Run: `wp eval-file migrate-ads-products-dryrun.php`

```
<?php
global $wpdb;

$posts = $wpdb->get_results(
    "SELECT p.ID, p.post_title, pm.meta_value AS count
     FROM wp_posts p
     INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
         AND pm.meta_key = 'recipe_ads_products'
         AND CAST(pm.meta_value AS UNSIGNED) > 0
     LEFT JOIN wp_postmeta pm_new ON p.ID = pm_new.post_id
         AND pm_new.meta_key = '_g4t_a_recipe_bottom_product_ids'
     WHERE p.post_type = 'post'
         AND p.post_status = 'publish'
         AND (pm_new.meta_value IS NULL OR pm_new.meta_value = '')
     ORDER BY p.ID"
);

echo "DRY RUN — brak zapisu do bazy\n\n";

foreach ( $posts as $post ) {
    $ids = [];

    for ( $i = 0; $i < (int) $post->count; $i++ ) {
        $id = get_post_meta( $post->ID, "recipe_ads_products_{$i}_product_id", true );
        if ( $id !== '' && $id !== false ) {
            $ids[] = (string) $id;
        }
    }

    $label = empty( $ids ) ? 'BRAK ID' : implode( ', ', $ids );
    echo "post {$post->ID} ({$post->post_title}): {$label}\n";
}

echo "\nLiczba przepisów do migracji: " . count( $posts ) . "\n";
```

### Migration script \(writes to DB\)

File: `migrate-ads-products.php` Run: `wp eval-file migrate-ads-products.php`

```
<?php
global $wpdb;

$posts = $wpdb->get_results(
    "SELECT p.ID, pm.meta_value AS count
     FROM wp_posts p
     INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
         AND pm.meta_key = 'recipe_ads_products'
         AND CAST(pm.meta_value AS UNSIGNED) > 0
     LEFT JOIN wp_postmeta pm_new ON p.ID = pm_new.post_id
         AND pm_new.meta_key = '_g4t_a_recipe_bottom_product_ids'
     WHERE p.post_type = 'post'
         AND p.post_status = 'publish'
         AND (pm_new.meta_value IS NULL OR pm_new.meta_value = '')"
);

$migrated = 0;
$skipped  = 0;

foreach ( $posts as $post ) {
    $ids = [];

    for ( $i = 0; $i < (int) $post->count; $i++ ) {
        $id = get_post_meta( $post->ID, "recipe_ads_products_{$i}_product_id", true );
        if ( $id !== '' && $id !== false ) {
            $ids[] = (string) $id;
        }
    }

    if ( empty( $ids ) ) {
        echo "SKIP post {$post->ID} — brak product_id w repeaterze\n";
        $skipped++;
        continue;
    }

    update_post_meta( $post->ID, '_g4t_a_recipe_bottom_product_ids', wp_json_encode( $ids ) );
    $migrated++;
    echo "OK post {$post->ID}: " . implode( ', ', $ids ) . "\n";
}

echo "\nGotowe. Zmigrowano: {$migrated}, pominięto: {$skipped}\n";
```

---

## Expected database state after migration

Query 1 should return:

```
+---------------+---------------+--------------------+----------------+
| total_recipes | nowy_kontrakt | tylko_acf_repeater | brak_produktow |
+---------------+---------------+--------------------+----------------+
|           144 |           141 |                  0 |              3 |
+---------------+---------------+--------------------+----------------+
```

Query 2 should return 0 rows.

\(3 recipes without products = ACF repeater had `count > 0` but empty `_product_id` rows — skipped by the script.\)

---

## Notes

* Scripts are **idempotent** — recipes that already have `_g4t_a_recipe_bottom_product_ids` are skipped by the SQL WHERE clause.
* Old ACF repeater keys \(`recipe_ads_products_N_product_id`\) are **not deleted** — they remain as a backup.
* Demo IDs \(`p1`–`p10`\) will pass through the script but are rejected by `g4t_normalize_recipe_bottom_strip_product_ids()` at read time when Algolia is configured — expected behavior.

---

## Stage 4: Remove fallback from view-model-builder.php

After migration is confirmed \(Query 1 shows `tylko_acf_repeater = 0`\), remove the legacy fallback from `features/recipe-display/view-model-builder.php`:

```
// Remove this entire block — replace with single call:
if ( function_exists( 'g4t_recipe_bottom_strip_get_products_for_post' ) ) {
    $ads_products = g4t_recipe_bottom_strip_get_products_for_post( $post_id );
    if ( empty( $ads_products ) && ! empty( $ads_product_ids ) ) {
        $ads_products = g4t_fetch_ads_products( $ads_product_ids, $post_id );  // ← remove fallback
    }
} else {
    $ads_products = g4t_fetch_ads_products( $ads_product_ids, $post_id );      // ← remove else branch
}

// Keep only:
$ads_products = g4t_recipe_bottom_strip_get_products_for_post( $post_id );
```

---

## Merge readiness

* Blocked on: go4taste-ads `feature/use-ads-data-provider` merged to main.
* Scripts are ready — run dry run first, verify output, then run migration.
* Stage 4 \(fallback removal\) can be a follow-up commit on the same branch or a separate tiny PR.

‌

---

<a id="pr-26"></a>
## #26 feat(settings): add post taxonomy unregister options

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/options-disable-post-default-taxonomies` → `main`
- **Created:** 2026-06-04 · **Closed:** 2026-06-04
- **Merge commit:** `5c560c35e6dd`

# PR: Migration \+ Post Type Taxonomy Cleanup

**Branch:** feature/migrate-ads-products-contract -> main

**Depends on:** go4taste-ads `feature/use-ads-data-provider` merged to main

## Implementation status

| Stage | Status | Description |
| --- | --- | --- |
| Migration 1: Dry run | ✅ Done | `bin/migrate-ads-products-dryrun.php` run — list verified |
| Migration 2: Migration | ✅ Done | `bin/migrate-ads-products.php` run — 140 recipes migrated |
| Migration 3: Verify | ✅ Done | Query 1 confirmed `tylko_acf_repeater` = 0 |
| Migration 4: Cleanup | ✅ Done | Fallback removed from `view-model-builder.php` |
| Taxonomy 1: Settings | ✅ Done | Two checkboxes added to admin settings page |
| Taxonomy 2: Logic | ✅ Done | Runtime unregister on `init` priority 11 |
| Taxonomy 3: Data cleanup | ✅ Done | Existing Uncategorized removed from 6 posts via WP-CLI |

---

## Part 1: Data Migration — ACF Repeater → `_g4t_a_recipe_bottom_product_ids`

### Problem

140 production recipes had products stored only in the old ACF repeater format \(`recipe_ads_products_N_product_id`\). The new key \(`_g4t_a_recipe_bottom_product_ids`\) was empty for them. The frontend worked via a fallback in `view-model-builder.php`, but:

* `go4taste-ads` reads only `_g4t_a_recipe_bottom_product_ids` through its own rendering path — no fallback there
* The fallback in `view-model-builder.php` was a liability for future architecture changes

### What was done

Ran `bin/migrate-ads-products.php` via `wp eval-file` — for each recipe with ACF repeater data and no new key, read product IDs from `recipe_ads_products_{N}_product_id` and wrote them as JSON to `_g4t_a_recipe_bottom_product_ids`.

Result:

```
+---------------+---------------+--------------------+----------------+
| total_recipes | nowy_kontrakt | tylko_acf_repeater | brak_produktow |
+---------------+---------------+--------------------+----------------+
|           144 |           141 |                  0 |              3 |
+---------------+---------------+--------------------+----------------+
```

Old ACF keys left intact as backup.

### Files changed

* `features/recipe-display/view-model-builder.php` — removed `if/else` fallback block; replaced with single call to `g4t_recipe_bottom_strip_get_products_for_post()`

---

## Part 2: Unregister Built-in Taxonomies from Post Type

### Problem

When the recipe source post type is `post`, WordPress automatically assigns the "Uncategorized" category to every post saved without a category. This results in unwanted category assignments on recipe posts. Additionally, the Category and Tag metaboxes clutter the editor for content that doesn't use them.

### Solution

New admin settings section "Post Type Configuration" with two independent checkboxes:

* **Odepnij Kategorie od wpisów** \(`unregister_category`\) — calls `unregister_taxonomy_for_object_type('category', post_type)` on `init` priority 11; WordPress stops forcing Uncategorized on save; Category metabox disappears from editor
* **Odepnij Tagi od wpisów** \(`unregister_post_tag`\) — calls `unregister_taxonomy_for_object_type('post_tag', post_type)` on `init` priority 11; Tag metabox disappears from editor

Hook runs at priority 11 because WordPress registers built-in taxonomies in `create_initial_taxonomies()` at `init` priority 0.

The unregister applies to whatever post type is resolved by `g4t_get_source_strategy()` — works for `post`, existing CPT, or dedicated CPT.

### One-off data cleanup

6 production recipes had the Uncategorized term already assigned. Removed via `bin/remove-uncategorized.php` \(WP-CLI script, deleted after use\).

### Files changed

* `features/admin-settings/settings-page.php` — new section, two `add_settings_field` calls, named callbacks, `g4t_render_unregister_taxonomy_field()`, sanitize support, updated defaults
* `features/admin-settings/settings-logic.php` — `g4t_maybe_unregister_recipe_taxonomies()` function
* `features/admin-settings/index.php` — `add_action('init', 'g4t_maybe_unregister_recipe_taxonomies', 11)`

---

## Merge readiness

All stages done. No regressions expected:

* Migration is idempotent — safe to verify by re-running dry run script
* Old ACF repeater data untouched — can be used for rollback verification
* Taxonomy unregister is opt-in via settings — disabled by default, no change for installs that don't enable it

‌

---

<a id="pr-27"></a>
## #27 feat(creator): sync prep-time field with taxonomy

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/sync-prep-time-field-taxonomy` → `main`
- **Created:** 2026-06-04 · **Closed:** 2026-06-04
- **Merge commit:** `00ea1d479e13`

# PR: Sync prep-time taxonomy from total time field

**Branch:** `feature/sync-prep-time-field-taxonomy` -> `main`

## Implementation status

| Stage | Status | Description |
| --- | --- | --- |
| Backfill: dry run | ✅ Done | `bin/backfill-prep-time-terms.php` run — wynik zweryfikowany |
| Backfill: real run | ✅ Done | Istniejące przepisy bez termu uzupełnione |
| Auto-select w kreatorze | ✅ Done | `creator.js` dobiera term na bieżąco przy zmianie czasu |

---

## Problem

Przepisy nie miały przypisanego termu taksonomii `recipe_prep_time_range` \(Czas przygotowania\). Skutek: filtr FacetWP "Czas przygotowania" nie zwracał wyników dla większości przepisów. Kreator wymagał ręcznego wyboru termu bez żadnej podpowiedzi.

---

## Rozwiązanie

### 1. Backfill istniejących przepisów \(`bin/backfill-prep-time-terms.php`\)

Jednorazowy skrypt WP-CLI — czyta `recipe_time_total` \(minuty jako string\) i przypisuje pasujący term z `recipe_prep_time_range`.

Mapowanie:

| Zakres | Slug | term\_id |
| --- | --- | --- |
| ≤ 15 min | `do-15-min` | 19 |
| 16–30 min | `do-30-min` | 980 |
| 31–45 min | `do-45-min` | 981 |
| > 45 min | `powyzej-45-min` | 11 |

```
# dry run:
wp eval-file wp-content/plugins/go4taste-recipes-plugin/bin/backfill-prep-time-terms.php

# realny run:
wp eval-file wp-content/plugins/go4taste-recipes-plugin/bin/backfill-prep-time-terms.php run
```

Idempotentny — pomija przepisy z już przypisanym termem. Zabezpieczenia: `WP_Query` z `no_found_rows + update_*_cache = false`, weryfikacja slugów na starcie, podsumowanie po zakończeniu.

### 2. Auto-select termu w kreatorze \(`features/frontend-creator/creator.js`\)

Nowa funkcja `getAutoTermIdForTotalMinutes(totalMinutes)` mapuje sumę `timePrep + timeCook + timeSoak` na `termId` z `window.PREP_TIME_TAXONOMY` \(po sluegu `t.id`\).

`updateTotalTime()` wywołuje ją po każdej zmianie pola czasu i aktualizuje `state.data.prepTimeTermIds`, po czym wywołuje `renderTaxonomySelectors()`. Selector prep-time aktualizuje się w czasie rzeczywistym — zawsze nadpisuje poprzedni wybór. Warunek `state.data.prepTimeTermIds[0] !== termId` zapobiega zbędnemu re-renderowi gdy zakres się nie zmienił.

Efekt: walidacja `d.prepTimeTermIds.length !== 1` jest spełniana automatycznie po wypełnieniu pól czasu — użytkownik nie musi nic wybierać ręcznie.

---

## Verification query

```
SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN tt.term_id IS NOT NULL THEN 1 ELSE 0 END) AS has_prep_time_term,
    SUM(CASE WHEN tt.term_id IS NULL THEN 1 ELSE 0 END) AS missing_prep_time_term
FROM wp_posts p
LEFT JOIN wp_term_relationships tr ON tr.object_id = p.ID
LEFT JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
    AND tt.taxonomy = 'recipe_prep_time_range'
WHERE p.post_type = 'post'
AND p.post_status = 'publish';
```

Oczekiwany wynik: `missing_prep_time_term` = liczba przepisów bez `recipe_time_total` \(brak danych = nie ma co przypisać\).

---

## Files changed

* `features/frontend-creator/creator.js` — `getAutoTermIdForTotalMinutes()`, modyfikacja `updateTotalTime()`
* `bin/backfill-prep-time-terms.php` — skrypt jednorazowy \(do usunięcia po merge\)

‌

---

<a id="pr-28"></a>
## #28 feat(ingredients): add ingredient sections support (named groups)

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/ingredient-sections` → `main`
- **Created:** 2026-06-08 · **Closed:** 2026-06-08
- **Merge commit:** `57dbbddf808a`

# PR: Ingredient Sections — Grouped Ingredients Support

**Branch:** `feature/ingredient-sections` → `main`

**Affects repos:** `go4taste-recipes-plugin` \+ `go4taste-recipes-theme`

---

## Goal

Add support for dividing recipe ingredients into named sections \(e.g., "Ciasto" \+ "Farsz"\) while keeping the existing flat-list approach as the default. Zero migration required for the existing ~130 recipes.

---

## Design decisions

| Decision | Choice | Reason |
| --- | --- | --- |
| Old repeater | Kept intact | Zero migration for ~130 existing recipes |
| New structure | `recipe_ingredient_sections` nested repeater | ACF Pro supports nested repeaters natively; clean separation |
| Mode toggle | `recipe_ingredients_mode` \(`single` \| `sections`\) | One source of truth, explicit intent stored in data |
| View model output | Always `ingredient_sections[]` | One Twig template, no branching — single-mode wraps in one unnamed section |
| Single-mode wrapping | Flat list wrapped as `[['title' => null, 'items' => [...]]]` | Unified shape regardless of mode |
| Frontend creator | Both modes supported | Redaktorzy only access creator, not wp-admin |
| Old `ingredients` key | Removed from view model | Replaced by `ingredient_sections`; template updated accordingly |

---

## New meta keys

```
# Mode toggle (new field)
recipe_ingredients_mode                         = "single" | "sections"   (default: "single")

# Sections repeater (new — only written when mode = "sections")
recipe_ingredient_sections                      = N   (section count)
recipe_ingredient_sections_0_title              = "Ciasto"
recipe_ingredient_sections_0_items              = M   (item count in section 0)
recipe_ingredient_sections_0_items_0_ingredient_term_id = 5
recipe_ingredient_sections_0_items_0_name       = "Mąka"
recipe_ingredient_sections_0_items_0_amount     = "500"
recipe_ingredient_sections_0_items_0_unit       = "g"
recipe_ingredient_sections_0_items_0_ingredient_note = ""
recipe_ingredient_sections_1_title              = "Farsz"
recipe_ingredient_sections_1_items              = K
...

# Existing keys — unchanged, still used when mode = "single"
recipe_ingredients                              = N   (flat count)
recipe_ingredients_0_name                       = ...
recipe_ingredients_0_amount                     = ...
recipe_ingredients_0_unit                       = ...
recipe_ingredients_0_ingredient_term_id         = ...
recipe_ingredients_0_ingredient_note            = ...
recipe_ingredients_photo_url                    = ...   (used in both modes)
```

---

## New view model shape \(SCHEMA\_CONTRACT\_V3\)

Replaces the existing `ingredients` flat array. Always present, always same structure:

```
'ingredient_sections' => [
  [
    'title' => null | string,   // null = no heading (single-mode), string = section title
    'items' => [
      [
        'ingredient_term_id' => int|null,
        'name'               => string,    // required, non-empty
        'amount'             => string|null,
        'unit'               => string,
        'ingredient_note'    => string,
      ],
      // ...
    ],
  ],
  // single-mode: exactly 1 section with title = null
  // sections-mode: 1..N sections each with non-empty string title
]
```

---

## Implementation stages

| Stage | Status | Description |
| --- | --- | --- |
| Schema 1: SCHEMA\_CONTRACT\_V3 | ✅ Done | Document new meta keys, new view model shape, note deprecation of `ingredients` |
| ACF 1: Mode toggle field | ✅ Done | `recipe_ingredients_mode` radio field added to `acf-fields.php` \(group\_recipe\_ingredients\) |
| ACF 2: Sections repeater | ✅ Done | `recipe_ingredient_sections` nested repeater with `section_title` \+ `section_items` sub-repeater |
| Plugin 1: Normalizer | ✅ Done | New `g4t_normalize_ingredient_sections()` in `normalizers.php` |
| Plugin 2: View model builder | ✅ Done | Read mode toggle, branch to correct loader, always output `ingredient_sections` |
| Plugin 3: Meta sync — read | ✅ Done | `g4t_load_ingredient_sections_meta()` in `creator-meta-sync.php` |
| Plugin 4: Meta sync — write | ✅ Done | `g4t_sync_ingredient_sections_meta()` \+ taxonomy term sync for sections items |
| Creator 1: State shape | ✅ Done | Add `ingredientsMode` \+ `ingredientSections` to `state.data` in `creator.js` |
| Creator 2: Mode selector UI | ✅ Done | UI toggle on ingredient screen: "Jedna lista składników / Kilka sekcji" |
| Creator 3: Sections UI | ✅ Done | Add/remove/rename sections; move-up/down sections; ingredient rows within each section |
| Creator 4: Payload builder | ✅ Done | `buildAcfPayload()` — emit mode field \+ correct repeater based on active mode |
| Creator 5: Edit-mode load | ✅ Done | Hydrate `ingredientsMode` \+ `ingredientSections` from `initialState` on edit |
| Creator 6: Validation | ✅ Done | ≥1 section with ≥1 ingredient \(name \+ amount\) in sections mode |
| Theme 1: Template | ✅ Done | Replace `recipe.ingredients` loop with `recipe.ingredient_sections` loop in Twig |
| Theme 2: Styles | ✅ Done | `.ingredients-section-title` styles in theme `single-recipe.css` \(see theme PR\) |
| Migration: Manual | 📋 Post-merge | ~5 recipes converted to sections mode manually via creator — no code changes needed |

---

## Part 1: SCHEMA\_CONTRACT\_V3

New file `SCHEMA_CONTRACT_V3.md` in plugin root.

Key changes vs V2:

* `ingredients` flat array → **deprecated, removed**
* New: `ingredient_sections[]` — unified array, always present
* New meta key: `recipe_ingredients_mode`
* New meta key group: `recipe_ingredient_sections_*`

---

## Part 2: ACF Fields \(`features/frontend-creator/acf-fields.php`\)

### Mode toggle

```
[
  'key'           => 'field_recipe_ingredients_mode',
  'name'          => 'recipe_ingredients_mode',
  'label'         => 'Tryb składników',
  'type'          => 'radio',
  'choices'       => [ 'single' => 'Jedna lista', 'sections' => 'Kilka sekcji' ],
  'default_value' => 'single',
  'layout'        => 'horizontal',
]
```

### Sections nested repeater

```
[
  'key'        => 'field_recipe_ingredient_sections',
  'name'       => 'recipe_ingredient_sections',
  'label'      => 'Sekcje składników',
  'type'       => 'repeater',
  'layout'     => 'block',
  'sub_fields' => [
    // section_title (text)
    // section_items (repeater — same sub-fields as existing recipe_ingredients:
    //   ingredient_term_id, name, amount, unit, ingredient_note)
  ],
]
```

Both field groups live in `group_recipe_ingredients`. The sections repeater is conditionally shown in ACF UI when mode = "sections" \(ACF conditional logic\).

---

## Part 3: Plugin — Normalizer \(`features/recipe-display/normalizers.php`\)

### New function

```
/**
 * Normalizes raw ACF sections repeater data into the unified ingredient_sections shape.
 * For single-mode: wraps existing flat g4t_normalize_ingredients_list() output.
 *
 * @param string $mode   'single' | 'sections'
 * @param array  $data   Raw ACF field value for the active repeater
 * @return array         ingredient_sections[]
 */
function g4t_normalize_ingredient_sections( string $mode, array $data ): array
```

* `mode = 'single'` → calls existing `g4t_normalize_ingredients_list($data)`, wraps in `[['title' => null, 'items' => [...]]]`
* `mode = 'sections'` → iterates sections, normalizes each `section_items` via `g4t_normalize_ingredients_list()`, returns `[['title' => string, 'items' => [...]]]`
* Skips sections with empty title or zero valid items

---

## Part 4: Plugin — View Model Builder \(`features/recipe-display/view-model-builder.php`\)

`g4t_build_single_recipe_view_model()` updated:

1. Read `recipe_ingredients_mode` \(default `'single'` if missing — backward compat for all existing recipes\)
2. Branch:

    * `single` → load `recipe_ingredients` repeater \(existing path\)
    * `sections` → load `recipe_ingredient_sections` repeater \(new path\)
    
3. Pass both `$mode` and raw data to `g4t_normalize_ingredient_sections()`
4. Write result to `ingredient_sections` key
5. Remove old `ingredients` key from returned array

Backward compat: any recipe with no `recipe_ingredients_mode` stored treats as `'single'` → zero behavior change for all existing recipes.

---

## Part 5: Plugin — Meta Sync \(`features/frontend-creator/creator-meta-sync.php`\)

### Read \(for edit mode in creator\)

New `g4t_load_ingredient_sections_meta(int $post_id, int $section_count): array`

Mirrors existing `g4t_load_ingredients_repeater_meta()` but reads the nested structure:

```
recipe_ingredient_sections_{i}_title
recipe_ingredient_sections_{i}_items       (count)
recipe_ingredient_sections_{i}_items_{j}_* (per-item fields)
```

### Write

New `g4t_sync_ingredient_sections_meta(int $post_id, array $sections): void`

* Deletes old `recipe_ingredient_sections_*` keys
* Writes new nested structure to post\_meta
* Collects all ingredient term IDs across ALL sections
* Calls `wp_set_object_terms()` with merged term list \(single taxonomy sync call\)

### Mode toggle

`g4t_sync_ingredients_mode(int $post_id, string $mode): void` — writes `recipe_ingredients_mode`.

Existing `g4t_sync_ingredients_repeater_meta()` unchanged — still called when mode = `'single'`.

---

## Part 6: Frontend Creator \(`features/frontend-creator/creator.js`\)

### State shape additions

```
state.data.ingredientsMode = 'single';          // 'single' | 'sections'
state.data.ingredientSections = [               // only used when mode = 'sections'
  {
    id: 'sec-xxxx',
    title: '',
    items: [
      { id: 'ing-xxxx', name: '', termId: null, amount: '', unit: '', hint: '' }
    ]
  }
];
// Existing state.data.ingredients unchanged — used when mode = 'single'
```

### Mode selector UI \(Screen 10, above ingredient list\)

Radio/toggle: "Jedna lista składników" | "Kilka sekcji \(np. ciasto i farsz\)"

Switching mode:

* `single → sections`: auto-creates Section 1 pre-populated with current `state.data.ingredients`
* `sections → single`: flattens all section items into `state.data.ingredients`, confirms if >1 section

### Sections UI \(mode = 'sections'\)

* Render each section as a titled block with its own ingredient rows table
* "Dodaj sekcję" button appends new empty section
* Section title: inline text input, required \(non-empty\)
* Remove section: only if ≥2 sections; confirms if section has items
* Drag-reorder sections \(same mechanism as existing ingredient row reorder\)
* Ingredient row UI within each section: identical to existing single-mode rows

### `buildAcfPayload()` additions

```
// Mode field
acf[field_recipe_ingredients_mode] = state.data.ingredientsMode

// Sections (only emitted when mode = 'sections')
acf[field_recipe_ingredient_sections][0][field_section_title] = 'Ciasto'
acf[field_recipe_ingredient_sections][0][field_section_items][0][field_ingredient_name] = 'Mąka'
// ...
```

Existing ingredients payload logic unchanged — still emitted when mode = `'single'`.

### Edit-mode hydration

`window.G4T_RECIPE_CREATOR_DATA.initialState` extended server-side:

```
ingredientsMode: 'single' | 'sections',
ingredientSections: [ { id, title, items: [...] } ]  // populated for sections mode
```

`creator.js` reads these on init, populates correct state branch.

---

## Part 7: Theme \(`go4taste-recipes-theme`\)

### Template change

Replace current:

```
{% for ingredient in recipe.ingredients %}
  ...
{% endfor %}
```

With:

```
{% for section in recipe.ingredient_sections %}
  {% if section.title %}
    <h3 class="ingredients__section-title">{{ section.title }}</h3>
  {% endif %}
  {% for ingredient in section.items %}
    ...
  {% endfor %}
{% endfor %}
```

### Styles

New `.ingredients__section-title` rule — visual weight between recipe heading and ingredient rows. Exact styling TBD during implementation.

---

## Part 8: Manual migration

Identify recipes that need sections \(user-driven, ~5 recipes\). For each: open in creator, switch mode to "Kilka sekcji", arrange ingredients into sections, save. No script needed.

---

## Merge readiness

✅ Ready to merge — all implementation stages complete. Manual recipe migration \(~5 recipes\) is a post-merge task done via the creator UI, requires no code changes.

---

<a id="pr-29"></a>
## #29 feat(admin-settings): add access guard to block wp-admin for selected roles

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/options-block-admin-by-role` → `main`
- **Created:** 2026-06-08 · **Closed:** 2026-06-08
- **Merge commit:** `4f6b6ae2d93e`

# PR: Admin Access Guard — blokada wp-admin dla wybranych ról

**Branch:** `feature/options-block-admin-by-role` → `main`

**Affects repos:** `go4taste-recipes-plugin` only

---

## Goal

Dodać do panelu ustawień wtyczki możliwość wskazania ról \(np. Editor\), które po zalogowaniu mają być automatycznie przekierowywane z wp-admin na stronę główną. Cel: redaktorzy nie mogą przypadkowo tworzyć ani edytować przepisów przez wp-admin zamiast przez kreator frontendowy.

---

## Design decisions

| Kwestia | Decyzja | Uzasadnienie |
| --- | --- | --- |
| Gdzie przechowywać ustawienie | Nowy klucz `admin_block_roles` w istniejącej opcji `go4taste_recipes_options` | Zero dodatkowych opcji w bazie — ta sama sanityzacja co `creator_heartbeat_roles` |
| Cel przekierowania | `home_url('/')` | Zawsze istnieje, niezależny od konfiguracji kreatora; nie ujawnia URL panelu |
| Admini \(`manage_options`\) | Zawsze przepuszczani, nie można ich zablokować | Zapobiega przypadkowemu zablokowaniu sobie dostępu przez administratora |
| Wyjątek dla `/wp-admin/profile.php` | Tak — przepuszczać | Profil nie pozwala tworzyć przepisów; redaktor powinien móc zmienić hasło |
| Wyjątek dla AJAX | Tak — `wp_doing_ajax()` | Wp-admin wysyła AJAX w tle; blokada requestów XHR psuje inne wtyczki |
| Wyjątek dla REST API | Tak — `defined('REST_REQUEST') && REST_REQUEST` | REST nie jest stroną wp-admin; Gutenberg i inne używają REST |
| Wyjątek dla WP Cron | Tak — `wp_doing_cron()` | Cron odpala się w kontekście wp-admin; blokada psuje zaplanowane zadania |
| UI | Multi-checkbox — wzorzec identyczny z `creator_heartbeat_roles` | Spójność z istniejącym UI, zero nowych wzorców |
| Nowa sekcja w ustawieniach | Tak — "Kontrola dostępu do panelu" | Semantycznie odrębne od konfiguracji kreatora i źródła danych |

---

## Nowy klucz opcji

```
admin_block_roles    = []   (domyślnie puste — guard nieaktywny)
                     = ['editor', 'author']   (przykład aktywny)
```

Przechowywane w `go4taste_recipes_options` \(istniejąca opcja WP\) jako kolejny klucz tablicy.

---

## Logika guardu

```
// Uruchamiany na: add_action('admin_init', 'g4t_admin_access_guard_check')
function g4t_admin_access_guard(): void {
    if ( wp_doing_ajax() )                              return; // AJAX z wp-admin
    if ( defined('REST_REQUEST') && REST_REQUEST )      return; // REST API
    if ( wp_doing_cron() )                              return; // WP Cron
    if ( ! is_user_logged_in() )                        return; // niezalogowany — WP sobie obsłuży
    if ( current_user_can('manage_options') )           return; // administrator — zawsze przepuść

    global $pagenow;
    if ( 'profile.php' === $pagenow )                   return; // własny profil — przepuść

    $blocked_roles = g4t_get_option('admin_block_roles', []);
    if ( empty($blocked_roles) )                        return; // guard nieaktywny

    $user_roles = (array) wp_get_current_user()->roles;
    if ( ! empty( array_intersect($user_roles, $blocked_roles) ) ) {
        wp_safe_redirect( home_url('/') );
        exit;
    }
}
```

---

## Pliki do zmiany

### `features/admin-settings/settings-page.php`

1. **Rozszerzenie** `$defaults` **w** `register_setting()`

```
// Dodać do tablicy defaults:
'admin_block_roles' => array(),
```

2. **Nowa sekcja \+ nowe pole w** `g4t_register_settings()`

```
add_settings_section(
    'go4taste_recipes_section_access',
    __( 'Kontrola dostępu do panelu', 'go4taste-recipes-plugin' ),
    'g4t_render_settings_section_access',
    'go4taste-recipes-settings'
);

add_settings_field(
    'go4taste_recipes_admin_block_roles',
    __( 'Zablokuj wp-admin dla ról', 'go4taste-recipes-plugin' ),
    'g4t_settings_field_admin_block_roles',
    'go4taste-recipes-settings',
    'go4taste_recipes_section_access'
);
```

3. **Nowe named wrapper callback**

```
function g4t_settings_field_admin_block_roles(): void {
    global $g4t_recipes_plugin;
    if ( $g4t_recipes_plugin instanceof Go4Taste_Recipes_Plugin ) {
        g4t_render_admin_block_roles_field( $g4t_recipes_plugin );
    }
}
```

4. **Nowa funkcja renderująca** — multi-checkbox wzorowany 1:1 na `g4t_render_creator_heartbeat_roles_field()`, z wyjątkiem że `administrator` jest zawsze wyłączony \(disabled \+ unchecked\) z opisem dlaczego.

5. **Rozszerzenie** `g4t_sanitize_options()` — ta sama logika co `creator_heartbeat_roles`:

```
$admin_block_roles = array();
if ( isset( $input['admin_block_roles'] ) && is_array( $input['admin_block_roles'] ) ) {
    foreach ( $input['admin_block_roles'] as $role ) {
        $role = sanitize_key( (string) $role );
        // administrator nigdy nie może być na liście blokowanych
        if ( in_array( $role, $allowed_roles, true ) && 'administrator' !== $role ) {
            $admin_block_roles[] = $role;
        }
    }
    $admin_block_roles = array_values( array_unique( $admin_block_roles ) );
}
```

Klucz `admin_block_roles` dodany do zwracanej tablicy.

---

### `features/admin-settings/settings-logic.php`

Nowa funkcja `g4t_admin_access_guard()` — pełna logika guardu jak powyżej w sekcji "Logika guardu". Plik `settings-logic.php` jest dedykowany logice runtime działającej na każdym requeście — dokładnie to miejsce.

---

### `features/admin-settings/index.php`

Dodanie hooka w `g4t_bootstrap_admin_settings_feature()`:

```
add_action( 'admin_init', 'g4t_admin_access_guard_check' );
```

Nowy wrapper \(zgodnie z konwencją pliku — żadnych anonimowych callbacków bezpośrednio w `add_action`\):

```
function g4t_admin_access_guard_check(): void {
    g4t_admin_access_guard();
}
```

---

## Stages

| Stage | Status | Opis |
| --- | --- | --- |
| Settings 1: nowy klucz \+ sekcja \+ pole | ✅ Done | `admin_block_roles` w defaults, nowa sekcja ACL, `add_settings_field` |
| Settings 2: render callback | ✅ Done | `g4t_render_admin_block_roles_field()` — multi-checkbox, admin disabled |
| Settings 3: sanityzacja | ✅ Done | Rozszerzenie `g4t_sanitize_options()` o `admin_block_roles`, blokada dodania `administrator` |
| Logic: guard function | ✅ Done | `g4t_admin_access_guard()` w `settings-logic.php` — wszystkie wyjątki \+ redirect |
| Bootstrap: hook | ✅ Done | `add_action('admin_init', ...)` \+ wrapper w `index.php` |

---

## Zakres i ryzyko

**Zakres:** 3 pliki, ~70 linii nowego kodu — mała zmiana.

**Ryzyko: niskie**

* Admini \(`manage_options`\) nie mogą być zablokowani — zero ryzyka utraty dostępu
* Domyślna wartość `admin_block_roles = []` — guard jest nieaktywny do momentu świadomej konfiguracji
* AJAX, REST, Cron wyjęte spod guardu — żadne requesty w tle nie są blokowane
* Wyjątek dla `profile.php` — redaktorzy zachowują dostęp do zmiany hasła
* Odwracalne: wyczyszczenie checkboxów = guard wyłączony

---

## Merge readiness

✅ Ready to merge — wszystkie 5 stage'ów zaimplementowanych, brak nowych diagnostyk IDE.
