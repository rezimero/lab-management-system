# Project Handover

Updated:

    15 August 2026

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

## 1. Project Identity

This repository contains Rezhel, a multi-tenant Laboratory Information Management System built with Laravel and React.

The system is designed for independent laboratories operating on isolated tenant databases while platform-wide administration, tenant profiles, media metadata and authentication tokens remain in the landlord database.

Repository:

    lab-management-system

Latest merged product milestone:

    Pull request:
        #23

    Feature commit:
        c93503fc0ffdb5460dd5914f9a2fac1299f462d5

    Merge commit:
        f2c054060a9e964324b4564d0cd3a97e573bf92e

    Milestone:
        Notifications and Critical-Result Escalation

Milestone validation:

    complete backend: 255 tests / 6,655 assertions passed
    complete frontend: 44 test files / 160 tests passed
    TypeScript passed
    production build passed
    browser smoke N01-N03 passed
    PR Backend and Frontend CI passed
    post-merge develop CI passed
    local develop synchronized
    feature branch removed locally and remotely
    deployment not performed

Current product-feature state:

    No product feature branch is active.

Next product milestone:

    Analyzer Integration

The Analyzer Integration implementation branch must not be created until a read-only architecture inspection has frozen the analyzer ownership, mapping, ingestion, provenance, validation, error-handling, isolation and audit contracts.

---

## 2. Product Purpose

The system manages the complete operational, commercial and integration lifecycle of diagnostic laboratories.

Implemented core capabilities include:

- tenant onboarding
- tenant authentication
- laboratory users and roles
- laboratory identity and legal profile
- laboratory logo, favicon and director signature
- tenant-owned report brand colours
- patients
- laboratory test catalogue
- laboratory orders
- order items
- specimen collection
- specimen receiving and rejection
- specimen replacement
- test processing
- test-result entry
- test-result review
- result approval
- order completion
- immutable reporting
- historical signatory and branding preservation
- immutable report-owned media preservation
- printable browser report presentation
- downloadable server-generated PDF reports

Remaining mandatory core capabilities and launch work are governed by the Section 0 roadmap and ADR-033. The older list below is retained as historical planning context; items already delivered through PR #23 must not be treated as unfinished.

Historical mandatory-scope list:

- report amendment and supersession
- multi-branch and collection-centre operations
- SaaS subscriptions and feature entitlements
- test pricing, quotations, invoices, payments and receipts
- corporate and insurance accounts
- barcode labels and specimen chain of custody
- reagents, consumables and inventory
- referral laboratories and send-out testing
- email, SMS and supported WhatsApp notifications
- critical-result escalation
- patient and clinician portals
- analyzer and instrument integration
- HL7, ASTM and FHIR interoperability
- hospital and clinic system integration
- clinical, operational and commercial analytics
- deployment, monitoring, backups, recovery and production support

Commercial and integration modules are mandatory core product features. They are not optional expansion work.

The backend result, reporting, profile and PDF workflows are stable.

The frontend result, report, profile and PDF-download integrations are complete.

The current working planning estimate after pull request #23 is approximately 68 percent. This is a planning estimate, not a code-derived metric, and production readiness is lower until the remaining integrations and hardening gates are complete.

The next product feature is Analyzer Integration, after this documentation-consistency closeout.

---

## 3. Technology Stack

Backend:

- Laravel 13.17.0
- PHP 8.5.6
- MySQL 8.4
- Laravel Sanctum 4.3.2
- Spatie Laravel Permission 8.1
- Spatie Laravel Multitenancy
- `barryvdh/laravel-dompdf` 3.1.2
- `dompdf/dompdf` 3.1.6
- `guzzlehttp/guzzle` 7.15.2
- PHPUnit through Laravel Artisan testing

Frontend:

- React 19.2.7
- Vite 8.1.5
- TypeScript 6.0.3
- Vitest 4.1.10
- React Router DOM 7.18.1
- Axios 1.18.1
- MUI 9.x

Development environment:

- Windows
- PowerShell
- UTF-8 without BOM

---

## 4. Database Architecture

The system uses database-per-tenant isolation.

### Landlord database

The landlord database contains platform-wide records, including:

- tenant registrations
- platform users
- personal access tokens
- tenant metadata
- tenant database names
- tenant domains
- tenant laboratory profiles
- tenant profile media metadata

Development landlord database:

    lab_saas_landlord

Automated test landlord database:

    lab_saas_landlord_test

### Tenant databases

Each laboratory has its own physical MySQL database.

