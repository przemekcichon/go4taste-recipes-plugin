# Blueprint Plan — Key Findings & Recommendations

**Document:** Review of go4taste-recipes-plugin → Blueprint Transformation  
**Date:** 2026-05-11  
**Recommendation:** PROCEED with Phase 1-4; Phase 5+ optional based on feedback  

---

## What's Working Well (Keep It)

✅ **Clean monolithic design** — Single well-organized class with clear responsibilities  
✅ **Strong schema contract** — `SCHEMA_CONTRACT_V1.md` freezes the view model shape  
✅ **Flexible architecture** — Configurable post type (post / existing CPT / new CPT)  
✅ **Block-to-meta sync** — Automatic sanitization + repeater normalization  
✅ **Taxonomy binding** — Auto-creates + assigns taxonomy from repeater data  
✅ **Theme filters** — Clean Timber integration via context filters  
✅ **Good defaults** — Fallback when config fails; dependency checks  

---

## What Needs Improvement (Blueprint Goals)

❌ **Everything in one class** — Hard to reuse for other domains (products, articles, etc.)  
→ **Solution Phase 1:** Extract 5 reusable base classes (`DataPlugin`, `ViewModelBuilder`, etc.)

❌ **No extension patterns** — Devs must modify source code or copy-paste entire plugin  
→ **Solution Phase 1:** Template method pattern + abstract methods to override

❌ **No Copilot integration** — Creating a block requires diving into code  
→ **Solution Phase 2:** Add .agent.md workflows + .instructions.md validation rules

❌ **No scaffolding** — Each new block/taxonomy is boilerplate from scratch  
→ **Solution Phase 3:** Provide copy-paste templates + LLM-first SKILL procedures

❌ **Limited documentation** — Developers must read source code to understand patterns  
→ **Solution Phase 4:** Guides, examples, architecture diagrams, inline comments

❌ **Untested on other domains** — Unknown whether base classes truly work for non-recipes  
→ **Solution Phase 5:** Dry run with 2 test plugins (Board, Product)

---

## Why This Blueprint Approach?

### The Problem
Every domain-specific WordPress plugin that uses ACF blocks solves the same problems:
- Register custom post type
- Create ACF blocks with editor controls
- Sync block data → post_meta
- Create/assign taxonomy terms
- Build normalized view models
- Inject view models into Timber context
- Debug sync issues

**Current approach:** Start from scratch, copy-paste patterns, reinvent normalizers.  
**Time waste:** 40% per new plugin rebuilding infrastructure.

### The Solution
Turn go4taste-recipes-plugin into a **reusable blueprint** that provides:
- Proven base classes for all plugins to extend
- Guided workflows (Copilot agents)
- Scaffolding to reduce boilerplate
- Full documentation + working examples
- Community patterns + reference implementations

**Expected outcome:** New domain plugin in 30 min (vs. current 1-2 days).

---

## Architecture Decision: 5 Reusable Base Classes

### Why Break Into Pieces?

**Option A: Just refactor (single modular Go4Taste class)**
- ❌ Still tightly coupled to recipes domain
- ❌ Defaults baked in (recipe blocks, ingredient taxonomy, etc.)
- ❌ Hard to extend without inheritance
- ❌ Copy-paste still required

**Option B: Extract 5 base classes (recommended)**
- ✅ Each class solves ONE problem (single responsibility)
- ✅ Plugins compose + extend via inheritance + config
- ✅ Clear extension points (abstract methods)
- ✅ Reusable across any domain
- ✅ Easier to test + maintain

### The 5 Base Classes

1. **DataPlugin** (abstract)
   - **Why:** Template method; orchestrates lifecycle
   - **Override:** `get_post_types()`, `get_taxonomies()`, `get_blocks()`, `get_acf_fields()`
   - **Provides:** init action hooks, settings UI, dependency notices
   - **Example:** `class Go4Taste_Recipes_Plugin extends DataPlugin`

