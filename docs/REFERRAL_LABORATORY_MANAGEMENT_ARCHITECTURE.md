# Referral Laboratory Management and Send-Out Testing

Status: Approved architecture contract

Repository baseline:

    branch: feature/referral-laboratory-management
    commit: 6ff700a57c96b4b262c8deed111716169bd6ee02

Handoff archive:

    sha256: C78F1D5A7A26A56E4E01326C2889F7289476D3530C58CB9EB31E94561ADFF514

This contract is binding for the Referral Laboratory Management milestone.
Implementation must preserve the existing laboratory-order, specimen,
test-result, report, billing, branch, entitlement and tenant-isolation rules.

## 1. Purpose

The milestone shall provide tenant-scoped referral-laboratory and send-out
testing workflows for tests that are performed outside the tenant laboratory.

The minimum production-capable beta scope is:

- referral-laboratory directory
- referral-laboratory test and cost catalogue
- order-item send-out workflow
- specimen association and dispatch evidence
- external-result capture
- internal review through the existing result workflow
- referral cost, turnaround and status tracking
- branch-aware visibility
- subscription entitlement enforcement
- complete tenant isolation and audit evidence
- tenant frontend workspace and laboratory-order integration

Referral laboratories remain external organizations. They are not tenant
branches, collection centres, tenant users or child tenants.

## 2. Existing Architecture That Must Be Preserved

The following rules are non-negotiable:

- referral data remains in the current tenant database
- public API identifiers remain UUID-based
- tenant route binding must fail safely across tenants
- non-administrator users remain restricted to assigned branch networks
- laboratory orders keep their current statuses:
  `pending`, `sample_collected`, `in_progress`, `completed`, `cancelled`
- laboratory order items keep their current statuses:
  `pending`, `in_progress`, `completed`, `cancelled`
- a laboratory order item has at most one standard `test_results` row
- results continue through:
  `draft`, `submitted`, `approved`, `rejected`
- approving a result completes its laboratory order item
- an order completes only after every active item has an approved result
- report generation continues to require completed items and approved results
- issued reports and approved result corrections remain immutable
- patient billing remains based on the order-item `price_snapshot`
- referral cost is an internal operational cost and must not silently change
  the patient invoice
- referral entitlement enforcement uses
  `CommercialFeatureCatalog::REFERRAL_LABS`
- no empty navigation, fake data or unsupported external integration claim is
  permitted

## 3. Core Aggregate Boundary

A referral belongs to one `laboratory_order_item`.

This is the authoritative aggregate boundary because:

- one laboratory order may contain both internally processed and referred tests
- referral laboratory, cost and turnaround differ per test item
- the existing result and completion workflows are item-scoped
- report eligibility is item-scoped
- a referred test must still produce the standard result belonging to the same
  order item

The beta shall not create a second laboratory order for the external
laboratory.

The beta shall not attach one referral directly to an entire order.

A later shipment or courier module may group multiple referrals under one
dispatch manifest without changing this item-level clinical ownership.

## 4. Referral Laboratory Directory

A new `referral_laboratories` table shall store tenant-owned external
laboratory records.

Required fields:

    id
    uuid
    code
    name
    accreditation_number
    contact_name
    email
    phone
    address_line_1
    address_line_2
    city
    state
    country
    currency
    default_turnaround_hours
    is_active
    notes
    created_by
    updated_by
    created_at
    updated_at
    deleted_at

Required rules:

- `uuid` is unique
- `code` is unique inside the tenant database
- code is normalized to uppercase
- name and code are required
- currency is an uppercase ISO-style three-character code
- inactive laboratories cannot receive new referrals
- laboratories referenced by historical referrals are deactivated or
  soft-deleted, never hard-deleted
- directory edits do not change historical referral snapshots
- directory access is tenant-scoped
- directory management is administrator-only

A referral laboratory is not stored in `branches`.

## 5. Referral Laboratory Test Catalogue

A new `referral_laboratory_tests` table shall map the tenant's laboratory test
catalogue to an external laboratory offering.

Required fields:

    id
    uuid
    referral_laboratory_id
    lab_test_id
    external_test_code
    external_test_name
    cost
    currency
    turnaround_hours
    specimen_requirements
    is_active
    created_by
    updated_by
    created_at
    updated_at
    deleted_at

Required constraints:

- `uuid` is unique
- `(referral_laboratory_id, lab_test_id)` is unique
- the mapped local `lab_test_id` must be active when a mapping is created
- the external laboratory must be active when a mapping is created or
  reactivated
