# Patient and Clinician Portals Architecture

Status:

    Accepted and implemented; merged in PR #22

Architecture checkpoint:

    P10 complete

Date:

    10 August 2026

Branch:

    merged into develop at d96eb5d4f35f5a8b4977cdbd8b05c0f0741a082f

## 1. Purpose

This document defines the binding architecture for Rezhel Patient and
Clinician Portals.

The portals provide tenant-owned external access to governed laboratory order
status and issued laboratory reports without exposing internal laboratory
operator workflows or widening the internal tenant-user authentication
boundary.

This architecture is intentionally defined before implementation.

## 2. Scope

The module includes:

- tenant-owned patient portal accounts
- tenant-owned referring-clinician records
- tenant-owned clinician portal accounts
- dedicated portal authentication and bearer tokens
- invitation and activation workflow
- patient portal order-status access
- patient portal current issued-report access
- clinician portal referred-order access
- clinician portal current issued-report access
- report PDF download through portal-safe authorization
- internal administration for portal access
- notification-ready domain events
- tenant-isolation, authorization and security tests
- separate patient and clinician frontend shells

V1 is read-only for external clinical data.

## 3. Non-goals

V1 does not include:

- patient editing of core clinical demographics
- external clinician order creation
- public self-registration
- MRN and date-of-birth account claiming
- clinician-name matching for authorization
- email, SMS or WhatsApp transport
- critical-result escalation delivery
- analyzer connectivity
- HL7, ASTM or FHIR exchange

Those capabilities belong to later controlled milestones.

## 4. Tenant Ownership Invariant

The system uses database-per-tenant isolation.

Each of the following belongs to exactly one tenant:

- Patient
- ReferringClinician
- PatientPortalAccount
- ClinicianPortalAccount
- PortalAccessToken
- PortalInvitation
- LaboratoryOrder
- LaboratoryReport

There is no platform-wide patient or clinician identity.

The same email address may validly exist in more than one tenant.

Example:

    Tenant A
        patient@example.com

    Tenant B
        patient@example.com

These are independent tenant-owned identities.

Tenant context must be established before account or token lookup.

## 5. Existing Patient Model

The current Patient model remains the clinical record.

It must not gain password or authentication responsibilities merely to support
the portal.

Patient clinical email and phone values are contact data and are not assumed
to be verified or unique authentication identifiers.

Portal credentials belong to a separate account model.

## 6. Patient Portal Account

Add a tenant-database model conceptually equivalent to:

    patient_portal_accounts

Required characteristics:

- UUID public identifier
- one-to-one Patient relationship
- explicit login email
- password hash
- activation state
- optional email-verification timestamp
- active/inactive state
- last-login timestamp
- created/updated timestamps
- audit-safe creator metadata where appropriate

Required constraints inside each tenant database:

    UNIQUE(patient_id)
    UNIQUE(email)

Patient account email is the portal login identity for that tenant. It does
not rewrite the Patient clinical contact email.

## 7. Referring Clinician Domain Model

The current free-text laboratory-order `referring_clinician` value is
insufficient for authorization.

Add a tenant-owned referring clinician model conceptually equivalent to:

    referring_clinicians

Suggested fields:

- UUID
- display name
- email
- phone
- organisation or facility
- registration number, optional
- specialty, optional
- active/inactive state
- timestamps

This is an external clinician directory, not an internal tenant-user role.

The same real-world clinician working with two laboratories is represented by
two independent tenant-owned records.

## 8. Clinician Portal Account

Add a tenant-database model conceptually equivalent to:

    clinician_portal_accounts

Required characteristics:

- UUID public identifier
- one-to-one ReferringClinician relationship
- explicit login email
- password hash
- activation state
- optional email-verification timestamp
- active/inactive state
- last-login timestamp
- timestamps

Required tenant-database constraints:

    UNIQUE(referring_clinician_id)
    UNIQUE(email)

The internal tenant-user role `doctor` is not the clinician portal account.

## 9. Durable Order-to-Clinician Linkage

Laboratory orders currently preserve a free-text referring-clinician value.

Retain that value as historical/display evidence.

Add a nullable durable relationship conceptually equivalent to:

    laboratory_orders.referring_clinician_id

New or edited eligible orders may link to a tenant-owned ReferringClinician.

The displayed clinician snapshot may continue to preserve the historical name.

Authorization rule:

    free-text clinician name
        != authorization

Only an explicit tenant-owned referring-clinician relationship can place an
order inside a clinician portal account's scope.

Historical orders must not be auto-linked by name.

Any historical reconciliation must be explicit and auditable.

## 10. Dedicated Portal Authentication Boundary

The existing internal API uses tenant-first Sanctum authentication for
Tenant\User identities.

Portal accounts must not be added to that existing internal authentication
identity set.

Create a dedicated portal authentication mechanism.

Recommended tenant-database token model:

    portal_access_tokens

Suggested characteristics:

- UUID
- tokenable type
- tokenable account UUID / key
- SHA-256 or equivalent token digest
- optional device/session label
- abilities or portal scope
- last-used timestamp
- expires-at timestamp
- revoked-at timestamp
- timestamps