2. **ViewModelBuilder**
   - **Why:** Normalize heterogeneous block/meta data → schema contract shape
   - **Methods:** `build_from_post()`, `apply_normalizers()`, `merge_with_fallback()`
   - **Reusable:** Process blocks → view model for ANY domain
   - **Example:** Same code normalizes recipes, products, articles

3. **MetaSyncer**
   - **Why:** Extract block content, sanitize, store to post_meta + repeaters
   - **Methods:** `extract_block_data()`, `sync_to_meta()`, `sanitize_value()`
   - **Reusable:** Generic block parsing + meta update logic
   - **Example:** Works for all block types (hero, specs, related items, etc.)

4. **TaxonomyManager**
   - **Why:** Register all taxonomies + handle term sync from repeater fields
   - **Methods:** `register_taxonomy()`, `sync_terms_from_repeater()`, `ensure_term()`
   - **Reusable:** Pattern applies to any repeater → taxonomy mapping
   - **Example:** Ingredients → recipe_ingredient, features → product_feature

5. **ContextFilter**
   - **Why:** Manage Timber context filter registration + application
   - **Methods:** `register_context_filters()`, `apply_context()`
   - **Reusable:** Same pattern for single, archive, home templates
   - **Example:** Inject view model into theme $context

---

## Why Copilot Integration (Phase 2) Matters

### Without Copilot Files (.agent.md, .instructions.md)
- Developer must ask: "How do I create a block?"
- Answer: Read the source code, study existing blocks, copy-paste recipes/hero block
- Time: 30+ minutes, high error rate

### With Copilot Files
- Developer asks: "Create a product-details block"
- Copilot: "I'll guide you through 5 steps using the acf-block-developer agent"
- Copilot walks through: data model → register block → ACF fields → sync mapping → test
- Time: 5 minutes, guided by pattern, validated by .instructions.md rules

**Examples:**
- `.agent.md` — Multi-turn conversation guiding entire workflow
- `.instructions.md` — Rules enforced (e.g., all blocks must have render_callback, all fields must be in text domain)
- `.prompt.md` — "Create ACF block" → Copilot prompts for info (block name, fields, validation)
- `.skill.md` — Reference docs for architecture patterns

---

## Recommended Start: Phases 1-4 (Core Blueprint)

### Phase 1 Priority: CRITICAL
**Why:** Without base classes, everything else is on moving sand.  
**Outcome:** Working base classes; go4taste still 100% functional.  
**Effort:** 1-2 weeks (modularization only, no new features).

### Phase 2 Priority: HIGH
**Why:** Copilot files make the blueprint *discoverable* + *usable*.  
**Outcome:** Devs can ask Copilot "create block" → guided workflow.  
**Effort:** 1 week (writing .agent.md, .instructions.md, SKILL.md).

### Phase 3 Priority: MEDIUM
**Why:** Scaffolding reduces friction but not critical.  
**Outcome:** `cp scaffold/block.template.php` → rename + edit.  
**Effort:** 3-5 days (write 3 templates, optional WP-CLI).

### Phase 4 Priority: HIGH
**Why:** Without docs, blueprint is a mystery.  
**Outcome:** Developers can learn architecture + patterns.  
**Effort:** 1 week (BLUEPRINT_GUIDE.md + examples + comments).

### Phase 5 Priority: MEDIUM (Optional)
**Why:** Validates that blueprint works for other domains.  
**Outcome:** Identify gaps; fix before community uses it.  
**Effort:** 1 week (build 2 test plugins).

### Phases 6-7 Priority: LOW (Later)
**Why:** Release + community are nice-to-haves after validation.  
**Effort:** 2-3 days release; ongoing community.

---

## Implementation Tips & Pitfalls to Avoid

### ✅ Do This

1. **Keep Go4Taste_Recipes_Plugin 100% functional during refactor**
   - Existing code uses this plugin; don't break it
   - Run smoke tests after each phase

2. **Write extensive inline comments in base classes**
   - Explain "why" this method exists
   - Show extension examples in docblocks
   - Use `@param`, `@return` heavily

