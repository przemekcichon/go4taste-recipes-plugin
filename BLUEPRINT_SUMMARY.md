# Blueprint Transformation Plan — Executive Summary

**Status:** DRAFT — Ready for Review  
**Saved to:** `BLUEPRINT_PLAN.md` in plugin root  
**Current Plugin:** go4taste-recipes-plugin v0.1.0

---

## Plan Overview

Transform the working recipes plugin into a **reusable ACF Blocks blueprint** that lets developers quickly build domain data plugins (products, articles, events, etc.) with:

- ✅ 5-minute ACF block creation using Copilot
- ✅ 3-minute taxonomy registration with auto-sync
- ✅ 2-minute post type setup  
- ✅ Guided workflows via .agent.md files
- ✅ Copy-paste scaffolds
- ✅ Full documentation with working examples

---

## 7-Phase Implementation Roadmap

### Phase 1: Modularization (1-2 weeks) ⚙️
**Extract reusable base classes from monolithic plugin class**

**New files to create:**
- `includes/Core/DataPlugin.php` — Template pattern base class (override 4 abstract methods)
- `includes/Core/ViewModelBuilder.php` — Normalize block data → schema contract
- `includes/Core/MetaSyncer.php` — Block → post_meta + repeater sync
- `includes/Core/TaxonomyManager.php` — Register taxonomies + auto-create terms
- `includes/Core/ContextFilter.php` — Timber theme integration

**Refactor existing:**
- `includes/class-go4taste-recipes-plugin.php` — Become concrete example, extend DataPlugin

**Outcome:** All current functionality preserved; new plugins simply extend DataPlugin + override 4 methods

---

### Phase 2: Copilot Customization Files (1 week) 🤖
**Add .agent.md, .instructions.md, .prompt.md, SKILL.md for guided development**

**New files:**
- `.github/agents/acf-block-developer.agent.md` — "Create new ACF block" workflow
- `.github/agents/acf-taxonomy-developer.agent.md` — "Add taxonomy" workflow
- `.github/agents/acf-post-type-developer.agent.md` — "Register post type" workflow
- `.github/agents/acf-blueprint-reviewer.agent.md` — Code audit agent

**Instructions (validation rules):**
- `.github/instructions/acf-block-implementation.instructions.md` — Block patterns
- `.github/instructions/acf-taxonomy-implementation.instructions.md` — Taxonomy patterns
- `.github/instructions/view-model-contract.instructions.md` — Schema contract rules

**Skills & Prompts:**
- `skills/acf-blocks-blueprint/SKILL.md` — Blueprint documentation + workflows
- `.github/prompts/create-acf-block.prompt.md` — Guided block creation
- `.github/prompts/extend-schema-contract.prompt.md` — Schema updates

**Outcome:** Ask Copilot "create a new product block" → guided step-by-step workflow

---

### Phase 3: Scaffolding & Templates (3-5 days) 📋
**Provide copy-paste templates to reduce boilerplate**

**New files:**
- `scaffolds/blocks/example-block.template.php` — Block scaffold (copy + rename + edit)
- `scaffolds/taxonomies/example-taxonomy.template.php` — Taxonomy scaffold
- `scaffolds/post-types/example-post-type.template.php` — Post type scaffold
- `skills/acf-blocks-blueprint/SKILL.md` — Detailed LLM-first procedures instead of WP-CLI

**Outcome:** `cp scaffolds/blocks/example-block.template.php includes/blocks/my-block.php` → ready to edit

---

### Phase 4: Documentation & Examples (1 week) 📚
**Teach patterns with working code examples & guides**

**New files:**
- `docs/BLUEPRINT_GUIDE.md` — How everything fits together (architecture + lifecycle)
- `docs/ADDING_BLOCKS.md` — Step-by-step: create block → register → test
- `docs/ADDING_TAXONOMIES.md` — How to register + sync taxonomies
- `docs/VIEW_MODEL_CONTRACT.md` — Schema contract freezing + versioning
- `docs/TIMBER_INTEGRATION.md` — How themes consume view models
- `examples/simple-plugin.php` — Minimal 50-line domain plugin (Board plugin)
- `examples/complex-plugin/` — Full feature product plugin