Tenant databases contain:

- users
- patients
- laboratory tests
- laboratory orders
- laboratory order items
- specimens
- test results
- laboratory reports
- immutable report snapshots
- tenant-specific operational data

Automated tests generate temporary tenant databases and remove them during teardown.

Never point automated tests at the development landlord database.

---

## 5. Tenant Resolution and Authentication

Tenant API requests require:

    X-Tenant-Domain: tenant-domain

Tenant middleware order is:

    SetTenantContext:required
    auth:sanctum

The tenant context must be resolved before Sanctum authentication.

Final tenant authentication flow:

1. read the tenant domain from `X-Tenant-Domain`
2. resolve the tenant in the landlord database
3. switch the active tenant database connection
4. read the bearer token from the landlord token table
5. confirm that the token belongs to the resolved tenant
6. load the tenant user from the active tenant database
7. bind the tenant user to the tenant authentication guard

Sanctum tokens are tenant-bound using:

    personal_access_tokens.tenant_id

A token issued for one tenant must never authenticate against another tenant.

Tenant context restoration must restore both database and guard:

    Config::set('database.default', 'landlord');
    Config::set('auth.defaults.guard', 'landlord');
    Auth::forgetGuards();

---

## 6. Tenant Authorization

Authenticated tenant API users currently use:

    App\Models\Tenant\User

Tenant authorization is based on the user `role` column through:

    App\Support\Tenant\TenantUserPermissionCatalog

Current test-result authorization:

| Role | View | Enter | Edit/Submit | Approve/Reject |
|---|---:|---:|---:|---:|
| admin | Yes | Yes | Yes | Yes |
| lab_technician | Yes | Yes | Yes | No |
| doctor | Yes | No | No | Yes |
| receptionist | Yes | No | No | No |

Laboratory-profile management uses:

    MANAGE_TENANT_PROFILE

The profile-management boundary allows:

- authorized tenant administrators
- platform superadministrators

Other tenant roles must not access profile-management operations merely because they can view reports.

Platform superadministrator remains a landlord-level role.

Do not replace the active tenant authorization path with Spatie permission calls unless tenant provisioning and authentication are deliberately redesigned.

---

## 7. Public Identifiers

Public API routes use UUIDs.

Database numeric IDs remain internal implementation details.

Current UUID-bound resources include:

- laboratory orders
- laboratory order items
- specimens
- test results
- laboratory reports
- tenant profile media

---

## 8. Completed Backend Capabilities

### Tenant infrastructure

Completed:

- public tenant registration
- tenant database provisioning
- tenant migrations
- initial tenant administrator creation
- cleanup when provisioning fails
- tenant login
- landlord login
- tenant-bound Sanctum tokens
- cross-tenant token protection
- isolated automated tenant testing

### Core laboratory operations

Completed:

- departments
- users
- patients
- laboratory test catalogue
- laboratory orders
- multiple order items
- order status state machine
- specimen collection
- specimen receiving and rejection
- specimen replacement and lineage
- processing readiness
- test processing

### Test results

Completed:

- one result per laboratory order item
- draft creation and editing
- submission
- rejection with required reason
- rejected-result correction
- resubmission
- approval
- approved-result immutability
- order-item completion after approval
- order-completion readiness guard
- audit users and timestamps
- result query and statistics
- UUID routes
- tenant isolation
- role authorization

### Laboratory reporting

Completed:

- one immutable report snapshot per completed order
- laboratory, patient, order and result snapshots
- immutable director qualification
- immutable director registration number
- immutable signatory designation
- immutable accreditation details
- immutable primary, secondary and accent colours
- immutable report-owned logo and signature copies
- image path, MIME and SHA-256 snapshot metadata
- report-media cleanup when generation fails
- generating-user audit
- UUID route binding
- tenant isolation
- view and generation authorization
- JSON report resources
- printable browser report presentation
- Dompdf server-generated PDF rendering
- snapshot-only PDF data mapping
- remote-resource, JavaScript and PHP execution disabled
- local image path, MIME and hash validation
- sanitized PDF attachment filename
- tenant-authorized PDF endpoint

### Tenant laboratory profile

Completed:

- explicit `TenantProfileResource`
- authorized profile view and update requests
- administrator-only profile-management permission
- tenant-isolated profile contract
- laboratory identity, legal, accreditation and signatory fields
- primary, secondary and accent colour fields
- logo, favicon and director-signature service wrappers
- shared secure upload request with multipart field `file`
- JPG, JPEG, PNG and WEBP validation
- 2 MB upload limit
- SVG rejection
- media UUID and tenant ownership
- replaced-media physical cleanup

