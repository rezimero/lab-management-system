# Seven-Day Market-Ready Commercial Beta Plan

**Sprint start:** 2026-08-01
**Beta target:** 2026-08-08
**Hardening window:** one to two weeks after the beta release candidate

## Release objective

Deliver a production-engineered, market-ready commercial beta that supports
the complete clinical workflow and minimum marketable production scope for
every module required to demonstrate and sell the product credibly.

This is not permission to ship placeholders. Each included module must have a
working end-to-end path, authorization, tenant isolation, validation, tests,
and an honest statement of supported scope.

## Included marketable scope

### Clinical governance

- Controlled laboratory-report amendment and supersession
- Immutable historical reports and PDFs
- Critical-result workflow and escalation foundation
- Clinical audit evidence and separation of duties

### Multi-branch and collection centres

- Branch and collection-centre records
- Staff assignment
- Branch-scoped operational records
- Collection routing
- Head-office consolidated visibility

### SaaS plans and subscriptions

- Plans
- Feature entitlements
- Trial and subscription states
- Usage limits
- Tenant suspension controls
- Manual administrative lifecycle for the beta

### Billing, invoices, and payments

- Price lists
- Invoices
- Payment recording
- Receipts
- Outstanding balances
- Basic corporate-account billing

### Inventory and reagent management

- Stock items
- Batches
- Expiry dates
- Stock movements
- Low-stock warnings
- Reagent or consumable usage capture

### Referral laboratories

- Referral-laboratory directory
- Send-out workflow
- External-result capture
- Cost and status tracking

### Patient and clinician portals

- Secure authentication
- Authorized order and report access
- Historical report access
- PDF download

### Notifications

- In-application delivery records
- Email delivery where environment credentials are available
- Result-ready notices
- Critical-result escalation records
- Retry-safe notification logging

### Analyzer and interoperability foundation

- Analyzer adapter contract
- One validated simulator or reference adapter
- Message persistence and audit logs
- HL7 and ASTM queue/framework foundations
- FHIR reference resources and test exchanges

The beta must be marketed as integration-ready with validated reference
connectors, not universally compatible with untested devices or external
systems.

### Analytics

- Operational workload
- Turnaround time
- Branch activity
- Billing totals and balances
- Inventory status
- Referral status
- Subscription and usage summaries

## Seven-day execution

### Day 1

- Record the approved amendment architecture
- Freeze the beta scope
- Create the amendment implementation branch
- Capture the clean baseline
- Implement report-lineage and amendment database foundations

### Day 2

- Complete amendment backend workflow
- Complete authorization, concurrency, tenant-isolation, and audit tests
- Begin multi-branch foundation

### Day 3

- Complete amendment API, PDF, and frontend workflow
- Complete multi-branch and collection-centre minimum scope
- Begin subscriptions and feature entitlements

### Day 4

- Complete subscriptions
- Complete billing, invoices, payments, and receipts
- Begin inventory and referral workflows

### Day 5

- Complete inventory and referral workflows
- Complete patient and clinician portal minimum scope
- Complete notification foundation

### Day 6

- Complete analyzer/reference connector and interoperability foundations
- Complete minimum analytics dashboards
- Run clean and existing-data migration rehearsals
- Run full backend and frontend quality gates

### Day 7

- Production hardening
- Backup and restore rehearsal
- Deployment and rollback rehearsal
- Staging acceptance testing
- Release-blocking fixes
- Final CI
- Market-ready commercial beta release candidate

## Non-negotiable release gates

The beta is blocked by any of the following:

- failing backend or frontend tests;
- frontend typecheck or production-build failure;
- unsafe or destructive migrations;
- broken tenant isolation;
- mutable historical reports;
- amendment self-approval;
- uncontrolled lineage branching;
- critical authorization defects;
- unverified backup restoration;
- missing rollback instructions;
- unsupported integration claims;
- unresolved critical security or clinical data-integrity defects.
