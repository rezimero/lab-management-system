# Controlled Laboratory Report Amendment and Supersession

Status: Approved architecture contract with binding clarifications in section 22

Repository baseline:

    branch: develop
    commit: 56674f2c6a4ea81016f10d5d89530c8bd6da1d96

Inspection evidence:

    backend review:
        C:\Users\Rezi\AppData\Local\Temp\lab-management-system\report-amendment-architecture-inspection\report-amendment-backend-contract-review.txt
        lines: 6523
        sha256: 00BE10881C14DD8FCFCB9CD870D6E55E79354DC483398FDA440DEA9138FEBD61

    presentation review:
        C:\Users\Rezi\AppData\Local\Temp\lab-management-system\report-amendment-architecture-inspection\report-amendment-presentation-contract-review.txt
        lines: 5608
        sha256: 2D06540F7BC944641F872566772BDDF95CE833FEBBCDD377C46B498CA7D31E19

This document is an architecture proposal only.

No implementation branch exists.
No repository file has been modified.

## 1. Purpose

The amendment milestone shall permit a previously issued laboratory report
to be corrected without changing or deleting any historical issued report.

Every issued version remains an immutable clinical record.

An amendment creates:

- a controlled amendment workflow record
- append-only corrected result data where clinical values change
- a new immutable laboratory-report row
- an explicit lineage relationship to the superseded report
- complete authorization and audit evidence

The original report remains retrievable and downloadable.

## 2. Existing Architecture That Must Be Preserved

The following existing rules remain non-negotiable:

- tenant data remains inside the active tenant database
- public report and result identifiers remain UUID-based
- report snapshots remain immutable
- issued report rows cannot be updated
- issued report rows cannot be deleted
- browser and PDF rendering use report-owned snapshots
- report media remains report-owned and immutable
- PDF rendering never reads current mutable profile media
- report generation remains transactional
- concurrent issuance remains protected by row locks
- approved source results are never edited in place
- cross-tenant route binding must continue to fail safely

## 3. Report Lineage Model

The `laboratory_reports` table shall support multiple immutable versions for
one laboratory order.

The current unique constraint on `laboratory_order_id` shall be removed.

The table shall gain:

    lineage_uuid
        UUID shared by every report in one lineage

    version
        Unsigned integer beginning at 1

    supersedes_report_id
        Nullable self-referencing foreign key
        Points to the immediately previous report version

    amendment_reason
        Nullable text
        Required for versions greater than 1

    amendment_summary
        Nullable JSON
        Records the fields and result items changed

    amended_by
        Nullable tenant-user foreign key

    amendment_actor_snapshot
        Nullable JSON
        Preserves actor name, email and role if the user later changes

    amended_at
        Nullable timezone-aware timestamp

Required constraints:

- `uuid` remains unique
- `report_number` remains unique
- `(lineage_uuid, version)` is unique
- `(laboratory_order_id, version)` is unique
- `supersedes_report_id` is unique
- a report may directly supersede only one report
- a report may be directly superseded only once
- all reports in a lineage reference the same laboratory order
- version 1 has no predecessor or amendment metadata
- version N must supersede version N minus 1
- no lineage may branch
- no lineage may skip version numbers
- all self-references remain inside the current tenant database
- deletion remains restricted

The current report is derived as the lineage row without a successor.

No `is_current` flag shall be stored because changing that flag would mutate
an already issued report.

## 4. Report Numbering

The original report number remains unchanged.

Version 1:

    RPT-{order-number}

Amendment 1, which is report version 2:

    RPT-{order-number}-A1

Amendment 2, which is report version 3:

    RPT-{order-number}-A2

The original report number is the stable lineage display reference.

Every amended version receives a new unique report number and UUID.

PDF filenames continue to use the version-specific report number:

    RPT-{order-number}.pdf
    RPT-{order-number}-A1.pdf
    RPT-{order-number}-A2.pdf

## 5. Amendment Workflow Aggregate

A new `laboratory_report_amendments` table shall control the workflow.

