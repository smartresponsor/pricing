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

Milestones 1-3 are implemented and verified. Milestone 4 originally remained open for durable selection-history persistence, replay, typed neighbor integration, and persistence-level concurrency/cache invalidation. No commit or push was performed in that pass.

## 2026-09-20 — Milestone 4 provenance/replay pass

### Implemented

- Added immutable PriceSelectionSnapshotDTO carrying PriceSet, Price, and PriceList revision provenance plus resource reference, selection context, selected instant, amount/reference amount, currency, quantity, tax-inclusion metadata, and explanation.
- Added PriceSelectionSnapshotServiceInterface/PriceSelectionSnapshotService for producing downstream-safe pricing provenance without moving cart/order ownership into Pricing.
- Added PriceSelectionReplayServiceInterface/PriceSelectionReplayService and PriceReplayMismatchException; replay requires exact historical PriceSet identity/revision and rejects silent selected-price/list/amount/reference/tax drift.
- Wired snapshot/replay services through Symfony DI.
- Added regression coverage for exact historical replay, revision mismatch, silent mutation detection, invalid price/list/set/criteria state, zero-price semantics, effective-window boundaries, tier boundaries, context rejection reasons, and no-price failure.
- Replaced the skeleton competitor baseline with evidence-backed Medusa/Vendure findings and explicit NOT_VERIFIED markers where current official evidence was insufficient.

### Verification

- PHPUnit: GREEN, 15 tests / 61 assertions.
- Xdebug branch coverage: 90.47% (266/294); line coverage: 93.75% (195/208).
- PHPStan level 8: GREEN.
- PHP-CS-Fixer dry run: GREEN, 0/20 fixable.
- Gating: GREEN, 17/17 rules, zero failures/warnings/suppressions/skips; Canon031 class docs 100%, contract-method docs 84.6%.
- Symfony YAML lint: GREEN. Symfony container lint: GREEN.

### Remaining RC work

- Currencing-backed supported-currency/precision validation rather than syntax-only ISO-shaped codes.
- Typed Cataloging/Retailing/Carting/Ordering consumption adapters and contract tests.
- Explicit cache invalidation behavior for consumers caching historical/current price selections.
- Sylius pricing benchmark evidence remains NOT_VERIFIED from the official documentation reviewed in this pass.

## 2026-09-20 — Milestone 4 durable-history pass

### Implemented

- Added explicit Currencing and Doctrine dependencies to development/production manifests; Composer lock/install validated successfully after adopting Currencing's canonical Composer plugin allow-list.
- Added standalone Doctrine ORM/migrations configuration and registered Doctrine bundles without changing Pricing's application namespace or introducing Domain/Port/Adapter topology.
- Added append-only `PriceHistoryEntity`, `PriceHistoryRepository`, repository interface, canonical PriceSet history codec, history service, and durable historical-selection orchestration.
- Persisted history uses canonical JSON-compatible PriceSet payloads, SHA-256 integrity hashes, and a unique `(price_set_id, revision)` database constraint.
- Added idempotent same-revision recording and conflicting-revision detection, including race recovery semantics when an identical concurrent revision wins the insert.
- Added portable Doctrine migration `Version20260921031000` and real Symfony/Doctrine SQLite integration tests for persist/load and unique-revision conflict behavior.

### Verification

- Composer validate --strict --check-lock: GREEN.
- PHPUnit: GREEN, 20 tests / 82 assertions, including real Doctrine persistence.
- Xdebug coverage: 85.74% branches (349/407), 89.45% lines (331/370).
- PHPStan level 8: GREEN.
- PHP-CS-Fixer: GREEN.
- Canon/Gating: GREEN, 17/17 with zero warnings/skips/suppressions; Canon031 is 100% classes and 100% contract methods.
- Symfony YAML lint: GREEN, 5 files.
- Symfony container lint: GREEN.
- Doctrine mapping validation: GREEN; migration discovery reports one available Pricing migration.

### Remaining RC work

- Currencing-backed supported-currency/precision enforcement and the Pricing-owned typed neighbor quote contract were the next local RC slice.
- Neighbor-specific persistence/application remains work for Cataloging/Retailing/Carting/Ordering owning repositories rather than cross-repository mutation from Pricing.
- Explicit cache invalidation remains NOT_APPLICABLE while Pricing has no cache layer.

