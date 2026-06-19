# Etch Builders WP Rendering Tests — Design

- **Date:** 2026-06-19
- **Status:** Approved (awaiting implementation plan)
- **Owner:** maintainer
- **Repo:** `honestlydesign/etch-builders-wp-tests`
- **Target dir:** `/Users/woji/Dev/Packages/Composer/etch-builders-wp-tests`

## Goal

Prove the **rendering contract** between the `honestlydesign/etch-builders`
package's serialization and the real Etch runtime: every public method of every
builder class, plus their logical combinations, rendered end-to-end through a
live Etch install and asserted on actual HTML + emitted `<style>` tags.

The package's own 105-test suite proves *serialization* correctness (the right
`etch_styles` entry + `<!-- wp:etch/element -->` markup). This repo proves
*rendering* correctness — that Etch's `StylesRegister`/`CssProcessor`/
`ClassProperty`/`LoopHandler`/`ConditionEvaluator`/`DynamicContentProcessor`
actually produce the expected HTML + CSS from what the package serializes.

## Background — what's being tested

- **Package under test:** `honestlydesign/etch-builders: ^1.0` (the pure-PHP
  builder API). Installed via Composer.
- **Runtime:** Etch 1.5.1 plugin + Etch theme 0.0.7, pinned as zips in
  `assets/plugin/` and `assets/theme/`.
- **WP:** `wp-env` (Docker) with the Etch plugin + theme active.
- **License keys:** in `.env` (`OMIDE_ETCH_LICENSE_KEY`,
  `OMIDE_ETCH_THEME_LICENSE_KEY`).

## Architecture decisions

**Render strategy — live render end-to-end.** Each test: build the block tree
via the package builder → persist to `etch_styles`/`etch_loops`/etc. via the WP
adapters → insert as a `wp_post` → call `render_block()`/`render_post()` →
assert on the rendered HTML AND the `<style id="etch-page-styles">` emitted via
`wp_head`. Exercises the FULL Etch runtime. Slower per test (~50-200ms) but
proves the end-to-end contract.

**WP test data — per-test seeding.** Each test seeds its own fixtures
(`wp_insert_post`, `wp_insert_term`, `wp_insert_user`) and tears them down
after. No shared state. Most deterministic.

**Coverage — every method + logical combinations.** Every public method of
every builder gets at least one render test. Fluent setters that affect output
each get a test showing their effect on rendered HTML. Throw paths get a test
each. Logical combinations (BEM `&__`, `@media(--tablet)`, `:hover`, nested
loops, conditions, component slots) tested together. ~186 test methods.

**Etch pin — deterministic 1.5.1/0.0.7 zips.** No network fetches, no version
drift. Update the zips + re-baseline tests when upgrading intentionally.

**Organization — one test file per builder class.** Maps 1:1 to the package's
class structure. Easy to trace a failing test to the exact method.

## Repo shape

```
etch-builders-wp-tests/
├── .env                              # EXISTS — Etch license keys
├── .gitignore                        # vendor/, .env, composer.lock, node_modules/
├── .github/workflows/ci.yml          # wp-env + composer test
├── README.md
├── composer.json                     # require honestlydesign/etch-builders:^1.0
├── .wp-env.json                      # mounts Etch plugin+theme zips, test mu-plugin
├── phpunit.xml.dist                  # runs INSIDE wp-env (WP_TESTS_DIR bootstrap)
├── tests/
│   ├── bootstrap.php                 # WP test lib bootstrap + test mu-plugin load
│   └── Integration/
│       ├── RenderingTestCase.php     # base class: render_block_markup, RenderResult, seed helpers, teardown
│       ├── RenderResult.php          # value object: html() + style_tag()
│       ├── ElementBlockRenderingTest.php
│       ├── DynamicElementBlockRenderingTest.php
│       ├── TextBlockRenderingTest.php
│       ├── RawHtmlBlockRenderingTest.php
│       ├── SvgBlockRenderingTest.php
│       ├── DynamicImageBlockRenderingTest.php
│       ├── ConditionBlockRenderingTest.php
│       ├── LoopBlockRenderingTest.php
│       ├── LoopPresetRenderingTest.php
│       ├── ComponentBlockRenderingTest.php
│       ├── SlotContentBlockRenderingTest.php
│       ├── ComponentRenderingTest.php
│       ├── PatternRenderingTest.php
│       ├── StyleRenderingTest.php
│       └── StylesheetRenderingTest.php
├── mu-plugin/
│   ├── etch-builders-test-bootstrap.php  # loads Composer autoload + Environment::configure(...)
│   └── src/
│       ├── WpStorage.php                 # implements StorageInterface
│       ├── WpMode.php                    # implements ModeProviderInterface
│       ├── WpAssetRegistry.php           # implements AssetRegistryInterface
│       └── WpComponentRefResolver.php    # implements ComponentRefResolverInterface
└── assets/
    ├── plugin/etch-1.5.1.zip             # EXISTS
    └── theme/etch-theme-0.0.7.zip        # EXISTS
```

