# Current Project State

Updated:

    15 August 2026

This document is the current repository handover snapshot.

Update it before every milestone commit, push, merge, tag or transfer to another engineer or AI assistant.

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
## 1. Current Git Context

Repository:

    lab-management-system

Canonical integration branch:

    develop

Current merged baseline:

    Pull request:
        #23

    Feature commit:
        c93503fc0ffdb5460dd5914f9a2fac1299f462d5

    Merge commit:
        f2c054060a9e964324b4564d0cd3a97e573bf92e

    Milestone:
        Notifications and Critical-Result Escalation

Current product-feature branch:

    none

Documentation consistency branch for this revision:

    docs/post-notifications-consistency

Documentation revision base:

    f2c054060a9e964324b4564d0cd3a97e573bf92e

Next product milestone:

    Analyzer Integration

Do not create the Analyzer Integration product branch until the read-only architecture assessment and contract freeze are complete.

---

## 2. Non-Negotiable Architecture

Preserve:

- database-per-tenant MySQL isolation
- landlord database for tenant metadata, tenant profiles, media metadata and Sanctum tokens
- `X-Tenant-Domain` tenant resolution
- `SetTenantContext:required` before `auth:sanctum`
- tenant-bound Sanctum tokens
- `App\Models\Tenant\User` as the authenticated tenant model
- `TenantUserPermissionCatalog` as the active tenant permission catalogue
- UUID public route binding
- order-item-driven test results
- one result per laboratory order item
- result workflow state machine
- approved-result order-completion guard
- immutable laboratory report snapshots
- immutable signatory and brand-colour snapshots
- immutable report-owned logo and director-signature media
- server-generated PDFs rendered only from report snapshots
- local PDF image validation by report path, MIME and hash
- remote PDF resources, JavaScript and PHP execution disabled
- tenant-owned laboratory identity and colours
- administrator-only laboratory-profile management
- secure tenant-profile image validation and physical cleanup
- tenant database transactions for workflow mutations
- isolated automated MySQL tenant tests

The test landlord database must be:

    lab_saas_landlord_test

Automated tests must never target:

    lab_saas_landlord

---

## 3. Tenant Context Restoration

Tenant operations and tests must restore both database and authentication context:

    Config::set('database.default', 'landlord');
    Config::set('auth.defaults.guard', 'landlord');
    Auth::forgetGuards();

Failure to restore both contexts can leak tenant state into later landlord operations or tests.

Landlord-side tenant-profile and media operations must still constrain every query to the resolved tenant.

---

## 4. Completed Backend Milestones

Completed and validated backend capabilities include:

- landlord and tenant authentication
- tenant provisioning and cleanup
- tenant-bound Sanctum token isolation
- departments
- tenant users
- patients
- laboratory test catalogue
- laboratory orders
- specimens and replacement lineage
- processing readiness
- order-item processing
- draft, submit, reject, correct and approve result workflow
- approved-result order-completion guard
- tenant result authorization
- tenant-isolated result queries and statistics
- immutable laboratory report snapshots
- tenant-isolated report retrieval and generation
- explicit tenant-profile API resource
- administrator-only tenant-profile view and update authorization
- `MANAGE_TENANT_PROFILE` permission catalogue capability
- tenant-isolated logo, favicon and director-signature upload handling
- JPG, JPEG, PNG and WEBP validation with a 2 MB limit
- SVG rejection
- media UUID and tenant-ownership alignment
- replaced-media physical-file cleanup
- director qualification snapshot
- director registration-number snapshot
- signatory-designation snapshot
- accreditation-details snapshot
- tenant primary, secondary and accent colour snapshotting
- report-owned immutable logo and director-signature copies
- report-media path, MIME and SHA-256 snapshot metadata
- cleanup of copied report media when generation fails
- Dompdf server-side PDF generation
- snapshot-only PDF view-data mapping
- secure local PDF image embedding
- sanitized PDF attachment filenames
- tenant-authorized PDF download endpoint

Latest confirmed complete backend baseline:

    255 tests passed
    6,655 assertions
    validated 15 August 2026

---

## 5. Result Workflow