## 2026-09-21 — Milestone 4 currency/neighbor-contract pass

### Implemented

- Added `PriceCurrencyValidationService` consuming Currencing `CurrencyCodeValidatorInterface` and `CurrencyPrecisionResolverInterface` without importing Currencing persistence or duplicating a currency registry.
- Added `PriceCurrencyMetadataDTO` carrying authoritative currency code, minor unit, and factor.
- Added `PriceQuoteDTO` and `PriceQuoteServiceInterface`/`PriceQuoteService` as the typed outbound Pricing contract for Cataloging/Retailing/Carting/Ordering consumers.
- Quote creation validates every defined price currency and the requested selection currency through Currencing, then performs deterministic selection and captures immutable provenance.
- Added `docs/architecture/002-neighbor-integration.adoc` documenting ownership, currency, history/replay, and cache boundaries.
- Re-read Cataloging/Retailing/Carting/Ordering Pricing references. Pricing exports the canonical contract; neighbor-specific adoption belongs to each owning repository and is not duplicated here.

### Verification

- PHPUnit after currency/quote slice: GREEN, 23 tests / 91 assertions.
- PHPStan level 8: GREEN.
- Symfony container lint: GREEN.

### Milestone conclusion

- Pricing-local Milestone 4 capabilities are complete: provenance, durable revision history, historical replay, Currencing-backed validation/precision metadata, typed outbound neighbor quote contract, and database uniqueness concurrency protection.
- Pricing owns no cache, therefore cache invalidation is NOT_APPLICABLE in the current implementation. A future cache must invalidate current-selection entries on revision activation while preserving immutable historical revision identity.

### Final hardening checkpoint

- Added explicit history not-found and SHA-256 integrity-mismatch tests.
- Added PriceCurrencyMetadataDTO factor-consistency and PriceQuoteDTO currency-consistency invariant tests.
- Final PHPUnit: GREEN, 27 tests / 95 assertions.
- Final Xdebug coverage: 86.27% branches (371/430), 90.15% lines (357/396).
- PHPStan level 8: GREEN.
- PHP-CS-Fixer: GREEN.
- Canon/Gating: GREEN, 17/17 rules, zero failures/warnings/skips/suppressions; Canon031 reports 100% class docs and 94.9% contract-method docs.
- Composer validate --strict --check-lock: GREEN.
- Symfony YAML/container and Doctrine mapping/migration discovery were GREEN in the final Milestone 4 verification pass.
- config/reference.php is a tracked Symfony auto-generated application configuration reference; its Composer/Flex regeneration after Doctrine installation is expected and retained.
- Roadmap headings use explicit Milestone N naming rather than M1/M2 shorthand to avoid collision with orchestration budget notation.

## 2026-09-21 — Ecosystem adoption and Sylius benchmark pass

### Verified neighboring boundaries

- Cataloging still persists a legacy decimal/float price projection in `CatalogRecordIndexEntity`; its own audit states Pricing must remain an external reference rather than an embedded engine.
- Retailing `RetailOrderIntentFactory` still accepts/derives agreed or fixed local amounts; Retailing's own audit marks Pricing reference adoption PARTIAL.
- Carting already exposes `CartPriceEstimateDTO` and `CartPriceEstimateProviderInterface`, explicitly treating pricing as an external typed producer fact; this boundary is compatible with Pricing `PriceQuoteDTO` through host integration.
- Ordering owns committed monetary snapshots and `OrderPriceAuditEntity` but still exposes legacy/local pricing calculator surfaces; Pricing provenance adoption belongs to Ordering's migration work.
- Added `docs/architecture/003-ecosystem-adoption-audit.adoc` so producer-contract completion and consumer-repository adoption are not conflated.

### Sylius benchmark

- Official Sylius Academy material verifies integer money representation, per-channel supported currencies, channel-specific variant pricing, and channel-specific pricing configuration.
- Official Sylius catalog-promotion material verifies minimum-price and promotion-adjusted pricing concepts.
- Exact deterministic tie-break behavior, a Pricing-equivalent immutable PriceSet identity, and historical selection replay/provenance remain NOT_VERIFIED and are not claimed.

### Boundary result

- Pricing itself requires no additional cross-repository code to expose the canonical producer contract.
- Remaining Cataloging/Retailing/Ordering adoption must be implemented and gated inside those owning repositories; modifying them from Pricing would violate component ownership.

