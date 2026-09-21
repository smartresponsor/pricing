# CMCP Change Journal

## Canonization read

Read current local Canonization `AGENTS.md`, `README.md`, `MANIFEST.json`, and Canon000, 007, 009, 018, 020, 022, 023, 024, 025, 026, 029, 031, 032, 033, 034, 039, and 043 normative rules. Read the corresponding executable rules in current local Gating.

## Target-to-canon mapping

- Composer identity: `pricing/price`.
- Namespace root: `App\\Pricing\\`.
- Subject vocabulary: `Price*`.
- Dual runtime: standalone Symfony application plus reusable `PricingBundle`.
- Mandatory standalone dependencies: Objecting, Cruding, Collectioning, Tabling, Viewing, Interfacing, EasyAdmin.
- Development first-party dependencies: sibling path repositories, symlinked, exact `dev-master`.
- Production manifest: packaged dependencies only; no sibling path repositories.
- Generic CRUD remains owned by Cruding.

## Baseline repository state

The target repository did not exist before this materialization. Collision probes for Pricing, Price, and Prices returned no canonical repository-file evidence, and workspace creation succeeded without overwrite.

## Created

Canonical Composer manifests and lockfile, Symfony bootstrap/bundle surfaces, component-owned `.gating/profile.yaml`, quality tooling, one non-domain smoke test, repository ignore baseline, product boundary/roadmap/benchmark documentation, Git repository metadata on `master`, and this execution journal.

## Risks

- Composer installation depends on local sibling package consistency and external package resolution.
- This wave deliberately does not define Pricing product entities or services.

## Gates to run

Composer validation/install, PHP lint, PHPUnit, coverage contract where the local coverage driver permits it, PHPStan, PHP-CS-Fixer dry run, Gating, and Symfony standalone boot/container checks.

## Validation result

All requested component-local gates passed after repair: Composer validation/install, explicit PHP syntax lint, PHPUnit, Xdebug branch coverage with persistent summary, PHPStan, PHP-CS-Fixer dry-run, selected Gating rules (17/17; zero failures/warnings/skips), Symfony boot, YAML lint, and container lint.

## 2026-09-20 — Product capability implementation pass

### Reconnaissance

- Re-read current local Canonization `AGENTS.md`, `README.md`, `MANIFEST.json`, and normative Canon000, 007, 009, 018, 020, 022, 023, 024, 025, 026, 029, 031, 032, 033, 034, 039, and 043 rule documents; Canonization remained READ_ONLY.
- Re-read current local Gating contract and the mandatory Objecting, Cruding, Viewing, and Interfacing dependency contours.
- Inspected Pricing README, Composer manifests, config, source, tests, architecture/product docs, audit register, and Git baseline.
- Read directly relevant pricing boundaries/contracts from Cataloging, Retailing, Carting, Ordering, Currencing, Exchanging, Taxating, Promoting, and Stocking.
- Market reconnaissance covered current Medusa pricing concepts/rules/calculation/price lists and Vendure price-rule capabilities; RC-critical correctness was separated from growth work.

### Selected RC-critical work

The live repository confirmed M1 as the earliest incomplete milestone and contained no Pricing business implementation. The coherent vertical slice was extended through M3 because the same deterministic selection contract safely covers price identity, price lists, context, effective windows, quantity tiers, reference price metadata, tax-inclusion metadata, and explanation without crossing into promotion, FX, tax calculation, payment, or order totals.

### Canon mapping

- Canon000/018: new owned PHP types use the `Price*` subject vocabulary under `App\\Pricing\\`.
- Canon007/020: literal PSR-4 paths use DTO, Service, and ServiceInterface technical roots; no Domain/Port/Adapter/Adaptor/Common/Core/Support/Utility tree was introduced.
- Canon009: no Host implementation dependency was introduced.
- Canon022-026/032-034/043: existing dual-runtime and package wiring remains unchanged.
- Canon031/039: new contract-significant behavior is documented and covered by PHPUnit.

### Files and behavior

- Added immutable PriceDefinitionDTO, PriceListDTO, PriceSetDTO, PriceSelectionCriteriaDTO, and PriceSelectionResultDTO contracts.
- Added PriceSelectionServiceInterface and deterministic PriceSelectionService.
- Added stable rejection reasons and ranking provenance, including a final price-id tie-break for overlapping candidates.
- Added PHPUnit coverage for contextual/tier selection, mismatch evidence, overlap replayability, and invalid quantity tiers.
- Wired PriceSelectionService through Symfony DI.
- Updated the Pricing boundary and PRODUCT_CAPABILITY_AUDIT.adoc to the implemented M1-M3 state.

### Risks and growth work

- Durable history persistence and historical replay storage remain M4.
- Cataloging/Retailing/Carting/Ordering typed integration adapters remain M4.
- Currencing remains responsible for supported-currency/precision validation and Exchanging remains the sole FX owner.
- Persistence-level effective-window concurrency and cache invalidation remain M4 hardening; selection itself is deterministic under overlapping in-memory definitions.

### Gates to run

Composer validation, PHP syntax lint where exposed by Console MCP, PHPUnit, branch coverage, PHPStan, PHP-CS-Fixer dry run, Gating, Symfony YAML/container checks, and final workspace inspection.

### Gate result

- Composer validate --strict: GREEN.
- PHP syntax lint on all changed/untracked PHP files: GREEN.
- PHPUnit: GREEN, 5 tests / 14 assertions.
- Xdebug branch coverage execution with persistent summary: GREEN.
- PHPStan level 8: initial nullsafe.neverNull findings repaired; final run GREEN.
- PHP-CS-Fixer: initial format/line-ending findings repaired with repository-owned fixer; final dry-run GREEN.
- Gating: GREEN, 17/17 rules with zero failures, warnings, suppressions, or skips; Canon031 reports 100% class and 75% contract-method documentation coverage.
- Symfony standalone boot: GREEN (Symfony 8.1.7 / PHP 8.4.13). YAML lint: GREEN. Container lint: GREEN.
- Managed PHP web runtime probe found no public/ directory, so a browser server is not applicable and none was started or restarted.
- No UI/navigation/form/browser behavior changed, so Panther/Playwright screenshots and visual evidence are not applicable to this pass.

### Material checkpoint

M1-M3 are implemented and verified. M4 remains intentionally open: durable selection-history persistence, historical replay storage, typed Cataloging/Retailing/Carting/Ordering integration adapters, and persistence-level effective-window concurrency/cache invalidation. No commit or push was performed.
