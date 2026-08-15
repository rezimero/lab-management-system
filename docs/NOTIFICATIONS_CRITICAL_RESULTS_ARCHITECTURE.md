# Notifications and Critical-Result Escalation

## Baseline and purpose

This module is built on the post-portal `develop` baseline at merge commit
`d96eb5d4f35f5a8b4977cdbd8b05c0f0741a082f`. It owns delivery records,
in-application inboxes, email delivery, notification preferences, secure portal
password-reset delivery and critical-result escalation.

The patient and clinician portal authorization boundaries remain unchanged.
The module preserves existing report-ready events and uses the existing
explicit `critical` result flag. It does not expose report contents, result
values, patient identifiers or passwords in external email. One-time tokens
appear only in the required secure action URL fragment, never in ordinary
message prose, event payloads or application logs.

## Delivery guarantees

`notification_messages` is the tenant-owned in-application inbox.
`notification_deliveries` is the tenant-owned delivery outbox. Every record has
a unique idempotency key, status, retry count and sanitized failure state.
Destinations and pending email payloads are encrypted with the application key.
They are redacted after a successful or simulated delivery.
The destination fingerprint is an application-keyed HMAC rather than a plain
email hash.

Delivery states are:

- `pending`: recorded and ready for a worker;
- `processing`: claimed by a worker;
- `retrying`: a retryable delivery failed and has a future due time;
- `sent`: handed to a configured external mail transport;
- `simulated`: captured by Laravel's `array` mailer or intentionally withheld
  from the `log` mailer;
- `failed`: the bounded retry budget was exhausted;
- `suppressed`: disabled by configuration or an allowed preference.

The database queue is pinned to the landlord connection by default. The queued
job remains tenant-aware, so Spatie Multitenancy establishes the recorded
tenant before reading its outbox. This prevents jobs from being stored in an
arbitrary current tenant database and prevents cross-tenant delivery reads.
Outbox creation is idempotent. External transport is at-least-once because an
ambiguous provider/network failure can occur after provider acceptance but
before the local terminal state is saved; templates are therefore safe when a
recipient receives a duplicate.

## Supported channels

In-application delivery is supported for internal tenant users and activated
patient and clinician portal accounts.

Email uses Laravel's reviewed mail transports. SMTP can be configured without
a new dependency. In local development, `MAIL_MAILER=log` records a simulated
delivery without writing a one-time invitation or password-reset URL into
application logs and does not claim that an external recipient received it.
Composite `failover` and `roundrobin` mailers are rejected for these sensitive
messages because a fallback to the log transport could expose a one-time URL
and the outbox could not reliably identify which child transport accepted it.

SMS and WhatsApp are deliberately reported as unavailable. No provider,
credentials, delivery receipt contract or webhook signature scheme has been
selected. They must not be marked sent until a separate provider integration is
reviewed and configured.

## Notification flows

### Portal invitations

Creating a patient or clinician invitation continues to return the one-time
activation link to the authorized administrator. It now also creates a
mandatory encrypted email outbox record. The established public domain events
remain secret-free; the one-time token is passed directly to the notification
boundary inside the invitation transaction and is never added to the event
payload.

### Laboratory report availability

The report-issuance transaction creates an in-app message and, when enabled,
an email outbox row before the existing patient and clinician report-ready
events are dispatched after commit. The email says only that a report is
available and links to the secure portal. It contains no patient identity or
report content.

### Portal password reset

An administrator can select **Send reset link** for an activated patient or
clinician account. The administrator never enters, sees or selects the new
password. Portal users can also request a reset from the sign-in page.

Reset requests have generic public responses to prevent account enumeration.
Only a SHA-256 token hash is stored in `portal_password_resets`; the link is
one-time, expires, and replaces any previous unused link. Completing a reset
revokes every active token for that portal account and writes portal audit
events.

### Critical results

Approval of a test result creates an escalation when at least one result row
has `flag: critical`. Approved report amendments are also inspected for newly
critical corrected rows. `critical_result_escalations` uses a unique immutable
source key, so redispatching the same approval cannot duplicate an escalation
while a later approved correction can create a new clinical escalation episode.

Active administrators, doctors and laboratory technicians receive mandatory
in-app and email notices. External email contains no patient or result detail.
Authorized responders must acknowledge an open record with an operational note
before it can be resolved with a resolution note.

An unacknowledged record becomes due after the configured interval. The
scheduled escalation command increments its level, emits another idempotent
notice and establishes the next due time. It does not silently resolve or
delete clinical evidence.

## Tenant schema

The single additive tenant migration creates:

- `notification_messages`;
- `notification_deliveries`;
- `notification_preferences`;
- `critical_result_escalations`;
- `portal_password_resets`.

The migration does not rename, delete or rewrite any existing table or row.
Its rollback drops only these five module tables. Production rollback should
not be used after live notification or escalation evidence exists without an
approved data-retention plan.

## Permissions

`view_notifications` is granted to all current active tenant roles.
`acknowledge_critical_results` is granted to superadmin, admin, doctor and
laboratory technician roles. Receptionists may view their own ordinary inbox
but cannot access the critical-result escalation API or actions.

Portal accounts can access only messages whose recipient type and numeric ID
match the authenticated portal token. Internal users have the same recipient
scoping. UUID guessing cannot cross this boundary.

## Runtime processes

The API and frontend continue to run as before. Reliable asynchronous delivery
also requires:

1. `php artisan queue:work --queue=default --tries=5`;
2. `php artisan schedule:work`.

The scheduler runs `notifications:dispatch-pending` and
`notifications:escalate-critical` every minute with overlap protection. The
dispatch command recovers pending outbox rows if the original queue dispatch
was unavailable.

## Configuration

Important environment variables are:

- `DB_QUEUE_CONNECTION=landlord`;
- `NOTIFICATION_QUEUE_CONNECTION=database`;
- `NOTIFICATION_EMAIL_ENABLED=true`;
- `NOTIFICATION_EMAIL_MAX_ATTEMPTS=5`;
- `NOTIFICATION_PROCESSING_TIMEOUT_MINUTES=15`;
- `PORTAL_FRONTEND_URL=http://127.0.0.1:5173` for local development;
- `PORTAL_PASSWORD_RESET_TTL_MINUTES=60`;
- `CRITICAL_RESULT_ACKNOWLEDGEMENT_MINUTES=15`;
- `CRITICAL_RESULT_REPEAT_MINUTES=15`.

The deployment environment must use its real public HTTPS frontend URL for
`PORTAL_FRONTEND_URL`. Credentials belong in the environment or secret manager,
never in Git.

## Validation contract

Automated validation must include:

- Composer validation, advisory audit and platform requirements;
- Pint checks for the reviewed PHP scope;
- focused notification/password-reset/critical-result backend tests;
- the complete backend test suite against test-only landlord and tenant
  databases;
- the complete frontend test suite, TypeScript validation and production build;
- an exact Git scope check before commit.

Development-tenant acceptance should verify one ordinary report-ready notice,
one password-reset link and one designated critical-result lifecycle. It must
not paste credentials, one-time links, patient identifiers or result values
into PowerShell, chat or evidence files.
