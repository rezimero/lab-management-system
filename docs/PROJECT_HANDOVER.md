# Project Handover

Updated:

    23 July 2026

## 1. Project Identity

This repository contains a multi-tenant Laboratory Information Management System built with Laravel and React.

The system is designed for independent laboratories operating on isolated tenant databases while platform-wide administration remains in a landlord database.

Repository:

    lab-management-system

Completed frontend milestone:

    Frontend Vite migration and foundation refactor

GitHub closeout:

    Pull request:
        #9

    Merge commit:
        f88f993

    Final tag:
        frontend-vite-foundation-complete

Current backend status:

    Result processing and immutable laboratory reporting are complete and validated

---

## 2. Product Purpose

The system manages the operational lifecycle of a diagnostic laboratory, including:

- tenant onboarding
- tenant authentication
- laboratory users and roles
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

The Vite migration and frontend foundation refactors are complete.

The next development priority is characterization and integration of the frontend result and immutable-report APIs.

The backend result and reporting workflows are stable.

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

1. Read the tenant domain from `X-Tenant-Domain`.
2. Resolve the tenant in the landlord database.
3. Switch the active tenant database connection.
4. Read the bearer token from the landlord token table.
5. Confirm that the token belongs to the resolved tenant.
6. Load the tenant user from the active tenant database.
7. Bind the tenant user to the tenant authentication guard.

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

Platform superadmin remains a landlord-level role.

Do not replace the active tenant authorization path with Spatie permission calls unless tenant provisioning and tenant authentication are deliberately redesigned.

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
- generating-user audit
- UUID route binding
- tenant isolation
- view and generation authorization
- JSON report resources

Deferred:

- PDF rendering and export

Latest confirmed backend baseline:

    133 tests passed
    3,458 assertions

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

## 11. Active Result and Report API Contracts

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

---

## 13. Frontend State

Frontend root:

    lab-frontend

Canonical integration branch:

    develop

Merged milestone baseline:

    f88f993
    frontend-vite-foundation-complete

The completed feature branch was deleted locally and remotely after merge verification.

Current frontend status:

- Vite migration complete
- TypeScript validation active
- Vitest active
- Create React App is not the active toolchain
- authentication context and API interceptors characterized
- authentication storage decomposed
- landlord tenant management characterized and decomposed
- result and report interfaces still require active API integration

Current frontend test baseline:

    5 test files passed
    47 tests passed

Current build baseline:

    tsc --noEmit passed
    vite build passed

Authentication structure:

    lab-frontend/src/auth/authTypes.ts
    lab-frontend/src/auth/authSessionMapper.ts
    lab-frontend/src/auth/authStorage.ts
    lab-frontend/src/contexts/AuthContext.tsx
    lab-frontend/src/api/client.ts

Landlord tenant-management structure:

    lab-frontend/src/components/landlords/AllTenants.tsx
    lab-frontend/src/components/landlords/tenantManagementApi.ts
    lab-frontend/src/components/landlords/tenantManagementSelectors.ts
    lab-frontend/src/components/landlords/tenantManagementTypes.ts
    lab-frontend/src/components/landlords/useTenantManagement.ts
    lab-frontend/src/components/landlords/TenantManagementStates.tsx
    lab-frontend/src/components/landlords/TenantManagementHero.tsx
    lab-frontend/src/components/landlords/TenantSummaryCards.tsx
    lab-frontend/src/components/landlords/TenantPortfolio.tsx
    lab-frontend/src/components/landlords/TenantPortfolioRow.tsx
    lab-frontend/src/components/landlords/TenantActionMenu.tsx
    lab-frontend/src/components/landlords/TenantConfirmationDialog.tsx

Frontend recovery tags:

    frontend-vite-baseline
    frontend-auth-refactor-wave-1
    frontend-tenant-management-wave-1
    frontend-tenant-management-wave-2

Planned sequence:

1. backend result workflow - completed
2. backend laboratory reporting - completed
3. frontend Vite migration - completed
4. frontend authentication foundation - completed
5. landlord tenant-management foundation - completed
6. characterize current result and report screens - next
7. integrate result and report APIs
8. select and integrate a compatible PDF renderer

Do not perform broad frontend redesign before characterizing existing result and report behavior.

---

## 14. Known Technical Debt

Known but non-blocking items:

- legacy test-result columns remain
- legacy landlord result tools may still query the old result shape
- tenant authorization and platform authorization use different models
- the role-and-permission seeder still describes historical Spatie-based tenant permissions
- frontend result screens require order-item-driven API integration
- frontend report screens require immutable-report API integration
- some test-support and orchestration files exceed the preferred line-count target
- PDF rendering and export are not implemented

Resolved items that should not be reported as current debt:

- Vite migration
- Create React App as active tooling
- frontend authorization-header logging
- authentication-storage decomposition
- tenant-management decomposition

---

## 15. Current Git and Recovery Context

Canonical integration branch:

    develop

Merged frontend foundation baseline:

    Pull request:
        #9

    Merge commit:
        f88f993

    Final tag:
        frontend-vite-foundation-complete

Frontend recovery tags:

    frontend-vite-baseline
        64495da

    frontend-auth-refactor-wave-1
        f01fe8a

    frontend-tenant-management-wave-1
        e7d3a90

    frontend-tenant-management-wave-2
        e06166e

    frontend-vite-foundation-complete
        f88f993

The completed feature branch was deleted locally and remotely after merge verification.

After this documentation closeout is merged, begin the next implementation milestone from updated `develop` on:

    feature/frontend-result-report-integration

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
- latest commits and recovery tags
- active landlord database
- current passing focused tests
- current full-suite baseline
- the next unfinished milestone task

No implementation should begin from assumptions.