States:

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
- result creation requires an in-progress order item
- the parent order must be in progress
- a received specimen must cover the order item
- draft results are editable
- submitted results are immutable
- rejected results are editable
- editing a rejected result clears review state and returns it to draft
- rejection requires a reason
- approved results are terminal
- approval completes the linked order item

An order may complete only when every active order item:

- is completed
- has a result
- has an approved result

---

## 6. Active Backend API Contract

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

The PDF route uses the same tenant context, UUID route binding and report-view authorization boundary as JSON report retrieval.

Tenant laboratory profile:

- explicit tenant-profile resource
- authorized profile view and update requests
- dedicated logo, favicon and director-signature upload handling
- multipart upload field `file`
- administrator-only tenant-management boundary
- tenant-isolation contract tests

The exact route list must be verified from current route definitions before changing the profile contract.

---

## 7. Active Frontend Toolchain

Frontend root:

    lab-frontend

Current stack:

- React 19.2.7
- Vite 8.1.5
- TypeScript 6.0.3
- Vitest 4.1.10
- React Router DOM 7.18.1
- Axios 1.18.1
- MUI 9.x
- Testing Library React 16.3.2
- user-event 14.6.1
- jsdom 29.1.1

Build command:

    npm run build

Build pipeline:

    tsc --noEmit && vite build

Create React App is not the active frontend toolchain.

---

## 8. Completed Frontend Milestones

### Vite application foundation

Completed:

- Vite application shell
- TypeScript build validation
- Vitest test runner
- authentication-context characterization and storage refactor
- API-client interceptor characterization
- landlord tenant-management characterization and decomposition

Merged baseline:

    pull request #9
    merge commit f88f993
    tag frontend-vite-foundation-complete

### Result and immutable-report integration

Completed:

- order-item-driven result API integration
- result workflow UI alignment
- immutable laboratory-report retrieval and generation
- report route and view integration
- regression coverage around result and report screens

Merged baseline:

    pull request #11
    merge commit 200adfe

### Laboratory profile and report branding

Completed:

- administrator-only route `/settings/laboratory-profile`
- tenant-admin route guard
- laboratory-profile API mapping and form state
- laboratory identity, legal, accreditation and signatory fields
- logo and director-signature multipart uploads
- multipart request preservation in the Axios client
- primary, secondary and accent colour editors
- native colour pickers and editable six-digit HEX values
- uppercase colour normalization
- profile validation and server-error feedback
- starter palette and safe fallback colours
- readable black-or-white text over the accent colour
- live report-branding preview
- report laboratory name and divider in primary colour
- supporting identity text in secondary colour
- issued badge in accent colour
- logo rendering in browser and print output
- director-signature footer
- immutable report colours and signatory details from the report snapshot
- tenant-owned branding limited to profile and reports

Merged baseline:

    pull request #12
    merge commit 81c1e67

### Server-generated laboratory-report PDF export

Completed and validated on the active feature branch:

- authenticated blob request through the shared Axios client
- Download PDF action on the issued-report view
- downloading and error states
- response filename parsing and sanitization
- temporary object URL creation and cleanup
- focused download success and failure tests
- newly issued PDF smoke validation with logo and director signature

Historical product checkpoint:

    218ac1f

This PDF milestone was subsequently merged and is retained here as chronology only.

---

## 9. Frontend Authentication and API Client Structure

Current authentication files:

    lab-frontend/src/auth/authTypes.ts
    lab-frontend/src/auth/authSessionMapper.ts
    lab-frontend/src/auth/authStorage.ts
    lab-frontend/src/contexts/AuthContext.tsx
    lab-frontend/src/api/client.ts

Characterized public `AuthContext` interface:

- `user`
- `tenant`
- `impersonator`
- `isLoading`
- `isSuperadmin`
- `isImpersonating`
- `isAuthenticated`
- `login`
- `register`
- `startImpersonation`
- `stopImpersonation`
- `logout`
- `refreshUser`

API-client invariants include:

- attach the current bearer token
- remove stale authorization headers
- include stored or recovered tenant domain for tenant requests
- exclude tenant header for platform superadmins
- clean malformed tenant storage
- clear invalid sessions on ordinary 401 responses
- preserve login and registration exception handling
- preserve impersonated logout behaviour
- do not force JSON `Content-Type` on `FormData`

