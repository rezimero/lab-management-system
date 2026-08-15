# AI Engineer Brief

## Role

You are taking over as lead architect and full-stack developer for a Laravel multi-tenant Laboratory Information Management System.

Your responsibility is to preserve the current architecture, complete the active milestone and avoid introducing unnecessary redesign.

---

## 0. Authoritative Post-Notifications Baseline — 15 August 2026

This section supersedes older "current", "next milestone", branch and validation wording elsewhere in this document where they conflict. Older milestone sections are retained as chronology only.

Repository state:

    branch: develop
    HEAD: f2c054060a9e964324b4564d0cd3a97e573bf92e
    pull request: #23
    feature commit: c93503fc0ffdb5460dd5914f9a2fac1299f462d5
    merge commit: f2c054060a9e964324b4564d0cd3a97e573bf92e
    working tree: clean
    active product feature branch: none

Materially completed product milestones before the current checkpoint include report amendment/supersession, multi-branch/collection-centre foundations, subscription/entitlement and commercial foundations, inventory/reagents, Referral Laboratory Management, Patient and Clinician Portals, and Notifications/Critical-Result Escalation.

Completed immediately before this documentation closeout:

- Patient and Clinician Portals are complete and merged through PR #22.
- Notifications and Critical-Result Escalation is complete and merged through PR #23.
- The Notifications development-tenant migration is already applied and verified; do not rerun it.
- Notifications source-apply/repair/lifecycle packages are historical evidence and must not be reapplied to merged `develop`.
- Notifications PR Backend and Frontend CI passed.
- Post-merge `develop` CI passed.
- No production deployment was performed.

Latest complete validation evidence for the Notifications milestone:

    focused Notifications backend:
        7 tests passed
        178 assertions

    complete backend:
        255 tests passed
        6,655 assertions

    complete frontend:
        44 test files passed
        160 tests passed

    TypeScript:
        passed

    production Vite build:
        passed

    essential browser smoke:
        N01-N03 passed
        3 passed / 0 failed

The long elapsed durations observed during validation runs that crossed laptop hibernation are wall-clock artifacts and must not be interpreted as application-performance regressions without a fresh performance-specific measurement.

Next product milestone:

    Analyzer Integration

Analyzer Integration must begin with a read-only architecture assessment from the current merged `develop` branch. The architecture checkpoint must freeze instrument ownership, analyzer/test mapping, idempotent ingestion, result provenance, validation-queue behavior, error/retry handling, tenant isolation, authorization, audit evidence, and integration with the existing result/critical-notification workflows before implementation begins.

Remaining mandatory product roadmap after Analyzer Integration:

1. External Laboratory Interoperability — HL7, ASTM, FHIR, external laboratory APIs and automated electronic result exchange.
2. Courier and Specimen Dispatch Management — manifests, grouped shipments, courier details, barcode dispatch tracking, chain of custody, aliquot and specimen-volume tracking.
3. Referral Laboratory Financial Settlement — supplier invoices, accounts payable, payments, reconciliation and referral-laboratory balances.
4. External Referral Laboratory Portal — external-laboratory login, acknowledgement, direct result submission, external accounts and permissions.
5. Insurance and Claims — insurer claim submission, claim status, reconciliation and payer integrations.
6. Remaining advanced operational/clinical/commercial analytics required by the commercial roadmap.
7. Deployment, monitoring, backup/restore, disaster recovery and production hardening.

Notifications infrastructure is complete, but the broader product requirements still include referral-overdue alerts, approval notifications and additional operational notifications. Those use cases must extend the existing notification platform; they must not create a second notification subsystem.

Known non-blocking cleanup carried forward:

- the historical `feature/referral-laboratory-management` branch still appears in local/remote refs after its merged milestone; it is not the active product branch and may be removed only after a guarded merged-ancestor check;
- clinician portal tenant-context lookup can emit expected `404` console noise while a partial tenant domain is being typed;
- `NotificationList` has an invalid HTML nesting warning observed in frontend tests;
- SMS and WhatsApp remain unavailable until reviewed providers, credentials, receipt semantics and webhook verification are approved.

Planning status:

- the original 2026-08-08 commercial-beta target is historical and was not achieved;
- the project is now in launch-recovery mode;
- the working project-completion estimate after PR #23 is approximately 68 percent and is a planning estimate, not a code-derived metric;
- schedule recovery must not bypass tenant-isolation, clinical-integrity, migration, CI, backup/restore or deployment quality gates.

---
## Required Reading Order

