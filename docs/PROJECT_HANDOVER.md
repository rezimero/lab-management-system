# Project Handover

Updated:

    30 July 2026

## 1. Project Identity

This repository contains Rezhel, a multi-tenant Laboratory Information Management System built with Laravel and React.

The system is designed for independent laboratories operating on isolated tenant databases while platform-wide administration, tenant profiles, media metadata and authentication tokens remain in the landlord database.

Repository:

    lab-management-system

Latest merged architecture and operations milestone:

    Pull request:
        #15

    Merge commit:
        d0321d66b263f435711a9f58f6d4697a08373c7f

Milestone:

    Core roadmap, documentation correction and CI baseline

Milestone validation:

    Backend CI passed
    Frontend CI passed
    normal merge commit verified
    local develop synchronized
    origin/develop synchronized
    milestone branch removed locally and remotely

Documentation synchronization branch used for this revision:

    docs/close-out-core-roadmap-ci-baseline

Documentation revision base:

    d0321d66b263f435711a9f58f6d4697a08373c7f

Current product-feature state:

    No product feature branch is active.

Next product milestone:

    Controlled laboratory-report amendment and supersession

The amendment implementation branch must not be created until the architecture contract has been inspected, documented and approved.

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

Remaining mandatory core capabilities include:

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

The project-wide planning completion baseline after pull request #14 is approximately 45 percent.

The next product feature is controlled laboratory-report amendment and supersession, after this roadmap and CI milestone is merged.

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
21. notifications and critical-result escalation - pending
22. patient and clinician portals - pending
23. analyzer integration framework - pending
24. HL7, ASTM, FHIR and external-system interoperability - pending
25. operational and commercial analytics - pending
26. deployment and production hardening - pending
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

Latest merged architecture and operations milestone:

    Pull request:
        #15

    Merge commit:
        d0321d66b263f435711a9f58f6d4697a08373c7f

Milestone commits:

    eead0c8c20906907e4604f8acfcc8284f5be3308
    chore add continuous integration baseline

    803c2b7582c83e0dc7d313589775453ea5d61c70
    docs define core roadmap and CI baseline

Verified CI run:

    workflow:
        Continuous Integration

    run:
        30633924711

    Backend:
        passed

    Frontend:
        passed

Documentation synchronization branch used for this revision:

    docs/close-out-core-roadmap-ci-baseline

Documentation revision base:

    d0321d66b263f435711a9f58f6d4697a08373c7f

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

## Active release target: market-ready commercial beta

The active delivery target is a production-engineered commercial beta by
2026-08-08, followed by a one-to-two-week hardening window.

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
