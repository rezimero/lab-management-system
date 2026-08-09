# Architecture Decisions

This document records decisions that should not be reopened without a clear technical reason, impact analysis and replacement plan.

Updated:

    30 July 2026

---

## ADR-001: Database-per-Tenant Isolation

Status:

    Accepted

Decision:

Each laboratory uses a separate physical MySQL database.

The landlord database stores platform-wide tenant metadata, tenant-profile records, media metadata and authentication token records.

Reason:

- strong tenant data isolation
- easier tenant backup and restoration
- reduced risk of accidental cross-tenant queries
- independent tenant lifecycle management

Consequence:

Every tenant request must resolve and activate the correct tenant database before tenant authentication and tenant-model queries.

Landlord-side tenant-profile and media queries must still be constrained to the resolved tenant.

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

Tenant permissions are evaluated through:

    App\Support\Tenant\TenantUserPermissionCatalog

The authenticated tenant model is:

    App\Models\Tenant\User

Reason:

The active tenant user model uses a role column and does not currently use Spatie's `HasRoles` trait.

Consequence:

Do not call `$tenantUser->can(...)` for tenant workflow authorization unless tenant role provisioning is redesigned.

Current result-role capabilities:

    admin
        view, create, edit, submit, approve, reject

    lab_technician
        view, create, edit, submit

    doctor
        view, approve, reject

    receptionist
        view

Laboratory-profile management uses the explicit catalogue capability:

    MANAGE_TENANT_PROFILE

Only tenant administrators and platform superadministrators may manage the tenant laboratory profile.

---

## ADR-012: Focused Migration Responsibilities

Status:

    Accepted

Decision:

Workflow and profile modernization use focused migrations with one clear responsibility.

Examples include separate migrations for:

1. order-item processing metadata
2. result workflow columns
3. legacy result data migration
4. final result constraints
5. report signatory profile fields

Reason:

A single oversized migration mixes schema, data and validation responsibilities and becomes difficult to review or recover.

Consequence:

Future workflow and tenant-profile migrations should remain focused and independently reviewable.

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

When modifying existing behaviour, tests must describe the intended behaviour before refactoring or changing the implementation.

Reason:

This prevents accidental regression disguised as cleanup.

Consequence:

Frontend and backend refactors must preserve or deliberately update their characterization boundaries.

---

## ADR-016: Focused Development Test Cycle

Status:

    Accepted

Decision:

During milestone development:

1. run syntax and whitespace checks
2. run focused feature tests
3. fix demonstrated failures
4. run affected regression tests
5. run the full relevant suite once the milestone is complete
6. run frontend typecheck and production build when applicable
7. perform browser or print smoke checks for user-visible rendering changes
8. create the milestone checkpoint
9. push, review and merge normally

Reason:

Running every suite after every small edit is too slow while skipping milestone validation is unsafe.

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
- one normal merge commit
- one documentation synchronization

The canonical integration branch and merged milestone baseline are recorded in `docs/CURRENT_STATE.md`.

Verified merged milestones include:

    frontend foundation:
        pull request #9
        merge commit f88f993

    frontend result/report integration:
        pull request #11
        merge commit 200adfe

    tenant laboratory profile and report branding:
        pull request #12
        merge commit 81c1e67

    laboratory-profile documentation closeout:
        pull request #13
        merge commit cce1e62

    immutable laboratory report PDF export:
        pull request #14
        merge commit 81e9c0066609691d7ec537ce166a4cde3389ec02

    core roadmap and CI baseline:
        pull request #15
        merge commit d0321d66b263f435711a9f58f6d4697a08373c7f

New product implementation work must begin from updated `develop` on a separate feature or milestone branch.

A documentation-only closeout branch may be created after a milestone merge to record the verified pull-request, merge and CI state. It must not contain product-code changes.

---

## ADR-018: Documentation as Project Memory

Status:

    Accepted

Decision:

Repository documentation is the durable handover mechanism between:

- ChatGPT accounts
- AI coding assistants
- human engineers
- future maintainers

Reason:

Conversation history and account memory are not portable or reliable project storage.

Consequence:

The five core documents under `docs/` must be synchronized before a completed milestone is handed over or the next product feature begins:

    AI_ENGINEER_BRIEF.md
    ARCHITECTURE_DECISIONS.md
    CURRENT_STATE.md
    PROJECT_HANDOVER.md
    TESTING_AND_OPERATIONS.md

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

- landlord-side laboratory profile
- tenant-owned primary, secondary and accent colours
- director name
- director qualification
- director registration number
- signatory designation
- accreditation details
- patient identity
- source order information
- approved result values
- result-entry, submission and review audit users

Before the report row is created, the generation service pre-generates the report UUID and copies the current profile logo and director signature into report-owned immutable storage:

    laboratory-reports/{report-uuid}/...

The laboratory snapshot records, when present:

- report-owned image path
- detected MIME type
- SHA-256 hash
- retained display URL for API compatibility

Report generation failure removes any copied report-media directory.

The report record also stores the generating tenant-user relationship and generation timestamp.

Issued report records are not updated or deleted when source records later change.

Browser reports and server-generated PDFs render from the persisted report snapshot.

Report authorization uses `TenantUserPermissionCatalog`:

- superadmin: view and generate
- admin: view and generate
- lab technician: view and generate
- doctor: view and generate
- receptionist: view only

Reason:

Historical clinical reports must remain reproducible after profile, patient, order, result, logo, signature or colour changes.

Consequence:

Replacing or deleting current tenant-profile media does not remove report-owned immutable media.

Reports issued before report-owned media snapshotting may lack immutable image paths. Those reports may omit images in server-generated PDFs rather than reading mutable live-profile media.

---

## ADR-020: Vite and TypeScript Frontend Toolchain

Status:

    Accepted

Decision:

The active React frontend uses Vite and TypeScript.

The validated frontend build command is:

    npm run build

which executes:

    tsc --noEmit && vite build

Reason:

- Vite provides the active development and production build pipeline
- TypeScript validation catches contract errors before bundling
- Vitest integrates with the current frontend toolchain
- obsolete Create React App assumptions must not guide future work

Consequence:

Documentation, scripts and implementation guidance must treat Vite as the source-of-truth frontend toolchain.

---

## ADR-021: Characterized Frontend Authentication Boundary

Status:

    Accepted

Decision:

Frontend authentication behaviour is protected by characterization tests before refactoring.

Authentication responsibilities are separated across:

    lab-frontend/src/contexts/AuthContext.tsx
    lab-frontend/src/auth/authTypes.ts
    lab-frontend/src/auth/authSessionMapper.ts
    lab-frontend/src/auth/authStorage.ts
    lab-frontend/src/api/client.ts

The API client must preserve:

- bearer-token attachment
- stale authorization-header removal
- tenant-domain header attachment for tenant users
- tenant-header exclusion for platform superadmins
- malformed stored-tenant cleanup
- 401 session clearing and redirect behaviour
- impersonated logout behaviour
- multipart `FormData` requests without forcing the JSON content type

Reason:

Authentication storage, interceptors, tenant context and multipart handling are security-sensitive integration boundaries.

Consequence:

Any future authentication or API-client change must first update or extend the existing characterization suites.

---

## ADR-022: Landlord Tenant-Management Layering

Status:

    Accepted

Decision:

Landlord tenant management is decomposed into explicit layers:

- API operations
- shared types
- selectors and aggregate calculations
- React Query and interaction orchestration
- presentational components
- existing form and password-reset dialogs

The orchestration boundary is:

    lab-frontend/src/components/landlords/useTenantManagement.ts

The route-level composition boundary is:

    lab-frontend/src/components/landlords/AllTenants.tsx

Reason:

The original component combined network operations, cache invalidation, filtering, dialog state, mutations and all visual markup in one file.

Consequence:

Future tenant-management changes should be made in the narrowest responsible module.

The following behaviour must remain characterized:

- array and paginated response support
- search by name, domain and database
- create and update
- activate and suspend
- exact-domain permanent-deletion confirmation
- administrator password reset
- maintenance impersonation
- cache invalidation
- mutation failure feedback