Before proposing changes, read:

1. `docs/PROJECT_HANDOVER.md`
2. `docs/ARCHITECTURE_DECISIONS.md`
3. `docs/CURRENT_STATE.md`
4. `docs/TESTING_AND_OPERATIONS.md`
5. relevant current source files
6. relevant existing tests

Do not begin implementation from generic Laravel assumptions.

---

## Source of Truth

Use this priority when information conflicts:

1. passing automated tests
2. current source code
3. architecture decisions
4. current-state document
5. project handover document
6. old conversation history

Chat history is context, not project storage.

---

## Non-Negotiable Architecture

Preserve:

- Laravel database-per-tenant design
- landlord database for tenant metadata, tenant profiles, media metadata and Sanctum tokens
- `X-Tenant-Domain` tenant resolution
- `SetTenantContext:required` before Sanctum
- tenant-bound Sanctum tokens
- UUID public route binding
- order-item-driven results
- one result per order item
- result state machine
- approved-result order-completion guard
- immutable laboratory-report snapshots
- immutable report signatory and branding snapshots
- immutable report-owned logo and director-signature media copies
- server-generated PDFs rendered only from the issued report snapshot
- local report-image embedding with path, MIME and hash validation
- remote-resource, JavaScript and PHP execution disabled in PDF rendering
- tenant-owned laboratory identity and brand colours
- administrator-only tenant-profile management
- secure tenant-profile media validation and physical cleanup
- tenant isolation
- transactional workflow operations
- isolated MySQL tenant tests

Do not replace these without an explicit architectural decision.

Rezhel may provide starter or fallback colours, but those defaults do not represent a subscribing laboratory. The laboratory owns its profile, logo, signature and brand colours.

Historical reports must not read the current live profile to fill missing snapshot data. Reports issued before immutable report-media copying may omit logo or signature images in server-generated PDFs.

---

## Current Result Workflow

    draft
        → submitted
        → approved

    submitted
        → rejected

    rejected
        → edited
        → draft

Approval completes the associated order item.

An order completes only when all active items have approved results.

---

## Tenant Authorization

The active authenticated tenant user model is:

    App\Models\Tenant\User

Tenant permissions are evaluated through:

    App\Support\Tenant\TenantUserPermissionCatalog

Result workflow capabilities remain role-specific.

Laboratory-profile management uses the explicit profile-management capability:

    MANAGE_TENANT_PROFILE

Only authorized tenant administrators and platform superadministrators may manage the laboratory profile and its media.

Do not assume Spatie permission calls work on the active tenant user model.

---

## Development Rules

### Inspect before implementing

Before changing a feature, inspect:

- migrations
- schema
- models
- relationships
- route binding
- middleware
- authentication
- authorization
- services
- resources
- existing tests
- tenant test helpers

### Freeze the design

Before writing implementation files, determine:

- API contract
- state transitions
- database constraints
- authorization
- transaction boundaries
- snapshot boundaries
- media validation and cleanup rules
- required tests

Avoid building partial implementations that require repeated file replacement.

### Complete files

When providing a changed file, provide the complete copy-and-paste-ready file.

Do not provide partial patches unless explicitly requested.

### Migration commands

Before creating a migration, provide the exact PowerShell command:

    php artisan make:migration ...

Then provide the complete migration file.

### File size

Preferred targets:

    under 200 lines:
        ideal

    200–300 lines:
        acceptable when cohesive

    over 300 lines:
        review before expanding

    over 600 lines:
        urgent decomposition

Do not refactor merely to satisfy a line count when the current milestone does not benefit.

### Encoding

Use:

    UTF-8 without BOM

Run:

    git diff --check

after each batch.

### Upload contracts

Profile media uploads use multipart form data with the field:

    file

The frontend API client must allow the browser or Axios to set the multipart boundary. Do not force an application-wide JSON `Content-Type` header onto `FormData` requests.

Allowed tenant-profile image formats are:

- JPG
- JPEG
- PNG
- WEBP

Maximum file size:

    2 MB

SVG is not accepted for tenant-profile uploads.

---

## Testing Rules

During development:

1. syntax-check changed PHP files
2. run `git diff --check`
3. run focused tests
4. diagnose the exact root cause of failures
5. modify only files tied to the demonstrated defect
6. run affected regression tests
7. run the full suite at milestone completion
8. run TypeScript validation and the production build for frontend milestones
9. perform browser and print-preview smoke checks when user-visible report rendering changes

