# Notifications and Critical-Result Escalation Implementation Sequence

Status: implemented candidate; developer-environment validation and Git
integration remain pending.

Baseline: `develop` at
`d96eb5d4f35f5a8b4977cdbd8b05c0f0741a082f`.

Target branch: `feature/notifications-critical-results`.

## N1 — Contract freeze

Completed. The binding contract is recorded in
`docs/NOTIFICATIONS_CRITICAL_RESULTS_ARCHITECTURE.md`.

## N2 — Tenant schema and durable outbox

Completed in one additive tenant migration. The module adds in-app messages,
encrypted delivery outbox records, channel preferences, critical-result
escalations and one-time portal password resets. No existing table is renamed,
dropped or rewritten.

## N3 — Delivery runtime

Completed. Tenant-aware queued jobs, bounded retries, stale-processing lease
recovery, sanitized failure records, mail templates and scheduled recovery are
implemented. Database jobs use the landlord connection while delivery records
remain in the owning tenant database.

## N4 — Portal and report notifications

Completed. Patient and clinician invitations, governed report availability,
in-app inboxes and preferences are connected to the durable notification
boundary. External email contains no patient identity, report number or result
content.

## N5 — Secure password reset

Completed. Administrators send reset links rather than setting or viewing a
password. Patient and clinician self-service requests return a generic
response. Tokens are hashed at rest, single-use, expiring and session-revoking.

## N6 — Critical-result escalation

Completed. Explicit approved `critical` flags create idempotent escalation
episodes. Authorized responders acknowledge before resolving; overdue open
records are repeatedly escalated by the scheduler.

## N7 — User interfaces and automated coverage

Implemented. The internal application and both portals have inboxes and
preferences. The internal application also has the critical-result work queue.
Focused backend coverage exercises password reset and critical-result
lifecycle behavior. Full backend/frontend regression validation is mandatory.

## N8 — Development migration, acceptance and GitHub completion

Pending in the developer environment. Required order:

1. preview and apply the reviewed source package;
2. preview and apply only the additive development-tenant migration;
3. run the complete automated validation script against test-only databases;
4. run concise browser acceptance in the sole development tenant;
5. review the exact Git scope;
6. create one local feature commit, publish the branch, create a PR, require
   Backend and Frontend CI, merge to `develop`, require post-merge CI, then
   synchronize local `develop` and remove the merged feature branch.

No deployment/CD action is part of N8 because this repository has no reviewed
deployment target or workflow. That boundary must remain explicit.