- cost is non-negative with no more than two decimal places
- turnaround is nullable or a positive whole number of hours
- `specimen_requirements` is nullable JSON
- historical referrals use snapshots and therefore remain stable when the
  catalogue changes
- inactive mappings cannot create new referrals

The local `LabTest` remains the clinical definition used by order snapshots,
parameter validation and report generation.

## 6. Referral Record

A new `laboratory_referrals` table shall control one send-out workflow.

Required fields:

    id
    uuid
    referral_number
    laboratory_order_item_id
    referral_laboratory_id
    referral_laboratory_test_id
    dispatch_branch_id
    open_order_item_key
    status
    laboratory_snapshot
    test_snapshot
    quoted_cost
    final_cost
    currency
    external_reference
    dispatch_notes
    turnaround_due_at
    created_by
    dispatched_by
    acknowledged_by
    result_received_by
    completed_by
    rejected_by
    cancelled_by
    created_at
    updated_at
    dispatched_at
    acknowledged_at
    result_received_at
    completed_at
    rejected_at
    cancelled_at
    rejection_reason
    cancellation_reason

Required public numbering:

    REF-{YYYYMMDD}-{8-character uppercase suffix}

Required constraints:

- `uuid` is unique
- `referral_number` is unique
- `laboratory_order_item_id` references the stable local test item
- `dispatch_branch_id` is the order's processing branch
- `open_order_item_key` is nullable and unique
- while the referral is non-terminal, `open_order_item_key` contains the order
  item UUID
- terminal transitions clear `open_order_item_key`
- this permits historical referrals while preventing two simultaneous open
  referrals for one order item
- quoted cost and currency are immutable snapshots after dispatch
- final cost is nullable until result capture or completion
- `laboratory_snapshot` freezes directory identity and contact details
- `test_snapshot` freezes external code, name, cost, currency, turnaround and
  specimen requirements
- referral records cannot be hard-deleted

## 7. Referral Status Machine

Allowed statuses:

    draft
    dispatched
    acknowledged
    result_received
    completed
    rejected
    cancelled

Permitted transitions:

    draft -> dispatched
    draft -> cancelled

    dispatched -> acknowledged
    dispatched -> result_received
    dispatched -> rejected
    dispatched -> cancelled

    acknowledged -> result_received
    acknowledged -> rejected
    acknowledged -> cancelled

    result_received -> completed

    completed -> no transition
    rejected -> no transition
    cancelled -> no transition

Rules:

- `draft` is a locally prepared but not yet sent referral
- `dispatched` records that the specimen and request were sent
- `acknowledged` is optional confirmation from the external laboratory
- `result_received` means external result evidence has been captured locally
- `completed` is set only after the linked standard test result is approved
- `rejected` records an external-laboratory rejection or inability to perform
  the test
- `cancelled` records a local cancellation
- a terminal record remains permanent audit evidence
- a replacement referral may be created only after the previous referral is
  terminal
- status transitions use row locks and tenant transactions
- unsupported transitions return validation errors rather than silent changes

## 8. Order and Item Eligibility

A referral may be created only when:

- the subscription permits `referral_labs`
- the actor has referral-creation permission
- the order item exists in the current tenant
- the order is `in_progress`
- the order item is `in_progress`
- the order item is not completed or cancelled
- the item has at least one received specimen
- the item does not already have a `test_results` row
- the item does not already have an open referral
- the selected referral laboratory is active
- the selected external test mapping is active
- the mapping belongs to the same local lab test as the order item
- the actor can access the order's processing branch

The existing `LaboratoryOrderItemProcessingService` remains the authority for
starting an item.

Referral creation must not bypass specimen receiving or order processing
requirements.

## 9. Specimen Association

A new `laboratory_referral_specimens` table shall associate received specimens
with a referral.

Required fields:

    id
    laboratory_referral_id
    specimen_id
    specimen_snapshot
    linked_by
    created_at
    updated_at

Required constraints:

- `(laboratory_referral_id, specimen_id)` is unique
- the specimen belongs to the same laboratory order as the referral item
- the specimen is already linked to the referral item through
  `laboratory_order_item_specimen`
- the specimen status is `received`
- rejected specimens cannot be dispatched
- `specimen_snapshot` freezes accession number, type, container, collection
  metadata and relevant notes
- linking a specimen does not change the existing specimen status

The beta intentionally does not add `dispatched` to the existing specimen
status enum.