Allowed tokenable account types are limited to:

- PatientPortalAccount
- ClinicianPortalAccount

Raw bearer tokens are returned only when issued and are never stored in plain
text.

Internal Sanctum token tables and portal token tables remain separate.

## 11. Request Resolution Order

Every portal request follows this boundary:

    X-Tenant-Domain
        -> resolve landlord Tenant
        -> activate tenant database
        -> select portal authentication middleware
        -> hash and resolve portal bearer token in active tenant database
        -> validate account active/activated state
        -> bind patient or clinician portal principal
        -> derive record ownership from principal
        -> execute portal-safe query

Authentication without tenant context is invalid.

A token issued inside Tenant A cannot authenticate against Tenant B because
Tenant B queries a different portal-token table.

## 12. Portal Route Isolation

Use dedicated routes, conceptually:

    /api/portal/patient/*
    /api/portal/clinician/*

Do not expose existing internal controllers directly to portal accounts.

Internal endpoints such as:

    /api/patients
    /api/test-results
    /api/users

continue to require internal tenant-user authentication.

Portal middleware must reject internal tenant-user tokens, and internal
middleware must reject portal tokens.

## 13. Ownership-Based Authorization

Patient queries derive patient ownership from:

    authenticated PatientPortalAccount
        -> patient_id

The browser never chooses the authorized patient.

Clinician queries derive clinician ownership from:

    authenticated ClinicianPortalAccount
        -> referring_clinician_id

The browser never chooses the authorized clinician.

A route UUID may identify a candidate order/report, but the server must prove
that candidate belongs to the authenticated principal before returning it.

## 14. Patient Portal Clinical Visibility

Patient Portal V1 may expose:

- patient account/profile summary
- own laboratory orders
- portal-safe order status
- ordered tests
- collection/processing summary appropriate for patients
- report availability
- current issued laboratory report
- current amended laboratory report
- authorized PDF download

Patient Portal V1 must not expose:

- draft test results
- submitted test results
- rejected test results
- internal review comments
- internal workflow permissions
- referral operational costs
- internal audit data not intended for the patient
- other patients' records

## 15. Report Governance Boundary

LaboratoryReport is already immutable after issue and preserves:

- laboratory snapshot
- patient snapshot
- order snapshot
- result snapshot
- report version
- amendment lineage

Portal clinical values therefore come from LaboratoryReport rather than direct
mutable/internal TestResult workflow data.

Current report selection follows existing lineage rules:

    no successor
        -> current report

Current lifecycle status may be:

    issued
    amended

Superseded reports are not the default patient/clinician view.

Historical lineage may be exposed only through an explicit portal-safe
history decision.

## 16. Clinician Portal Visibility

Clinician Portal V1 may expose:

- clinician profile summary
- patients represented by explicitly linked referred orders
- own referred laboratory orders
- order/test processing status
- priority where clinically appropriate
- current issued/amended laboratory report
- authorized PDF download

Clinician Portal V1 must not expose:

- orders belonging to another referring clinician
- patients with no clinician-linked order
- internal result drafting/review workflow
- internal billing unless a future explicit clinician billing requirement is approved
- internal user administration
- branch administration
- referral-laboratory configuration
- test catalogue administration

## 17. Portal-Safe API Shape

Patient API, conceptually:

    POST /api/portal/patient/login
    POST /api/portal/patient/logout
    GET  /api/portal/patient/me
    GET  /api/portal/patient/orders
    GET  /api/portal/patient/orders/{order}
    GET  /api/portal/patient/reports
    GET  /api/portal/patient/reports/{report}
    GET  /api/portal/patient/reports/{report}/pdf

Clinician API, conceptually:

    POST /api/portal/clinician/login
    POST /api/portal/clinician/logout
    GET  /api/portal/clinician/me
    GET  /api/portal/clinician/patients
    GET  /api/portal/clinician/orders
    GET  /api/portal/clinician/orders/{order}
    GET  /api/portal/clinician/reports
    GET  /api/portal/clinician/reports/{report}
    GET  /api/portal/clinician/reports/{report}/pdf

Exact route/controller names may change during implementation, but the
security boundary is binding.

## 18. Portal Resources

Do not reuse broad internal resources if they expose internal-only fields.

Create explicit portal resources/view models such as:

- PatientPortalProfileResource
- PatientPortalOrderResource
- PatientPortalReportResource
- ClinicianPortalProfileResource
- ClinicianPortalPatientResource
- ClinicianPortalOrderResource
- ClinicianPortalReportResource

Portal responses follow minimum-necessary disclosure.

## 19. Account Provisioning

V1 provisioning is invitation-based.

Internal administrator flow:

    select tenant Patient or ReferringClinician
        -> create/enable portal account
        -> create one-time invitation
        -> display activation link
        -> recipient establishes password
        -> invitation marked used
        -> account activated

Do not implement public account claiming from MRN, date of birth, clinician
name or report number.

## 20. Portal Invitations

Use tenant-owned invitation records.

Required properties:

- UUID
- target portal account type
- target account identifier
- cryptographically random token
- only a token hash stored at rest
- expiration timestamp
- used timestamp
- revoked timestamp where required
- creator/audit metadata
- timestamps

Replacing an invitation revokes prior unused invitations for that account.

Activation tokens are single-use.

## 21. Notification Integration Boundary

Patient and Clinician Portals precede the Notifications module.

The portal module may emit events such as:

    PatientPortalInvitationCreated
    ClinicianPortalInvitationCreated
    LaboratoryReportAvailableForPatient
    LaboratoryReportAvailableForClinician

V1 must not require an email/SMS/WhatsApp provider to function.

An authorized administrator can copy a generated activation link manually.

The subsequent Notifications and Critical-Result Escalation milestone will
consume portal events and own:

- channel selection
- templates
- retry
- delivery status
- critical-result escalation
- notification preferences
- provider configuration

## 22. Frontend Separation

The internal laboratory application shell is not reused as the portal shell.

Recommended browser routes:

Patient:

    /patient-portal/login
    /patient-portal
    /patient-portal/orders
    /patient-portal/orders/:uuid
    /patient-portal/reports
    /patient-portal/reports/:uuid

Clinician:

    /clinician-portal/login
    /clinician-portal
    /clinician-portal/patients
    /clinician-portal/orders
    /clinician-portal/orders/:uuid
    /clinician-portal/reports
    /clinician-portal/reports/:uuid

The two portals may share safe presentational components and branding tokens
but use separate authentication contexts.

## 23. Frontend Session Storage

Do not reuse the internal application token storage key.

Recommended V1 session keys use sessionStorage, not long-lived localStorage.

Conceptually:

    patient_portal_token
    patient_portal_tenant_domain

    clinician_portal_token
    clinician_portal_tenant_domain

A dedicated API client/interceptor set must attach the correct portal token
and tenant domain without interacting with the internal AuthContext session.

## 24. Internal Administration

V1 requires internal tenant administration for:

- referring clinician directory
- patient portal account enable/disable
- clinician portal account enable/disable
- invitation creation/replacement
- invitation status
- explicit historical order-to-clinician reconciliation if implemented

Portal administration should use a new explicit tenant permission rather than
assuming every internal user can manage portal access.

Recommended permission:

    MANAGE_PORTAL_ACCESS

Initial role assignment should be conservative, normally administrator-only
until a business requirement broadens it.

Platform superadministrator behavior must follow the project's established
tenant-management boundary.

## 25. Password and Session Security

Portal passwords use Laravel's configured password hashing.

Login endpoints require rate limiting.

Authentication responses must not reveal whether a patient/clinician exists in
another tenant.

Portal tokens:

- are random and high entropy
- are hashed at rest
- expire
- can be revoked
- update last-used metadata
- are deleted/revoked on logout
- are invalid when the portal account is inactive

Password reset delivery is deferred until Notifications is available unless a
safe administrator-assisted reset is explicitly implemented.

## 26. Tenant Isolation Tests

Automated tests must prove:

- same patient email can exist in Tenant A and Tenant B
- Tenant A patient credentials cannot authenticate against Tenant B
- Tenant A patient token cannot authenticate against Tenant B
- Tenant A patient cannot access Tenant B orders/reports
- Tenant A clinician credentials cannot authenticate against Tenant B
- Tenant A clinician token cannot authenticate against Tenant B
- Tenant A clinician cannot access Tenant B orders/reports
- patient and clinician tokens cannot authenticate internal tenant routes
- internal Sanctum tokens cannot authenticate portal-only routes
- patient A cannot request patient B's order/report inside one tenant
- clinician A cannot request clinician B's linked order/report inside one tenant

## 27. Clinical Governance Tests

Automated tests must prove:

- draft results are not externally visible
- submitted results are not externally visible
- rejected results are not externally visible
- current issued report is visible to the owning patient
- current amended report replaces the superseded report in default views
- superseded report is not returned as current
- report PDF uses the same portal ownership boundary as report JSON
- clinician report access requires explicit clinician-linked order ownership

## 28. Historical Data

Existing Patient records remain valid without portal accounts.

Existing orders with only free-text referring-clinician values remain valid.

No destructive backfill is required.

Historical clinician linkage is optional and explicit.

Do not infer a clinician relationship solely from:

- name
- email text embedded in notes
- organisation text
- report snapshot text

## 29. Public Identifiers

Portal URLs and APIs use UUIDs.

Numeric tenant-database primary keys remain internal implementation details.

## 30. Audit Requirements

Record security-significant portal events, including:

- account created
- account activated
- account disabled/enabled
- invitation created/replaced/revoked/used
- successful login
- logout/token revocation
- administrator-assisted credential action if introduced
- clinician-order linkage/reconciliation changes

Audit data must preserve tenant ownership and actor identity.

## 31. Implementation Gate

No portal clinical-data frontend should be implemented until the dedicated
portal identity/token boundary has characterization tests proving separation
from internal tenant-user authentication.

The next checkpoint is:

    P2 - Portal identity and authentication foundation
