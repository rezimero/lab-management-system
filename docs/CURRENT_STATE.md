# Current Project State

Updated:

    23 July 2026

This document is the current repository handover snapshot.

Update it before every milestone commit, push, merge, tag or transfer to another engineer or AI assistant.

---

## 1. Current Git Context

Repository:

    lab-management-system

Canonical integration branch:

    develop

Merged frontend foundation milestone:

    Pull request:
        #9

    Merge commit:
        f88f993
        Merge pull request #9 from rezimero/feature/frontend-vite-migration

    Final milestone tag:
        frontend-vite-foundation-complete

The feature branch was merged and removed locally and remotely after verification.

The documentation closeout is performed on a short-lived documentation-only branch and must be merged back into `develop` before the next implementation branch is created.

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

Milestone commit sequence:

    f88f993 Merge pull request #9 from rezimero/feature/frontend-vite-migration
    de13940 docs synchronize frontend migration handover
    e06166e refactor landlord tenant management presentation
    e7d3a90 refactor landlord tenant management orchestration
    076e3d5 test stabilize landlord tenant management characterization
    e80123c test characterize landlord tenant management
    f01fe8a refactor frontend API authentication storage
    f002eee test characterize frontend API client interceptors
    f35e09b refactor frontend authentication session storage
    4cabf8c test characterize frontend authentication context
    64495da chore remove obsolete backup source files
    6c9475b refactor Vite frontend application shell
    5499877 refactor landlord cross-tenant management
    4f40083 refactor tenant user governance services
    ac67b88 Migrate frontend tooling from CRA to Vite

The merged milestone was verified as contained in `develop`.

The repository worktree was clean immediately after the merge, tag push and feature-branch deletion.

Archived investigation material is outside the repository under:

    C:\Users\Rezi\Documents\Rezhel-Refactor-Artifacts\20260723

---

## 2. Non-Negotiable Architecture

Preserve:

- database-per-tenant MySQL isolation
- landlord database for tenant metadata and Sanctum tokens
- `X-Tenant-Domain` tenant resolution
- `SetTenantContext:required` before `auth:sanctum`
- tenant-bound Sanctum tokens
- `App\Models\Tenant\User` as the authenticated tenant model
- UUID public route binding
- order-item-driven test results
- one result per laboratory order item
- result workflow state machine
- approved-result order-completion guard
- immutable laboratory report snapshots
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

Latest confirmed backend baseline:

    133 tests passed
    3,458 assertions

This backend baseline was not rerun during the frontend documentation synchronization.

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

PDF rendering remains deferred until a compatible renderer is deliberately selected.

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

## 8. Completed Frontend Foundation Milestones

### Vite application baseline

Completed:

- Vite application shell
- TypeScript build validation
- Vitest test runner
- obsolete backup source removal
- stable baseline tag

Checkpoint:

    frontend-vite-baseline
    64495da

### Authentication characterization and refactor

Completed:

- `AuthContext` behavior characterization
- API-client interceptor characterization
- authentication type extraction
- API session mapping extraction
- authentication storage extraction
- API authentication storage consolidation
- bearer-token and tenant-header behavior preservation
- 401 and impersonation behavior preservation

Checkpoints:

    4cabf8c test characterize frontend authentication context
    f35e09b refactor frontend authentication session storage
    f002eee test characterize frontend API client interceptors
    f01fe8a refactor frontend API authentication storage
    frontend-auth-refactor-wave-1

### Landlord tenant-management characterization and refactor

Completed:

- 14-test tenant-management characterization boundary
- test stabilization without global timeout increases
- tenant API operation extraction
- tenant types and selector extraction
- React Query and interaction orchestration extraction
- presentation decomposition
- exact-domain deletion protection preservation
- tenant maintenance impersonation preservation

Checkpoints:

    e80123c test characterize landlord tenant management
    076e3d5 test stabilize landlord tenant management characterization
    e7d3a90 refactor landlord tenant management orchestration
    e06166e refactor landlord tenant management presentation
    frontend-tenant-management-wave-1
    frontend-tenant-management-wave-2

---

## 9. Frontend Authentication Structure

Current files:

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
- preserve impersonated logout behavior

---

## 10. Landlord Tenant-Management Structure