Latest confirmed backend baseline:

    148 tests passed
    3,979 assertions

---

## 9. Laboratory Order Workflow

Current order status transitions:

    pending
        → cancelled

    sample_collected
        → in_progress
        → cancelled

    in_progress
        → completed

    completed
        → terminal

    cancelled
        → terminal

Specimen collection owns the transition to `sample_collected`.

An order cannot enter `in_progress` unless every active order item has at least one received specimen.

An order cannot enter `completed` unless every active order item:

- is completed
- has a test result
- has an approved test result

---

## 10. Test-Result Workflow

The result workflow is:

    draft
        → submitted
        → approved

    submitted
        → rejected

    rejected
        → edited
        → draft
        → submitted

Rules:

- one result per laboratory order item
- a result can only be created for an in-progress order item
- the parent order must be in progress
- a received specimen must cover the order item
- draft results are editable
- submitted results are immutable
- rejected results are editable
- editing a rejected result returns it to draft
- rejection requires a reason
- approved results are terminal and immutable
- approval completes the associated order item

---

## 11. Active Result, Report and Profile Contracts

Order-item processing:

    POST /api/laboratory-order-items/{uuid}/start

Result creation:

    POST /api/laboratory-order-items/{uuid}/test-result

Result query:

    GET /api/test-results
    GET /api/test-results/stats
    GET /api/test-results/{uuid}

Result editing:

    PUT /api/test-results/{uuid}

Result workflow:

    POST /api/test-results/{uuid}/submit
    POST /api/test-results/{uuid}/approve
    POST /api/test-results/{uuid}/reject

Laboratory reporting:

    POST /api/laboratory-orders/{laboratoryOrder}/report
    GET  /api/laboratory-orders/{laboratoryOrder}/report
    GET  /api/laboratory-reports
    GET  /api/laboratory-reports/{laboratoryReport}
    GET  /api/laboratory-reports/{laboratoryReport}/pdf

The PDF response:

- requires authenticated tenant context
- reuses report-view authorization
- uses tenant-scoped UUID route binding
- returns `application/pdf`
- downloads with a sanitized attachment filename
- renders from immutable report snapshots only

Tenant profile:

- explicit profile resource
- authorized view and update requests
- secure logo, favicon and director-signature uploads
- multipart upload field `file`
- tenant isolation
- administrator-only management

Verify current route definitions before changing or documenting exact tenant-profile route paths.

Legacy standalone result creation and deletion routes must not be reintroduced into the active tenant workflow.

---

## 12. Testing Architecture

Automated tenant tests:

1. register a temporary tenant through the real registration API
2. provision a physical tenant database
3. run tenant migrations
4. create the tenant administrator
5. log in through the real login API
6. send API requests with bearer token and tenant domain
7. assert API responses and tenant database state
8. restore landlord database and guard context
9. delete temporary tenant records
10. drop temporary tenant databases

The test suite refuses to run unless the active landlord database is:

    lab_saas_landlord_test

Temporary tenant database deletion is guarded by strict database-name validation.

PDF coverage includes:

- permitted PDF download
- unknown-role default denial
- cross-tenant UUID isolation
- attachment filename sanitization
- snapshot immutability after profile changes
- successful generation without media
- report-owned local image embedding
- remote URL rejection
- traversal rejection
- MIME mismatch rejection
- SHA-256 mismatch rejection
- frontend blob download and error feedback

Latest complete validation:

    backend:
        148 tests
        3,979 assertions

    frontend:
        32 test files
        121 tests

    production:
        TypeScript validation passed
        Vite build passed

Deployment migrations must be executed deliberately in the intended environment and must not be conflated with automated-test setup.

---

## 13. Frontend State

Frontend root:

    lab-frontend

Canonical integration branch:

    develop

Latest integration baseline:

    cce1e62
    pull request #13

Current frontend status:

- Vite migration complete
- TypeScript validation active
- Vitest active
- Create React App is not the active toolchain
- authentication context and API interceptors characterized
- authentication storage decomposed
- landlord tenant management characterized and decomposed
- result workflow integrated with the order-item-driven API
- immutable report retrieval and generation integrated
- administrator-only laboratory-profile page integrated
- tenant profile and media uploads integrated
- multipart `FormData` handling corrected
- tenant brand-colour editing and preview integrated
- report logo, signature and signatory details integrated
- browser and print report branding integrated
- authenticated PDF blob request integrated
- Download PDF action integrated
- PDF filename parsing and sanitization integrated
- object URL cleanup integrated
- download error feedback integrated