**Update existing:**
- Inline code comments in all base classes (explain "why", not just "what")
- README.md — Add blueprint usage section

**Outcome:** New developer reads guide (30 min) + reviews example code (1 hour) → ready to build

---

### Phase 5: Validation — Dry Run (1 week) ✅
**Test blueprint by building 2 new plugins to catch gaps**

**Test 1: Simple "Board" Plugin**
- CPT: board
- Block: board-info (title + description)
- Taxonomy: board_status (flat)
- Verify: sync + Timber rendering

**Test 2: Product Plugin** (Full-featured)
- CPT: product
- Blocks: hero, specifications, related-items
- Taxonomies: product_category, product_brand, product_feature
- Repeaters + custom normalizers
- Verify: all patterns work

**Outcome:** Identify missing pieces; fix before release

---

### Phase 6: Release (2-3 days) 🚀
**Package for GitHub; establish blueprint status**

**Updates:**
- Version bump to v1.0.0 (modular + documented blueprint)
- Changelog: document blueprint improvements + any breaking changes
- README.md fork/usage instructions
- CI/CD: PHPStan static analysis + test suite
- Tag release: `v1.0.0-blueprint-stable`

**Outcome:** Others can confidently fork/use as blueprint

---

### Phase 7: Community & Feedback (Ongoing) 👥
**Gather real-world feedback; build reference implementations**

**Optional Phase 8+ deliverables:**
- Interactive web generator: form → skeleton plugin zip
- Community showcase: plugins built on blueprint
- Discussions/issues for feedback capture
- Live docs site with interactive examples

---

## Key Files to Create (By Phase)

| Phase | File | Purpose |
|-------|------|---------|
| 1 | `includes/Core/DataPlugin.php` | Abstract base for all domain plugins |
| 1 | `includes/Core/ViewModelBuilder.php` | Schema normalization |
| 1 | `includes/Core/MetaSyncer.php` | Block → meta sync |
| 1 | `includes/Core/TaxonomyManager.php` | Taxonomy registration + sync |
| 2 | `.github/agents/acf-block-developer.agent.md` | Copilot: create block |
| 2 | `.github/instructions/acf-block-implementation.instructions.md` | Block validation rules |
| 2 | `skills/acf-blocks-blueprint/SKILL.md` | Blueprint documentation |
| 3 | `scaffolds/blocks/example-block.template.php` | Copy-paste block template |
| 4 | `docs/BLUEPRINT_GUIDE.md` | Architecture overview |
| 4 | `examples/simple-plugin.php` | Minimal working example |
| 4 | `docs/ADDING_BLOCKS.md` | Step-by-step block guide |

---

## Decisions Confirmed

1. **File structure:** split by concern (`includes/registration/`, `includes/sync/`, `includes/view-model/`, etc.)
2. **PHP baseline:** 8.3+ minimum (target tests on newer runtimes too)
3. **Backward compatibility:** not required; this blueprint is a clean foundation, not a legacy-preserving layer
4. **WPGraphQL:** full implementation later, but architecture is frontend-agnostic from day one and docs/prompts should guide future adapters
5. **Testing framework:** PHPUnit + WordPress integration tests as the default standard
6. **Generator scope:** no WP-CLI generator; prefer LLM-first workflows via prompt/agent/SKILL guidance
7. **Settings UX:** always present in generated implementations
8. **Editor UX:** locked block template by default for configured post type

---

## Success Metrics

Once complete, a developer should achieve:

| Task | Time | Source |
|------|------|--------|
| Create new ACF block | 5 min | Copilot agent |
| Add taxonomy | 3 min | Copilot agent |
| Register post type | 2 min | Copilot agent |
| Write schema contract | 10 min | Template |
| Create full domain plugin | 30 min | Copy scaffold + docs |
| Debug block → meta sync | < 5 min | Built-in inspection |
| Learn architecture | 1.5 hrs | Docs + code |

---

## Next Steps

1. **Review this plan** — Approval or feedback?
2. **Prioritize phases** — Do all 7, or start with 1-4?
3. **Assign Phase 1 kickoff** — Begin modularization
4. **Communicate to team** — Document decision + roadmap

---

**Full detailed plan:** See `BLUEPRINT_PLAN.md`