Physical aliquot accounting, courier manifests and full barcode chain of
custody remain separate future aggregates.

## 10. Persistent Audit Timeline

A new `laboratory_referral_events` table shall provide an immutable operational
timeline.

Required fields:

    id
    uuid
    laboratory_referral_id
    event_type
    from_status
    to_status
    actor_id
    actor_snapshot
    payload
    occurred_at
    created_at

Required rules:

- `uuid` is unique
- events are append-only
- events cannot be updated or deleted
- actor snapshot preserves name, email and role
- payload contains transition-specific evidence
- every referral creation and status transition writes one event inside the
  same tenant transaction
- application events may also be dispatched after commit for the future
  notification module, but the database timeline is authoritative

Required event types:

    created
    dispatched
    acknowledged
    external_result_received
    completed
    rejected
    cancelled

## 11. External Result Capture

A new `laboratory_referral_results` table shall preserve external-result
evidence separately from the standard internal result.

Required fields:

    id
    uuid
    laboratory_referral_id
    version
    supersedes_referral_result_id
    external_report_number
    results_data
    notes
    reported_at
    received_at
    received_by
    receiver_snapshot
    source_payload_hash
    test_result_id
    created_at

Required constraints:

- `uuid` is unique
- `(laboratory_referral_id, version)` is unique
- `supersedes_referral_result_id` is unique
- versions are linear and sequential
- version 1 has no predecessor
- later versions point to the immediately previous version
- rows are append-only and cannot be updated or deleted
- `source_payload_hash` is a SHA-256 hash of the canonical external payload
- parameter keys are validated against the local order item's frozen
  `parameters_snapshot`
- external result capture requires referral status `dispatched` or
  `acknowledged`
- the referral moves to `result_received` after successful capture
- the actor requires external-result capture permission

The beta supports a later external-result version only while the linked
standard result is `draft` or `rejected`.

A new external-result version is blocked while the standard result is
`submitted` or `approved`.

Corrections after internal approval must use the existing controlled result
and report amendment architecture rather than mutating referral evidence.

## 12. Standard Test Result Integration

External results do not bypass the existing clinical governance workflow.

On first external-result capture, one standard `test_results` row is created
for the same `laboratory_order_item_id`:

    status: draft
    results_data: normalized external results_data
    notes: referral source summary and external notes
    entered_by: receiving tenant user

The referral-result row stores the resulting `test_result_id`.

The standard result then follows the existing workflow:

    draft -> submitted -> approved
    submitted -> rejected -> draft

Required integration rules:

- normal result entry is blocked while an open referral exists
- referral result import is the only allowed result-creation path for an open
  referred item
- existing submission validation remains active
- existing result approval permission remains active
- approving the standard result completes the order item
- in the same approval transaction, a `result_received` referral becomes
  `completed`
- rejecting the standard result leaves the referral at `result_received`
- editing a rejected standard result does not alter historical external-result
  rows
- report eligibility remains unchanged
- reports continue to read approved standard results and immutable result
  snapshots

The `TestResultResource` shall expose a compact optional `referral_source`
summary when the result originated from a referral.

## 13. Cost and Margin Semantics

The order item's `price_snapshot` remains the patient-facing charge.

Referral costs are internal operational costs.

Required values:

- `quoted_cost`: frozen from the referral test catalogue at referral creation
- `final_cost`: actual amount confirmed at external-result capture or
  completion
- `currency`: frozen referral cost currency
- `patient_charge`: derived from the order-item price snapshot
- `gross_margin`: derived only when patient charge and referral cost currencies
  match

Rules:

- referral creation never updates the order total
- referral creation never updates an invoice
- changing referral cost never changes an issued invoice
- final cost cannot be negative
- no accounts-payable ledger is created in this milestone
- no automatic payment to a referral laboratory is recorded
- summary APIs may aggregate quoted and final costs by status and date

## 14. Branch Access

The referral dispatch branch is the order's `processing_branch_id`.

Administrators may access every tenant referral.

Non-administrator access requires assignment to either:

- the order's collection branch, or
- the order's processing branch

Mutation requires access to the processing branch.

Directory management remains tenant-wide and administrator-only.

Cross-branch UUID probing must return not found or forbidden consistently with
the existing order and inventory contracts.

## 15. Subscription Entitlement

`CommercialFeatureCatalog::REFERRAL_LABS` already exists.

The implementation shall:

- add `REFERRAL_LABS` to `enabledByDefault()`
- add one landlord migration that enables the feature for existing commercial
  beta plans
