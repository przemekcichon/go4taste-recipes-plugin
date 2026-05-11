# ACF Blocks Plugin Blueprint — Transformation Plan

**Status:** DRAFT  
**Created:** 2026-05-11  
**Goal:** Transform go4taste-recipes-plugin into a reusable template for ACF Block-based domain data plugins

---

## Executive Summary

The go4taste-recipes-plugin currently solves the recipe data problem well but is difficult to reuse for other domains (products, articles, etc.). This plan modularizes the architecture, documents patterns, and creates Copilot-assisted scaffolding workflows so developers can:

- **Create a new ACF block** in minutes with guided workflow
- **Add a new taxonomy** with auto-registration + REST + admin sync
- **Register a post type** with schema contract enforcement
- **Extend the view model** with custom filters and normalizers
- **Debug block-to-meta sync** with built-in inspection tools

---

## Phase 1: Architecture Modularization (Prepare for Reuse)

### Goals
- Split monolithic class into focused, composable components
- Keep all functionality intact (no behavior changes)
- Establish clear interfaces for extension
- Add comprehensive code documentation

### Deliverables

#### 1.1 Create Base Classes (New Files)

**`includes/Core/DataPlugin.php`** — Abstract base class for all domain data plugins
```php
abstract class DataPlugin {
    // Configuration methods (to override)
    abstract protected function get_post_types(): array;
    abstract protected function get_taxonomies(): array;
    abstract protected function get_block_names(): array;
    abstract protected function get_acf_field_groups(): array;

    // Core registration (template pattern)
    protected function register_all(): void {
        add_action( 'init', [ $this, 'register_post_types' ] );
        add_action( 'init', [ $this, 'register_taxonomies' ] );
        add_action( 'init', [ $this, 'register_blocks' ] );
        add_action( 'acf/init', [ $this, 'register_acf_fields' ] );
    }

    // Generic implementations
    public function register_post_types(): void { /* implementation */ }
    public function register_taxonomies(): void { /* implementation */ }
    public function register_blocks(): void { /* implementation */ }
    public function register_acf_fields(): void { /* implementation */ }
}
```

**`includes/Core/ViewModelBuilder.php`** — Generic schema contract builder
```php
class ViewModelBuilder {
    // Map block data → normalized view model
    public function build_from_post( WP_Post $post, array $schema_map ): array { }
    
    // Apply normalizers (ingredients, steps, images, etc.)
    public function apply_normalizers( array $source, array $normalizer_registry ): array { }
    
    // Merge primary + fallback sources
    public function merge_with_fallback( array $primary, array $fallback ): array { }
}
```

**`includes/Core/MetaSyncer.php`** — Block-to-meta synchronization
```php
class MetaSyncer {
    // Extract data from parsed block content
    public function extract_block_data( WP_Post $post, array $block_names ): array { }
    
    // Sync parsed data to post meta + repeaters
    public function sync_to_meta( int $post_id, array $data ): void { }
    
    // Sanitize + escape based on field type
    public function sanitize_value( $value, string $field_type ): mixed { }
}
```

**`includes/Core/TaxonomyManager.php`** — Taxonomy registration + term sync
```php
class TaxonomyManager {
    // Register taxonomy from config
    public function register_taxonomy( string $key, array $config, array $post_types ): void { }
    
    // Sync terms from repeater (e.g., ingredients → taxonomy)
    public function sync_terms_from_repeater( int $post_id, array $rows, string $taxonomy, string $lookup_field ): void { }
    
    // Ensure term exists (create if missing)
    public function ensure_term( string $name, string $taxonomy ): ?int { }
}
```

**`includes/Core/ContextFilter.php`** — Timber integration filter manager
```php
class ContextFilter {
    // Register context filters (single, archive, home)
    public function register_context_filters( array $filter_config ): void { }
    
    // Apply view model to context array
    public function apply_context( array $context, WP_Post $post, array $view_model ): array { }
}
```

#### 1.2 Refactor Go4Taste_Recipes_Plugin (Existing File)

Replace monolithic class with:
- Extend `DataPlugin` base class
- Use `ViewModelBuilder`, `MetaSyncer`, `TaxonomyManager`, `ContextFilter` internally
- Override abstract methods with recipe-specific config
- Add extensive inline comments explaining each section

#### 1.3 Documentation Updates

