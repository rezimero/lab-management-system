# Current Project State

Updated:

    30 July 2026

This document is the current repository handover snapshot.

Update it before every milestone commit, push, merge, tag or transfer to another engineer or AI assistant.

---

## 1. Current Git Context

Repository:

    lab-management-system

Canonical integration branch:

    develop

Latest integration baseline:

    Pull request:
        #14

    Merge commit:
        81e9c0066609691d7ec537ce166a4cde3389ec02
        Merge pull request #14 from rezimero/feature/laboratory-report-pdf-export

Latest merged product milestone:

    Immutable laboratory report PDF export and authenticated frontend download

Active milestone branch:

    chore/core-roadmap-ci-baseline

The branch was created from:

    81e9c0066609691d7ec537ce166a4cde3389ec02

Current milestone checkpoint:

    eead0c8c20906907e4604f8acfcc8284f5be3308
    chore add continuous integration baseline

Current state:

    worktree:
        clean before documentation update

    branch pushed:
        no

    pull request opened:
        no

    GitHub Actions remotely exercised:
        no

Current milestone:

    Core roadmap, documentation correction and CI baseline

Historical frontend recovery tags remain available:

    frontend-vite-baseline
    frontend-auth-refactor-wave-1
    frontend-tenant-management-wave-1
    frontend-tenant-management-wave-2
    frontend-vite-foundation-complete

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

Latest confirmed backend baseline:

    148 tests passed
    3,979 assertions

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

Product checkpoint:

    218ac1f

GitHub closeout remains pending.

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

No uncommitted product-code change exists.

The GitHub Actions workflow has been committed locally but has not yet been exercised remotely. Remote execution is a milestone closeout gate, not a confirmed product blocker.

The active work is documentation synchronization followed by branch push, pull-request review and first CI execution.

---

## 15. Current Milestone Status

Milestone:

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
    exact workflow path verified
    CI workflow checkpoint committed locally
    documentation synchronization in progress
    branch push pending
    pull request pending
    first remote GitHub Actions execution pending

Checkpoint:

    eead0c8c20906907e4604f8acfcc8284f5be3308
    chore add continuous integration baseline

No clinical, commercial or integration behaviour is changed by this milestone.

---

## 16. Immediate Next Task

Complete the roadmap and CI milestone:

1. review the five updated core documentation files
2. verify UTF-8 without BOM
3. run whitespace validation
4. verify exactly the five intended documentation files changed
5. stage the five exact paths
6. commit the documentation checkpoint
7. push `chore/core-roadmap-ci-baseline`
8. open one pull request into `develop`
9. inspect both GitHub Actions jobs
10. correct only confirmed CI-environment incompatibilities
11. merge using a normal merge commit after all checks pass
12. update and verify local `develop`
13. delete the completed milestone branch locally and remotely
14. perform a read-only amendment-workflow architecture inspection
15. freeze the amendment and supersession contract
16. create the amendment feature branch only after the contract is agreed

The next product feature is:

    Controlled laboratory-report amendment and supersession

---

## 17. Git Continuation

Current milestone branch:

    chore/core-roadmap-ci-baseline

Required base commit:

    81e9c0066609691d7ec537ce166a4cde3389ec02

Current committed checkpoint:

    eead0c8c20906907e4604f8acfcc8284f5be3308
    chore add continuous integration baseline

Required documentation paths:

    docs/AI_ENGINEER_BRIEF.md
    docs/ARCHITECTURE_DECISIONS.md
    docs/CURRENT_STATE.md
    docs/PROJECT_HANDOVER.md
    docs/TESTING_AND_OPERATIONS.md

Before the documentation commit:

    git status -sb
    git diff --check
    git diff --name-status
    git diff --stat

Stage exact paths only.

Do not use:

    git add .

After the documentation checkpoint:

1. verify a clean worktree
2. push `chore/core-roadmap-ci-baseline`
3. open a pull request into `develop`
4. wait for and inspect backend and frontend CI jobs
5. merge normally only after successful review
6. switch to `develop`
7. pull with `--ff-only`
8. verify local and remote merge identity
9. delete the milestone branch locally and remotely

Generated inspection, review and backup artifacts belong under:

    $env:TEMP\lab-management-system\core-roadmap-ci-baseline

Do not place them on the Desktop or in the repository.

Create the report-amendment feature branch only after its architecture contract is frozen.

---