- enforce the entitlement in every referral controller
- add a frontend `ReferralAccessRoute`
- hide referral navigation when the feature is disabled
- return 403 before any referral mutation is written when entitlement is
  disabled

The existing landlord subscription test that needs an unimplemented feature
shall move to another still-unimplemented key such as `notifications`.

## 16. Permissions

The tenant permission catalogue shall gain:

    VIEW_REFERRALS
    MANAGE_REFERRAL_LABORATORIES
    CREATE_REFERRALS
    DISPATCH_REFERRALS
    CAPTURE_REFERRAL_RESULTS

Role allocation:

    superadmin
        all referral permissions

    admin
        all referral permissions

    lab_technician
        VIEW_REFERRALS
        CREATE_REFERRALS
        DISPATCH_REFERRALS
        CAPTURE_REFERRAL_RESULTS

    receptionist
        VIEW_REFERRALS

    doctor
        VIEW_REFERRALS

Existing `APPROVE_TEST_RESULTS` remains the permission for approving the
standard result created from an external result.

No new referral-specific clinical approval role is introduced.

## 17. Backend Service Boundaries

Required services:

    ReferralLaboratoryService
        directory and external-test catalogue mutations

    ReferralQueryService
        directory, mappings, referral list, detail, timeline and summary

    ReferralBranchAccessService
        branch-aware read and mutation scope

    ReferralNumberService
        unique REF number generation

    ReferralCreationService
        eligibility, snapshots, specimen links and created event

    ReferralWorkflowService
        dispatch, acknowledge, reject and cancel transitions

    ReferralResultService
        external payload validation, versioning and standard result import

    ReferralCompletionService
        result-approval integration and completed event

    ReferralActorSnapshotFactory
        stable audit actor snapshots

    ReferralPayloadHasher
        canonical SHA-256 external-result hashes

Controllers shall remain thin.

All mutations use `DB::connection('tenant')->transaction()` and row locks.

## 18. Model Relationships

Required relationships include:

    LaboratoryOrderItem
        hasMany laboratoryReferrals
        hasOne currentOpenReferral

    Specimen
        belongsToMany laboratoryReferrals

    TestResult
        hasOne originatingReferralResult

    Branch
        hasMany dispatchedReferrals

    ReferralLaboratory
        hasMany testMappings
        hasMany referrals

    ReferralLaboratoryTest
        belongsTo referralLaboratory
        belongsTo labTest
        hasMany referrals

    LaboratoryReferral
        belongsTo orderItem
        belongsTo referralLaboratory
        belongsTo referralLaboratoryTest
        belongsTo dispatchBranch
        belongsToMany specimens
        hasMany events
        hasMany externalResults
        hasOne latestExternalResult

    LaboratoryReferralResult
        belongsTo referral
        belongsTo superseded result
        hasOne successor
        belongsTo standard testResult

Public referral, laboratory, mapping and external-result models use UUID route
binding.

## 19. API Contract

All routes remain inside the existing tenant-context and Sanctum middleware
group.

Directory:

    GET    /api/referrals/laboratories
    POST   /api/referrals/laboratories
    GET    /api/referrals/laboratories/{laboratory}
    PUT    /api/referrals/laboratories/{laboratory}

Mappings:

    GET    /api/referrals/laboratories/{laboratory}/tests
    POST   /api/referrals/laboratories/{laboratory}/tests
    PUT    /api/referrals/laboratory-tests/{mapping}

Referral workspace:

    GET    /api/referrals
    GET    /api/referrals/summary
    POST   /api/referrals
    GET    /api/referrals/{referral}

Workflow:

    POST   /api/referrals/{referral}/dispatch
    POST   /api/referrals/{referral}/acknowledge
    POST   /api/referrals/{referral}/reject
    POST   /api/referrals/{referral}/cancel
    POST   /api/referrals/{referral}/results

Order-item integration:

    GET    /api/laboratory-order-items/{laboratoryOrderItem}/referrals

There is no public `complete` action.

Completion occurs only through approval of the linked standard result.

## 20. Request Validation

Required validation includes:

- UUID existence checks on the tenant connection
- normalized codes, email and currency
- exact allowed referral statuses
- meaningful rejection and cancellation reasons
- no more than two monetary decimal places
- received specimen UUID array with no duplicates
- specimen ownership and item linkage verified inside the service transaction
- external result payload uses the same shape accepted by standard test results
- unknown result parameter keys are rejected
- `reported_at` cannot be unreasonably later than `received_at`
- pagination is bounded at 100 records
- date ranges use `after_or_equal`
- mutation requests fail before writes when entitlement or permission is absent