Required fields:

    uuid
    source_report_id
    target_report_id
    status
    reason
    change_summary
    proposed_patient_snapshot
    proposed_order_snapshot
    proposed_results_snapshot
    requested_by
    requester_snapshot
    requested_at
    reviewed_by
    reviewer_snapshot
    reviewed_at
    rejection_reason
    created_at
    updated_at

Allowed statuses:

    submitted
    approved
    rejected

Rules:

- requests are submitted against one exact report UUID
- only the current report in a lineage may be amended
- one submitted request cannot target an already superseded report
- an approved request cannot be edited or deleted
- a rejected request remains as audit evidence
- approval records the newly created target report
- the requester cannot approve their own amendment
- the reason is mandatory and must contain meaningful explanatory text
- at least one report field or result value must actually change
- submitting an identical snapshot is rejected
- live patient, order, result or profile data is never silently substituted
- proposed snapshots are created explicitly from the source report snapshot
- every correction must be visible in the stored change summary

The workflow aggregate may change status during review.

Issued laboratory-report rows remain immutable throughout the workflow.

## 6. Corrected Result Data

Approved `test_results` rows shall not be edited in place.

Where an amendment changes clinical result values, a new append-only
`test_result_amendments` record shall be created.

Required fields:

    uuid
    laboratory_report_amendment_id
    test_result_id
    version
    supersedes_test_result_amendment_id
    source_payload_hash
    corrected_results_data
    corrected_notes
    reason
    amended_by
    amendment_actor_snapshot
    approved_by
    approval_actor_snapshot
    approved_at
    created_at

Rules:

- the original approved `test_results` row is effective version 1
- the first approved correction is effective version 2
- later corrections increment sequentially
- amendment rows are append-only after approval
- an amendment references the stable original `test_results` row
- corrected parameter names must match the source test definition
- unknown result items or parameters are rejected
- result corrections remain associated with the original order item
- a report snapshot records the effective result version used
- a report snapshot records the result-amendment UUID when applicable
- current result reads resolve the latest approved amendment
- historical report reads always use their own stored result snapshots
- no historical report changes when a later result correction is approved

## 7. Amendment Eligibility

An amendment request may be submitted only when:

- the source report exists in the current tenant
- the source report is the current report in its lineage
- the source order remains completed
- the source report has not already been superseded
- there is no other submitted amendment for the same source report
- every referenced order item belongs to the source report
- every referenced test result belongs to the source order item
- every corrected result is based on an approved result
- at least one explicit correction exists
- the reason is present
- the requester has amendment-request permission

An amendment may be approved only when:

- the request remains submitted
- the source report remains current
- the reviewer has amendment-approval permission
- the reviewer is not the requester
- every correction remains valid
- the lineage has not advanced concurrently
- the source report and order remain tenant-local
- the proposed snapshots pass the report snapshot contract

## 8. Authorization

The permission catalogue shall gain:

    request_laboratory_report_amendments
    approve_laboratory_report_amendments

Role allocation:

    superadmin
        request and approve

    admin
        request and approve

    doctor
        request and approve

    lab_technician
        request only

    receptionist
        neither request nor approve

Separation of duties is mandatory:

- a requester cannot approve their own amendment
- authorization is checked in backend requests and services
- frontend visibility is convenience only and never the security boundary
- inactive users receive no amendment permission
- cross-tenant UUIDs return not found or forbidden according to the existing
  tenant-isolation contract

## 9. Approval Transaction

Approval shall run inside one tenant-database transaction with retry support.

The service shall:

1. lock the amendment request
2. lock the source report
3. lock the source laboratory order
4. lock the latest report in the lineage
5. verify the source report is still current
6. lock every affected test result
7. validate reviewer authorization and separation of duties
8. validate the proposed snapshots and correction summary
9. create approved append-only test-result amendments
10. allocate the next report version and report number
11. allocate a new report UUID
12. copy immutable media from the source report-owned media directory
13. build the new report entirely from approved proposed snapshots
14. create the new immutable laboratory-report row
15. link it to the immediately superseded report
16. mark the workflow request approved
17. record the target report ID and reviewer evidence
18. commit
19. dispatch amendment and supersession events after commit

