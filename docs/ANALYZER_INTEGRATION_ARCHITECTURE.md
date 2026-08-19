# Rezhel Analyzer Integration Architecture

## 1. Purpose

Analyzer Integration imports laboratory instrument results into Rezhel while preserving tenant isolation, specimen identity, clinical provenance, human validation, existing result approval, critical-result escalation, and immutable report governance.

The architecture is deliberately divided into:

- instrument administration;
- machine authentication;
- adapter/gateway boundary;
- immutable raw-message evidence;
- idempotent normalization and matching;
- result-import candidates;
- validation/review queue;
- automatic draft population;
- provenance and audit events;
- operational status and replay.

It does not implement generic external-laboratory interoperability.

## 2. Domain boundary

### Analyzer Integration owns

- analyzer/instrument registration;
- instrument branch ownership;
- machine credential issuance and rotation;
- analyzer health/last-seen state;
- analyzer test mappings;
- analyzer parameter mappings;
- canonical analyzer message ingestion;
- raw message evidence and payload hashing;
- idempotency;
- specimen/accession matching;
- order-item matching;
- result normalization;
- validation issues;
- import/review/reject/reprocess actions;
- machine-to-result provenance;
- operational import statistics;
- analyzer-specific frontend workspace.

### Analyzer Integration does not own

- generic partner-laboratory APIs;
- generic FHIR endpoints;
- generalized HL7 exchange with hospitals or external laboratories;
- external referral laboratory accounts;
- insurance/payer interfaces;
- report approval;
- report amendment/supersession;
- critical-result acknowledgement;
- patient/clinician portal delivery.

## 3. Proposed tenant schema

### `analyzer_instruments`

Purpose: tenant-owned configured devices/gateways.

Core fields:

- `id`
- `uuid`
- `branch_id`
- `code`
- `name`
- `manufacturer`
- `model`
- `serial_number`
- `adapter_key`
- `transport_mode`
- `machine_token_hash`
- `machine_token_hint`
- `token_rotated_at`
- `is_active`
- `last_seen_at`
- `last_success_at`
- `last_error_at`
- `last_error_code`
- `notes`
- `created_by`
- `updated_by`
- timestamps

Rules:

- `code` unique within tenant;
- branch required for new instruments;
- raw machine token never stored;
- no destructive delete API; deactivate instead;
- evidence tables restrict deletion of referenced instruments.

### `analyzer_test_mappings`

Purpose: map device test identifiers to Rezhel catalogue tests.

Core fields:

- `uuid`
- `analyzer_instrument_id`
- `lab_test_id`
- `instrument_test_code`
- `instrument_test_name`
- `is_active`
- `created_by`
- `updated_by`
- timestamps

Unique:

`analyzer_instrument_id + instrument_test_code`

### `analyzer_parameter_mappings`

Purpose: map device parameter identifiers to the Rezhel result-row contract.

Core fields:

- `uuid`
- `analyzer_test_mapping_id`
- `instrument_parameter_code`
- `instrument_parameter_name`
- `target_parameter_name`
- `expected_unit`
- `is_required`
- `is_active`
- timestamps

No executable conversion expression is stored.

Initial milestone behavior:

- exact value is preserved;
- unit is validated;
- mismatched unit enters review unless an explicitly supported non-clinical normalization applies;
- automatic clinical unit conversion is out of scope for v1.

### `analyzer_messages`

Purpose: immutable machine-message evidence.

Core fields:

- `uuid`
- `analyzer_instrument_id`
- `idempotency_key`
- `external_message_id`
- `adapter_key`
- `content_type`
- `payload_hash`
- encrypted `raw_payload`
- `source_timestamp`
- `received_at`
- `processed_at`
- `status`
- `last_error_code`
- sanitized `last_error`
- timestamps

Unique:

`analyzer_instrument_id + idempotency_key`

The raw payload and payload hash are immutable after creation.

### `analyzer_result_imports`

Purpose: one analyzable result candidate extracted from a message.

Core fields:

- `uuid`
- `analyzer_message_id`
- `analyzer_instrument_id`
- `analyzer_test_mapping_id`
- `specimen_id`
- `laboratory_order_item_id`
- `test_result_id`
- `source_result_key`
- `source_sample_identifier`
- `instrument_test_code`
- `status`
- `normalized_results`
- `mapping_snapshot`
- `validation_issues`
- `attempts`
- `last_processed_at`
- `accepted_by`
- `accepted_at`
- `rejected_by`
- `rejected_at`
- `review_note`
- timestamps

Unique:

`analyzer_message_id + source_result_key`

Recommended status set:

- `received`
- `needs_review`
- `ready`
- `imported`
- `rejected`
- `failed`
- `duplicate`

### `analyzer_import_events`

Purpose: append-only audit history for an import.

Core fields:

- `analyzer_result_import_id`
- `event_type`
- `from_status`
- `to_status`
- `actor_type`
- `actor_id`
- `actor_snapshot`
- `payload`
- `occurred_at`
- `created_at`

This table is append-only.