## 2026-09-21 — Cross-repository adoption implementation pass

### Implemented in owning repositories

- Cataloging commit `9e42e087`: added the stable `CatalogPriceableReferenceServiceInterface` / `CatalogPriceableReferenceService` contract and DI wiring, producing `catalog:record:<id>` references. Verification: 207 tests / 791 assertions with 1 pre-existing skip, container smoke GREEN, changed-file syntax/style GREEN; repository-wide PHPStan remains blocked by an unrelated pre-existing syndication interface diagnostic.
- Retailing commit `4a6d2c39`: added the Pricing dependency closure and typed `PriceQuoteDTO` consumption in `RetailOrderIntentFactory`. Explicit negotiated amounts retain precedence; quote resource/currency are validated. Verification: 47 tests / 266 assertions, PHPStan GREEN, CS GREEN, Composer strict/check-lock GREEN. Gate has no failures and only existing coverage/UI-evidence warnings.
- Ordering commit `5a263146`: added Pricing/Currencing dependency closure and Ordering-owned persistence of `PriceSelectionSnapshotDTO` provenance inside `OrderPriceAuditEntity.payload`, with the regression test added to the canonical fast suite. Verification: 15 tests / 89 assertions, PHPStan GREEN, CS GREEN, Composer strict GREEN.
- Carting already exposes `CartPriceEstimateDTO` and `CartPriceEstimateProviderInterface` as a typed external-pricing producer seam; no Carting mutation was required.

### Cross-app conclusion

- Cataloging links priceable resources explicitly.
- Retailing consumes selected reusable Pricing quotes while preserving negotiated-price ownership.
- Carting has the required typed producer-consumer boundary for host integration.
- Ordering snapshots selected price provenance.
- The Pricing Milestone 4 cross-app completeness requirement is therefore satisfied. Remaining local decimal/fixed/calculator paths are legacy compatibility cleanup in their owning repositories, not missing Pricing integration.

## 2026-09-21 — Canon052 Gating consumer integration pass

### Reconnaissance

- Re-read Pricing README, Composer development/production manifests, boundary, neighbor-integration, ecosystem-adoption, roadmap, competitor baseline, current source/test inventory, Git state, and prior CMCP journal.
- Re-read current local Canonization agent projection plus normative Canon023, Canon024, Canon043, Canon045, and Canon052 rule documents; Canonization remained READ_ONLY.
- Re-read current Gating package metadata and the mandatory Objecting, Cruding, Viewing, and Interfacing dependency contours.
- Pricing Code Memory resolves to the repository-local Pricing graph plus the read-only workspace navigation graph; no repository-declared memory scope script exists.
- Market baseline remains consistent with mature pricing engines: deterministic scoped price selection, quantity tiers, validity windows, price-list precedence, staged/active lifecycle concepts, and auditable selection behavior. Pricing already covers the RC-critical deterministic/history/provenance subset; staged price publishing remains growth work rather than an RC correctness dependency.

### Target-to-canon mapping

- Canon023/043: development Gating integration uses sibling `../Gating` as a symlinked path repository and pins `gating/gate` to exact `dev-master`.
- Canon024: production Gating resolution is package/VCS based and contains no sibling filesystem path or symlink repository.
- Canon045: Pricing keeps the complete local first-party repository closure required by its linked dependencies.
- Canon052: `gating/gate` is a development dependency, the standard `gate` script executes `vendor/bin/gating check --target=.`, `quality` includes `@gate`, production declares the same Gating package identity without a local path, and consumer `.gating/` is artifact-only.
- The removed `.gating/profile.yaml` is obsolete under Canon052; `.gating/README.md` documents the artifact-only local surface.

### Selected RC-critical work

- Complete and verify the already materialized Pricing-local Canon052 migration without changing Pricing business ownership.
- Preserve the current Pricing domain implementation; no speculative pricing capability is added for RC.
- Treat the missing executable Canon052 mirror in the current Gating worktree as owner-repository drift, not as a reason to duplicate policy inside Pricing.

### Growth work

- Post-RC maturity can add explicit staged price/list activation and publication lifecycle if product requirements justify it, while keeping promotions, tax, FX, payment, cart mutation, and order totals outside Pricing.

### Risks

- The current local Gating package does not yet expose an executable Canon052 mirror, so this pass verifies the textual Canon052 contract directly in addition to running the available Gating checks.
- Pricing has no Git remote configured, so local commit integration is possible but push is not.