---

## 10. Laboratory Profile and Report Structure

Profile feature root:

    lab-frontend/src/features/laboratory-profile

Key profile modules:

    api/laboratoryProfileApi.ts
    components/LaboratoryBrandColors.tsx
    components/LaboratoryProfileForm.tsx
    components/LaboratoryProfileMedia.tsx
    hooks/useLaboratoryProfile.ts
    types/laboratoryProfile.types.ts
    utils/laboratoryBrandColors.ts
    utils/laboratoryProfileMapper.ts
    utils/profileErrorMessage.ts

Route-level profile page:

    lab-frontend/src/pages/LaboratoryProfile.tsx

Authorization boundary:

    lab-frontend/src/components/TenantAdminRoute.tsx

Browser report components:

    lab-frontend/src/features/laboratory-reports/components/ReportIdentitySection.tsx
    lab-frontend/src/features/laboratory-reports/components/ReportSignatureFooter.tsx
    lab-frontend/src/features/laboratory-reports/components/ReportPrintStyles.tsx
    lab-frontend/src/features/laboratory-reports/components/ReportViewHeader.tsx

Server PDF services:

    app/Services/Tenant/LaboratoryReport/LaboratoryReportPdfService.php
    app/Services/Tenant/LaboratoryReport/LaboratoryReportPdfViewDataFactory.php
    app/Services/Tenant/LaboratoryReport/LaboratoryReportPdfImageResolver.php
    app/Services/Tenant/LaboratoryReport/LaboratoryReportPdfFilename.php

Server PDF views:

    resources/views/pdf/laboratory-report.blade.php
    resources/views/pdf/laboratory-report/

Frontend PDF modules:

    lab-frontend/src/features/laboratory-reports/api/laboratoryReportApi.ts
    lab-frontend/src/features/laboratory-reports/hooks/useDownloadLaboratoryReportPdf.ts
    lab-frontend/src/features/laboratory-reports/utils/downloadBlob.ts
    lab-frontend/src/pages/LaboratoryReportView.tsx

Browser and PDF report rendering must use the report snapshot rather than the live laboratory profile.

Brand colours are tenant-owned. Rezhel defaults are only starter or fallback values.

Clinical semantic colours remain separate from tenant branding.

The PDF renderer embeds only report-owned immutable images and never fetches current profile URLs.

---

## 11. Current Validation Baseline

Backend:

    148 tests passed
    3,979 assertions
    duration 2,279.04 seconds
    validated 30 July 2026

Frontend:

    32 test files passed
    121 tests passed
    duration 1,211.13 seconds
    validated 30 July 2026

Production validation:

    Composer validation passed
    Composer platform requirements passed
    TypeScript validation passed
    Vite production build passed

Manual smoke validation:

- administrator opened the laboratory-profile settings page
- profile data saved successfully
- logo uploaded and rendered
- director signature uploaded and rendered
- tenant brand colours saved and previewed
- browser report rendered tenant identity and signatory information
- browser print preview retained logo, branding and signature
- authenticated Download PDF action returned a server-generated PDF
- newly issued PDF displayed the immutable laboratory logo
- newly issued PDF displayed the immutable director signature
- PDF signatory details, accreditation details and snapshot colours rendered
- an older pre-media-snapshot report omitted images without reading current profile media

Validated commands:

    composer validate --no-check-publish
    composer check-platform-reqs
    php artisan test --stop-on-failure

    cd lab-frontend
    npm test -- --no-file-parallelism --testTimeout=15000
    npm run typecheck
    npm run build

---

## 12. Testing and Refactor Discipline

For each behaviour change:

1. inspect the active component, API client, service and authorization boundary
2. locate or create characterization coverage
3. run focused tests
4. make the smallest responsible change
5. rerun focused and affected regression tests
6. run the full relevant suite at milestone completion
7. run TypeScript validation and the production build for frontend work
8. run `git diff --check`
9. stage exact paths only
10. compare intended paths with `git diff --cached --name-only`
11. commit only passing code
12. perform browser and print smoke checks when rendering changes

