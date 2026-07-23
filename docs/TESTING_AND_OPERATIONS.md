# Testing and Operations

Updated:

    23 July 2026

## 1. Safety Principle

Automated tests must never connect to the real development landlord database.

Allowed automated test landlord database:

    lab_saas_landlord_test

Development landlord database:

    lab_saas_landlord

The base test class validates both:

- configured landlord database
- active landlord database

Tests fail immediately when either value is unsafe.

---

## 2. Required Backend Test Environment

PowerShell environment:

    $env:APP_ENV = "testing"
    $env:DB_CONNECTION = "landlord"
    $env:DB_LANDLORD_DATABASE = "lab_saas_landlord_test"
    $env:DB_TENANT_DATABASE = "tenant_test_unresolved"

Verify Laravel environment:

    php artisan about

Verify active test configuration through the test suite rather than manually connecting automated tests to a development tenant.

---

## 3. Automated Tenant Test Lifecycle

Feature tests use the real application workflow.

Each test tenant is created by:

1. calling `/api/register`
2. creating a landlord tenant record
3. provisioning a tenant database
4. running tenant migrations
5. creating the tenant administrator
6. logging in through `/api/login`
7. creating a tenant-bound token
8. sending authenticated requests with `X-Tenant-Domain`

During teardown:

1. authentication state is cleared
2. landlord database and guard context are restored
3. temporary tenant records are deleted
4. temporary tenant databases are dropped

Tenant restoration must include:

    Config::set('database.default', 'landlord');
    Config::set('auth.defaults.guard', 'landlord');
    Auth::forgetGuards();

Database cleanup refuses to drop databases that do not match the expected automated-test naming pattern.

---

## 4. Tenant Request Headers

Authenticated tenant API requests require:

    Accept: application/json
    Authorization: Bearer <token>
    X-Tenant-Domain: <domain>

Never print real token values in documentation, commits or chat responses.

---

## 5. PHP Syntax Validation

Validate one PHP file:

    php -l path\to\file.php

Validate a batch:

    foreach ($file in $paths) {
        php -l $file

        if ($LASTEXITCODE -ne 0) {
            throw "PHP syntax validation failed: $file"
        }
    }

---

## 6. UTF-8 Without BOM Normalization

Use:

    $utf8NoBom = [System.Text.UTF8Encoding]::new($false)

For each file:

    $resolvedPath = Resolve-Path $path

    $content = [System.IO.File]::ReadAllText(
        $resolvedPath
    )

    $content = $content `
        -replace "`r`n", "`n" `
        -replace "`r", "`n"

    $content = $content.TrimEnd(
        "`r",
        "`n"
    ) + "`n"

    [System.IO.File]::WriteAllText(
        $resolvedPath,
        $content,
        $utf8NoBom
    )

---

## 7. Whitespace Validation

Run after every implementation batch:

    git diff --check

Expected:

    no output

Any output must be corrected before commit.

---

## 8. Backend Route Inspection

Result routes:

    php artisan route:list `
        --path=api/test-results

Order-item processing routes:

    php artisan route:list `
        --path=api/laboratory-order-items

Expected result routes:

    GET   api/test-results
    GET   api/test-results/stats
    GET   api/test-results/{testResult}
    PUT   api/test-results/{testResult}
    POST  api/test-results/{testResult}/submit
    POST  api/test-results/{testResult}/approve
    POST  api/test-results/{testResult}/reject

Expected processing routes:

    POST  api/laboratory-order-items/{laboratoryOrderItem}/start
    POST  api/laboratory-order-items/{laboratoryOrderItem}/test-result

Reporting routes:

    php artisan route:list `
        --path=api/laboratory-reports

---

## 9. Backend Test Baselines

Latest confirmed complete backend result:

    133 tests passed
    3,458 assertions

This baseline was not rerun during the frontend documentation synchronization.

At backend milestone completion:

    php artisan test

Do not create a backend milestone commit until the required focused, regression and full suites pass.

Record the final test count, assertion count, duration and date in:

    docs/CURRENT_STATE.md

---

## 10. Frontend Test and Build Environment

Frontend root:

    lab-frontend

Focused test example:

    npm test -- `
        src/components/landlords/AllTenants.test.tsx

Complete frontend suite:

    npm test -- --run

Production validation:

    npm run build

The build command executes:

    tsc --noEmit && vite build

Latest confirmed frontend baseline:

    5 test files passed
    47 tests passed
    production build passed

Current passing test files:

    src/contexts/AuthContext.test.tsx
        15 tests

    src/components/PrivateRoute.test.tsx
        4 tests

    src/components/landlords/AllTenants.test.tsx
        14 tests

    src/api/client.test.ts
        13 tests

    src/App.test.tsx
        1 test

Use a single `Push-Location` and `finally` block so failures do not leave PowerShell in the wrong directory:

    Push-Location .\lab-frontend

    try {
        npm test -- --run

        if ($LASTEXITCODE -ne 0) {
            throw "Frontend tests failed."
        }

        npm run build

        if ($LASTEXITCODE -ne 0) {
            throw "Frontend build failed."
        }
    }
    finally {
        Pop-Location
    }