---

## ADR-023: Exact-Path Frontend Checkpoints

Status:

    Accepted

Decision:

Frontend refactor batches use:

- focused characterization tests
- full frontend tests
- TypeScript and Vite build validation
- `git diff --check`
- exact-path staging
- local checkpoint commits
- annotated recovery tags only at stable wave boundaries

Broad staging commands such as `git add .` are not used.

Reason:

Exact-path staging prevents unrelated reports, archives and investigation files from entering production commits.

Consequence:

Before each frontend commit, compare the intended file list with `git diff --cached --name-only`.

Repository-root investigation artifacts must remain untracked or be archived outside the repository.

---

## ADR-024: Explicit Tenant Laboratory-Profile Contract

Status:

    Accepted

Decision:

Tenant laboratory-profile reads and updates use explicit request authorization and an explicit API resource.

The contract includes laboratory identity, legal and accreditation details, signatory information and tenant-owned brand colours.

Only users with `MANAGE_TENANT_PROFILE` or platform-superadministrator authority may manage the profile.

Reason:

Implicit model serialization and controller-only authorization make it easier to expose internal fields or permit accidental cross-tenant access.

Consequence:

Future profile fields must be added deliberately to:

- landlord migration and model
- update validation
- API resource
- service mapping
- frontend types and mapper
- tenant-isolation and authorization tests
- report snapshot when the field must be historically immutable

---

## ADR-025: Secure Tenant Profile Media

Status:

    Accepted

Decision:

Laboratory logo, favicon and director-signature uploads use a shared secured profile-media contract.

The multipart field is:

    file

Accepted formats are:

- JPG
- JPEG
- PNG
- WEBP

Maximum file size:

    2 MB

SVG is rejected.

Media records use tenant ownership and UUID public identity.

Replacing or deleting profile media must clean up the superseded physical file through the media service.

Reason:

Tenant media is both an authorization boundary and an untrusted file-upload boundary.

Consequence:

Do not add direct public-path writes, bypass tenant ownership checks or accept arbitrary file types.

The frontend API client must preserve multipart boundaries by not forcing JSON headers onto `FormData` requests.

---

## ADR-026: Tenant-Owned Brand Colours and Report Scope

Status:

    Accepted

Decision:

Each subscribing laboratory owns its:

- primary colour
- secondary colour
- accent colour

Colours are stored as validated six-digit HEX values and normalized for display.

Rezhel supplies only starter or fallback values for missing or invalid data.

Brand colours currently apply to:

- laboratory-profile editing
- laboratory-profile live report preview
- report identity
- report divider and supporting identity text
- issued-report badge
- browser print rendering
- server-generated PDF rendering

Brand colours do not currently apply to:

- global application shell
- sidebar
- dashboard
- clinical semantic statuses
- custom CSS
- custom fonts
- arbitrary report templates

Issued reports render snapshot colours rather than current live-profile colours.

Reason:

A tenant's identity must not be confused with Rezhel's platform identity, and historical reports must not change after rebranding.

Consequence:

Any future expansion into global tenant theming or template customization requires a separate architecture decision, accessibility review and regression plan.

---

## ADR-027: Server-Generated Immutable Laboratory Report PDFs

Status:

    Accepted

Decision:

Rezhel generates downloadable laboratory-report PDFs on the server using:

- `barryvdh/laravel-dompdf` 3.1.2
- `dompdf/dompdf` 3.1.6

The tenant endpoint is:

    GET /api/laboratory-reports/{laboratoryReport}/pdf

The endpoint reuses the report-view authorization request and tenant-scoped UUID route binding.

The PDF renderer:

- reads the persisted report row and its four immutable snapshots only
- does not query current patient, order, result or tenant-profile records
- validates six-digit snapshot brand colours and applies safe fallbacks
- embeds only report-owned local image bytes
- requires the image path to remain under the current report UUID directory
- rejects traversal, backslashes and null bytes
- verifies detected MIME type against the snapshot MIME type
- verifies SHA-256 when a snapshot hash is present
- embeds accepted images as data URIs
- disables remote resources
- disables JavaScript
- disables PHP execution
- sanitizes the attachment filename