Current frontend test baseline:

    32 test files passed
    121 tests passed

Current build baseline:

    tsc --noEmit passed
    vite build passed

Profile route:

    /settings/laboratory-profile

Profile feature root:

    lab-frontend/src/features/laboratory-profile

Report download modules:

    lab-frontend/src/features/laboratory-reports/api/laboratoryReportApi.ts
    lab-frontend/src/features/laboratory-reports/components/ReportViewHeader.tsx
    lab-frontend/src/features/laboratory-reports/hooks/useDownloadLaboratoryReportPdf.ts
    lab-frontend/src/features/laboratory-reports/utils/downloadBlob.ts
    lab-frontend/src/pages/LaboratoryReportView.tsx

Brand colours are tenant-owned and report-scoped in the current architecture.

Rezhel starter values are fallback values only.

Issued browser reports and PDFs render from immutable snapshot values rather than the current live profile.

Planned sequence status:

1. backend result workflow - completed
2. backend laboratory reporting - completed
3. frontend Vite migration - completed
4. frontend authentication foundation - completed
5. landlord tenant-management foundation - completed
6. frontend result and report integration - completed
7. secure laboratory profile and report branding - completed
8. immutable server-generated PDF export - completed
9. authenticated frontend PDF download - completed
10. core roadmap and GitHub Actions CI baseline - completed and merged in PR #15
11. PR #15 remote Backend and Frontend CI validation - completed
12. roadmap and CI documentation closeout - current repository-maintenance revision
13. report-amendment architecture inspection and contract freeze - next
14. controlled report amendment and supersession implementation - pending
15. multi-branch and collection-centre foundation - pending
16. SaaS plans, subscriptions and feature entitlements - pending
17. pricing, invoices, payments, receipts and account billing - pending
18. barcode labels and specimen chain of custody - pending
19. reagents, consumables and inventory - pending
20. referral laboratory and send-out workflow - pending
21. patient and clinician portals - completed and merged in PR #22
22. notifications and critical-result escalation - completed and merged in PR #23
23. analyzer integration - next; architecture assessment required before implementation
24. external laboratory interoperability - pending
25. courier and specimen dispatch management - pending
26. referral laboratory financial settlement - pending
27. external referral laboratory portal - pending
28. insurance and claims - pending
29. remaining advanced operational/clinical/commercial analytics - pending
30. deployment and production hardening - pending
---

## 14. Known Technical Debt

Known but non-blocking technical-debt items:

- legacy test-result columns remain
- legacy result rows may lack order-item relationships
- legacy landlord result tools may still query the old result shape
- tenant authorization and platform authorization use different models
- the role-and-permission seeder still describes historical Spatie-based tenant permissions
- some test-support and orchestration files exceed the preferred line-count target
- reports issued before immutable report-media copying are not backfilled and may omit logo or signature images in PDFs
- clinician portal tenant-context lookup can emit `404` console noise while a partial tenant domain is being typed
- `NotificationList` has a non-blocking invalid HTML nesting warning in frontend tests

Known presentation exclusions:

- global tenant application theming is not implemented
- report-template customization is not implemented

The presentation exclusions require separate architecture and accessibility decisions.

Commercial and integration modules are unfinished core scope, not technical debt and not optional features.

Resolved items that should not be reported as current debt:

- Vite migration
- Create React App as active tooling
- frontend authorization-header logging
- authentication-storage decomposition
- tenant-management decomposition
- frontend result/report integration
- profile multipart uploads
- report logo rendering
- immutable report signatory fields
- tenant-owned report brand colours
- server-generated PDF rendering and export
- immutable report-owned media storage
- frontend PDF download

---

## 15. Current Git and Recovery Context

Canonical integration branch:

    develop

Latest merged product milestone:

    Pull request:
        #23

    Feature commit:
        c93503fc0ffdb5460dd5914f9a2fac1299f462d5

    Merge commit:
        f2c054060a9e964324b4564d0cd3a97e573bf92e

Validation and closeout:

    PR Backend CI: passed
    PR Frontend CI: passed
    develop CI run 31871444220: passed
    local develop synchronized
    local feature branch removed
    remote feature branch removed
    working tree clean
    deployment not performed

Documentation consistency branch for this revision:

    docs/post-notifications-consistency

