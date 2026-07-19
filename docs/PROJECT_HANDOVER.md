# Project Handover

## 1. Project Identity

This repository contains a multi-tenant Laboratory Information Management System built with Laravel.

The system is designed for independent laboratories operating on isolated tenant databases while platform-wide administration remains in a landlord database.

Repository:

    lab-management-system

Primary remote:

    https://github.com/rezimero/lab-management-system.git

Current backend milestone:

    Laboratory reporting - implementation complete

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
- reporting

The next development priority is the frontend Vite migration, followed by result and report API integration.

The backend result and reporting workflows are stable. Frontend build-tool migration and API integration are the next planned milestones.

---

## 3. Technology Stack

Current backend stack:

- Laravel 13.17.0
- PHP 8.5.6
- MySQL 8.4
- Laravel Sanctum 4.3.2
- Spatie Laravel Permission 8.1
- Spatie Laravel Multitenancy
- PHPUnit through Laravel Artisan testing

Development environment:

- Windows
- PowerShell
- Sublime Text
- UTF-8 without BOM

Frontend:

- React 19
- Create React App currently
- Vite migration is the next planned milestone

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

Tenant resolution is performed using the tenant domain.

The final tenant authentication flow is:

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

Models participating in public tenant routes should return:

    uuid

from `getRouteKeyName()`.

Current UUID-bound resources include:

- laboratory orders
- laboratory order items
- specimens
- test results

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

### Patient management

Completed:

- create
- list
- show
- update
- delete
- statistics

### Laboratory test catalogue

Completed:

- create
- list
- show
- update
- delete
- category listing

### Laboratory orders

Completed:

- order creation
- order listing
- order details
- multiple order items
- status state machine
- cancellation
- sample collection protection
- processing-readiness validation

### Specimens

Completed:

- multiple specimens per order
- many-to-many specimen and order-item links
- accession generation
- collection
- receiving
- rejection
- rejection reason validation
- query
- statistics
- details
- replacement
- replacement lineage
- duplicate replacement prevention
- processing-readiness restoration

### Test processing and results

Implemented in the completed result-processing milestone:

- start processing an order item
- one result per laboratory order item
- create a draft result
- update a draft result
- submit a result
- reject a submitted result
- edit a rejected result and return it to draft
- resubmit a corrected result
- approve a submitted result
- mark the order item completed after approval
- prevent order completion until every active item has an approved result
- audit entry, submission and review users
- audit entry, submission and review timestamps
- UUID test-result routes
- tenant-isolated result operations

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

An order cannot be changed to `sample_collected` through the generic status endpoint.

Specimen collection performs that transition.

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

## 11. Current Result API Contract

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

Legacy standalone result creation and deletion routes must not be reintroduced into the tenant workflow.

---

## 12. Result Schema Strategy

The existing `test_results` table originated as a standalone patient/test result table.

The current workflow modernizes it using four focused migrations:

1. Add order-item processing metadata.
2. Add result workflow columns.
3. Migrate legacy result statuses and audit data.
4. Enforce final workflow constraints.

New workflow columns include:

- uuid
- laboratory_order_item_id
- entered_by
- submitted_by
- reviewed_by
- submitted_at
- reviewed_at
- rejection_reason

Legacy columns remain temporarily for compatibility:

- patient_id
- lab_test_id
- user_id
- test_date
- completed_at

New workflow writes should use `laboratory_order_item_id`.

Legacy rows may have a null `laboratory_order_item_id`.

Active order-driven queries exclude rows that are not linked to an order item.

---

## 13. Current Code Organization

Result controllers:

    app/Http/Controllers/Api/Tenant/
        LaboratoryOrderItemProcessingController.php
        TestResultController.php
        TestResultWorkflowController.php

Result requests:

    app/Http/Requests/TestResult/
        IndexTestResultRequest.php
        StoreTestResultRequest.php
        UpdateTestResultRequest.php
        RejectTestResultRequest.php

Result services:

    app/Services/Tenant/TestResult/
        TestResultManagementService.php
        TestResultQueryService.php
        TestResultWorkflowService.php
        TestResultSubmissionValidator.php

Order processing service:

    app/Services/Tenant/LaboratoryOrder/
        LaboratoryOrderItemProcessingService.php
        LaboratoryOrderStatusService.php

Result resources:

    app/Http/Resources/
        TestResultResource.php
        LaboratoryOrderItemResource.php

Result routes:

    routes/api/tenant/test-results.php

Tenant authorization catalogue:

    app/Support/Tenant/TenantUserPermissionCatalog.php

---

## 14. Testing Architecture

Automated tenant tests:

1. Register a temporary tenant through the real registration API.
2. Provision a physical tenant database.
3. Run tenant migrations.
4. Create the tenant administrator.
5. Log in through the real login API.
6. Send API requests with bearer token and tenant domain.
7. Run assertions against API responses and tenant database state.
8. Restore landlord context.
9. Delete temporary tenant records.
10. Drop temporary tenant databases.

The test suite refuses to run unless the active landlord database is:

    lab_saas_landlord_test

Temporary tenant database deletion is guarded by strict database-name validation.

---

## 15. Frontend State

The frontend is located in:

    lab-frontend

Current frontend status:

- React 19
- Create React App
- reusable visual components exist
- result user interface is based on the legacy result model
- API integration requires alignment with the new order-item-driven result workflow
- Authorization header logging exists in `src/api/client.ts` and must be removed before production

Planned sequence:

1. backend result workflow - completed
2. backend laboratory reporting - completed
3. migrate frontend build tooling to Vite - next
4. integrate frontend with the result and report APIs

Do not perform broad frontend refactoring beyond the planned Vite migration and API integration.

### Backend laboratory reporting

The backend supports one immutable report record per completed laboratory order.

Active routes:

    POST /api/laboratory-orders/{laboratoryOrder}/report
    GET  /api/laboratory-orders/{laboratoryOrder}/report
    GET  /api/laboratory-reports
    GET  /api/laboratory-reports/{laboratoryReport}

Report generation requires every active order item to be completed with an approved result.

The report persists laboratory, patient, order and approved-result snapshots. It also records the generating tenant user and generation timestamp.

Report records have no update or delete API. PDF rendering and export are deferred until a compatible renderer is deliberately selected.

---

## 16. Known Technical Debt

Known but non-blocking items:

- legacy test-result columns remain
- legacy landlord result tools may still query the old result shape
- tenant authorization and platform authorization use different models
- the role-and-permission seeder still describes historical Spatie-based tenant permissions
- frontend result screens use the legacy model
- the frontend API client logs authorization headers
- some test-support files are near or slightly above the preferred 300-line review threshold
- PDF rendering and export are not yet implemented; issued reports currently use immutable JSON snapshots

Do not perform broad cleanup while completing and handing over the reporting milestone.

Refactor only when:

- a test proves a defect
- security is affected
- data integrity is affected
- the current feature is blocked
- an agreed architecture rule is violated

---

## 17. Source-of-Truth Priority

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

## 18. New Engineer Handover Procedure

A new engineer or AI assistant must first read:

1. `docs/AI_ENGINEER_BRIEF.md`
2. `docs/PROJECT_HANDOVER.md`
3. `docs/ARCHITECTURE_DECISIONS.md`
4. `docs/CURRENT_STATE.md`
5. `docs/TESTING_AND_OPERATIONS.md`

Before modifying code, the engineer must confirm:

- current Git branch
- current Git status
- latest commits
- active landlord database
- current passing focused tests
- the next unfinished milestone task

No implementation should begin from assumptions.
