# Patient and Clinician Portals Implementation Sequence

Status:

    Completed through P10 and merged into develop in PR #22

Branch:

    merged; historical branch feature/patient-clinician-portals removed

## P1 - Architecture and dependency review

Deliver:

- ADR-031
- binding portal architecture
- tenant-ownership invariant
- portal-before-notifications dependency review
- implementation sequence

Exit criteria:

- architecture documented
- no product behavior changed
- `git diff --check` clean

## P2 - Portal identity and authentication foundation

Backend:

- patient portal account schema/model
- referring clinician schema/model
- clinician portal account schema/model
- tenant portal access-token schema/model
- portal invitation schema/model
- password hashing
- tenant-first portal login services
- patient/clinician portal authentication middleware
- logout/revocation
- account active/activation checks
- login throttling
- token expiry
- portal principal binding

Tests:

- tenant isolation
- same-email-across-tenants
- internal-token rejection on portal routes
- portal-token rejection on internal routes
- inactive-account rejection
- expired/revoked-token rejection

No clinical-data portal API in this phase.

## P3 - Referring clinician directory and durable order linkage

Backend:

- internal clinician directory CRUD
- conservative management permission
- nullable LaboratoryOrder referring-clinician relationship
- preserve existing free-text clinician snapshot
- order-create/update linkage where allowed
- explicit historical reconciliation service if required

Frontend:

- internal clinician directory
- order form selection integration
- no free-text authorization semantics

Tests:

- tenant ownership
- inactive clinician handling
- cross-tenant link prevention
- historical unlinked order compatibility

## P4 - Portal-safe backend read APIs

Patient:

- profile
- own orders
- own order detail
- current reports
- report detail
- report PDF

Clinician:

- profile
- patients derived from linked orders
- linked orders
- linked order detail
- current reports
- report detail
- report PDF

Tests:

- ownership-by-authenticated-principal
- no client-selected patient/clinician scope
- report lineage/current-version behavior
- no internal workflow leakage
- PDF authorization parity

## P5 - Patient Portal frontend

Deliver:

- separate patient portal shell
- login
- session handling
- dashboard
- orders
- order detail
- reports
- report detail
- PDF download
- responsive/mobile behavior
- accessibility
- loading/error/empty states

Do not reuse the internal laboratory sidebar.

## P6 - Clinician Portal frontend

Deliver:

- separate clinician portal shell
- login
- session handling
- dashboard
- referred patients
- linked orders
- order detail
- reports
- report detail
- PDF download
- responsive/mobile behavior
- accessibility
- loading/error/empty states

V1 remains read-only.

## P7 - Internal portal account administration and invitations

Deliver:

- patient account enable/disable
- clinician account enable/disable
- invitation create/replace/revoke
- activation page
- password establishment
- invitation status
- manual copy activation link
- notification-ready domain events

No email/SMS/WhatsApp provider integration.

## P8 - Security and governance hardening

Validate:

- tenant isolation
- account enumeration resistance
- throttling
- token expiry/revocation
- portal/internal token separation
- current report selection
- amended/superseded report behavior
- audit events
- inactive/deactivated accounts
- cross-account access attempts
- branch/tenant interactions
- permission parity

## P9 - Final QA and refinement

Validate:

- patient desktop/mobile
- clinician desktop/mobile
- keyboard navigation
- contrast/accessibility
- loading/error/empty states
- session persistence rules
- PDF download
- order/report terminology
- no internal-only data leakage
- cross-page regression
- browser console
- backend portal test suite
- frontend typecheck/build

## P10 - Git and GitHub completion

Status:

    Completed 12 August 2026

Verified merge baseline:

    develop at d96eb5d4f35f5a8b4977cdbd8b05c0f0741a082f

Required:

- exact-path review
- no generated artifacts inside repository
- `git diff --check`
- backend tests
- frontend typecheck/build
- stage only intended source
- completion commit
- push feature branch
- verify local/remote hash
- pull request into develop
- merge verification
- synchronize local develop

Temporary collectors, backups and debug output remain under `%TEMP%` or
`Downloads`, never inside the repository.