The React frontend downloads the PDF through the authenticated shared Axios client with `responseType: 'blob'`, creates a temporary object URL, clicks a temporary hidden anchor and revokes the object URL.

Reason:

A downloadable clinical report must preserve the exact issued snapshot, avoid remote-resource fetching and remain independent of later source-record or profile changes.

Consequence:

The server-generated PDF is the authoritative downloadable artifact for an issued report.

Browser printing remains available as a separate presentation path.

Legacy reports that lack report-owned media descriptors may render without logo or signature images. The implementation does not weaken immutability by substituting current live-profile media.

---

## ADR-028: Commercial and Integration Modules Are Core Product Scope

Status:

    Accepted

Decision:

Rezhel is a complete commercial and integrated Laboratory Information Management System.

The following are mandatory core product capabilities:

- clinical laboratory workflow
- report governance
- multi-branch and collection-centre operations
- SaaS subscriptions and feature entitlements
- pricing, invoicing, payments, receipts and account billing
- barcode labels and specimen chain of custody
- laboratory inventory and reagent management
- referral laboratory workflows
- notifications and critical-result escalation
- patient and clinician portals
- analyzer and instrument connectivity
- HL7, ASTM, FHIR and external-system interoperability
- operational, clinical and commercial analytics
- production operations and support controls

Commercial and integration capabilities must not be classified as optional enhancements.

Reason:

Order, specimen, report, branch, billing, inventory and integration models affect one another. Treating the commercial and integration layers as late optional additions would create avoidable schema changes, duplicated workflows and incompatible authorization boundaries.

Consequence:

Delivery follows this dependency order:

1. report amendment and supersession
2. multi-branch and collection-centre foundation
3. subscriptions and feature entitlements
4. pricing and financial workflows
5. barcode and chain of custody
6. inventory and reagents
7. referral laboratories
8. notifications
9. patient and clinician portals
10. analyzer integration
11. interoperability
12. analytics
13. production hardening

A change to this order requires a documented dependency review and architecture decision.

The project-wide completion baseline after pull request #14 is approximately 45 percent. This is a planning estimate, not a code-derived metric.

---

## ADR-029: GitHub Actions Continuous Integration Baseline

Status:

    Accepted and remotely validated

Decision:

The repository uses:

    .github/workflows/ci.yml

The workflow runs on:

- pull requests targeting `develop`
- pushes to `develop`
- manual workflow dispatch

The backend job uses:

- Ubuntu 24.04
- PHP 8.5
- MySQL 8.4
- `lab_saas_landlord_test`
- Composer manifest validation
- locked-dependency security audit
- platform-requirement validation
- landlord migration execution
- the complete Laravel test suite

The frontend job uses:

- Ubuntu 24.04
- Node.js 24
- deterministic `npm ci`
- the complete Vitest suite
- TypeScript validation
- the Vite production build

The workflow grants read-only repository-content permission and cancels superseded runs for the same branch or pull request.

Reason:

The remaining commercial and integration milestones will modify foundational models and services. Automated pull-request checks reduce the risk of merging tenant-isolation, clinical-workflow or frontend regressions.

Remote validation:

    pull request:
        #15

    workflow:
        Continuous Integration

    run:
        30633924711

    head:
        803c2b7582c83e0dc7d313589775453ea5d61c70

    conclusion:
        success

    Backend:
        passed

    Frontend:
        passed

    merge commit:
        d0321d66b263f435711a9f58f6d4697a08373c7f

Consequence:

- a pull request must not be merged while a configured CI job is failing
- CI complements local focused and characterization testing
- CI does not replace exact-path review or local milestone validation
- automated tests must never target the production landlord database
- CI-environment changes require the same review discipline as product configuration changes

---

<!-- BEGIN ADR-REPORT-AMENDMENT-SUPERSESSION-2026-08-01 -->

