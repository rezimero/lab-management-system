# AI Engineer Brief

## Role

You are taking over as lead architect and full-stack developer for a Laravel multi-tenant Laboratory Information Management System.

Your responsibility is to preserve the current architecture, complete the active milestone and avoid introducing unnecessary redesign.

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

The complete Rezhel LIMS product includes all of the following core areas:

- multi-tenant platform administration
- clinical laboratory operations
- report amendment and supersession
- multi-branch and collection-centre operations
- SaaS plans, subscriptions and feature entitlements
- test pricing, quotations, invoices, payments and receipts
- corporate and insurance billing
- barcode labels and specimen chain of custody
- reagents, consumables and inventory
- referral laboratories and send-out testing
- notifications and critical-result escalation
- patient and clinician portals
- analyzer and instrument integration
- HL7, ASTM, FHIR and external health-system interoperability
- operational, clinical and commercial analytics
- deployment, monitoring, backup, recovery and production support

Commercial and integration modules are mandatory core product features. They must never be described as optional enhancements.

Current delivery order:

1. core roadmap, documentation and CI baseline
2. laboratory-report amendment and supersession
3. multi-branch and collection-centre foundation
4. SaaS plans, subscriptions and feature entitlements
5. pricing, invoices, payments, receipts, corporate and insurance billing
6. barcode labels and specimen chain of custody
7. reagents, consumables and inventory
8. referral laboratories and send-out testing
9. notifications and critical-result escalation
10. patient and clinician portals
11. analyzer integration framework
12. HL7, ASTM, FHIR and external health-system interoperability
13. operational and commercial analytics
14. deployment and production hardening

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

Latest merged architecture and operations milestone:

    Pull request:
        #15

    Merge commit:
        d0321d66b263f435711a9f58f6d4697a08373c7f
        Merge pull request #15 from rezimero/chore/core-roadmap-ci-baseline

Merged milestone commits:

    eead0c8c20906907e4604f8acfcc8284f5be3308
    chore add continuous integration baseline

    803c2b7582c83e0dc7d313589775453ea5d61c70
    docs define core roadmap and CI baseline

Milestone result:

- the GitHub Actions CI baseline is merged
- commercial and integration modules are recorded as mandatory core scope
- the dependency-driven product roadmap is recorded
- stale repository and milestone documentation was corrected
- backend CI passed
- frontend CI passed
- the milestone branch was removed locally and remotely

Verified GitHub Actions run:

    workflow:
        Continuous Integration

    run:
        30633924711

    event:
        pull_request

    head:
        803c2b7582c83e0dc7d313589775453ea5d61c70

    conclusion:
        success

    jobs:
        Backend - passed
        Frontend - passed

Current product-feature state:

    No product feature branch is active.

Documentation synchronization branch used for this revision:

    docs/close-out-core-roadmap-ci-baseline

Documentation revision base:

    d0321d66b263f435711a9f58f6d4697a08373c7f

Next product milestone:

    Controlled laboratory-report amendment and supersession

The amendment implementation branch must not be created until a read-only architecture inspection is completed and the following contract is frozen:

- amendment eligibility
- immutable original-report preservation
- version and sequence rules
- supersession relationships
- amendment reasons
- authorization boundaries
- audit-event requirements
- PDF and browser presentation
- legacy-report behaviour
- tenant-isolation requirements
- notification consequences
- downstream integration consequences

The documentation-closeout revision changes project memory only. It does not alter clinical, commercial or integration behaviour.

Current validation baseline:

    backend:
        148 tests passed
        3,979 assertions

    frontend:
        32 test files passed
        121 tests passed

    typecheck:
        passed

    production build:
        passed

    GitHub Actions:
        Backend passed
        Frontend passed

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

## Accelerated beta execution constraints

The project is operating under a seven-day commercial-beta deadline.

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

Broad architecture inspection is complete for report amendment and
supersession. Do not repeat it unless implementation reveals a concrete
contradiction. Begin product implementation after this accepted decision is
merged into `develop`.

<!-- END ACCELERATED-BETA-ENGINEERING-2026-08-01 -->