## 21. API Resources

`ReferralLaboratoryResource` shall include:

- uuid, code, name and active state
- accreditation and contact details
- currency and default turnaround
- mapped-test count
- timestamps

`ReferralLaboratoryTestResource` shall include:

- uuid
- local lab-test summary
- external code and name
- cost, currency and turnaround
- specimen requirements
- active state

`LaboratoryReferralResource` shall include:

- uuid and referral number
- status
- order and item summary
- patient summary where loaded
- dispatch branch
- laboratory snapshot
- test snapshot
- quoted cost, final cost, currency and derived same-currency margin
- external reference
- turnaround due date and overdue flag
- specimen snapshots
- latest external-result summary
- linked standard result summary
- status timestamps
- immutable event timeline when loaded

`LaboratoryOrderItemResource` shall expose:

    referral
        null or a compact current/latest referral summary

This allows the existing order view to render referral state without replacing
the referral workspace.

## 22. Frontend Contract

New route:

    /referrals

New protected route component:

    ReferralAccessRoute

New page:

    ReferralLaboratories

The page shall provide:

- referral status summary cards
- searchable and filterable referral list
- referral details and audit timeline
- overdue turnaround indication
- quoted and final cost display
- referral-laboratory directory
- external-test mapping management for administrators
- external-result capture for authorized users

Laboratory order integration shall provide:

- `Refer test` action only for eligible in-progress items
- laboratory and mapping selection
- received specimen selection
- referral status chip
- external reference and due date
- navigation to the referral detail
- no local `Enter result` action while an open referral is awaiting an
  external result
- normal result review actions after external result import

Navigation:

- group: `Commercial operations`
- label: `Referral laboratories`
- route: `/referrals`
- visible only when entitlement and role allow viewing

The frontend must not assume that navigation visibility is authorization.

## 23. Frontend Feature Structure

Required structure:

    lab-frontend/src/components/ReferralAccessRoute.tsx

    lab-frontend/src/features/referrals/api/referralApi.ts
    lab-frontend/src/features/referrals/types/referral.types.ts
    lab-frontend/src/features/referrals/permissions/referralPermissions.ts
    lab-frontend/src/features/referrals/hooks/useReferralWorkspace.ts

    lab-frontend/src/features/referrals/components/
        ReferralSummaryCards.tsx
        ReferralList.tsx
        ReferralDetailPanel.tsx
        ReferralTimeline.tsx
        ReferralCreateDialog.tsx
        ReferralResultDialog.tsx
        ReferralLaboratoryDirectory.tsx
        ReferralLaboratoryTestMappings.tsx

    lab-frontend/src/pages/ReferralLaboratories.tsx

React Query keys must be rooted at:

    ['referrals']

Mutations shall invalidate referral, laboratory-order and test-result query
families where relevant.

## 24. Immutability and Deletion

The following records are immutable after creation:

- referral events
- external referral-result versions
- historical laboratory and test snapshots
- completed, rejected and cancelled referrals except for loaded relationships

Referral laboratories and mappings use deactivation and soft deletion.

Referral workflow rows are never hard-deleted.

Deleting an order or order item must not cascade away referral audit evidence.
Existing order soft deletion remains the normal behavior; foreign keys for
referral audit records use restriction.

## 25. Concurrency and Transaction Rules

All workflow mutations shall:

- select the referral with `lockForUpdate`
- lock the order item when open-referral ownership may change
- lock the standard result during external version import
- validate current status after locks are acquired
- write the state change and event in the same transaction
- retry deadlocks consistently with existing clinical workflow services

The unique nullable `open_order_item_key` remains the database-level guard
against concurrent open-referral creation.

Referral number generation retries collisions.

External-result version numbers are allocated under a referral row lock.

## 26. Required Backend Tests

Focused backend coverage shall include:

Directory and mapping:

- administrator creates and updates a referral laboratory
- non-administrator cannot manage the directory
- inactive laboratory cannot receive a new mapping or referral
- mapping must match an active local lab test
- referral catalogue is tenant-isolated

Referral creation:

- eligible item creates a draft referral with snapshots
- item without received specimen is rejected
- pending or completed item is rejected
- mapping for another test is rejected
- unassigned branch actor is rejected
- second open referral for the same item is rejected
- entitlement-disabled mutation produces no writes