Documentation revision base:

    f2c054060a9e964324b4564d0cd3a97e573bf92e

No product feature branch is active.

After this documentation revision is merged:

1. update local `develop` using `--ff-only`
2. verify local and remote merge identity
3. delete the documentation branch locally and remotely
4. perform the report-amendment architecture inspection
5. freeze and record the amendment contract
6. create the product feature branch only after contract approval

Use exact-path staging.

Do not use:

    git add .

Temporary inspection, review and backup files must be stored under:

    $env:TEMP\lab-management-system\<milestone>

---

## 16. Source-of-Truth Priority

When documentation, chat history and code appear to disagree, use this order:

1. passing automated tests
2. current committed source code
3. current branch source code
4. architecture decisions
5. current-state document
6. project handover document
7. previous chat history

Chat history is not the permanent source of truth.

---

## 17. New Engineer Handover Procedure

A new engineer or AI assistant must first read:

1. `docs/AI_ENGINEER_BRIEF.md`
2. `docs/PROJECT_HANDOVER.md`
3. `docs/ARCHITECTURE_DECISIONS.md`
4. `docs/CURRENT_STATE.md`
5. `docs/TESTING_AND_OPERATIONS.md`

Before modifying code, the engineer must confirm:

- current Git branch
- current Git status
- latest merge commits and recovery tags
- active landlord database
- current passing focused tests
- current backend full-suite baseline
- current frontend full-suite and build baseline
- current browser or print smoke evidence when rendering is involved
- the next unfinished milestone task

No implementation should begin from assumptions.

<!-- BEGIN MARKET-READY-BETA-HANDOVER-2026-08-01 -->

## Historical release target and current launch-recovery status

The 2026-08-08 commercial-beta target is historical and was not achieved.
The project is now in launch-recovery mode. No replacement release date is
asserted by repository documentation until the remaining module and production
gates are assessed. Quality gates remain binding despite schedule pressure.

The first implementation milestone is controlled laboratory-report amendment
and supersession. The accepted architecture preserves immutable historical
reports and original approved results while creating audited, authorized,
append-only corrected versions.

The beta also includes minimum marketable production workflows for branches,
subscriptions, billing, inventory, referrals, portals, notifications,
reference integrations, interoperability foundations, and analytics.

Future engineers must not reduce these modules to placeholders or claim
universal analyzer, HL7, ASTM, or FHIR compatibility without validated
external-system testing.

<!-- END MARKET-READY-BETA-HANDOVER-2026-08-01 -->

<!-- BEGIN REFERRAL-LABORATORY-HANDOVER-2026-08-04 -->

## Referral Laboratory Management handover

Baseline:

    branch: feature/referral-laboratory-management
    commit: 6ff700a57c96b4b262c8deed111716169bd6ee02

Approved contract:

    docs/REFERRAL_LABORATORY_MANAGEMENT_ARCHITECTURE.md

Controlled implementation sequence:

    docs/REFERRAL_LABORATORY_MANAGEMENT_IMPLEMENTATION_SEQUENCE.md

The approved aggregate is one referral per laboratory order item, with one
open referral allowed at a time. External result evidence is append-only and
must create or synchronize the existing standard test result. Approval of that
standard result completes the referral and the order item atomically.

Next action:

    implement schema and commercial gates on the current feature branch

Do not mix notifications, portals, analyzer integration or interoperability
into this milestone.

<!-- END REFERRAL-LABORATORY-HANDOVER-2026-08-04 -->

<!-- BEGIN PATIENT-CLINICIAN-PORTALS-HANDOVER-2026-08-10 -->

## Patient and Clinician Portals architecture checkpoint

Current active product branch:

    feature/patient-clinician-portals

Current merged integration baseline:

    develop
    c6d7f42
    Merge pull request #21 from rezimero/feature/referral-laboratory-management

Referral Laboratory Management is complete and merged.

The next product module is Patient and Clinician Portals.

Architecture status:

    approved for implementation

Architecture documents:

    docs/PATIENT_CLINICIAN_PORTALS_ARCHITECTURE.md
    docs/PATIENT_CLINICIAN_PORTALS_IMPLEMENTATION_SEQUENCE.md

Core ownership rule:

A Patient, ReferringClinician, PatientPortalAccount and
ClinicianPortalAccount belong to exactly one tenant.

The same real-world person may exist independently in more than one tenant,
but those records are not a shared platform identity and do not authorize
cross-tenant access.

Authentication rule:

Portal authentication is a dedicated external authentication boundary. It
must not widen the existing internal Sanctum tenant-user identity model.