Do not use:

    git add .

Do not raise global test timeouts to hide a slow test without identifying the cause.

---

## 13. Known Technical Debt

Known but non-blocking technical-debt items:

- legacy test-result columns remain for compatibility
- legacy result rows may lack an order-item relationship
- old landlord result utilities may still use the legacy shape
- tenant authorization and landlord authorization use different user models
- the historical role-and-permission seeder does not describe the active tenant authorization path
- some test-support and orchestration files exceed the preferred line-count target
- legacy reports issued before immutable report-media copying are not backfilled and may omit logo or signature images in server-generated PDFs
- clinician portal tenant-context lookup may emit `404` console noise while a partial domain is typed; this is a non-blocking portal UX/code cleanup item
- `NotificationList` has an invalid HTML nesting warning observed during frontend testing; this is non-blocking cleanup

Known presentation exclusions:

- global tenant application theming is not implemented
- report-template customization is not implemented and requires a separate architecture and accessibility decision

Commercial and integration modules are not technical debt and are not optional. They are unfinished core product scope governed by ADR-028.

Resolved items that must not remain in technical-debt lists:

- Vite migration
- Create React App removal as active tooling
- frontend authorization-header logging
- authentication-storage decomposition
- landlord tenant-management decomposition
- frontend result API integration
- immutable-report frontend integration
- profile multipart upload handling
- report logo rendering
- tenant-owned report brand colours
- immutable signatory snapshot fields
- server-generated PDF rendering and export
- authenticated frontend PDF download
- immutable report-owned logo and signature storage

---

## 14. Current Blockers

No confirmed product-code blocker exists.

No known backend test failure exists.

No known frontend test failure exists.

No known frontend typecheck or build failure exists.

No CI failure exists for the merged Notifications milestone or its post-merge `develop` validation.

No uncommitted product-code change exists.

Analyzer Integration is intentionally gated on a read-only architecture inspection and contract freeze. This is a design gate, not a product-code defect.

---

## 15. Historical Core-Roadmap/CI Milestone Status

Historical completed milestone:

    Core roadmap, documentation correction and CI baseline

Status:

    remaining-scope audit completed
    commercial modules confirmed as core product scope
    integration modules confirmed as core product scope
    dependency-driven implementation order frozen
    GitHub Actions workflow created
    workflow action tags verified
    Composer manifest validation passed
    Composer platform validation passed
    Composer locked-dependency audit passed
    CI workflow committed
    roadmap documentation committed
    milestone branch pushed
    pull request #15 opened
    Backend CI passed
    Frontend CI passed
    pull request #15 merged normally
    merge commit verified
    local develop synchronized
    origin/develop synchronized
    milestone branch removed locally and remotely

Merge commit:

    d0321d66b263f435711a9f58f6d4697a08373c7f

No clinical, commercial or integration behaviour was changed by this milestone.

Documentation-closeout purpose:

    Record the verified PR #15 merge and successful first remote CI execution.

---

## 16. Historical Next-Task Record

The following sequence described the PR #15-era next task and is retained only as chronology. The authoritative next task is the Analyzer Integration assessment in Section 0.

Historical sequence:

1. update local `develop` with `--ff-only`
2. verify a clean worktree
3. delete the completed documentation branch locally and remotely
4. perform a read-only inspection of the existing result, report, authorization, audit and PDF architecture
5. identify every existing model, migration, service, controller, policy, route, test and frontend surface affected by report amendment
6. define amendment eligibility and authorization rules
7. define immutable original-report preservation
8. define report versioning and supersession relationships
9. define amendment reasons and audit-event requirements
10. define browser, print and PDF presentation rules
11. define legacy-report compatibility
12. define notification and downstream-integration consequences
13. record the contract in an architecture decision
14. create the report-amendment feature branch only after the contract is approved
15. begin implementation with characterization and contract tests

The next product feature is:

    Controlled laboratory-report amendment and supersession

No amendment implementation should begin during documentation closeout.

---

## 17. Git Continuation

Documentation revision branch:

    docs/close-out-core-roadmap-ci-baseline

