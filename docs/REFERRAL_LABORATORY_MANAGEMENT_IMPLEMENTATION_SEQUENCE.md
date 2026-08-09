# Referral Laboratory Management — Controlled Implementation Sequence

Baseline: `feature/referral-laboratory-management` at
`6ff700a57c96b4b262c8deed111716169bd6ee02`.

## Controlled path

1. Apply the approved architecture contract as the first documentation change.
2. Implement schema, models, feature entitlement and permissions.
3. Implement directory and test mappings.
4. Implement referral creation and workflow transitions.
5. Integrate external result capture with the existing standard result workflow.
6. Implement the Referral Laboratories frontend and order-item integration.
7. Run focused referral tests plus the affected clinical/commercial regression
   tests.
8. Run one final scoped formatting, PHP syntax, frontend typecheck and build
   gate.
9. Stage only controlled milestone paths.
10. Create one local feature commit or a small sequence of coherent local
    commits.
11. Push, create a pull request into `develop`, and use a normal merge commit.

## Stop conditions

Stop without resetting work when:

- the feature branch or base commit is unexpected
- the worktree contains unrelated changes
- a tenant migration conflicts with the existing schema
- order/result/report regression contracts fail
- a remote feature branch has diverged
- GitHub required checks fail
- merge conflicts occur

Do not:

- force push
- squash
- rebase
- run live tenant migrations
- stage with `git add .`
- mix notification, portal, analyzer or interoperability work into this branch