Route-level composition:

    lab-frontend/src/components/landlords/AllTenants.tsx

Current line count:

    237

The original component was:

    1,541 lines

Orchestration and data modules:

    tenantManagementApi.ts
    tenantManagementSelectors.ts
    tenantManagementTypes.ts
    useTenantManagement.ts

Presentation modules:

    TenantManagementStates.tsx
    TenantManagementHero.tsx
    TenantSummaryCards.tsx
    TenantPortfolio.tsx
    TenantPortfolioRow.tsx
    TenantActionMenu.tsx
    TenantConfirmationDialog.tsx

Existing focused dialogs remain:

    TenantFormDialog.tsx
    TenantPasswordResetDialog.tsx

Characterized behavior:

- loading and error states
- array and paginated tenant response shapes
- aggregate statistics
- empty portfolio
- retry
- case-insensitive search by name, domain and database
- create
- update
- suspend
- activate
- exact-domain permanent deletion
- administrator password reset
- maintenance impersonation and dashboard navigation
- mutation failure feedback
- tenant and landlord dashboard cache invalidation

`useTenantManagement.ts` remains above the preferred 300-line review threshold but is cohesive and validated. Do not split it merely for line count.

---

## 11. Current Frontend Validation Baseline

Focused tenant-management suite:

    1 test file passed
    14 tests passed

Complete frontend suite:

    5 test files passed
    47 tests passed

Passing files:

    src/contexts/AuthContext.test.tsx
        15 tests

    src/components/PrivateRoute.test.tsx
        4 tests

    src/components/landlords/AllTenants.test.tsx
        14 tests

    src/api/client.test.ts
        13 tests

    src/App.test.tsx
        1 test

Production build:

    TypeScript validation passed
    Vite build passed

Commands:

    cd lab-frontend
    npm test -- --run
    npm run build

---

## 12. Testing and Refactor Discipline

For each frontend behavior change:

1. inspect the active component, API client and context
2. locate or create characterization coverage
3. run the focused test
4. make the smallest responsible change
5. run the focused test again
6. run the full frontend suite
7. run `npm run build`
8. run `git diff --check`
9. stage exact paths only
10. compare intended paths with `git diff --cached --name-only`
11. commit only passing code
12. create a tag only at a stable wave boundary

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
- frontend result screens still require alignment with the order-item-driven result API
- frontend report screens still require immutable-report API integration
- some test-support and orchestration files exceed the preferred line-count target
- PDF rendering and export are not implemented

Resolved items that must not remain in technical-debt lists:

- Vite migration
- Create React App removal as active tooling
- frontend authorization-header logging
- authentication-storage decomposition
- landlord tenant-management decomposition

---

## 14. Current Blockers

No confirmed production blocker exists.

No frontend test failure exists.

No frontend build failure exists.

No uncommitted source change exists.

---

## 15. Current Milestone Status

Milestone:

    Frontend Vite migration and foundation refactor

Status:

    Vite migration complete
    authentication foundation characterized and refactored
    landlord tenant management characterized and refactored
    focused tests passed
    full frontend suite passed
    production build passed
    pull request #9 merged into develop
    merge commit f88f993 verified
    final tag frontend-vite-foundation-complete pushed
    completed feature branch removed locally and remotely
    documentation closeout ready for its own review and merge

The frontend foundation milestone is complete. Do not add result or report integration changes to the documentation closeout branch.

---

## 16. Immediate Next Task

Before changing frontend result or report behavior:

1. locate the active result and report routes, screens and API calls
2. inspect their current legacy data assumptions
3. map the backend result and report contracts
4. create characterization tests for the current user-visible behavior
5. freeze the intended API integration boundary
6. implement the smallest integration batch
7. preserve tenant headers, authentication and route protection
8. run focused tests, the full frontend suite and the production build

Do not begin by broadly rewriting the result interface.

---

## 17. Git Continuation

Before beginning the next implementation milestone:

    git checkout develop
    git pull --ff-only origin develop
    git status --short
    git log -8 --oneline --decorate
    git tag --list "frontend-*"

Required starting branch:

    develop

Required merged frontend baseline:

    f88f993
    frontend-vite-foundation-complete

Required worktree:

    clean

After the documentation closeout is merged, create:

    feature/frontend-result-report-integration