Documentation revision base:

    d0321d66b263f435711a9f58f6d4697a08373c7f

Required documentation paths:

    docs/AI_ENGINEER_BRIEF.md
    docs/ARCHITECTURE_DECISIONS.md
    docs/CURRENT_STATE.md
    docs/PROJECT_HANDOVER.md
    docs/TESTING_AND_OPERATIONS.md

For this documentation revision:

1. verify only the five intended documentation files changed
2. verify UTF-8 without BOM
3. verify final newlines
4. run `git diff --check`
5. stage the five exact paths
6. commit locally
7. verify a clean worktree
8. push the documentation branch
9. open one documentation-only pull request into `develop`
10. review and merge normally
11. update local `develop` with `--ff-only`
12. verify local and remote merge identity
13. delete the documentation branch locally and remotely

Do not use:

    git add .

Generated inspection, review and backup artifacts belong under:

    $env:TEMP\lab-management-system\core-roadmap-ci-baseline

Do not place generated review artifacts on the Desktop or inside the repository.

After documentation closeout, perform the read-only amendment architecture inspection before creating a product branch.

---

<!-- BEGIN MARKET-READY-BETA-SPRINT-2026-08-01 -->

## Seven-day market-ready commercial beta sprint

The project entered an accelerated commercial-beta sprint on 2026-08-01.

The immediate implementation feature is controlled laboratory-report
amendment and supersession. Its architecture is approved and is recorded in
`docs/REPORT_AMENDMENT_SUPERSESSION_ARCHITECTURE.md`.

The beta target includes minimum marketable production scope for:

- multi-branch and collection centres;
- SaaS plans, subscriptions, and feature entitlements;
- billing, invoices, payments, and receipts;
- inventory and reagent management;
- referral laboratories;
- patient and clinician portals;
- notifications and critical-result escalation;
- analyzer integration with a validated reference adapter or simulator;
- HL7, ASTM, and FHIR integration foundations;
- operational, commercial, and inventory analytics.

The beta release candidate target is 2026-08-08. A one-to-two-week production
hardening window follows the beta. No module may be represented by empty
navigation, fake data, or unsupported compatibility claims.

<!-- END MARKET-READY-BETA-SPRINT-2026-08-01 -->

<!-- BEGIN REFERRAL-LABORATORY-MILESTONE-2026-08-04 -->

## Referral Laboratory Management milestone

The Inventory and Reagent Management milestone is merged in `develop` at
`6ff700a57c96b4b262c8deed111716169bd6ee02`.

Referral Laboratory Management now proceeds on:

    feature/referral-laboratory-management

The architecture is approved in:

    docs/REFERRAL_LABORATORY_MANAGEMENT_ARCHITECTURE.md

The implementation sequence is recorded in:

    docs/REFERRAL_LABORATORY_MANAGEMENT_IMPLEMENTATION_SEQUENCE.md

The binding design keeps referrals attached to individual laboratory order
items and routes external results through the existing internal result review
and report-governance workflow.

Implementation has not begun at this documentation checkpoint.

<!-- END REFERRAL-LABORATORY-MILESTONE-2026-08-04 -->

<!-- BEGIN PATIENT-CLINICIAN-PORTALS-MILESTONE-2026-08-10 -->

## Patient and Clinician Portals milestone

Referral Laboratory Management is merged into `develop` through pull request
#21.

Verified merge baseline:

    merge commit:
        c6d7f42

    feature completion commit:
        d3cf477
        feat(referrals): complete referral laboratory management

The active product branch is:

    feature/patient-clinician-portals

The branch was created from clean, synchronized `develop`.

The Patient and Clinician Portals architecture has now been inspected and
frozen before implementation.

Binding architecture:

    docs/PATIENT_CLINICIAN_PORTALS_ARCHITECTURE.md

Implementation sequence:

    docs/PATIENT_CLINICIAN_PORTALS_IMPLEMENTATION_SEQUENCE.md

Key architecture invariants:

- every patient belongs to exactly one tenant
- every referring clinician belongs to exactly one tenant
- portal accounts are tenant-owned and live inside the tenant domain
- patient and clinician portal authentication is separate from internal
  tenant-user Sanctum authentication