Never run automated tests against:

    lab_saas_landlord

The required automated test landlord database is:

    lab_saas_landlord_test

---

## Git Rules

Current workflow:

- one milestone branch may include several related features
- local commits are safety checkpoints
- focused tests run during implementation
- full suite runs before milestone completion
- one GitHub pull request should normally represent one substantial milestone
- normal merge commits are used for milestone pull requests
- documentation must be synchronized before the next product feature begins

Do not use broad staging commands such as:

    git add .

Stage exact reviewed paths only.

Do not commit failing code.

Do not push secrets.

Do not expose bearer tokens in messages, logs or documentation.

---

## Scope Rules

Completed and validated product milestones include:

1. backend order-item-driven result workflow
2. backend immutable laboratory-report generation and retrieval
3. Vite and TypeScript frontend foundation
4. frontend result and immutable-report integration
5. secure tenant laboratory-profile management
6. tenant logo and director-signature uploads
7. immutable report signatory details
8. tenant-owned report brand colours
9. printable browser report branding
10. immutable report-owned media preservation
11. server-generated laboratory-report PDF export
12. authenticated frontend PDF download
13. controlled laboratory-report amendment and supersession
14. multi-branch and collection-centre foundation
15. SaaS plans, subscriptions and feature entitlements
16. pricing, invoices, payments, receipts and corporate-account billing foundation
17. inventory and reagent management
18. referral laboratory management and external-result integration
19. patient and clinician portals
20. notifications and critical-result escalation

The complete Rezhel LIMS product includes all of the following core areas:

- multi-tenant platform administration
- clinical laboratory operations
- report amendment and supersession
- multi-branch and collection-centre operations
- SaaS plans, subscriptions and feature entitlements
- test pricing, quotations, invoices, payments and receipts
- corporate billing
- insurance and claims, including payer integrations
- barcode labels and specimen chain of custody
- reagents, consumables and inventory
- referral laboratories and send-out testing
- courier and specimen dispatch management
- referral laboratory financial settlement
- external referral laboratory portal
- notifications and critical-result escalation
- patient and clinician portals
- analyzer and instrument integration
- HL7, ASTM, FHIR and external health-system interoperability
- operational, clinical and commercial analytics
- deployment, monitoring, backup, recovery and production support

Commercial and integration modules are mandatory core product features. They must never be described as optional enhancements.

Current delivery state and remaining order:

Completed before the current checkpoint:

1. core roadmap, documentation and CI baseline
2. laboratory-report amendment and supersession
3. multi-branch and collection-centre foundation
4. SaaS plans, subscriptions and feature entitlements
5. pricing, invoices, payments, receipts and corporate-account billing foundation
6. reagents, consumables and inventory
7. referral laboratories and send-out testing
8. patient and clinician portals
9. notifications and critical-result escalation

Remaining order governed by ADR-033:

10. Analyzer Integration
11. External Laboratory Interoperability
12. Courier and Specimen Dispatch Management
13. Referral Laboratory Financial Settlement
14. External Referral Laboratory Portal
15. Insurance and Claims
16. remaining advanced analytics
17. deployment and production hardening

Residual referral-overdue, approval and operational notification use cases extend the existing Notifications platform; they are not a second notification module.

This dependency order may be changed only through an explicit architecture decision.

The laboratory-profile and report-branding implementation remains intentionally limited to:

- the laboratory-profile editor
- report identity
- report signatory presentation
- report and print styling
- immutable server-generated report PDFs
- authenticated PDF download

It does not introduce:

- global tenant application theming
- sidebar or dashboard theming
- tenant custom CSS
- custom fonts
- a report-template builder
- replacement of clinical semantic status colours

Those presentation exclusions do not make the commercial or integration modules optional.

The accepted PDF implementation uses `barryvdh/laravel-dompdf` and Dompdf. It renders from persisted report snapshots only, embeds validated report-owned local images as data URIs and does not fetch remote profile resources.

Do not reopen completed frontend foundation, result/report integration, profile/report-branding or PDF-export work without a failing test, security issue, integrity issue or confirmed architecture defect.

Do not begin broad legacy cleanup unless it blocks the current milestone.

---

## Verified Repository Baseline

Canonical integration branch:

    develop

Latest merged product milestone:

    Pull request:
        #23

    Feature commit:
        c93503fc0ffdb5460dd5914f9a2fac1299f462d5

    Merge commit:
        f2c054060a9e964324b4564d0cd3a97e573bf92e

Milestone:

    Notifications and Critical-Result Escalation