**`includes/Core/ARCHITECTURE.md`** — How the modular pieces fit together
- Lifecycle diagram (init → registration → sync → context)
- Extension points (create derived class, override methods)
- Common patterns (adding blocks, taxonomies, normalizers)

**`includes/CONTRIBUTING.md`** — Developer guide for extending the base classes

---

## Phase 2: Copilot Customization Files (Guided Development)

### Goals
- Provide Copilot agents for common tasks
- Document patterns via instructions
- Create reusable prompts + skills

### Deliverables

#### 2.1 Agents (`.agent.md` files)

**`.github/agents/acf-block-developer.agent.md`**
```yaml
---
name: ACF Block Developer
description: |
  Create, register, and sync a new ACF block for this domain data plugin.
  Handles: block configuration, editor controls, render callback, field mapping.
model: claude-3-5-sonnet
tools: enabled
---

## Workflow

You are an expert WordPress ACF block developer. When asked to create a new ACF block
for this domain data plugin:

1. **Understand the request** — What data does this block collect? Schema fields?
2. **Review existing blocks** — Follow the recipe plugin block patterns
3. **Create block boilerplate** — Register block, add editor controls, handle attributes
4. **Define ACF fields** — Register field group for this block (repeater/text/etc.)
5. **Implement sync** — Ensure block data syncs to post_meta via MetaSyncer
6. **Test rendering** — Verify block renders correctly in admin + frontend
```

**`.github/agents/acf-taxonomy-developer.agent.md`**
```yaml
---
name: ACF Taxonomy Developer
description: |
  Register, configure, and sync a new taxonomy for this domain data plugin.
  Handles: taxonomy registration, REST exposure, term auto-creation, post assignment.
model: claude-3-5-sonnet
tools: enabled
---

Workflow for adding a new taxonomy...
```

**`.github/agents/acf-post-type-developer.agent.md`**
```yaml
---
name: ACF Post Type Developer
description: |
  Register a new custom post type integrated with this domain data plugin.
  Handles: CPT registration, REST support, template locking, supports array.
model: claude-3-5-sonnet
tools: enabled
---

Workflow for adding a new post type...
```

**`.github/agents/acf-blueprint-reviewer.agent.md`**
```yaml
---
name: ACF Blueprint Reviewer
description: |
  Review plugin code for compliance with blueprint patterns and best practices.
  Findings-only review (no edits).
model: claude-3-5-sonnet
tools: enabled
---

Workflow for auditing plugin code...
```

#### 2.2 Instructions (`.instructions.md` files)

**`.github/instructions/acf-block-implementation.instructions.md`**
```yaml
---
name: ACF Block Implementation
description: |
  Template + rules for creating a new ACF block in this plugin.
applyTo: includes/blocks/**.php, assets/js/*.js
---

## Rules

1. **Block Registration** — Use DataPlugin::register_block_type() helper
2. **Attributes** — Declare all attributes in register_block_type()
3. **ACF Fields** — Always use acf_add_local_field_group; follow naming pattern
4. **Editor Controls** — Use @wordpress/components for UI
5. **Data Sync** — MetaSyncer handles block → meta; no manual update_post_meta()
6. **Render Callback** — Placeholder for view layer (Timber filters content)
7. **Comments** — Document field mapping; explain why sanitization chosen
```

**`.github/instructions/acf-taxonomy-implementation.instructions.md`**
```yaml
---
name: ACF Taxonomy Implementation
description: |
  Template + rules for registering a new taxonomy in this plugin.
applyTo: includes/core/TaxonomyManager.php, includes/**.php
---

## Rules

1. **Registration** — Call TaxonomyManager::register_taxonomy() from get_taxonomies()
2. **REST Exposure** — Always set show_in_rest = true
3. **Admin Sync** — Use show_admin_column = true for quick editing
4. **Term Auto-Creation** — If repeater field, set up sync via MetaSyncer
5. **Naming** — Singular/plural labels in plugin text domain
6. **Slug** — Use Polish-friendly slug (e.g., typ-dania, czas-przygotowania)
```