3. **Test modularization on real data**
   - Use existing recipes in your local WP
   - Verify block → meta → Timber rendering still works

4. **Keep schema contract frozen**
   - `SCHEMA_CONTRACT_V1.md` is the contract
   - New fields require version bump (v2)

5. **Use consistent naming**
   - Base classes: `class ClassName`
   - Methods: `public function method_name()`
   - Constants: `const THING_KEY = 'domain_key'`

### ❌ Don't Do This

1. ❌ **Don't break existing go4taste-ads integration**
   - Your memory says: "do NOT modify go4taste-ads now"
   - Recipe ads products still need to work post-refactor

2. ❌ **Don't add features during modularization**
   - Phase 1 = refactoring only, no new functionality
   - Keep it focused

3. ❌ **Don't assume base classes work for all plugins**
   - That's why Phase 5 (dry run) exists
   - Edge cases will emerge

4. ❌ **Don't skip documentation**
   - Code without docs won't be reused
   - Invest in Phase 4 heavily

---

## Decisions Confirmed Before Start

1. **File structure:** split by concern.
2. **Backward compatibility:** no requirement.
3. **WPGraphQL:** guidance now, full adapter implementation later.
4. **Testing framework:** PHPUnit + WordPress integration tests.
5. **Generation workflow:** LLM-first via prompts/skills/agents, no WP-CLI requirement.
6. **Default UX:** settings page always + locked template on configured post type.
7. **PHP baseline:** 8.3+.

---

## What Success Looks Like

### By End of Phase 1
- Base classes exist + well-commented
- Go4Taste_Recipes_Plugin extends DataPlugin
- All tests pass; recipes still work 100%
- ARCHITECTURE.md explains each class + extension points

### By End of Phase 2
- Copilot agents ready for testing
- Developers can ask: "Create product-hero block"
- Copilot guides through workflow (5 steps)
- Rules in .instructions.md prevent common mistakes

### By End of Phase 4
- Anyone can read BLUEPRINT_GUIDE.md (30 min) + example code (1 hour) → understand architecture
- Scaffolds reduce friction
- Known patterns documented + commented

### By End of Phase 5
- 2 test plugins (Board, Product) built successfully
- Gaps identified + fixed
- Blueprint is "ready for others to fork"

---

## Timeline Estimate

| Phase | Duration | Team Size | Effort | Difficulty |
|-------|----------|-----------|--------|------------|
| 1 | 1-2 weeks | 1-2 dev | HIGH | MEDIUM |
| 2 | 1 week | 1 dev | MEDIUM | LOW |
| 3 | 3-5 days | 1 dev | LOW | LOW |
| 4 | 1 week | 1-2 dev | HIGH | MEDIUM |
| 5 | 1 week | 1 dev | HIGH | MEDIUM |
| 6 | 2-3 days | 1 dev | MEDIUM | LOW |
| 7 | Ongoing | 1 dev | LOW | LOW |
| **Total** | **5-6 weeks** | 1-2 avg | **HIGH** | **MEDIUM** |

---

## Recommendation

✅ **PROCEED with Phases 1-4 immediately.**

This will result in:
- Modular, reusable base classes
- Copilot-guided workflows
- Scaffolding templates
- Comprehensive documentation + examples
- Blueprint ready for internal use + community sharing

**Skip Phase 5** until someone wants to build a second domain plugin on the blueprint.  
**Defer Phases 6-7** until Phase 5 validates the approach.

---

## Next Actions (If Approved)

1. [ ] Review this plan with stakeholders
2. [ ] Answer 5 open questions (file structure, WPGraphQL, tests, etc.)
3. [ ] Assign Phase 1 work (modularization kickoff)
4. [ ] Set up git branch: `feature/blueprint-modularization`
5. [ ] Weekly check-ins on progress + blocking issues

---

**Documents:** 
- Full plan: `BLUEPRINT_PLAN.md`  
- Summary: `BLUEPRINT_SUMMARY.md`  
- Findings: This file  

**Next:** Approval + Phase 1 kickoff