Milestone result:

- exact reviewed Notifications source merged
- complete automated validation passed
- N01-N03 essential browser smoke passed
- PR Backend and Frontend CI passed
- post-merge `develop` CI passed
- local `develop` synchronized
- feature branch removed locally and remotely
- no deployment performed

Current product-feature state:

    No product feature branch is active.

Documentation consistency branch for this closeout:

    docs/post-notifications-consistency

Documentation revision base:

    f2c054060a9e964324b4564d0cd3a97e573bf92e

Next product milestone:

    Analyzer Integration

Before creating the Analyzer Integration feature branch, perform one read-only architecture assessment and freeze:

- analyzer/instrument ownership and tenant boundary
- device/test-code mapping
- transport-neutral adapter boundary
- idempotent message/result ingestion
- raw-message and result provenance
- specimen/order/result reconciliation
- validation queue and state transitions
- units/reference/flag preservation
- malformed/unmapped/duplicate/error handling
- retry and dead-letter/review behavior
- authorization and audit evidence
- integration with existing result approval and critical-result notifications
- simulator/reference-adapter test strategy
- boundary with the later external-laboratory interoperability module

Current validation baseline:

    focused Notifications backend:
        7 tests passed
        178 assertions

    complete backend:
        255 tests passed
        6,655 assertions

    frontend:
        44 test files passed
        160 tests passed

    typecheck:
        passed

    production build:
        passed

    browser smoke:
        N01-N03 passed

    GitHub Actions:
        PR Backend passed
        PR Frontend passed
        post-merge develop CI passed

Legacy compatibility:

- reports issued before report-owned media snapshotting may omit logo or signature images in server-generated PDFs
- the renderer does not substitute mutable live-profile media for missing historical snapshot media

Inspection, review and backup artifacts must be written under:

    $env:TEMP\lab-management-system\<milestone>

Do not place generated investigation artifacts on the Desktop or inside the repository.

Do not expose Git author or tagger email addresses in shared logs.

---

## Communication Style

Be direct and concise.

Explain:

- root cause
- architectural decision
- exact implementation
- exact validation command
- expected result

Avoid unnecessary options when one clear technical decision is required.

Do not repeatedly replace validated files without a failing test, security issue, data-integrity issue or confirmed architecture defect.

---

## First Response Required From a New Engineer

Before changing code, summarize:

- project architecture
- tenant authentication flow
- completed milestones
- current branch
- current uncommitted milestone
- frozen architecture decisions
- latest passing tests
- pending tests
- next exact task

Then verify:

    git branch --show-current
    git status --short
    git log -5 --oneline

Do not modify code until the summary matches the repository.

---

## Recommended New-Account Opening Prompt

Use this prompt when transferring the project to another ChatGPT account:

    You are taking over as lead architect and full-stack developer for this
    Laravel multi-tenant laboratory management system.

    Read these files completely before proposing any changes:

    1. docs/AI_ENGINEER_BRIEF.md
    2. docs/PROJECT_HANDOVER.md
    3. docs/ARCHITECTURE_DECISIONS.md
    4. docs/CURRENT_STATE.md
    5. docs/TESTING_AND_OPERATIONS.md

    Treat the repository and these documents as the source of truth.
    Do not rely on generic Laravel assumptions or previous chat memory.

    First summarize:

    - the architecture
    - tenant authentication
    - completed milestones
    - current branch and milestone
    - frozen decisions
    - current passing tests
    - pending work
    - the next exact implementation step

    Do not modify code until that summary has been verified.

<!-- BEGIN ACCELERATED-BETA-ENGINEERING-2026-08-01 -->

## Accelerated launch-recovery execution constraints

The original seven-day commercial-beta target is historical and was missed.
The project is now operating in launch-recovery mode. Schedule recovery must
reduce coordination overhead, not quality gates.

Engineering work must be packaged into larger controlled milestones rather
than repeated broad inspections. Each milestone must still preserve:

- exact baseline and clean-worktree checks;
- tenant isolation;
- authorization and separation of duties;
- safe, reversible migrations;
- immutable clinical history;
- focused tests before checkpoint commits;
- full backend and frontend gates before release;
- honest integration capability statements.

Broad architecture inspection is complete for previously delivered milestones.
Do not repeat completed inspections without a concrete contradiction. The next
required inspection is the focused, read-only Analyzer Integration architecture
assessment described in the current baseline above.

<!-- END ACCELERATED-BETA-ENGINEERING-2026-08-01 -->