## The 4 WP adapters

Written here first, proven against real rendering, then reused by the starter
in Phase 4 of the package project.

- **`WpStorage implements StorageInterface`** — `get` → `get_option`, `set` →
  `update_option`, `delete` → `delete_option`.
- **`WpMode implements ModeProviderInterface`** — `is_dev_mode` → `true`
  (tests always run in dev mode so dev-only components register).
- **`WpAssetRegistry implements AssetRegistryInterface`** — in-process array
  (the test plugin doesn't enqueue real CSS/JS; it records what would be
  enqueued so tests can assert `enqueue_style` was called).
- **`WpComponentRefResolver implements ComponentRefResolverInterface`** —
  queries `wp_block` posts by `etch_component_html_key` meta (same lookup as
  the starter's `ComponentRegistry`, relocated here).

The mu-plugin's `etch-builders-test-bootstrap.php`:
1. Requires the Composer autoloader.
2. On `muplugins_loaded`, calls
   `Environment::configure(new WpStorage(), new WpMode(), new WpAssetRegistry(),
   new WpComponentRefResolver())`.

## Rendering infrastructure

**`RenderingTestCase`** (extends `WP_UnitTestCase`):

- `render_block_markup(string $markup): RenderResult` — serializes builder
  output to a `wp_post`, calls `render_block()` with output buffering,
  captures the HTML + the `<style id="etch-page-styles">` tag.
- `render_post(int $post_id): RenderResult` — renders an existing post (for
  dynamic-data tests where blocks reference `{this.title}` of the rendered
  post).
- `seed_post(array $overrides): int` / `seed_term(array $overrides): int` /
  `seed_user(array $overrides): int` — per-test fixture helpers.
- `tearDown()` — deletes all seeded posts/terms/users, `Environment::reset()`,
  `delete_option('etch_styles')`, `delete_option('etch_loops')`,
  `delete_option('etch_global_stylesheets')`. Each test starts clean.
- Assertion helpers: `assertRendersTag`, `assertRendersClass`,
  `assertStyleEmitted(RenderResult, selector, css)`,
  `assertStyleNotEmitted(RenderResult, selector)`.

**`RenderResult`** value object: `->html()`, `->style_tag()`,
`->raw_blocks()`.

## Test roster — 15 files, ~186 test methods

| File | Class covered | Methods + combos | Count |
|---|---|---|---|
| `ElementBlockRenderingTest` | `ElementBlock` | new, tag, class, attribute, attributes, content/child/children, style/styles, void_tag, to_block/to_string. Combos: void vs paired, class+style linkage, nested, dynamic attrs, BEM `&__`, `@media(--tablet)` | ~22 |
| `DynamicElementBlockRenderingTest` | `DynamicElementBlock` | same as Element + dynamic tag, dynamic class, dynamic attributes | ~12 |
| `TextBlockRenderingTest` | `TextBlock` | new, content (static + `{this.title}` + `.toUpperCase()`), to_block | ~8 |
| `RawHtmlBlockRenderingTest` | `RawHtmlBlock` | new, content (static + `{this.content}`), unsafe | ~6 |
| `SvgBlockRenderingTest` | `SvgBlock` | new, attribute (src, viewBox, fill), class, strip_colors | ~8 |
| `DynamicImageBlockRenderingTest` | `DynamicImageBlock` | new, media_id, use_srcset, maximum_size, dynamic mediaId, attachment lookup, placeholder fallback | ~10 |
| `ConditionBlockRenderingTest` | `ConditionBlock` | new, condition (object), condition_string, child, condition_operator (`===`, `>`, `isTruthy`, `||`), dynamic operand | ~14 |
| `LoopBlockRenderingTest` | `LoopBlock` | new, target, loop_id, item_id, index_id, loop_params, child. Combos: wp_query, json, nested, loop+condition, `slice()` modifier | ~16 |
| `ComponentBlockRenderingTest` | `ComponentBlock` | new, ref, ref_by_key, attribute, prop_string/number/boolean/array/class/expression/group/repeater, child/children (slots), prop_object. Render: class prop → classes + styles, slot fill | ~20 |
| `SlotContentBlockRenderingTest` | `SlotContentBlock` | new, name, child — inside a component | ~4 |
| `ComponentRenderingTest` | `Component` | new, key, title, description, add_style, add_blocks, prop_* defs, register_stylesheets, enqueue_style/script, dev_only, to_array/get_blocks. Render: definition → instance → output | ~18 |
| `PatternRenderingTest` | `Pattern` | new, key, add_blocks, add_style, register_stylesheets, to_array/get_blocks. Render: pattern instance → output | ~8 |
| `StyleRenderingTest` | `Style` | new, id, selector, css, type, collection, name, readonly, overwrite_on_register, add. Combos: class/id/element/attribute types, BEM, `@media`, `:hover`, `to-rem()`, mandatory-style emission, readonly, conflict resolution | ~20 |
| `StylesheetRenderingTest` | `Stylesheet` | new/register_references, register_custom_media, owner-key pruning, multi-fragment stacking, `@custom-media` macro resolution | ~10 |
| `LoopPresetRenderingTest` | `LoopPreset` | new, key, wp_query, wp_terms, wp_users, json, overwrite, register, register_internal. Render: preset → loop → items | ~10 |

**Total: ~186 test methods.**

## Rollout — 4 phases

Each phase ends with its own green gate.

### Phase A — wp-env scaffold
`.wp-env.json` mounts Etch 1.5.1 + theme 0.0.7 zips, the test mu-plugin,
Composer deps. Verify wp-env starts, Etch is active, the package autoloader
resolves, `Environment::configure(...)` runs. **Gate:** 1 green smoke test
(`render_block('<!-- wp:etch/element -->...')` returns non-empty HTML).

### Phase B — RenderingTestCase infrastructure
The base class, `RenderResult`, `seed_post/term/user`, assertion helpers,
per-test teardown. **Gate:** 1 infrastructure test green (a single
`ElementBlock` renders `<div class="test">Hello</div>` + linked style emits in
`<style id="etch-page-styles">`).

### Phase C — Element-family + style rendering tests
`ElementBlock`, `DynamicElementBlock`, `TextBlock`, `RawHtmlBlock`, `SvgBlock`,
`DynamicImageBlock`, `Style`, `Stylesheet`. The bulk of the rendering contract:
every method + BEM/`@media`/`:hover`/`to-rem()` combinations.
**Gate:** ~85 tests green.

### Phase D — Dynamic-data + component rendering tests
`ConditionBlock`, `LoopBlock`, `LoopPreset`, `Component`, `ComponentBlock`,
`Pattern`, `SlotContentBlock`. The complex flows: loop iteration with seeded
posts, condition evaluation, component slots + class props.
**Gate:** ~100 tests green; full suite (~186) passing.

## Explicit non-goals

- No ACF/JetEngine/MetaBox integration tests (third-party providers; the
  package's dynamic-data tests cover the core `this`/`user`/`site`/`term`
  sources).
- No performance benchmarking.
- No visual regression / Playwright / screenshot diffing. Pure PHP render-output
  assertions.
- No testing of the package's own pure-PHP unit tests (those live in the package
  repo). This repo tests **rendering** — the contract between the package's
  serialization and Etch's runtime.
- No starter swap. This repo produces the proven WP adapters that the starter
  *will* consume, but doesn't touch the starter.

## Success criteria

1. `composer test` (inside wp-env) passes ~186 render tests against the live
   Etch 1.5.1 + theme 0.0.7 runtime.
2. Every public method of every builder class in `honestlydesign/etch-builders`
   has at least one render test asserting its effect on rendered HTML or CSS.
3. Logical combinations covered: BEM `&__`/`&--`, `@media(--tablet)`, `:hover`,
   `to-rem()`, nested loops, condition operators, component slots, class props,
   dynamic data resolution.
4. The 4 WP adapters are proven against real rendering and ready for the
   starter's Phase 4.
5. GitHub Actions CI green: wp-env starts, Etch active, `composer test` passes.

## Risk + honest note

The biggest unknown is Etch's exact rendered output format for some edge cases
(how `CssProcessor` handles `&:hover` vs `&__elem`, how `ClassProperty` drops
non-`type=class` tokens). The first few tests in each file will likely surface
discrepancies between our model and Etch's actual behavior — those are the most
valuable tests because they catch real integration bugs. If a test reveals our
builder serializes something Etch mis-renders, that's a package bug to fix, not
a test to suppress.