Do not raise the global Vitest timeout merely because one test is slow.

When testing a large controlled MUI input and per-character behavior is not the subject under test, a direct Testing Library change event may be more appropriate than repeated user-event typing.

---

## 11. Frontend Characterization and Refactor Cycle

Required order:

1. inspect the active component and its API/context dependencies
2. identify the behavior boundary
3. add or update focused characterization tests
4. run the focused test
5. make the smallest responsible implementation change
6. rerun the focused test
7. run `npm test -- --run`
8. run `npm run build`
9. run `git diff --check`
10. stage exact paths only
11. compare intended paths with `git diff --cached --name-only`
12. commit only when all gates pass
13. create a recovery tag only at a stable wave boundary

Do not use:

    git add .

Do not delete backups until the commit is confirmed, unless the previous version is already recoverable from a verified commit or tag.

---

## 12. Migration Policy

During backend feature implementation:

- do not manually migrate the development tenant before focused tests pass
- temporary test tenants run all tenant migrations during provisioning
- migration failures should first be diagnosed through isolated test tenants

Before any manual migration:

1. confirm Git branch
2. confirm Git status
3. confirm focused tests
4. confirm regression tests
5. confirm full suite
6. confirm active development tenant
7. back up important tenant data when necessary

---

## 13. Development Tenant Migration

Development tenant:

    Domain:
        development

    Database:
        tenant_s4d0mhau_development

Do not guess the migration command.

Before migration, inspect the repository's existing tenant migration command or tenant switcher workflow.

Confirm the active database immediately before running a migration.

Never run tenant migrations against an unresolved tenant connection.

---

## 14. Git Verification

Before implementation:

    git branch --show-current
    git status --short
    git log -5 --oneline

Before commit:

    git diff --check
    git status --short
    git diff --stat

Inspect staged files:

    git diff --cached --name-status
    git diff --cached --stat
    git diff --cached --check

Preferred staging:

    git add -- path\to\file1 path\to\file2

Compare the intended and actual stage:

    $actualStage = @(
        git diff --cached --name-only
    )

    Compare-Object `
        $intendedFiles `
        $actualStage

The comparison must return no output.

Do not use:

    git add .

---

## 15. Backend Milestone Completion Procedure

Required order:

1. finish focused implementation
2. run PHP syntax checks
3. run whitespace checks
4. run focused tests
5. run related regression tests
6. run the full backend suite
7. migrate the development tenant when required
8. perform a manual API smoke test when required
9. update handover documents
10. review the Git diff
11. stage exact reviewed paths
12. create the local checkpoint commit
13. push the branch
14. open the pull request
15. merge after review
16. create the baseline tag

---

## 16. Frontend Milestone Completion Procedure

Required order:

1. finish or characterize the focused behavior
2. run the focused Vitest suite
3. run `npm test -- --run`
4. run `npm run build`
5. run `git diff --check`
6. update handover documents
7. review the Git diff
8. stage exact reviewed paths
9. compare the staged path list with the intended path list
10. create the local checkpoint commit
11. create a recovery tag when the wave is stable
12. push and open the pull request at the milestone boundary

---

## 17. Current Branch and Checkpoint

Current branch:

    feature/frontend-vite-migration

Current frontend checkpoint:

    e06166e
    frontend-tenant-management-wave-2

Completed frontend tags:

    frontend-vite-baseline
    frontend-auth-refactor-wave-1
    frontend-tenant-management-wave-1
    frontend-tenant-management-wave-2

---

## 18. Troubleshooting

### Frontend build reports that Vite is unavailable

Confirm the current directory:

    Get-Location

The frontend build must run from:

    lab-frontend

Use `try` and `finally` around `Push-Location` so failure handling does not pop the directory twice.

### A PowerShell hash variable shows an old value

A failed assignment does not necessarily clear the previous variable value.

Before retrying:

    Remove-Variable actualHash `
        -ErrorAction SilentlyContinue

Use `$ErrorActionPreference = "Stop"` inside protected installation blocks.

### Tests connect to the wrong database

Check:

    $env:APP_ENV
    $env:DB_CONNECTION
    $env:DB_LANDLORD_DATABASE
    $env:DB_TENANT_DATABASE

Clear cached configuration when necessary:

    php artisan config:clear

### Tenant request returns 404 for another tenant's UUID

This is expected tenant-isolation behavior.

### Tenant result endpoint returns 403

Check:

- active tenant user model
- tenant user role
- `TenantUserPermissionCatalog`
- authentication context
- tenant header
- active status

### Temporary tenant database remains after a failed test

First verify that its name contains the required automated-test pattern.

Use the project's guarded cleanup logic.

Do not manually run a broad database-drop command.

---

## 19. Secret and Identity Handling

Never include:

- bearer tokens
- production passwords
- Paystack keys
- database passwords
- private API keys
- customer credentials

in:

- source files
- Markdown documents
- test output
- Git commits
- chat messages

Use placeholders:

    <TOKEN>
    <PASSWORD>
    <SECRET_KEY>

Shared command logs should also redact Git author and tagger email addresses.
