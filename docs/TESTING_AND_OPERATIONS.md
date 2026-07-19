# Testing and Operations

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

## 2. Required Test Environment

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
2. landlord context is restored
3. temporary tenant records are deleted
4. temporary tenant databases are dropped

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

Any output must be corrected before tests or commits.

---

## 8. Route Inspection

Result routes:

    php artisan route:list `
        --path=api/test-results

Order-item processing routes:

    php artisan route:list `
        --path=api/laboratory-order-items

Current expected result routes:

    GET   api/test-results
    GET   api/test-results/stats
    GET   api/test-results/{testResult}
    PUT   api/test-results/{testResult}
    POST  api/test-results/{testResult}/submit
    POST  api/test-results/{testResult}/approve
    POST  api/test-results/{testResult}/reject

Current expected processing routes:

    POST  api/laboratory-order-items/{laboratoryOrderItem}/start
    POST  api/laboratory-order-items/{laboratoryOrderItem}/test-result

---

## 9. Current Focused Result Tests

Current passing focused command:

    php artisan test `
        tests\Feature\Tenant\TestResult\TenantTestResultDraftTest.php `
        tests\Feature\Tenant\TestResult\TenantTestResultWorkflowTest.php `
        tests\Feature\Tenant\TestResult\TenantTestResultCompletionReadinessTest.php `
        tests\Feature\Tenant\LaboratoryOrder\TenantLaboratoryOrderStatusTransitionTest.php `
        --stop-on-failure

Latest result:

    5 tests passed
    185 assertions

---

## 10. Laboratory Reporting Validation

Focused reporting suite:

    php artisan test `
        tests/Feature/Tenant/LaboratoryReport

Latest focused result:

    11 tests passed
    430 assertions
    81.83 seconds

Adjacent clinical regression suite:

    php artisan test `
        tests/Feature/Tenant/TestResult `
        tests/Feature/Tenant/LaboratoryOrder `
        tests/Feature/Tenant/Specimen

Latest regression result:

    49 tests passed
    1,501 assertions
    549.69 seconds

Latest full project result:

    112 tests passed
    2,953 assertions
    1,208.24 seconds

---

## 11. Full Project Suite

At milestone completion:

    php artisan test

Do not create the milestone commit until the full suite passes.

Record the final:

- test count
- assertion count
- duration
- date

in:

    docs/CURRENT_STATE.md

---

## 12. Migration Policy

During feature implementation:

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

    git diff --cached --stat
    git diff --cached

Do not use broad staging commands until the file list has been reviewed.

Preferred staging:

    git add path\to\file1 path\to\file2

---

## 15. Milestone Completion Procedure

Required order:

1. finish focused implementation
2. run syntax checks
3. run whitespace checks
4. run focused result tests
5. run related regression tests
6. run full suite
7. migrate the development tenant
8. perform a manual API smoke test
9. update handover documents
10. review Git diff
11. create local checkpoint commit
12. push branch
13. open pull request
14. merge after review
15. create baseline tag

---

## 16. Current Branch

Current milestone branch:

    feature/laboratory-reporting

Previous completed baseline:

    baseline-test-processing-results-2523

The reporting milestone has passed its focused, regression and full project suites. Tag only after documentation review, commit, push, pull request and merge.

---

## 17. Troubleshooting

### Tests connect to the wrong database

Check:

    $env:APP_ENV
    $env:DB_CONNECTION
    $env:DB_LANDLORD_DATABASE
    $env:DB_TENANT_DATABASE

Clear cached configuration when necessary:

    php artisan config:clear

### Tenant request returns 404 for another tenant's UUID

This is expected tenant isolation behavior.

### Tenant result endpoint returns 403

Check:

- active tenant user model
- tenant user role
- `TenantUserPermissionCatalog`
- authentication context
- tenant header
- active status

### Route is missing

Run:

    php artisan route:list --path=api/test-results

Confirm:

    routes/api/tenant.php

requires:

    routes/api/tenant/test-results.php

### Temporary tenant database remains after a failed test

First verify that its name contains `_test_`.

Use the project's guarded cleanup logic.

Do not manually run a broad database-drop command.

---

## 18. Secret Handling

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