## 4. Machine authentication

Analyzer gateways must not use a human Sanctum token.

Recommended request identity:

- `X-Tenant-Domain`
- `X-Analyzer-Id`
- `Authorization: Bearer <machine-secret>`
- `X-Rezhel-Timestamp`
- `X-Rezhel-Idempotency-Key`

Optional hardening in the same contract:

- `X-Rezhel-Signature` HMAC over canonical request bytes, timestamp, analyzer UUID and idempotency key.

Token rules:

- 256-bit random secret;
- plaintext shown only on creation/rotation;
- only a one-way hash stored;
- rotation revokes the previous secret;
- inactive instrument credentials are rejected;
- authentication failures do not reveal whether an instrument UUID exists.

## 5. Canonical analyzer envelope

Versioned canonical JSON is the central contract.

Example shape:

```json
{
  "schema": "rezhel.analyzer-result.v1",
  "message_id": "device-message-123",
  "sent_at": "2026-08-16T03:00:00+01:00",
  "results": [
    {
      "result_key": "sample-1:CBC",
      "sample_id": "SP-20260816-ABCDEFGH",
      "test_code": "CBC",
      "observations": [
        {
          "code": "HGB",
          "name": "Haemoglobin",
          "value": "13.4",
          "unit": "g/dL",
          "reference_range": "12.0-16.0",
          "flag": "N"
        }
      ]
    }
  ]
}
```

Vendor adapters convert device data into this envelope. The Rezhel server validates the envelope independently and never trusts the adapter to decide clinical workflow state.

## 6. Matching algorithm

For each result candidate:

1. Verify entitlement and instrument authentication.
2. Persist raw message evidence idempotently.
3. Resolve active analyzer test mapping by instrument + `test_code`.
4. Resolve specimen by exact normalized accession/sample identifier.
5. Require specimen status `received`.
6. Reject a rejected specimen or obsolete specimen lineage.
7. Load specimen-linked order items.
8. Match exactly one order item to mapped `lab_test_id`.
9. Require parent order `in_progress`.
10. Require order item `in_progress`.
11. Require order processing branch == instrument branch.
12. Require no open laboratory referral.
13. Resolve every observation through active parameter mapping.
14. Require no duplicate target parameter names.
15. Require all expected order-snapshot parameter names.
16. Validate allowed canonical flags.
17. Validate units without automatic clinical conversion.
18. Inspect existing result state.
19. If every rule passes, mark candidate `ready`.
20. If safe auto-import conditions pass, create the result draft and mark `imported`; otherwise `needs_review`.

Zero matches, multiple matches, stale mappings, branch mismatch, workflow mismatch, open referral, missing parameters, unexpected parameters, unit mismatch, malformed values, existing conflicting result state, or parser errors are validation-queue conditions.

## 7. Result-write policy

### Safe automatic behavior

A fully matched candidate may automatically create a `draft` `TestResult`.

The draft:

- contains normalized analyzer values;
- uses the existing result-row structure;
- has analyzer provenance through `analyzer_result_imports.test_result_id`;
- does not impersonate a human `entered_by`;
- remains editable/reviewable subject to existing permission rules.

### Forbidden automatic behavior in v1

The analyzer importer must never automatically:

- approve a result;
- amend an approved result;
- overwrite a submitted result;
- overwrite an approved result;
- overwrite a manual draft;
- overwrite a rejected result without review;
- resolve a critical result;
- complete a referral;
- change an order to in-progress;
- change a specimen to received;
- bypass branch access;
- invent a parameter mapping.

## 8. Existing service integration

The analyzer module should reuse the existing clinical rules rather than duplicate them.

A dedicated `AnalyzerDraftImportService` may create analyzer-origin drafts because `TestResultManagementService::createDraft()` currently requires a human user ID.

The new service must mirror the existing readiness guards and should extract shared readiness rules into a reusable domain guard only when doing so keeps behavior identical.

`TestResultWorkflowService` remains unchanged as the authority for submit/approve/reject.

Critical-result escalation continues to run from the existing approval path.

## 9. Provenance

`TestResultResource` should add an optional `analyzer_source` block analogous to existing `referral_source`.

Suggested fields:

- import UUID;
- instrument UUID/code/name;
- source message UUID;
- source message ID;
- sample identifier;
- test code;
- received timestamp;
- imported timestamp.

Raw payload is never exposed in normal result resources.

Raw message viewing requires a dedicated high-privilege analyzer audit permission and should default to redacted metadata rather than raw PHI.

## 10. Permissions

Add tenant permissions:

- `view_analyzers`
- `manage_analyzers`
- `manage_analyzer_mappings`
- `view_analyzer_imports`
- `review_analyzer_imports`
- `reprocess_analyzer_imports`
- `rotate_analyzer_credentials`

Recommended initial role access:

- `superadmin`: all analyzer permissions;
- `admin`: all analyzer permissions;
- `lab_technician`: view imports, review imports, reprocess imports;
- `doctor`: view imports when required for result provenance, no instrument configuration;
- `receptionist`: no analyzer permissions.