**`.github/instructions/view-model-contract.instructions.md`**
```yaml
---
name: View Model Contract
description: |
  Template + rules for defining a schema contract (SCHEMA_CONTRACT_V1.md).
applyTo: SCHEMA_CONTRACT_*.md
---

## Rules

1. **Structure** — document array shape (nested objects, arrays, nullable fields)
2. **Nullability Table** — Mark which fields can be null + default values
3. **Data Normalization** — Explain any list/string cleaning logic
4. **Source Contract** — Which ACF fields → which view model fields
5. **Taxonomy Binding** — How repeater fields map to taxonomies
6. **Example** — Include full PHP example of the view model
7. **Frozen Date** — Mark when contract was frozen (e.g., Phase 0)
```

#### 2.3 Prompts (`.prompt.md` files)

**`.github/prompts/create-acf-block.prompt.md`**
```yaml
---
name: Create ACF Block
agent: ask
description: Guided creation of new ACF block for domain data plugin
model: claude-3-5-sonnet
---

I want to create a new ACF block for this domain data plugin.

Please ask me to provide:
1. What data does this block collect? (e.g., "ingredients list with amounts")
2. What UI controls? (text, repeater, image upload, etc.)
3. Is it a repeater? If so, what fields?
4. Should block data sync to taxonomy? If yes, which?
5. Any validation rules?

Then guide me through the implementation.
```

**`.github/prompts/extend-schema-contract.prompt.md`**
```yaml
---
name: Extend Schema Contract
agent: ask
description: Add new fields to view model schema contract
model: claude-3-5-sonnet
---

I want to add new fields to the SCHEMA_CONTRACT.

Please ask:
1. New field name + data type?
2. Is it nullable? Default value?
3. Which ACF field(s) provide this data?
4. Any normalization/sanitization needed?
5. Should it appear in Timber context?

Then update the schema contract document.
```

#### 2.4 Skills (`.md` files in `skills/` directory)

**`skills/acf-blocks-blueprint/SKILL.md`**
```yaml
---
name: acf-blocks-blueprint
description: |
  Blueprint architecture for building domain data plugins using ACF blocks.
  Covers: base classes, modular design, view model contracts, meta sync,
  taxonomy binding, Timber integration, context filters, scaffolding.
---

# ACF Blocks Blueprint Skill

[Detailed documentation of the blueprint architecture]

## Core Concepts
- **DataPlugin base class** — inherit + override abstract methods
- **ViewModelBuilder** — normalize block data → schema contract
- **MetaSyncer** — map block fields → post meta + repeaters
- **TaxonomyManager** — register + sync taxonomies
- **ContextFilter** — expose view model to Timber templates

## Common Workflows
- Add a new ACF block (5 steps)
- Register a taxonomy with term sync (4 steps)
- Add a post type (3 steps)
- Create a new domain plugin from blueprint (8 steps)

## Files to Know
- `includes/Core/*.php` — base classes
- `includes/class-go4taste-recipes-plugin.php` — recipe example (extends DataPlugin)
- `SCHEMA_CONTRACT_V1.md` — view model shape for this domain
- `.github/agents/*.md` — Copilot workflows
```

---

## Phase 3: Scaffolding & Templates (Make It Easy to Extend)

### Goals
- Reduce boilerplate when extending the blueprint
- Provide runnable templates for common tasks
- Auto-generate skeleton files

### Deliverables

#### 3.1 Block Scaffold Template

**`scaffolds/blocks/example-block.template.php`** — Copy + rename to create new block
```php
<?php
/**
 * Scaffold: New ACF Block for {{ domain_plural }} Plugin
 * 
 * Instructions:
 * 1. Replace {{ block_name }} with your block key (e.g., product-details)
 * 2. Replace {{ Block_Label }} with human label (e.g., Product Details)
 * 3. Update $fields array with your ACF fields
 * 4. Adjust $sync_fields to map block data → post meta
 * 5. Update the get_acf_field_groups() method to register your fields
 * 6. Test: Create post, add block, check post_meta + sync behavior
 */

class {{ Domain }}_Block_{{ Block_Label }} {
    const BLOCK_NAME = '{{ domain }}/{{ block_name }}';
    
    public function __construct() {
        // Register block type
        // Register ACF fields
        // Add sync mappings
    }
    
    public static function register(): void {
        new self();
    }
    
    // ... standard block methods
}
```

#### 3.2 Taxonomy Scaffold Template

**`scaffolds/taxonomies/example-taxonomy.template.php`**
```php
class {{ Domain }}_Taxonomy_{{ Taxonomy_Label }} {
    const TAXONOMY_KEY = '{{ domain }}_{{ taxonomy_key }}';
    