- tenant context is resolved before portal authentication
- patient ownership is derived from the authenticated patient portal account
- clinician ownership is derived from the authenticated clinician portal
  account
- the internal `doctor` role is not the external clinician portal identity
- issued immutable laboratory reports are the V1 clinical visibility boundary
- historical clinician-name text is not an authorization mechanism
- public portal self-registration is outside V1
- notification transport is deferred to the next milestone
- this milestone emits notification-ready events and supports manual
  activation flows

The dependency order exception relative to ADR-028 is documented in ADR-031.

Next implementation checkpoint:

    P2 - portal identity and authentication foundation

Temporary collectors, backups and debug artifacts must remain outside the
repository under `%TEMP%` or `Downloads`.

<!-- END PATIENT-CLINICIAN-PORTALS-MILESTONE-2026-08-10 -->

<!-- BEGIN PATIENT-CLINICIAN-PORTALS-COMPLETION-2026-08-11 -->

## Patient and Clinician Portals implementation completion

The portal milestone is implemented through P9 on:

    feature/patient-clinician-portals

Delivered scope now includes:

- dedicated tenant-first patient and clinician authentication
- tenant-owned portal accounts, invitations and access tokens
- durable referring-clinician order linkage
- portal-safe patient and clinician read APIs and governed report PDFs
- separate responsive patient and clinician frontend shells
- internal portal-access administration
- one-time invitation create, replace, revoke and activation flows
- account enable/disable with immediate portal-token revocation
- immutable tenant-owned portal security audit events
- notification-ready invitation and issued-report events
- frontend session, invitation, activation and regression coverage

No notification provider, message transport or public self-registration is
part of this milestone.

Validation captured in the review workspace:

    frontend typecheck: passed
    frontend tests: 42 files / 156 tests passed
    frontend production build: passed
    PHP parser check: 572 files / 0 syntax failures

The focused Laravel portal, clinician-linkage and report-generation tests must
still be run in the developer's PHP environment before P10 staging or commit.
No development database reset or recovery operation is required.

Next checkpoint:

    P10 - exact-path review, local backend validation and Git completion

The next product module after P10 is Notifications and Critical-Result
Escalation.

<!-- END PATIENT-CLINICIAN-PORTALS-COMPLETION-2026-08-11 -->

<!-- BEGIN NOTIFICATIONS-CRITICAL-RESULTS-IMPLEMENTATION-2026-08-13 -->

## Notifications and Critical-Result Escalation implementation candidate

Patient and Clinician Portals are merged into `develop` through pull request
#22 at merge commit `d96eb5d4f35f5a8b4977cdbd8b05c0f0741a082f`.

The Notifications and Critical-Result Escalation implementation candidate is
prepared for:

    feature/notifications-critical-results

Delivered candidate scope:

- tenant-owned in-app inboxes and preferences for internal, patient and
  clinician recipients;
- encrypted, idempotent email outbox records with bounded retry and stale-job
  recovery;
- tenant-aware delivery jobs stored through the landlord queue connection;
- invitation and governed report-ready delivery;
- secure administrator-assisted and self-service portal password reset;
- explicit approved-critical-result detection, acknowledgement, resolution
  and repeated escalation;
- internal and portal frontend surfaces;
- one additive five-table tenant migration and focused automated tests.

SMS and WhatsApp remain explicitly unavailable until a provider, credentials,
delivery receipts and webhook verification are separately reviewed.

Binding documents:

    docs/NOTIFICATIONS_CRITICAL_RESULTS_ARCHITECTURE.md
    docs/NOTIFICATIONS_CRITICAL_RESULTS_IMPLEMENTATION_SEQUENCE.md

Historical note: the text above described the pre-N8 implementation candidate.
N8 is now complete: the development migration was verified current, complete
backend/frontend validation passed, N01-N03 browser smoke passed, PR #23 merged,
PR Backend/Frontend CI passed, post-merge `develop` CI passed, and the feature
branch was removed locally and remotely. No production deployment was performed.

<!-- END NOTIFICATIONS-CRITICAL-RESULTS-IMPLEMENTATION-2026-08-13 -->
