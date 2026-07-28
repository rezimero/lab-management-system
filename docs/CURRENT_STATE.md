# Current Project State

Updated:

    28 July 2026

This document is the current repository handover snapshot.

Update it before every milestone commit, push, merge, tag or transfer to another engineer or AI assistant.

---

## 1. Current Git Context

Repository:

    lab-management-system

Canonical integration branch:

    develop

Latest merged product milestone:

    Tenant laboratory profile, secure media, immutable signatory details and tenant-owned report branding

    Pull request:
        #12

    Merge commit:
        81c1e67
        Merge pull request #12 from rezimero/feature/laboratory-profile-report-signatory

Previous merged product milestone:

    Frontend result and immutable-report integration

    Pull request:
        #11

    Merge commit:
        200adfe
        Merge pull request #11 from rezimero/feature/frontend-result-report-integration

PR #12 milestone commits:

    1ac7a0f fix secure tenant profile contract
    7558a73 feat integrate laboratory profile settings
    b005572 feat extend report signatory snapshot
    e32aec6 fix preserve multipart profile uploads
    9cf11fb fix render laboratory logo on reports
    eb5b299 feat add tenant laboratory brand colours
    81c1e67 Merge pull request #12 from rezimero/feature/laboratory-profile-report-signatory

The PR #12 feature branch was deleted locally and remotely after merge verification.

Verified post-merge state:

    local develop:
        81c1e67

    origin/develop:
        81c1e67

    worktree:
        clean

    ahead/behind:
        0 / 0

Current documentation branch:

    docs/close-out-laboratory-profile-milestone

The branch was created from `81c1e67` and was clean with no commits ahead of `origin/develop` before this documentation update.

The documentation branch must be reviewed, committed, pushed and merged into `develop` before the next product feature begins.

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

Latest confirmed backend baseline:

    138 tests passed
    3,597 assertions

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

Tenant laboratory profile:

- explicit tenant-profile resource
- authorized profile view and update requests
- dedicated logo, favicon and director-signature upload handling
- multipart upload field `file`
- administrator-only tenant-management boundary
- tenant-isolation contract tests

The exact route list must be verified from the current route definitions before changing the profile contract.

Server-generated PDF export remains deferred until a compatible renderer is deliberately selected.

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

Key modules:

    api/laboratoryProfileApi.ts
    components/LaboratoryBrandColors.tsx
    components/LaboratoryProfileForm.tsx
    components/LaboratoryProfileMedia.tsx
    hooks/useLaboratoryProfile.ts
    types/laboratoryProfile.types.ts
    utils/laboratoryBrandColors.ts
    utils/laboratoryProfileMapper.ts
    utils/profileErrorMessage.ts

Route-level page:

    lab-frontend/src/pages/LaboratoryProfile.tsx

Authorization boundary:

    lab-frontend/src/components/TenantAdminRoute.tsx

Report identity and signatory components:

    lab-frontend/src/features/laboratory-reports/components/ReportIdentitySection.tsx
    lab-frontend/src/features/laboratory-reports/components/ReportSignatureFooter.tsx
    lab-frontend/src/features/laboratory-reports/components/ReportPrintStyles.tsx

Report rendering must use the report snapshot rather than the live laboratory profile.

Brand colours are tenant-owned. Rezhel defaults are only starter or fallback values.

Clinical semantic colours remain separate from tenant branding.

---

## 11. Current Validation Baseline

Backend:

    138 tests passed
    3,597 assertions

Frontend:

    31 test files passed
    119 tests passed

Production validation:

    TypeScript validation passed
    Vite production build passed

Manual smoke validation:

- administrator opened the laboratory-profile settings page
- profile data saved successfully
- logo uploaded and rendered
- director signature uploaded and rendered
- tenant brand colours saved and previewed
- report view rendered tenant identity and signatory information
- print preview retained logo, branding and signature
- report fit on one printed page in the confirmed smoke scenario

Commands:

    php artisan test

    cd lab-frontend
    npm test -- --run
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

Known but non-blocking items:

- legacy test-result columns remain for compatibility
- legacy result rows may lack an order-item relationship
- old landlord result utilities may still use the legacy shape
- tenant authorization and landlord authorization use different user models
- the historical role-and-permission seeder does not describe the active tenant authorization path
- some test-support and orchestration files exceed the preferred line-count target
- server-generated PDF rendering and export are not implemented
- global tenant application theming is not implemented and was deliberately excluded from PR #12
- report-template customization is not implemented and requires a separate architecture decision

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

---

## 14. Current Blockers

No confirmed production blocker exists.

No backend test failure exists.

No frontend test failure exists.

No frontend typecheck or build failure exists.

No known uncommitted product-code change exists.

The only active closeout task is synchronization of the five core documentation files.

---

## 15. Current Milestone Status

Milestone:

    Tenant laboratory profile and immutable report branding

Status:

    secure backend tenant-profile contract completed
    tenant profile and media authorization completed
    frontend profile settings completed
    immutable signatory fields completed
    multipart upload handling corrected
    report logo rendering corrected
    tenant-owned brand colours completed
    focused and full backend tests passed
    focused and full frontend tests passed
    TypeScript validation passed
    production build passed
    browser and print smoke checks passed
    pull request #12 merged into develop
    merge commit 81c1e67 verified locally and remotely
    completed feature branch deleted locally and remotely
    documentation closeout in progress

No product-code change belongs on the documentation closeout branch.

---

## 16. Immediate Next Task

Complete the documentation closeout:

1. review the five updated documents
2. run `git diff --check`
3. verify that only the five intended documentation paths changed
4. stage the five exact paths
5. commit the documentation closeout
6. push the documentation branch
7. open and merge a documentation-only pull request into `develop`
8. update local `develop`
9. delete the documentation branch locally and remotely
10. freeze the scope and exact branch name for the next product feature

No next product feature is frozen in this document.

Server-generated PDF export remains a backlog item, not an active instruction.

---

## 17. Git Continuation

Current documentation branch:

    docs/close-out-laboratory-profile-milestone

Required base commit:

    81c1e67

Before committing documentation:

    git status -sb
    git diff --check
    git diff --name-status
    git diff --stat

Required changed paths:

    docs/AI_ENGINEER_BRIEF.md
    docs/ARCHITECTURE_DECISIONS.md
    docs/CURRENT_STATE.md
    docs/PROJECT_HANDOVER.md
    docs/TESTING_AND_OPERATIONS.md

Stage exact paths only.

Do not use:

    git add .

After the documentation pull request is merged:

    git switch develop
    git pull --ff-only origin develop
    git status -sb
    git log --oneline --decorate -8

Create the next product branch only after its scope and descriptive name are agreed.