If the transaction fails, copied target-report media must be removed using
the same cleanup discipline as initial report generation.

The unique predecessor constraint is the final database protection against
concurrent branching.

## 10. Snapshot Rules

The amended report shall not rebuild itself from mutable live sources.

The new report shall begin with the source report snapshots.

Only explicitly approved corrections may change:

- patient snapshot fields
- order snapshot fields
- result snapshot fields
- report-level notes included in the amendment contract

The laboratory snapshot remains copied from the source report.

A later branding or signatory change does not silently alter an amendment.

Report-owned logo and signature files shall be copied from the source
report's immutable media into the new report UUID directory.

The target report must never read current tenant-profile media.

## 11. API Contract

New endpoints:

    POST /api/laboratory-reports/{report}/amendments
        Submit an amendment request

    GET /api/laboratory-reports/{report}/amendments
        List amendment requests for one report lineage

    GET /api/laboratory-report-amendments/{amendment}
        Retrieve one amendment request

    POST /api/laboratory-report-amendments/{amendment}/approve
        Approve and issue the amended report

    POST /api/laboratory-report-amendments/{amendment}/reject
        Reject an amendment request

    GET /api/laboratory-reports/{report}/lineage
        Retrieve all report versions in sequence

Existing endpoint behaviour:

    GET /api/laboratory-orders/{order}/report
        Returns the current report in the lineage

    GET /api/laboratory-reports/{report}
        Returns the exact historical report identified by UUID

    GET /api/laboratory-reports/{report}/pdf
        Downloads the exact historical report identified by UUID

    GET /api/laboratory-reports
        Returns current reports by default

The report index shall support:

    include_superseded=true

This filter allows authorized users to retrieve historical versions.

## 12. API Resource Fields

Every report resource shall add:

    lineage_uuid
    version
    amendment_number
    base_report_number
    lifecycle_status
    is_current
    supersedes
    superseded_by
    amendment
    lineage

`lifecycle_status` values:

    issued
    amended
    superseded

The amendment object shall expose:

    reason
    summary
    amended_at
    actor

The predecessor and successor objects shall expose only safe identifiers:

    uuid
    report_number
    version
    generated_at

Version 1 reports without a successor are `issued`.

Versions greater than 1 without a successor are `amended`.

Any report with a successor is `superseded`.

## 13. Browser Presentation

The report detail page shall display:

- report number
- version
- lifecycle status
- amendment number when applicable
- amendment reason
- amendment date
- amendment actor
- predecessor report link
- successor report link
- complete lineage history

Current version labels:

    ISSUED
    AMENDED - VERSION {version}

Historical version label:

    SUPERSEDED

A superseded report remains viewable but displays a prominent warning:

    This report has been superseded.
    Refer to {replacement-report-number}.

The report list shall:

- show current reports by default
- show version and lifecycle status
- provide an optional historical-version filter
- visually distinguish superseded versions
- open the exact selected UUID
- keep order-level navigation directed to the current version

The Amend Report action appears only when:

- the report is current
- the user has request permission
- no submitted amendment already exists

## 14. PDF Presentation

The PDF must preserve the selected report's immutable clinical content.

A current original report displays:

    ISSUED

A current amended report displays:

    AMENDED REPORT
    Version {version}
    Amendment reason
    Amendment date
    Supersedes {previous-report-number}

A historical report displays:

    SUPERSEDED
    Superseded by {replacement-report-number}

A supersession notice is lifecycle metadata.

It does not alter the historical patient, order, result, laboratory or
signatory snapshots.

The PDF must continue to:

- use only report-owned local images
- reject unsafe paths
- verify allowed MIME types
- verify stored hashes when present
- disable remote content
- disable JavaScript
- disable embedded PHP
- render without logo or signature when immutable media is unavailable