    public function __construct() {
        // Define configuration
        // Register taxonomy
        // Set up term sync (optional)
    }
    
    public static function register(): void {
        new self();
    }
}
```

#### 3.3 Post Type Scaffold Template

**`scaffolds/post-types/example-post-type.template.php`**

#### 3.4 WP-CLI Generator Command (Optional)

Replaced by LLM-first procedures in prompts/skills/agents.

**`skills/acf-blocks-blueprint/SKILL.md`** should include deterministic procedures for:
- adding a block,
- adding a taxonomy,
- adding a post type,
- updating schema contract and tests.

---

## Phase 4: Documentation & Examples (Learn from Recipes)

### Goals
- Document every pattern with examples
- Create copy-paste-ready code snippets
- Explain "why" behind architectural choices

### Deliverables

#### 4.1 Developer Guide

**`docs/BLUEPRINT_GUIDE.md`** — Main entry point
- Architecture overview (diagram)
- Core classes + responsibilities
- When to use each base class
- Lifecycle: init → registration → sync → render

**`docs/ADDING_BLOCKS.md`** — Step-by-step guide
```markdown
# Adding a New ACF Block

## Step 1: Understand the Data Model
- What fields? (repeater, text, image?)
- Does it sync to taxonomy?
- Validation rules?

## Step 2: Create Block Class
- Copy `scaffolds/blocks/example-block.template.php`

## Step 3: Register ACF Fields
- Use acf_add_local_field_group()

## Step 4: Define Block in `get_block_names()`
- Tell DataPlugin about your new block

## Step 5: Map Block → Meta via MetaSyncer
- Define sanitization + escaping

## Step 6: Test
- [Test checklist]
```

**`docs/ADDING_TAXONOMIES.md`** — Taxonomy registration + sync

**`docs/VIEW_MODEL_CONTRACT.md`** — How to define + freeze schema contracts

**`docs/TIMBER_INTEGRATION.md`** — How themes consume view models

#### 4.2 Code Examples

**`examples/simple-plugin.php`** — Minimal 50-line domain plugin
- Board plugin with title + description + status taxonomy
- Demonstrates all Blueprint patterns in smallest form

**`examples/complex-plugin/`** — Full product plugin
- Products CPT
- Multiple taxonomies (category, brand, tags)
- Repeater (features, specs)
- Image handling
- Shows best practices

#### 4.3 Comments in Source Code

Add extensive inline comments explaining:
- Why each base class exists
- How extension points work
- Common pitfalls + how to avoid them

---

## Phase 5: Quality Assurance & Validation

### Goals
- Ensure blueprint is truly reusable
- Catch gaps before others use it
- Establish testing patterns

### Deliverables

#### 5.1 Test the Blueprint by Creating 2 New Plugins (Dry Run)

**Test Case 1: Simple "Board" Plugin**
- Create minimal plugin extending DataPlugin
- Register 1 post type (board)
- 1 block (title + description)
- 1 flat taxonomy (board status)
- Verify: block → meta → taxonomy sync
- Verify: Timber integration works

**Test Case 2: Product Plugin**
- Create full-featured plugin extending DataPlugin
- Product CPT
- 3 ACF blocks (hero, specifications, related items)
- Multiple taxonomies with hierarchies
- Repeater fields with custom normalizers
- Image handling
- Verify: all patterns work

#### 5.2 Documentation Audit

- [ ] Every base class has @param PHPDoc
- [ ] Every abstract method explained
- [ ] Extension examples provided
- [ ] Common errors documented
- [ ] Performance notes included

#### 5.3 Copilot Integration Test

- [ ] Run .agent.md workflows (ask Copilot to create block/taxonomy)
- [ ] Verify .instructions.md rules are enforced
- [ ] Test .prompt.md guided workflows
- [ ] Validate SKILL.md explains each pattern

---

## Phase 6: Release & Promotion

### Goals
- Package blueprint for GitHub
- Make discoverable + citable
- Establish usage patterns

### Deliverables

#### 6.1 Repository Updates

**Add to README.md**
```markdown
## Using This Blueprint

This plugin is designed as a **reusable template** for WordPress domain data plugins 
using ACF blocks. To create a plugin for your own domain:

1. Fork this repository (or use as a dependency)
2. Adapt `includes/class-go4taste-recipes-plugin.php` to your domain
3. Use `.github/agents/acf-block-developer.agent.md` to add blocks
4. Use Copilot for guided workflows (see CONTRIBUTING.md)
5. Test with the examples in `examples/`

See [BLUEPRINT_GUIDE.md](docs/BLUEPRINT_GUIDE.md) for full documentation.
```

#### 6.2 CI/CD + Testing

- PHPStan static analysis for all new code
- Unit tests for ViewModelBuilder, MetaSyncer, TaxonomyManager
- Integration tests (block → meta → Timber context)

#### 6.3 Version Bump

- v1.0.0 = modular + documented blueprint ready for reuse
- Include changelog noting blueprint improvements + breaking changes (if any)

---

## Phase 7: Community & Feedback

### Goals
- Gather feedback from real usage
- Improve patterns based on feedback
- Build community

### Deliverables

#### 7.1 Example Plugins Built on Blueprint

Create reference implementations:
- Blog plugin (posts, categories, featured images, comments)
- Product plugin (catalog, variants, prices, reviews)
- Event plugin (dates, locations, speakers, registrations)

#### 7.2 GitHub Discussions / Issues

- Issue template for blueprint feedback
- Discussions board for questions
- Encourage sharing custom plugins built on blueprint

#### 7.3 Live Documentation Site (Optional Phase 8+)

- Interactive tutorials
- Blueprint generator (web form → skeleton plugin zip)
- Showcase of plugins built on blueprint

---

## Implementation Roadmap

| Phase | Duration | Priority | Key Deliverable |
|-------|----------|----------|-----------------|
| 1. Modularization | 1-2 weeks | CRITICAL | Base classes + refactored Go4Taste |
| 2. Copilot Files | 1 week | HIGH | .agent.md, .instructions.md, SKILL.md |
| 3. Scaffolding | 3-5 days | MEDIUM | Block + taxonomy templates |
| 4. Documentation | 1 week | HIGH | Guides + examples code |
| 5. QA (Dry Run) | 1 week | CRITICAL | Test on 2 new plugins |
| 6. Release | 2-3 days | MEDIUM | v1.0.0 + promote blueprint |
| 7. Community | Ongoing | LOW | Gather feedback long-term |

---

## Success Criteria

A developer should be able to:

✅ Create a new ACF block in **5 minutes** using Copilot agent  
✅ Add a taxonomy in **3 minutes**  
✅ Register a post type in **2 minutes**  
✅ Write schema contract file in **10 minutes**  
✅ Create a new domain plugin from blueprint in **30 minutes**  
✅ Understand architecture by reading docs (30 min) + code comments (1 hour)  
✅ Extend blueprint with custom normalizers without modifying base classes  
✅ Debug block → meta sync using built-in inspection tools  

---

## Confirmed Decisions

- Architecture split by concern.
- PHP baseline is 8.3+.
- Backward compatibility with prototype implementation is not required.
- Frontend-agnostic core is mandatory.
- WPGraphQL/Timber/standard WP adapter guidance should exist now; full adapter implementation comes later.
- PHPUnit + WordPress integration tests are the default quality baseline.
- LLM-first code generation workflow (no WP-CLI scaffolding requirement).
- Settings page UX is always included.
- Locked editor template for configured post type is the default behavior.

## Open Questions / Known Gaps

- [ ] How to handle complex repeater nesting (repeater in repeater)?
- [ ] Best pattern for file uploads + media attachment?
- [ ] How to version schema contracts (v1, v2, migrations)?
- [ ] Full WPGraphQL adapter implementation timeline and acceptance criteria (guidance exists earlier)
- [ ] Multisite support?
- [ ] Performance optimization for large datasets?
- [ ] Sync conflict resolution (block edited while meta also edited)?

---

## Related Documents

- `SCHEMA_CONTRACT_V1.md` — Frozen recipe view model shape
- `.github/copilot-instructions.md` — Global Copilot rules (if exists)
- `includes/CONTRIBUTING.md` — Developer setup + testing
- `docs/ARCHITECTURE.md` — Diagram + lifecycle (to be created in Phase 1)

---

**Next Step:** Approve this plan, then begin Phase 1 (Modularization).