Instrument machine authentication is independent of this human permission catalogue.

## 11. Entitlement

Every human analyzer endpoint must enforce:

`CommercialFeatureCatalog::ANALYZER_INTEGRATION`

Machine ingestion must also enforce the entitlement before accepting clinical payloads.

The commercial-beta feature remains disabled until acceptance.

## 12. Frontend

Add a dedicated `lab-frontend/src/features/analyzers/` feature.

Recommended routes:

- `/analyzers`
- `/analyzers/:uuid`
- `/analyzers/:uuid/mappings`
- `/analyzer-imports`
- `/analyzer-imports/:uuid`

Primary UI areas:

- analyzer summary cards;
- instrument directory;
- connection/health state;
- credential rotation dialog;
- test mapping workspace;
- parameter mapping workspace;
- validation queue;
- import detail/evidence timeline;
- retry/reprocess action;
- link to resulting test result.

Navigation should be entitlement-aware and role-aware.

## 13. Error model

Use machine-readable error codes, for example:

- `ANALYZER_AUTH_FAILED`
- `ANALYZER_DISABLED`
- `ANALYZER_ENTITLEMENT_DISABLED`
- `ANALYZER_MESSAGE_DUPLICATE`
- `ANALYZER_SCHEMA_INVALID`
- `ANALYZER_TEST_UNMAPPED`
- `ANALYZER_PARAMETER_UNMAPPED`
- `ANALYZER_SAMPLE_NOT_FOUND`
- `ANALYZER_SAMPLE_NOT_RECEIVED`
- `ANALYZER_ORDER_ITEM_NOT_FOUND`
- `ANALYZER_ORDER_ITEM_AMBIGUOUS`
- `ANALYZER_BRANCH_MISMATCH`
- `ANALYZER_OPEN_REFERRAL`
- `ANALYZER_RESULT_CONFLICT`
- `ANALYZER_UNIT_MISMATCH`
- `ANALYZER_FLAG_UNSUPPORTED`
- `ANALYZER_PROCESSING_FAILED`

API responses should not expose stack traces or raw PHI.

## 14. Idempotency and replay

Idempotency has two levels:

1. message-level: instrument + idempotency key;
2. candidate-level: message + source result key.

A duplicate request returns the existing message/import state rather than creating another result.

Reprocess operates on existing evidence. It creates audit events but does not replace the original raw message.

## 15. Queueing

Initial synchronous persistence should be minimal:

- authenticate;
- validate outer envelope;
- persist immutable message;
- persist candidate records;
- dispatch tenant-aware processing job after commit;
- return accepted state.

Matching and import processing should run in a tenant-aware queue job.

This keeps device ingress fast and resilient while using the multitenancy queue behavior already configured by Rezhel.

## 16. Observability

Track per instrument:

- last seen;
- last successful import;
- last error;
- messages in last 24 hours;
- imported candidates;
- needs-review candidates;
- failed candidates;
- duplicate messages.

Never treat absence of a message as proof that an analyzer is healthy.

## 17. Retention

Analyzer raw messages are clinical integration evidence and should not be casually deleted.

Retention duration must ultimately follow the laboratory's clinical-record policy and applicable regulatory obligations.

For the product milestone:

- no destructive UI delete;
- no cascade that destroys messages when a mapping is removed;
- instrument deactivation preserves historical evidence.

## 18. Adapter/gateway boundary

Define a versioned adapter contract now.

The first supported server adapter is:

`canonical_json_v1`

A local Rezhel Analyzer Gateway can later implement:

- serial;
- TCP;
- file/drop-folder;
- vendor ASTM;
- vendor HL7/MLLP;
- vendor-specific protocols.

This gateway converts device-specific data to the canonical envelope.

The later External Laboratory Interoperability module may reuse lower-level protocol libraries, but it owns partner-system semantics, external-laboratory workflows, FHIR resources, generic HL7 exchange, and partner APIs.

## 19. Acceptance standard

The module is not considered complete merely because rows appear in `test_results`.

Acceptance requires:

- tenant isolation tests;
- machine-auth tests;
- entitlement tests;
- instrument/mapping lifecycle tests;
- idempotency tests;
- duplicate/replay tests;
- accession matching tests;
- branch mismatch tests;
- specimen state tests;
- open-referral conflict tests;
- parameter completeness tests;
- unit/flag validation tests;
- existing-result conflict tests;
- queue/retry tests;
- analyzer provenance tests;
- critical-result approval integration test;
- frontend permissions/entitlement tests;
- frontend validation-queue tests;
- TypeScript validation;
- production build;
- focused browser smoke;
- development-tenant migration verification;
- rollback/recovery evidence.

## 20. Production claim boundary

Rezhel may claim that Analyzer Integration is implemented after the canonical end-to-end flow passes.

Rezhel must not claim compatibility with a named physical analyzer until that make/model/protocol has been tested against either:

- a vendor-approved simulator; or
- the physical instrument in a controlled laboratory acceptance exercise.
