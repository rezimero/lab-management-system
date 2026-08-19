# Analyzer Integration Phase C Acceptance

Phase C completes the user-facing Analyzer Integration workspace and prepares the module for controlled development-tenant acceptance.

## Source scope

Phase C adds:

- entitlement-aware analyzer routes;
- role-aware analyzer navigation;
- instrument administration workspace;
- one-time credential display/rotation workflow;
- analyzer test-code mapping workspace;
- analyzer parameter mapping workspace;
- validation/review queue;
- import provenance and audit timeline;
- reprocess/reject actions;
- canonical PowerShell simulator;
- frontend unit coverage for API, permissions and navigation.

## Safety boundary

Phase C source application does not:

- migrate the designated development tenant;
- enable `analyzer_integration` on the commercial beta plan;
- connect to a physical instrument;
- store plaintext machine credentials after the one-time UI response;
- auto-approve analyzer results.

## Validation order

1. Apply Phase C source only.
2. Run Analyzer-focused backend tests.
3. Run Analyzer-focused frontend tests.
4. Run complete frontend test suite.
5. Run `npm run typecheck`.
6. Run production `npm run build`.
7. Run complete backend regression suite.
8. Only then finalize the Analyzer migration against the designated development tenant.
9. Enable the analyzer entitlement for the designated development tenant/plan only after migration verification.
10. Use the canonical simulator against a mapped received specimen.
11. Verify draft creation, validation-queue behavior, provenance and existing approval/critical-result workflow.
12. Perform browser acceptance.
13. Commit/PR/CI only after the acceptance evidence is complete.