## 15. Legacy Reports

Existing reports shall be migrated as:

    lineage_uuid = report uuid
    version = 1
    supersedes_report_id = null
    amendment fields = null

Existing report numbers remain unchanged.

A legacy report is current unless a later report explicitly supersedes it.

Legacy reports issued before immutable media copying:

- remain viewable
- remain downloadable
- may omit logo or signature
- never substitute current profile media
- may be amended only after eligibility and snapshot validation succeeds

No legacy report is deleted or regenerated during migration.

## 16. Audit and Events

The amendment workflow must preserve:

- requester ID and snapshot
- reviewer ID and snapshot
- request timestamp
- review timestamp
- reason
- rejection reason
- change summary
- source report
- target report
- source result versions
- corrected result versions
- tenant context

After-commit domain events:

    LaboratoryReportAmendmentSubmitted
    LaboratoryReportAmendmentApproved
    LaboratoryReportAmendmentRejected
    LaboratoryReportSuperseded

External email, SMS, portal and health-system delivery are not implemented
in this milestone.

The events and metadata must remain suitable for later notification,
HL7, FHIR and external-system modules.

## 17. Required Backend Tests

Required migration and model coverage:

- legacy report lineage backfill
- unique lineage version
- unique order version
- unique predecessor
- invalid lineage branching rejection
- immutable report update rejection
- immutable report deletion rejection
- approved result-amendment immutability

Required authorization coverage:

- requester-role matrix
- approver-role matrix
- inactive-user rejection
- requester cannot self-approve
- tenant isolation for reports
- tenant isolation for amendment requests
- tenant isolation for result corrections

Required workflow coverage:

- current report can be submitted for amendment
- superseded report cannot be amended
- identical snapshots cannot be submitted
- amendment reason is required
- submitted request can be approved
- submitted request can be rejected
- approved request cannot be approved twice
- rejected request cannot be approved
- concurrent approval cannot branch the lineage
- corrected result versions are sequential
- original result remains unchanged
- new report snapshots use approved corrections
- source report snapshots remain unchanged
- media is copied from the source report
- media cleanup occurs after failed approval

Required query coverage:

- order route returns current report
- UUID route returns exact historical report
- default index excludes superseded versions
- historical filter includes superseded versions
- lineage endpoint returns ordered versions

## 18. Required PDF and Frontend Tests

Required PDF coverage:

- original current report displays Issued
- amended current report displays Amended Report
- superseded report displays replacement notice
- amended filename contains the amendment suffix
- historical clinical values remain unchanged
- amended clinical values appear only in the new report
- source report media remains unchanged
- target media belongs to the target UUID
- unsafe media remains rejected
- legacy reports remain downloadable

Required frontend coverage:

- report types include lineage metadata
- role permissions include request and approval actions
- current report shows amendment action when authorized
- superseded report hides amendment action
- amendment dialog validates reason and corrections
- requester cannot approve their request
- report list shows version and status
- lineage history navigation works
- predecessor and successor links open exact UUIDs
- PDF download uses the selected historical UUID
- browser printing shows the correct lifecycle notice
- legacy reports display as version 1

## 19. Implementation Sequence After Approval

The approved implementation sequence shall be:

1. architecture ADR and project-memory update
2. tenant migration and lineage backfill
3. report-lineage model relationships
4. amendment and result-correction models
5. permission catalogue changes
6. backend request and validation contracts
7. amendment submission service
8. amendment approval and rejection service
9. current-report and lineage query changes
10. API resources and routes
11. PDF lifecycle presentation
12. frontend types and permissions
13. frontend amendment workflow
14. frontend lineage and status presentation
15. focused backend tests
16. focused frontend tests
17. complete backend suite
18. complete frontend suite
19. typecheck
20. production build
21. documentation closeout
22. pull request and CI validation

## 20. Explicit Non-Goals

This milestone shall not:

- update an issued report row
- delete an issued report row
- overwrite an approved original test result
- silently read current mutable profile data
- silently read current mutable patient data
- silently read current mutable order data
- silently replace historical media
- permit lineage branching
- permit self-approval
- expose cross-tenant records
- implement external email or SMS delivery
- implement HL7 or FHIR transport
- create an implementation branch before this contract is approved

## 21. Approval Gate

Before an implementation branch is created, this contract must be reviewed
and explicitly approved.

Approval shall freeze:

- report-lineage fields and constraints
- numbering rules
- result-correction strategy
- amendment workflow states
- permission allocation
- separation-of-duty rule
- transaction and locking rules
- API endpoints
- resource fields
- browser behaviour
- PDF behaviour
- legacy compatibility
- audit requirements
- testing requirements

After approval, the contract shall be recorded in
`docs/ARCHITECTURE_DECISIONS.md` as a new ADR before product-code work begins.

## 22. Binding Architecture Clarifications and Approval Record

Architecture decision:

    Approved

This section is part of the approved contract.

Where wording elsewhere in this contract is ambiguous, this section
controls.

### 22.1 Enforcement Boundaries

The following rules must be enforced by database constraints:

- report UUID remains unique
- report number remains unique
- `(lineage_uuid, version)` remains unique
- `(laboratory_order_id, version)` remains unique
- `supersedes_report_id` remains unique when present
- amendment `target_report_id` remains unique when present
- amendment `open_source_report_id` remains unique when present
- `(test_result_id, version)` remains unique for result amendments
- `(laboratory_report_amendment_id, test_result_id)` remains unique
- `supersedes_test_result_amendment_id` remains unique when present
- foreign-key deletion remains restricted for clinical lineage records

The following rules require service validation inside locked tenant
transactions because ordinary relational constraints cannot fully express
them:

- every report in a lineage belongs to the same order
- version numbers begin at 1 and advance sequentially
- version N supersedes version N minus 1
- a source report must still be current when an amendment is submitted
- a source report must still be current when an amendment is approved
- no lineage may branch
- no lineage may skip versions
- result corrections must reference results belonging to the source report
- cross-tenant identifiers must never resolve

Database constraints are the final protection against duplicate versions,
duplicate predecessors and concurrent lineage branching.

### 22.2 Amendment Actor Semantics

For an amended report:

- `generated_by` records the reviewer who authorized and issued the report
- `amended_by` records that same authorizing reviewer
- `amendment_actor_snapshot` preserves that reviewer's name, email and role
- `amended_at` records the approval and issuance time
- `requested_by` remains the user who submitted the amendment request
- `requester_snapshot` preserves the requester's identity
- `reviewed_by` remains the user who approved or rejected the request
- `reviewer_snapshot` preserves the reviewer's identity

For approved amendments:

    generated_by = amended_by = reviewed_by

The requester and reviewer remain distinct because self-approval is
forbidden.

In API resources:

- `amendment.requester` means the requester's immutable snapshot
- `amendment.actor` means the authorizing reviewer's immutable snapshot
- `amendment.authorized_by` may be provided as an explicit alias for actor

Browser and PDF references to the amendment actor mean the authorizing
reviewer, not the requester.

### 22.3 One Open Amendment Request

The `laboratory_report_amendments` table shall include:

    open_source_report_id
        Nullable foreign key to laboratory_reports
        Unique when present

State rules:

- while status is `submitted`, `open_source_report_id` equals
  `source_report_id`
- when status becomes `approved`, `open_source_report_id` becomes null
- when status becomes `rejected`, `open_source_report_id` becomes null
- `target_report_id` is nullable and unique
- `target_report_id` is populated only during successful approval

Submission shall run inside a tenant transaction that:

1. locks the source report
2. locks the current report selected for the lineage
3. confirms the source remains current
4. confirms no submitted request already exists
5. creates the amendment with `open_source_report_id` populated

The unique nullable open-source key is the database protection against two
concurrent submitted requests for the same source report.

### 22.4 Result-Amendment Integrity

Every approved result correction shall satisfy:

- `(test_result_id, version)` is unique
- `(laboratory_report_amendment_id, test_result_id)` is unique
- `supersedes_test_result_amendment_id` is unique when present
- version 2 is the first correction of the original approved result
- every later version advances by exactly one
- every later correction supersedes the immediately previous correction
- an approved correction cannot be updated or deleted

`source_payload_hash` shall be SHA-256 over canonical JSON containing:

    result_uuid
    effective_version
    results_data
    notes

Canonicalization rules:

- associative object keys are sorted recursively
- indexed-array order is preserved
- Unicode is not escaped
- slashes are not escaped
- numbers, booleans and null values retain their JSON types

The source hash shall be calculated during submission and recalculated
during approval.

Approval fails when the effective source payload no longer matches the
submitted hash.

Current test-result responses shall resolve the latest approved effective
values and expose:

    effective_version
    is_amended
    effective_results_data
    effective_notes
    latest_amendment_uuid

The original approved `test_results` row remains unchanged.

Historical report responses continue to use their own stored snapshots and
never resolve newer effective result values.

### 22.5 Strict Correction Scope

An amendment payload must use a strict allowlist.

Permitted patient-snapshot corrections:

    medical_record_number
    first_name
    last_name
    date_of_birth
    gender
    email
    phone
    address

`full_name` shall be recomputed from the approved first and last names.

Immutable patient-snapshot fields:

    id

Permitted order-snapshot corrections:

    priority
    referring_clinician
    clinical_notes
    sample_collected_at

Immutable order-snapshot fields include:

    uuid
    order_number
    status
    total_amount
    ordered_at
    completed_at
    ordered_by

Permitted result-snapshot corrections:

    results_data
    notes

Immutable result-item fields include:

    order_item_uuid
    result_uuid
    test_name
    category
    parameters
    entered_by
    submitted_by
    reviewed_by
    submitted_at
    reviewed_at

Unknown keys are rejected.

The amendment workflow does not mutate the underlying patient or order row.

Corrections to mutable source records, where separately permitted by the
product, remain independent operations and are never silently imported into
an issued report amendment.

### 22.6 Lifecycle Rendering

Patient, order, result, laboratory, signatory and report-owned media
snapshots remain immutable.

Lifecycle presentation is derived from the current report lineage when a
browser view or PDF is requested.

Therefore:

- a previously current report may later display a `SUPERSEDED` notice
- predecessor and successor links may appear after a later version is issued
- the lifecycle banner is not part of the immutable clinical snapshot
- adding a lifecycle notice must not alter historical clinical values
- adding a lifecycle notice must not replace historical branding or media
- downloading a historical UUID always renders that exact report version

Tests must distinguish immutable clinical content from dynamic lifecycle
metadata.

### 22.7 Terminal Workflow Immutability

A submitted amendment may transition exactly once to:

    approved
    rejected

After reaching either terminal state:

- its reason cannot change
- its proposed snapshots cannot change
- its change summary cannot change
- its requester evidence cannot change
- its reviewer evidence cannot change
- its source and target report relationships cannot change
- it cannot be deleted

During approval, these actions occur in one transaction:

1. target result-amendment rows are created
2. the new immutable report is created
3. `target_report_id` is recorded
4. `open_source_report_id` is cleared
5. status becomes `approved`
6. reviewer evidence is recorded

During rejection:

1. `open_source_report_id` is cleared
2. status becomes `rejected`
3. reviewer evidence and rejection reason are recorded

Domain events are dispatched only after a successful transaction commit.

### 22.8 Final Approval Gate

The architecture contract is approved with this section incorporated.

The repository ADR must consolidate the contract and these clarifications
into one internally consistent decision. It must not preserve contradictory
or superseded wording.

The next permitted repository operation is a documentation-only architecture
branch created from the verified `develop` baseline.

Product-code implementation remains prohibited until:

1. the approved ADR is committed
2. project-memory documents are synchronized
3. the documentation pull request passes CI
4. the documentation pull request is merged normally
5. local and remote `develop` are synchronized