Tenant resolution occurs before portal credential or bearer-token lookup.

Clinical visibility rule:

Patient-facing result values are exposed through governed immutable laboratory
reports. Draft, submitted and rejected internal result workflow data are not
patient-visible.

External clinician rule:

The existing internal `doctor` tenant-user role remains an internal laboratory
role. External referring clinicians receive dedicated tenant-owned clinician
records and portal accounts.

Order linkage rule:

Free-text `referring_clinician` data is retained as historical display
evidence. Portal authorization requires an explicit referring-clinician
relationship. Historical orders are not automatically linked by clinician
name.

Notification dependency rule:

Patient and Clinician Portals now precede Notifications and Critical-Result
Escalation. Portal implementation may create notification-ready domain events
and manual invitation activation, but message transport is deferred to the
following module.

Implementation begins with the portal identity/authentication foundation and
must include tenant-isolation and token-boundary characterization tests before
portal clinical-data APIs are added.

Generated collectors, backups and review artifacts must be stored outside the
repository.

<!-- END PATIENT-CLINICIAN-PORTALS-HANDOVER-2026-08-10 -->

<!-- BEGIN PATIENT-CLINICIAN-PORTALS-COMPLETION-HANDOVER-2026-08-11 -->

## Patient and Clinician Portals completion handover

Implementation is complete through P9 on
`feature/patient-clinician-portals`. The milestone now includes the separate
patient and clinician portal applications, tenant-scoped read APIs, governed
report access, internal portal administration, one-time account activation,
account/token revocation, security audit events and notification-ready domain
events.

Frontend review evidence:

    typecheck passed
    42 test files / 156 tests passed
    production build passed

All 572 PHP files also passed an independent parser syntax check. The developer
must run the focused Laravel validation in the repository's configured test
environment before staging. This handover does not authorize a development
database reset, recovery script, live migration, Git staging or commit.

P10 remains the only portal checkpoint. After P10 is verified and committed,
the next product module is Notifications and Critical-Result Escalation.

<!-- END PATIENT-CLINICIAN-PORTALS-COMPLETION-HANDOVER-2026-08-11 -->

<!-- BEGIN NOTIFICATIONS-CRITICAL-RESULTS-HANDOVER-2026-08-13 -->

## Notifications and Critical-Result Escalation handover

Portal pull request #22 is merged and verified on `develop` at
`d96eb5d4f35f5a8b4977cdbd8b05c0f0741a082f`. The next branch is
`feature/notifications-critical-results`.

The implementation candidate includes tenant-scoped internal and portal
inboxes, user preferences, an encrypted email outbox, tenant-aware queued
delivery, portal invitation/report-ready mail, secure patient/clinician
password reset and a governed critical-result escalation lifecycle.

Clinical and security boundaries:

- tenant context is established before every message, reset or escalation
  query;
- one-time tokens are hashed in their authoritative table and occur only in
  encrypted pending delivery payloads;
- external email excludes patient identifiers and result/report content;
- administrators send a reset link and never set, receive or view a portal
  password;
- password completion revokes active portal sessions;
- critical-result email is only a prompt to sign in to the secure workspace;
- critical escalation is mandatory for authorized responders and cannot be
  disabled by a preference;
- SMS and WhatsApp are unavailable rather than simulated as successful.

The sole development tenant remains `development` (ID 12) in
`tenant_s4d0mhau_development`, owned by landlord `lab_saas_landlord`. Only the
single reviewed additive migration may be applied there. Automated validation
must use `lab_saas_landlord_test` and dynamically created test tenants.

Read these module documents before continuation:

1. `docs/NOTIFICATIONS_CRITICAL_RESULTS_ARCHITECTURE.md`;
2. `docs/NOTIFICATIONS_CRITICAL_RESULTS_IMPLEMENTATION_SEQUENCE.md`;
3. this handover entry;
4. ADR-032 in `docs/ARCHITECTURE_DECISIONS.md`.

N8 is complete. The reviewed source was validated, the additive development
migration was verified current, N01-N03 browser acceptance passed, PR #23
merged normally, PR Backend/Frontend CI passed, post-merge `develop` CI passed,
and feature-branch cleanup completed. No production deployment was performed.

The next product action is the read-only Analyzer Integration architecture
assessment. Do not reapply Notifications source packages or rerun its development
migration.

<!-- END NOTIFICATIONS-CRITICAL-RESULTS-HANDOVER-2026-08-13 -->
