# Architecture Decisions

This document records decisions that should not be reopened without a clear technical reason, impact analysis and replacement plan.

---

## ADR-001: Database-per-Tenant Isolation

Status:

    Accepted

Decision:

Each laboratory uses a separate physical MySQL database.

The landlord database stores platform-wide tenant metadata and authentication token records.

Reason:

- strong tenant data isolation
- easier tenant backup and restoration
- reduced risk of accidental cross-tenant queries
- independent tenant lifecycle management

Consequence:

Every tenant request must resolve and activate the correct tenant database before tenant authentication and model queries.

---

## ADR-002: Tenant Context Before Sanctum

Status:

    Accepted

Decision:

Tenant routes use middleware in this order:

    SetTenantContext:required
    auth:sanctum

Reason:

The tenant database must be active before the tenant user can be loaded.

Consequence:

Reversing the middleware order can authenticate against the wrong context or fail to resolve tenant users.

---

## ADR-003: Header-Based Tenant Resolution

Status:

    Accepted

Decision:

Tenant API requests use:

    X-Tenant-Domain

Fallback resolution may use a request or route domain only where explicitly supported.

Reason:

The domain is stable, readable and independent of internal database identifiers.

Consequence:

Tenant API clients must include the domain header on every authenticated tenant request.

---

## ADR-004: Tenant-Bound Sanctum Tokens

Status:

    Accepted

Decision:

Sanctum tokens are stored in the landlord database and include:

    tenant_id

Reason:

A bearer token must not be reusable across tenant contexts.

Consequence:

Token authentication must verify that the resolved tenant matches the token tenant.

---

## ADR-005: UUID Public Route Binding

Status:

    Accepted

Decision:

Tenant API resources expose UUIDs rather than numeric primary keys.

Reason:

- avoids exposing sequential IDs
- creates stable public identifiers
- supports tenant-safe route binding
- separates API contracts from database implementation

Consequence:

Models used in public routes should define UUID route binding.

---

## ADR-006: Order-Item-Driven Test Results

Status:

    Accepted

Decision:

A new test result belongs to a laboratory order item.

Relationship:

    LaboratoryOrderItem hasOne TestResult
    TestResult belongsTo LaboratoryOrderItem

Reason:

A laboratory result must be connected to:

- the patient through the order
- the ordered test through the order-item snapshot
- the specimen workflow
- the operational processing lifecycle

Consequence:

New result creation must not accept arbitrary patient, test or user IDs from the client.

---

## ADR-007: One Result Per Order Item

Status:

    Accepted

Decision:

Each laboratory order item may have no more than one result row.

Enforcement:

- application validation
- unique database constraint on `laboratory_order_item_id`

Reason:

Multiple independent result rows for one order item would create ambiguous approval and completion states.

---

## ADR-008: Result Workflow State Machine

Status:

    Accepted

Decision:

Result states are:

    draft
    submitted
    approved
    rejected

Transitions:

    draft → submitted
    submitted → approved
    submitted → rejected
    rejected → edited → draft

Reason:

The workflow separates data entry from clinical or supervisory review.

Consequence:

- submitted results are immutable
- approved results are terminal
- rejected results require correction before resubmission

---

## ADR-009: Approval Completes the Order Item

Status:

    Accepted

Decision:

Approving a result changes the linked order-item status to:

    completed

and records:

    completed_at

Reason:

The order item is operationally complete only after its result has been approved.

---

## ADR-010: Approved Results Required for Order Completion

Status:

    Accepted

Decision:

An in-progress laboratory order may be completed only when every active order item:

- is completed
- has a result
- has an approved result

Reason:

An order must not appear completed while active tests remain unapproved.

---

## ADR-011: Tenant Role Permission Catalogue

Status:

    Accepted for current tenant architecture

Decision:

Tenant result permissions are evaluated through:

    App\Support\Tenant\TenantUserPermissionCatalog

The authenticated tenant model is:

    App\Models\Tenant\User

Reason:

The active tenant user model uses a role column and does not currently use Spatie's `HasRoles` trait.

Consequence:

Do not call `$tenantUser->can(...)` for result workflow authorization unless tenant role provisioning is redesigned.

Current role capabilities:

    admin
        view, create, edit, submit, approve, reject

    lab_technician
        view, create, edit, submit

    doctor
        view, approve, reject

    receptionist
        view

---

## ADR-012: Focused Migration Responsibilities

Status:

    Accepted

Decision:

The result modernization uses separate migrations for:

1. order-item processing metadata
2. result workflow columns
3. legacy data migration
4. final constraints

Reason:

A single oversized migration mixed unrelated schema, data and validation responsibilities.

Consequence:

Future workflow migrations should remain focused and independently reviewable.

---

## ADR-013: Legacy Result Compatibility

Status:

    Accepted temporarily

Decision:

Legacy result columns remain nullable during the transition.

Legacy rows may remain without an order-item relationship.

Reason:

Existing data should not be destroyed merely because the new workflow is order-driven.

Consequence:

Active tenant result queries should include only order-driven rows where appropriate.

Legacy cleanup requires a separate planned milestone.

---

## ADR-014: Transactional Workflow Operations

Status:

    Accepted

Decision:

Processing start, result creation, result editing, submission, rejection and approval use tenant database transactions.

Rows involved in state transitions are locked where concurrency could produce duplicate or invalid state.

Reason:

Workflow operations must remain atomic under concurrent requests.

---

## ADR-015: Characterization Tests Before Behaviour Changes

Status:

    Accepted

Decision:

When modifying existing workflow behavior, existing tests must be updated or supplemented to describe the intended new behavior.

Reason:

This prevents accidental regression disguised as refactoring.

Example:

The previous order-completion characterization was updated because order completion now requires approved results.

---

## ADR-016: Focused Development Test Cycle

Status:

    Accepted

Decision:

During milestone development:

1. run syntax and whitespace checks
2. run focused feature tests
3. fix demonstrated failures
4. run the full suite once the milestone is complete
5. create the milestone checkpoint
6. push, review, merge and tag

Reason:

Running the full suite after every small edit is too slow while skipping it entirely is unsafe.

---

## ADR-017: Milestone Branch Strategy

Status:

    Accepted

Decision:

A milestone branch may contain several closely related features.

Local commits are safety checkpoints.

One substantial milestone should normally produce:

- one branch
- one GitHub push sequence
- one pull request
- one merge
- one baseline tag

Current branch:

    feature/laboratory-reporting

---

## ADR-018: Documentation as Project Memory

Status:

    Accepted

Decision:

The repository documentation is the durable handover mechanism between:

- ChatGPT accounts
- AI coding assistants
- human engineers
- future maintainers

Reason:

Conversation history and account memory are not portable or reliable project storage.

Consequence:

`docs/CURRENT_STATE.md` must be updated before each milestone is committed or handed over.

---

## ADR-019: Immutable Laboratory Report Snapshots

Status:

    Accepted

Decision:

Each completed laboratory order may issue one immutable laboratory report stored in the tenant database.

Report generation requires:

- a completed laboratory order
- at least one active order item
- every active order item to be completed
- every active order item to have an approved test result
- no previously issued report for the order

The report snapshots:

- landlord-side laboratory profile and branding
- patient identity
- source order information
- approved result values
- result-entry, submission and review audit users

The report record also stores the generating tenant-user relationship and generation timestamp.

Issued report records are not updated or deleted when source records later change.

The active reporting API returns JSON resources. PDF generation is deferred until a dedicated and compatible PDF renderer is selected.

Report authorization uses `TenantUserPermissionCatalog`:

- superadmin: view and generate
- admin: view and generate
- lab technician: view and generate
- doctor: view and generate
- receptionist: view only

Consequence:

Historical report data remains reproducible and tenant isolated. Future PDF output must render from the persisted report snapshot rather than live mutable clinical records.