Workflow:

- draft dispatch succeeds and creates an event
- acknowledge is optional and valid
- result can be captured directly from dispatched
- invalid transitions are rejected
- reject and cancel require meaningful reasons
- terminal referrals cannot transition
- terminal referral releases the open-item constraint

External results:

- capture creates immutable referral-result version 1
- capture creates one standard draft test result
- unknown parameters are rejected
- normal result creation is blocked for an open referral
- a new external version is allowed only for draft or rejected standard result
- submitted or approved result blocks a new external version
- standard result rejection preserves `result_received`
- standard result approval completes item and referral atomically
- order completion and report generation continue to work unchanged

Cost and visibility:

- patient order and invoice totals do not change
- final referral cost is tracked separately
- same-currency margin is derived correctly
- branch-scoped user cannot read another branch referral
- records are isolated between tenant databases

Audit:

- every transition creates one immutable event
- event actor snapshots remain stable
- referral events and external-result versions cannot be deleted

## 27. Required Frontend Tests

Required coverage includes:

- referral route blocks disabled entitlement
- allowed role can open referral workspace
- navigation appears only when entitlement and role permit
- referral list renders API data and filters
- administrator manages directory and mappings
- technician creates and dispatches an eligible referral
- external result capture submits the correct payload
- order view hides local result entry while referral is awaiting results
- order view displays referral status and link
- workflow API errors remain visible without closing the active dialog
- tenant-entitlement API failures produce a safe access state

## 28. Required Existing Regression Coverage

The focused milestone validation must also run these existing contracts:

- laboratory-order creation and status transition
- specimen receiving and replacement
- test-result creation, submission, rejection and approval
- order completion
- laboratory-report generation
- billing commercial flow
- branch commercial flow
- landlord subscription commercial flow
- tenant entitlement isolation

A full historical test-suite rerun is not required after every correction.

One final regression gate is sufficient unless a shared clinical service is
changed after that gate.

## 29. Implementation Sequence

### Phase 1 — schema and commercial gates

- tenant migration for referral tables
- landlord migration enabling `referral_labs`
- models and relationships
- permission catalogue
- commercial feature defaults
- immutable model guards

### Phase 2 — directory and mappings

- requests, resources, services and controllers
- directory and mapping routes
- administrator and tenant-isolation tests

### Phase 3 — referral workflow

- creation, number, branch-access and query services
- specimen association
- persistent event timeline
- dispatch, acknowledge, reject and cancel actions
- workflow tests

### Phase 4 — external-result integration

- payload hasher
- external-result versioning
- standard draft result import
- normal result-entry guard
- test-result approval completion integration
- order, result and report regression tests

### Phase 5 — frontend

- entitlement route
- API and types
- workspace
- directory and mapping administration
- order-item referral action and summary
- focused Vitest coverage
- TypeScript typecheck

### Phase 6 — documentation and release

- update architecture decisions
- update current state and handover
- focused validation
- exact-path staging
- local commit
- push, pull request and normal merge commit

## 30. Explicit Non-Goals

The following are not part of this milestone:

- electronic API integration with external laboratories
- HL7, ASTM or FHIR result exchange
- automatic email or portal delivery to referral laboratories
- courier routing and GPS tracking
- barcode dispatch manifests
- aliquot volume accounting
- referral-laboratory user accounts
- external-laboratory login
- automatic accounts-payable settlement
- insurer claim submission
- patient or clinician portals
- analyzer integration
- editing approved test results in place
- changing issued reports without the existing amendment workflow
- treating a referral laboratory as a tenant branch
- grouping multiple referrals into one shipment aggregate

These capabilities may build on this foundation later without changing the
item-level clinical ownership defined here.

## 31. Binding Decisions

The architecture is approved with these binding decisions:

1. Referral ownership is one laboratory order item.
2. External laboratories are directory records, not branches or tenants.
3. Standard test results remain the only route to item completion and report
   generation.
4. External-result evidence is append-only and separate from the standard
   result.
5. Standard result approval completes the referral automatically.
6. Patient billing and referral cost remain separate.
7. Existing order, specimen, result and report status enums are not expanded.
8. One open referral per order item is enforced at service and database levels.
9. Referral timeline events are persistent and immutable.
10. Entitlement and branch access are enforced on the backend regardless of
    frontend visibility.
11. No hard deletion of referral workflow evidence is permitted.
12. The implementation may begin without another architecture approval round.