## ADR: Controlled Laboratory Report Amendment and Supersession

**Date:** 2026-08-01
**Status:** Accepted
**Decision owner:** Product and architecture
**Detailed contract:** `docs/REPORT_AMENDMENT_SUPERSESSION_ARCHITECTURE.md`

### Context

Issued laboratory reports are immutable clinical records. The product needs a
controlled way to correct an issued report without overwriting or deleting
historical clinical evidence.

### Decision

An amendment creates a new immutable report version. The preceding report
remains retrievable and downloadable and becomes superseded when a successor
is issued.

The report lineage uses:

- a stable `lineage_uuid`;
- sequential report `version` values beginning at 1;
- a unique self-reference to the immediately superseded report;
- unique report numbers using `-A1`, `-A2`, and later amendment suffixes;
- database constraints and locked tenant transactions that prevent duplicate
  versions and lineage branching.

Approved original test-result rows are never edited. Clinical corrections use
append-only result-amendment records with sequential effective versions and a
canonical source-payload hash that is revalidated at approval.

The amendment workflow has submitted, approved, and rejected states. It
preserves requester and reviewer snapshots, requires a meaningful reason,
allows only explicitly permitted snapshot fields, rejects identical changes,
and prohibits self-approval.

Permissions are split between requesting and approving amendments. Backend
authorization is authoritative; frontend visibility is only a convenience.

Approval occurs in one tenant transaction. It locks the request, source
report, order, current lineage report, and affected results; validates that
the source remains current; writes append-only corrections; allocates the next
report version; copies immutable report-owned media; creates the new report;
and records terminal workflow evidence. Domain events are dispatched only
after commit.

Order-level report lookup returns the current lineage version. UUID lookup and
PDF download return the exact historical version requested. Report lists hide
superseded versions by default and may include them through an explicit
authorized filter.

Browser and PDF output display Issued, Amended, or Superseded lifecycle
metadata while preserving the selected report version's immutable clinical,
laboratory, signatory, and media snapshots.

Existing reports are backfilled as version 1 with their UUID as the lineage
UUID. Existing report numbers remain unchanged. No legacy report is deleted or
regenerated.

### Consequences

The existing one-report-per-order constraint must be replaced by one report
per order and version. New amendment, result-correction, permission, API,
frontend, PDF, migration, concurrency, tenant-isolation, and regression tests
are required.

### Non-goals

This decision does not permit mutation or deletion of issued reports, mutation
of approved original results, self-approval, cross-tenant access, silent use of
current mutable source data, or universal analyzer and interoperability
claims.

<!-- END ADR-REPORT-AMENDMENT-SUPERSESSION-2026-08-01 -->

<!-- BEGIN ADR-030-REFERRAL-LABORATORY-MANAGEMENT -->

## ADR-030: Referral Laboratory Management Uses Order-Item Ownership and Standard Result Governance

Status:

    Accepted

Decision:

Referral laboratory workflows are tenant-scoped and belong to individual
laboratory order items.

External laboratories are tenant-owned directory records. They are not
branches, child tenants or tenant users.

External result evidence is preserved separately and append-only, but every
referred test must still create and pass through the existing standard
test-result workflow before the order item can complete or a report can be
generated.

One open referral per laboratory order item is enforced at service and
database levels.

Referral costs are internal operational costs and do not mutate patient
charges or issued invoices.

The binding schema, workflow, authorization, API, frontend, audit and testing
contract is recorded in:

    docs/REFERRAL_LABORATORY_MANAGEMENT_ARCHITECTURE.md

Reason:

A laboratory order can contain both internal and referred tests. Existing
result approval, item completion and immutable reporting are already
order-item scoped. Building a parallel external-result completion path would
weaken clinical governance and duplicate core lifecycle rules.

Consequence:

Referral implementation must preserve current order, specimen, result and
report status enums. Standard result approval remains the only path that
completes a referred item. Future courier, portal and interoperability modules
may build on the referral aggregate without changing its item-level clinical
ownership.

<!-- END ADR-030-REFERRAL-LABORATORY-MANAGEMENT -->
