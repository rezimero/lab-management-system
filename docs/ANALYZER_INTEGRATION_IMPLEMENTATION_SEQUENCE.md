# Rezhel Analyzer Integration — Implementation Sequence

## Delivery strategy

To recover schedule without compromising clinical integrity, Analyzer Integration should be implemented as one feature branch with three controlled internal phases rather than a chain of tiny feature branches.

Target branch:

`feature/analyzer-integration`

Base:

`develop` at the post-documentation-closeout merge commit.

## Phase A — Backend domain foundation

Deliver:

- tenant migration for analyzer tables;
- landlord migration to enable analyzer entitlement only after final acceptance, kept separate until the last phase;
- analyzer models;
- immutable raw-message model behavior;
- analyzer permission catalogue entries;
- analyzer authorization concern;
- instrument administration requests/controllers/resources/services;
- test mapping and parameter mapping services;
- credential generation/rotation with hash-only persistence;
- human API routes;
- focused backend tests.

Do not enable commercial-beta entitlement yet.

## Phase B — Canonical ingestion and validation pipeline

Deliver:

- machine-auth middleware;
- canonical analyzer envelope validator;
- ingest endpoint;
- message idempotency;
- tenant-aware processing job;
- analyzer message processor;
- accession/specimen/order-item matcher;
- mapping resolver;
- normalized row builder;
- validation issue catalogue;
- result conflict guard;
- automatic analyzer-origin draft creation;
- analyzer provenance relationship/resource;
- import review/reject/reprocess services;
- import audit events;
- statistics/health query services;
- critical-result approval integration characterization test.

The phase is complete only when a duplicate canonical message cannot create a duplicate `TestResult`.

## Phase C — Frontend, simulator, acceptance and entitlement activation

Deliver:

- analyzer frontend feature;
- entitlement route guard;
- role-aware navigation;
- instrument directory/detail;
- test and parameter mapping UI;
- validation queue;
- import detail/audit timeline;
- credential rotation UI;
- canonical simulator/test harness;
- frontend unit/component tests;
- end-to-end browser smoke;
- full backend/frontend regression suite;
- TypeScript and production build;
- development-tenant migration finalization;
- guarded commercial-beta entitlement enablement migration;
- PR/develop CI;
- handover and evidence update.

## First implementation order

1. Create feature branch from the exact verified `develop` head.
2. Add architecture docs/ADR in the same feature branch.
3. Add tenant schema and models.
4. Add permissions and entitlement guards.
5. Add instrument and mapping administration APIs.
6. Add focused foundation tests.
7. Add machine authentication and canonical ingestion.
8. Add raw evidence/idempotency.
9. Add matching/validation/import processing.
10. Add review/reprocess APIs.
11. Add result provenance integration.
12. Add analyzer frontend.
13. Add simulator and acceptance tests.
14. Run focused validation.
15. Run complete backend/frontend validation.
16. Apply migration only to the designated development tenant after tests pass.
17. Run browser smoke.
18. Enable commercial-beta entitlement only after the feature is functionally validated.
19. Commit/push/PR.
20. Require PR CI and post-merge `develop` CI before module closure.

## Migration policy

The analyzer tenant migration must be additive and reversible.

Do not modify or drop existing result/order/specimen columns for Analyzer Integration.

Foreign keys to clinical evidence should use `restrictOnDelete` or nullable provenance links where appropriate; analyzer history must not disappear because a mapping or operator is removed.

The entitlement-enablement migration must be separate from the tenant-domain migration so the feature can remain off while implementation is under validation.

## Validation policy

Focused tests come first, then the complete suite.

Database-finalization scripts must explicitly name:

- landlord DB;
- target development tenant;
- target tenant DB;
- expected pending migration;
- expected post-migration state.

Automated test databases remain isolated from the designated development tenant.

## Physical analyzer adapter follow-up

The generic product module does not require the physical device make/model to begin.

Before production connection to the first analyzer, collect:

- manufacturer;
- model;
- firmware/version;
- interface option licensed on the analyzer;
- transport: serial/TCP/file/HTTP;
- protocol/version;
- host/port or serial settings;
- sample identifier field;
- test code list;
- parameter code list;
- result flag conventions;
- decimal/units behavior;
- acknowledgement/retry rules;
- vendor interface manual or simulator.

That information selects the first gateway adapter without changing the central Analyzer Integration domain.
