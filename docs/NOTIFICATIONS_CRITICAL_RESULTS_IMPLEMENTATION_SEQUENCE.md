# Notifications and Critical-Result Escalation Implementation Sequence

Status: completed and merged into `develop` through pull request #23 on
15 August 2026.

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

Completed.

Final evidence:

- development tenant migration already applied and verified current;
- focused Notifications backend: 7 tests / 178 assertions passed;
- complete backend: 255 tests / 6,655 assertions passed;
- complete frontend: 44 test files / 160 tests passed;
- TypeScript validation passed;
- production Vite build passed;
- essential browser smoke N01-N03 passed;
- pull request #23 Backend and Frontend CI passed;
- pull request #23 merged normally;
- post-merge `develop` CI run 31871444220 passed;
- local `develop` synchronized;
- local and remote feature branches removed;
- no production deployment performed.

Historical source-apply, repair, lifecycle and migration packages must not be
rerun against the merged `develop` branch. No deployment/CD action was part of
N8 because no reviewed production target or deployment workflow was approved.
