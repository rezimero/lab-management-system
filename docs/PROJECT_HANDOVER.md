# Project Handover

Updated:

    28 July 2026

## 1. Project Identity

This repository contains a multi-tenant Laboratory Information Management System built with Laravel and React.

The system is designed for independent laboratories operating on isolated tenant databases while platform-wide administration, tenant profiles, media metadata and authentication tokens remain in the landlord database.

Repository:

    lab-management-system

Latest completed product milestone:

    Tenant laboratory profile, secure media, immutable signatory details and tenant-owned report branding

GitHub closeout:

    Pull request:
        #12

    Merge commit:
        81c1e67

Previous product milestone:

    Frontend result and immutable-report integration

    Pull request:
        #11

    Merge commit:
        200adfe

Current validated status:

    Backend result, reporting and tenant-profile workflows are complete
    Frontend result, report and laboratory-profile integration is complete
    Printable browser report branding is complete
    Server-generated PDF export remains deferred

---

## 2. Product Purpose

The system manages the operational lifecycle of a diagnostic laboratory, including:

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
- printable report presentation

The backend result, reporting and profile workflows are stable.

The frontend result, report and profile integrations are complete.

The next product feature has not yet been frozen.

---

## 3. Technology Stack

Backend:

- Laravel 13.17.0
- PHP 8.5.6
- MySQL 8.4
- Laravel Sanctum 4.3.2
- Spatie Laravel Permission 8.1
- Spatie Laravel Multitenancy
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
- immutable logo and signature references
- generating-user audit
- UUID route binding
- tenant isolation
- view and generation authorization
- JSON report resources
- printable browser report presentation

Deferred:

- server-generated PDF rendering and export

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

    138 tests passed
    3,597 assertions

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

Tenant profile:

- explicit profile resource
- authorized view and update requests
- secure logo, favicon and director-signature uploads
- multipart upload field `file`
- tenant isolation
- administrator-only management

Verify the current route definitions before changing or documenting exact tenant-profile route paths.

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

The report signatory landlord migration was validated through the test landlord environment. Deployment migrations must be executed deliberately in the intended environment and must not be conflated with automated-test setup.

---

## 13. Frontend State

Frontend root:

    lab-frontend

Canonical integration branch:

    develop

Latest merged product baseline:

    81c1e67
    pull request #12

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

Current frontend test baseline:

    31 test files passed
    119 tests passed

Current build baseline:

    tsc --noEmit passed
    vite build passed

Profile route:

    /settings/laboratory-profile

Profile feature root:

    lab-frontend/src/features/laboratory-profile

Report branding components:

    lab-frontend/src/features/laboratory-reports/components/ReportIdentitySection.tsx
    lab-frontend/src/features/laboratory-reports/components/ReportSignatureFooter.tsx
    lab-frontend/src/features/laboratory-reports/components/ReportPrintStyles.tsx

Brand colours are tenant-owned and report-scoped in the current architecture.

Rezhel starter values are fallback values only.

Issued reports render from immutable snapshot values rather than the current live profile.

Planned sequence status:

1. backend result workflow - completed
2. backend laboratory reporting - completed
3. frontend Vite migration - completed
4. frontend authentication foundation - completed
5. landlord tenant-management foundation - completed
6. frontend result and report integration - completed
7. secure laboratory profile and report branding - completed
8. documentation closeout - active
9. next product feature - not yet frozen
10. compatible server-side PDF renderer - deferred backlog

---

## 14. Known Technical Debt

Known but non-blocking items:

- legacy test-result columns remain
- legacy landlord result tools may still query the old result shape
- tenant authorization and platform authorization use different models
- the role-and-permission seeder still describes historical Spatie-based tenant permissions
- some test-support and orchestration files exceed the preferred line-count target
- server-generated PDF rendering and export are not implemented
- global tenant application theming is not implemented
- report-template customization is not implemented

The last two items were intentionally excluded from PR #12 and require separate architecture and accessibility decisions.

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

---

## 15. Current Git and Recovery Context

Canonical integration branch:

    develop

Latest merged milestone:

    Pull request:
        #12

    Merge commit:
        81c1e67

The completed feature branch:

    feature/laboratory-profile-report-signatory

was deleted locally and remotely after merge verification.

Current documentation branch:

    docs/close-out-laboratory-profile-milestone

The documentation branch began from:

    81c1e67

After this documentation closeout is merged, begin the next implementation milestone from updated `develop` on a descriptive feature branch agreed for that scope.

Use exact-path staging.

Do not use:

    git add .

Do not include repository-root investigation reports or archives in production commits.

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
