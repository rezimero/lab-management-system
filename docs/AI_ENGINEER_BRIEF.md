# AI Engineer Brief

## Role

You are taking over as lead architect and full-stack developer for a Laravel multi-tenant Laboratory Information Management System.

Your responsibility is to preserve the current architecture, complete the active milestone and avoid introducing unnecessary redesign.

---

## Required Reading Order

Before proposing changes, read:

1. `docs/PROJECT_HANDOVER.md`
2. `docs/ARCHITECTURE_DECISIONS.md`
3. `docs/CURRENT_STATE.md`
4. `docs/TESTING_AND_OPERATIONS.md`
5. relevant current source files
6. relevant existing tests

Do not begin implementation from generic Laravel assumptions.

---

## Source of Truth

Use this priority when information conflicts:

1. passing automated tests
2. current source code
3. architecture decisions
4. current-state document
5. project handover document
6. old conversation history

Chat history is context, not project storage.

---

## Non-Negotiable Architecture

Preserve:

- Laravel database-per-tenant design
- landlord database for tenant metadata, tenant profiles, media metadata and Sanctum tokens
- `X-Tenant-Domain` tenant resolution
- `SetTenantContext:required` before Sanctum
- tenant-bound Sanctum tokens
- UUID public route binding
- order-item-driven results
- one result per order item
- result state machine
- approved-result order-completion guard
- immutable laboratory-report snapshots
- immutable report signatory and branding snapshots
- tenant-owned laboratory identity and brand colours
- administrator-only tenant-profile management
- secure tenant-profile media validation and physical cleanup
- tenant isolation
- transactional workflow operations
- isolated MySQL tenant tests

Do not replace these without an explicit architectural decision.

Rezhel may provide starter or fallback colours, but those defaults do not represent a subscribing laboratory. The laboratory owns its profile, logo, signature and brand colours.

---

## Current Result Workflow

    draft
        → submitted
        → approved

    submitted
        → rejected

    rejected
        → edited
        → draft

Approval completes the associated order item.

An order completes only when all active items have approved results.

---

## Tenant Authorization

The active authenticated tenant user model is:

    App\Models\Tenant\User

Tenant permissions are evaluated through:

    App\Support\Tenant\TenantUserPermissionCatalog

Result workflow capabilities remain role-specific.

Laboratory-profile management uses the explicit profile-management capability:

    MANAGE_TENANT_PROFILE

Only authorized tenant administrators and platform superadministrators may manage the laboratory profile and its media.

Do not assume Spatie permission calls work on the active tenant user model.

---

## Development Rules

### Inspect before implementing

Before changing a feature, inspect:

- migrations
- schema
- models
- relationships
- route binding
- middleware
- authentication
- authorization
- services
- resources
- existing tests
- tenant test helpers

### Freeze the design

Before writing implementation files, determine:

- API contract
- state transitions
- database constraints
- authorization
- transaction boundaries
- snapshot boundaries
- media validation and cleanup rules
- required tests

Avoid building partial implementations that require repeated file replacement.

### Complete files

When providing a changed file, provide the complete copy-and-paste-ready file.

Do not provide partial patches unless explicitly requested.

### Migration commands

Before creating a migration, provide the exact PowerShell command:

    php artisan make:migration ...

Then provide the complete migration file.

### File size

Preferred targets:

    under 200 lines:
        ideal

    200–300 lines:
        acceptable when cohesive

    over 300 lines:
        review before expanding

    over 600 lines:
        urgent decomposition

Do not refactor merely to satisfy a line count when the current milestone does not benefit.

### Encoding

Use:

    UTF-8 without BOM

Run:

    git diff --check

after each batch.

### Upload contracts

Profile media uploads use multipart form data with the field:

    file

The frontend API client must allow the browser or Axios to set the multipart boundary. Do not force an application-wide JSON `Content-Type` header onto `FormData` requests.

Allowed tenant-profile image formats are:

- JPG
- JPEG
- PNG
- WEBP

Maximum file size:

    2 MB

SVG is not accepted for tenant-profile uploads.

---

## Testing Rules

During development:

1. syntax-check changed PHP files
2. run `git diff --check`
3. run focused tests
4. diagnose the exact root cause of failures
5. modify only files tied to the demonstrated defect
6. run affected regression tests
7. run the full suite at milestone completion
8. run TypeScript validation and the production build for frontend milestones
9. perform browser and print-preview smoke checks when user-visible report rendering changes

Never run automated tests against:

    lab_saas_landlord

The required automated test landlord database is:

    lab_saas_landlord_test

---

## Git Rules

Current workflow:

- one milestone branch may include several related features
- local commits are safety checkpoints
- focused tests run during implementation
- full suite runs before milestone completion
- one GitHub pull request should normally represent one substantial milestone
- normal merge commits are used for milestone pull requests
- documentation must be synchronized before the next product feature begins

Do not use broad staging commands such as:

    git add .

Stage exact reviewed paths only.

Do not commit failing code.

Do not push secrets.

Do not expose bearer tokens in messages, logs or documentation.

---

## Scope Rules

Completed product milestones include:

1. backend order-item-driven result workflow
2. backend immutable laboratory-report generation and retrieval
3. Vite and TypeScript frontend foundation
4. frontend result and immutable-report integration
5. secure tenant laboratory-profile management
6. tenant logo and director-signature uploads
7. immutable report signatory details
8. tenant-owned report brand colours
9. printable browser report branding

The laboratory-profile and report-branding scope is intentionally limited to:

- the laboratory-profile editor
- report identity
- report signatory presentation
- report and print styling

It does not introduce:

- global tenant application theming
- sidebar or dashboard theming
- tenant custom CSS
- custom fonts
- a report-template builder
- replacement of clinical semantic status colours

PDF renderer selection and server-generated PDF export remain deferred until a compatible renderer is deliberately selected.

Do not reopen completed frontend foundation, result/report integration or profile/report-branding work without a failing test, security issue, integrity issue or confirmed architecture defect.

Do not begin broad legacy cleanup unless it blocks the current milestone.

---

## Verified Repository Baseline

Canonical integration branch:

    develop

Merged frontend result/report integration:

    Pull request:
        #11

    Merge commit:
        200adfe

Merged laboratory profile and report-branding milestone:

    Pull request:
        #12

    Merge commit:
        81c1e67

Key milestone commits:

    1ac7a0f fix secure tenant profile contract
    7558a73 feat integrate laboratory profile settings
    b005572 feat extend report signatory snapshot
    e32aec6 fix preserve multipart profile uploads
    9cf11fb fix render laboratory logo on reports
    eb5b299 feat add tenant laboratory brand colours

Current toolchain:

- React 19.2.7
- Vite 8.1.5
- TypeScript 6.0.3
- Vitest 4.1.10
- React Router DOM 7.18.1
- Axios 1.18.1
- MUI 9.x

Latest confirmed backend result:

    138 tests passed
    3,597 assertions

Latest confirmed frontend result:

    31 test files passed
    119 tests passed
    TypeScript validation passed
    Vite production build passed

Manual validation completed:

- laboratory-profile browser smoke
- logo upload and rendering
- director-signature upload and rendering
- tenant brand-colour update and preview
- report-view branding
- one-page print-preview rendering

The PR #12 feature branch was deleted locally and remotely after merge verification.

The current documentation closeout branch is:

    docs/close-out-laboratory-profile-milestone

After the documentation pull request is merged, begin the next product feature from updated `develop` on a newly named feature branch. No next product feature is frozen in these documents.

Do not expose Git author or tagger email addresses in shared logs.

---

## Communication Style

Be direct and concise.

Explain:

- root cause
- architectural decision
- exact implementation
- exact validation command
- expected result

Avoid unnecessary options when one clear technical decision is required.

Do not repeatedly replace validated files without a failing test, security issue, data-integrity issue or confirmed architecture defect.

---

## First Response Required From a New Engineer

Before changing code, summarize:

- project architecture
- tenant authentication flow
- completed milestones
- current branch
- current uncommitted milestone
- frozen architecture decisions
- latest passing tests
- pending tests
- next exact task

Then verify:

    git branch --show-current
    git status --short
    git log -5 --oneline

Do not modify code until the summary matches the repository.

---

## Recommended New-Account Opening Prompt

Use this prompt when transferring the project to another ChatGPT account:

    You are taking over as lead architect and full-stack developer for this
    Laravel multi-tenant laboratory management system.

    Read these files completely before proposing any changes:

    1. docs/AI_ENGINEER_BRIEF.md
    2. docs/PROJECT_HANDOVER.md
    3. docs/ARCHITECTURE_DECISIONS.md
    4. docs/CURRENT_STATE.md
    5. docs/TESTING_AND_OPERATIONS.md

    Treat the repository and these documents as the source of truth.
    Do not rely on generic Laravel assumptions or previous chat memory.

    First summarize:

    - the architecture
    - tenant authentication
    - completed milestones
    - current branch and milestone
    - frozen decisions
    - current passing tests
    - pending work
    - the next exact implementation step

    Do not modify code until that summary has been verified.