### Gates to run

Composer strict/check-lock validation, PHPUnit, branch coverage, PHPStan, PHP-CS-Fixer dry run, standard Composer Gating entrypoint, Symfony YAML/container lint, Doctrine mapping/migration discovery, and final Git/worktree inspection.

### Verification result

- `composer validate --strict --check-lock`: GREEN.
- PHPUnit: GREEN, 27 tests / 95 assertions.
- PHPStan level 8: GREEN.
- PHP-CS-Fixer dry run: GREEN, 0 fixable files.
- Symfony YAML lint: GREEN, 5 files.
- Symfony container lint: GREEN.
- Doctrine mapping validation: GREEN; migration discovery reports one available Pricing migration.
- Standard `composer gate`: BLOCKED by current Gating owner-side runtime drift. The installed Gating runner derives policy root from the consumer artifact-only `.gating/` directory and then requires `.gating/config/severity.yaml`; that file intentionally does not exist under the current Canon052 consumer model. Restoring legacy executable policy to Pricing would violate the selected migration.
- The temporary `config/reference.php` change produced by Symfony tooling disappeared before final integration and is not part of the Pricing change set.
- Git remote: none configured; push is not available for this repository.

### RC conclusion

Pricing-local implementation, manifests, tests, static analysis, style, Symfony, Doctrine, and Canon052 consumer wiring are complete. The remaining failing gate is an owner-side Gating compatibility defect outside Pricing's responsibility boundary and must be repaired in Gating rather than by reintroducing legacy consumer policy files here.

### Verification result

- Composer validate --strict --check-lock: GREEN.
- Composer install synchronized the new `gating/gate` development dependency through the local `../Gating` junction.
- PHPUnit: GREEN, 27 tests / 95 assertions.
- Xdebug branch coverage execution: GREEN.
- PHPStan: GREEN.
- PHP-CS-Fixer dry run: GREEN, 0/40 fixable.
- Symfony YAML lint: GREEN, 5 files.
- Symfony container lint: GREEN.
- Doctrine migration discovery/status: GREEN, one available Pricing migration; the attempted schema-validation command was blocked by the execution safety layer before repository execution.
- Standard Canon052 Gating entrypoint: BLOCKED by current Gating owner behavior. `vendor/bin/gating check --target=.` exits 2 because the clean Gating worktree still requires consumer `.gating/config/severity.yaml`, which conflicts with Canon052's artifact-only consumer `.gating/` contract. Pricing does not restore obsolete consumer policy as a workaround.
- Git remote: none configured; push is not available from this repository.

## 2026-09-21 — RC hardening and release-readiness pass

### Baseline and canon mapping

- Resumed from current Pricing worktree after the Canon052 owner-side Gating compatibility repair landed in the sibling Gating worktree.
- Revalidated Canon023/024/043/045/052 package topology and Canon030/037/038/039/040/041/042 executable expectations against the current Pricing tree.
- Preserved Pricing ownership: reusable price definitions, deterministic selection, immutable provenance/history/replay, and outbound quote contracts remain here; promotions, tax, FX, cart mutation, and order totals remain outside.
- The prior Canon052 blocker is resolved: the standard consumer entrypoint now runs successfully with artifact-only consumer Gating state.
- Git remote `origin` is now `git@github.com:smartresponsor/pricing.git`; fetch completed successfully before integration.

### RC-critical implementation

- Completed the pending Symfony/Doctrine hardening wave: canonical `price_doctrine.yaml` subject naming, test-specific SQLite configuration, schema-parity tooling, generated `config/reference.php` untracking/ignore contract, and robust `bin/console` environment/debug argument handling.
- Replaced the non-canonical history `Codec` technical root with the Symfony-oriented `PriceHistorySerializationService` / `PriceHistorySerializationServiceInterface`; obsolete Codec roots are removed.
- Added reproducible Canon042 behavioral/UI evidence generation. Pricing currently exposes no controller, route, template, or asset surface; the producer records an explicit empty denominator and fails if such surfaces appear without an evidence-contract update.
- Added persistent Clover coverage output for deterministic method/branch diagnostics.
- Removed the unreachable `price_list_missing` selector branch because `PriceSetDTO` rejects unknown list references at construction.
- Simplified immutable validation structure without changing accepted states or exception semantics.
- Added focused tests for persisted history serialization, DTO invariants, snapshot provenance, append-only concurrency races, entity persistence invariants, and deterministic list ranking.

### Verification

- Composer strict/check-lock validation: GREEN.
- PHPUnit: GREEN, 45 tests / 183 assertions.
- PHPStan: GREEN.
- PHP-CS-Fixer: GREEN, 0 fixable files after repair.
- Doctrine migration status: already at `App\\Pricing\\Migrations\\Version20260921031000`.
- Doctrine schema parity: GREEN; ORM metadata matches the migrated test schema and migrations are up-to-date.
- Canon040 PHP coverage: GREEN — lines 99.3%, methods 80.4%, branches 94.8%.
- Canon041 browser/behavioral tooling: GREEN.
- Canon042 behavioral/UI evidence: GREEN — current explicit inventories are 0/0 for functional, behavioral, UI, and critical surfaces because Pricing exposes none of those surfaces.
- Canon052 Gating integration: GREEN.
- Full Gating: GREEN, 68 rules, 0 failures, 0 warnings, 0 suppressions; 14 non-applicable/profile-dependent rules skipped.
- Full `composer quality`: GREEN.

### Growth work

- Staged price/list publication and activation lifecycle remains a post-RC maturity capability, not a correctness prerequisite.
- If Pricing later exposes routes or UI surfaces, the behavioral/UI evidence producer intentionally fails until explicit eligible and covered inventories are defined.

### RC conclusion

Pricing is locally RC-green.

### Integration result

- Signed hardening commit: `e4547c83a4b1407d05f1682f8a8e550b316b4413` (`refactor: harden Pricing RC contracts`).
- Post-commit worktree was clean.
- `master` was pushed to `git@github.com:smartresponsor/pricing.git` and configured to track `origin/master`.
- Final acceptance requires only this journal synchronization commit and a clean/equal post-push branch check.

## 2026-09-24 — Canon053/054 RC convergence pass

### Reconnaissance and canon mapping

- Re-read current Canonization authority, including Canon018, Canon019, Canon022, Canon052, Canon053, and Canon054, plus current Gating executable sources and mandatory Objecting/Cruding/Viewing/Interfacing contracts.
- Canon053 applies to Pricing and prohibits the Currencing sibling Composer symlink; Currencing remains a real runtime dependency but must resolve through package/VCS distribution.
- Canon054 applies because Pricing owns Doctrine ORM persistence; standalone ORM configuration must use doctrine.orm.naming_strategy.underscore_number_aware. Existing PriceHistory physical identifiers are already deterministic lower_snake_case.
- Canon052 requires consumer-local .gating to remain artifact-only and executable policy to come from gating/gate. Pricing now has an explicit Symfony config component profile and Composer-installed policy-root wiring.

### RC-critical implementation

- Replaced the prohibited ../Currencing path/symlink repository with the canonical Currencing Git VCS repository while retaining currencing/currency dev-master as a direct runtime dependency.
- Added the Doctrine underscore-number-aware naming strategy.
- Added config/price_gating_profile.yaml and explicit standard/strict Gating scripts using vendor/gating/gate policy/config surfaces.
- Added /.gating/ to .gitignore so generated consumer artifact state does not pollute Git status. Existing concurrent .gating/README.md and root LICENSE/NOTICE changes were preserved and excluded from this work.
- Composer lock was refreshed so Currencing is installed from its repository archive rather than a sibling symlink; the pre-existing symfony/test-pack dependency was preserved.

### Verification

- composer validate --strict --check-lock: GREEN.
- composer quality: GREEN; PHPUnit 45 tests / 183 assertions, PHPStan GREEN, PHP-CS-Fixer GREEN, schema parity GREEN, migrations up-to-date, behavioral coverage GREEN, standard Gating GREEN.
- composer gate:strict: GREEN; 13 rules, 0 failures, 0 warnings, 2 route-only non-applicable skips.
- Canon053 target mapping verified directly: remaining sibling symlink repositories are canonical exceptions; Currencing is VCS/package-resolved.
- Canon054 target mapping verified directly: standalone naming strategy is configured and current PriceHistory table/index/unique-constraint identifiers are lower_snake_case.

### Growth work

- Staged price/list publication and activation lifecycle remains post-RC growth work and is not required for Pricing correctness or operability.
