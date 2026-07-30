# Testing and Operations

Updated:

    30 July 2026

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

Expected report routes:

    GET   api/laboratory-reports
    GET   api/laboratory-reports/{laboratoryReport}
    GET   api/laboratory-reports/{laboratoryReport}/pdf

The PDF route must remain behind:

    SetTenantContext:required
    auth:sanctum

and must use the report-view authorization request.

Profile routes must be verified directly from current route definitions before changing the contract:

    php artisan route:list |
        Select-String "profile|logo|favicon|signature"

Do not document guessed profile route paths.

---

## 9. Backend Test Baselines

Latest confirmed complete backend result:

    148 tests passed
    3,979 assertions
    duration 2,279.04 seconds
    validated 30 July 2026

Relevant profile, reporting and PDF coverage includes:

- tenant-profile resource contract
- administrator and superadministrator authorization
- non-administrator denial
- tenant isolation
- logo, favicon and director-signature uploads
- media ownership and UUID handling
- invalid file type and size rejection
- physical cleanup when profile media is replaced
- immutable signatory snapshot fields
- immutable tenant brand-colour snapshots
- report generation and retrieval
- immutable report-owned media copying
- report-media cleanup on failed generation
- report-media path, MIME and SHA-256 metadata
- permitted PDF download
- unknown-role denial
- cross-tenant report isolation
- filename sanitization
- snapshot-only PDF rendering
- PDF generation without media
- remote image rejection
- traversal rejection
- MIME mismatch rejection
- hash mismatch rejection

At backend milestone completion:

    php artisan test --stop-on-failure

Do not create a backend milestone commit until required focused, regression and full suites pass.

Record final test count, assertion count, duration and date in:

    docs/CURRENT_STATE.md

---

## 10. Frontend Test and Build Environment

Frontend root:

    lab-frontend

Focused test examples:

    npm test -- `
        src/pages/LaboratoryProfile.test.tsx

    npm test -- `
        src/features/laboratory-profile/utils/laboratoryBrandColors.test.ts

    npm test -- `
        src/features/laboratory-reports/components/ReportSignatureFooter.test.tsx

    npm test -- `
        src/pages/LaboratoryReportView.test.tsx `
        src/pages/LaboratoryReportView.download.test.tsx

Complete frontend suite for the validated Windows environment:

    npm test -- `
        --no-file-parallelism `
        --testTimeout=15000

The serial command avoids observed test-runner resource contention. It does not change the repository-wide Vitest timeout.

TypeScript validation:

    npm run typecheck

Production validation:

    npm run build

The build command executes:

    tsc --noEmit && vite build

Latest confirmed frontend baseline:

    32 test files passed
    121 tests passed
    TypeScript validation passed
    production build passed
    validated 30 July 2026

Relevant profile and report test files include:

    src/api/client.test.ts
    src/components/Layout/navigationConfig.test.tsx
    src/components/TenantAdminRoute.test.tsx
    src/features/laboratory-profile/utils/laboratoryBrandColors.test.ts
    src/features/laboratory-reports/components/ReportSignatureFooter.test.tsx
    src/pages/LaboratoryProfile.test.tsx
    src/pages/LaboratoryReportView.test.tsx
    src/pages/LaboratoryReportView.download.test.tsx

Use a single `Push-Location` and `finally` block so failures do not leave PowerShell in the wrong directory.

Do not raise the global Vitest timeout merely because one test is slow.

When testing a large controlled MUI input and per-character behaviour is not the subject under test, a direct Testing Library change event may be more appropriate than repeated user-event typing.

---

## 11. Frontend Characterization and Refactor Cycle

Required order:

1. inspect the active component and its API/context dependencies
2. identify the behaviour boundary
3. add or update focused characterization tests
4. run the focused test
5. make the smallest responsible implementation change
6. rerun the focused test
7. run affected regression tests
8. run `npm test -- --run`
9. run `npm run build`
10. run `git diff --check`
11. stage exact paths only
12. compare intended paths with `git diff --cached --name-only`
13. commit only when all gates pass
14. create a recovery tag only at a stable wave boundary

Do not use:

    git add .

Do not delete backups until the commit is confirmed, unless the previous version is already recoverable from a verified commit or tag.

---

## 12. Tenant Profile Media Validation

Profile media uploads use multipart form data.

Required field:

    file

Accepted formats:

- JPG
- JPEG
- PNG
- WEBP

Maximum size:

    2 MB

Rejected:

- SVG
- files outside the allowed image formats
- oversized files
- unauthorized users
- cross-tenant access

Frontend invariant:

Do not force:

    Content-Type: application/json

when the request body is `FormData`.

The browser or Axios must set the multipart boundary.

Replacement invariant:

Replacing a logo, favicon or director signature must remove the superseded physical file through the media service after the replacement is persisted safely.

---

## 13. Migration Policy

During backend feature implementation:

- do not manually migrate the development landlord or tenant before focused tests pass
- temporary test tenants run all tenant migrations during provisioning
- migration failures should first be diagnosed through isolated test tenants
- automated tests use only `lab_saas_landlord_test`

The report signatory migration is:

    database/migrations/landlord/2026_07_27_190000_add_report_signatory_fields_to_tenant_profiles_table.php

It was validated against the automated test landlord environment.

Before any manual deployment migration:

1. confirm Git branch and deployed commit
2. confirm Git status
3. confirm focused, regression and full-suite evidence
4. confirm the intended landlord database
5. back up important data when necessary
6. inspect migration status
7. run the repository's normal landlord migration procedure
8. verify the new columns
9. perform an authorized profile and report smoke test

Never migrate:

    lab_saas_landlord

as part of automated test execution.

---

## 14. Development Tenant Migration

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

## 15. Browser, Print and PDF Smoke Checklist

Use a tenant administrator account.

Laboratory profile:

1. open `/settings/laboratory-profile`
2. confirm the existing profile loads
3. update a non-destructive text field
4. save and reload
5. upload an allowed logo
6. upload an allowed director signature
7. confirm validation feedback for an invalid HEX value
8. save primary, secondary and accent colours
9. confirm the live preview updates

Browser report view:

1. issue a new report after profile media is present
2. open the issued report
3. confirm the laboratory logo renders
4. confirm laboratory name and supporting identity text render
5. confirm director name, qualification, registration number and designation render
6. confirm accreditation details render when present
7. confirm the signature renders
8. confirm primary, secondary and accent colours render from the report snapshot
9. confirm clinical semantic statuses remain independent of brand colours

Print preview:

1. open browser print preview
2. confirm logo and signature remain visible
3. confirm tenant colours remain visible
4. confirm the report remains readable in black-and-white or reduced-colour printing
5. confirm the tested report layout does not unexpectedly overflow
6. do not claim universal one-page output from a single sample report

Server-generated PDF:

1. click `Download PDF`
2. confirm the browser downloads an `application/pdf` attachment
3. confirm the filename uses the report number
4. open the downloaded PDF
5. confirm the report number, patient, order and results match the issued report
6. confirm snapshot colours render
7. confirm the immutable laboratory logo renders
8. confirm the immutable director signature renders
9. confirm signatory and accreditation details render
10. change the live profile and confirm the issued report PDF remains unchanged

Legacy compatibility:

- reports issued before report-owned media snapshotting may omit logo or signature images
- do not treat live-profile media substitution as an acceptable fix
- issue a new report to validate the current immutable media flow

The 29 July 2026 smoke report confirmed both the immutable laboratory logo and director signature in the downloaded server-generated PDF.

---

## 16. Git Verification

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

## 17. Backend Milestone Completion Procedure

Required order:

1. finish focused implementation
2. run PHP syntax checks
3. run whitespace checks
4. run focused tests
5. run related regression tests
6. run Composer validation when dependencies change
7. run Composer platform checks when dependencies or PHP extensions change
8. run the full backend suite
9. migrate the intended environment when required
10. perform an authorized API, browser or PDF smoke test when required
11. update handover documents
12. review the Git diff
13. stage exact reviewed paths
14. create the local checkpoint commit
15. push the branch
16. open the pull request
17. merge after review using a normal merge commit
18. update and verify local `develop`

---

## 18. Frontend Milestone Completion Procedure

Required order:

1. finish or characterize the focused behaviour
2. run the focused Vitest suite
3. run affected regression tests
4. run the complete frontend suite
5. run `npm run typecheck`
6. run `npm run build`
7. run `git diff --check`
8. perform browser, print and download smoke checks when rendering or file delivery changes
9. update handover documents
10. review the Git diff
11. stage exact reviewed paths
12. compare the staged path list with the intended path list
13. create the local checkpoint commit
14. push and open the pull request at the milestone boundary
15. merge after review using a normal merge commit
16. update and verify local `develop`

---

## 19. Documentation-Only Closeout Procedure

For the current documentation branch:

    docs/close-out-laboratory-profile-milestone

Product tests do not need to be rerun solely because Markdown files changed when:

- the documentation records already validated milestone evidence
- no code, configuration, migration or dependency file changed
- the documented commands and counts are checked against the completed milestone logs

Required documentation gates:

1. confirm the branch is based on merge commit `81c1e67`
2. confirm only the five core documentation files changed
3. normalize UTF-8 without BOM
4. run `git diff --check`
5. inspect `git diff --stat`
6. stage the five exact paths
7. compare staged paths with the intended list
8. run `git diff --cached --check`
9. commit locally
10. push and open a documentation-only pull request
11. merge normally into `develop`
12. update local `develop`
13. delete the documentation branch locally and remotely

---

## 20. Current Branch and Checkpoint

Canonical integration branch:

    develop

Latest integration baseline:

    Pull request:
        #13

    Merge commit:
        cce1e62

Current feature branch:

    feature/laboratory-report-pdf-export

Branch base:

    cce1e62

Product checkpoint before documentation:

    218ac1f

Milestone commits:

    4eb0980 chore add laboratory report PDF renderer
    570ae31 feat preserve immutable report media
    bfb1fbe feat add laboratory report PDF export
    218ac1f feat add laboratory report PDF download

Validation state:

    backend:
        148 tests passed
        3,979 assertions

    frontend:
        32 test files passed
        121 tests passed

    typecheck:
        passed

    production build:
        passed

    browser PDF smoke:
        passed with logo and director signature

The feature branch has not yet been pushed and no pull request has been opened.

After documentation is committed, push the feature branch and open one milestone pull request into `develop`.

---

## 21. Troubleshooting

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

This is expected tenant-isolation behaviour.

### Tenant result endpoint returns 403

Check:

- active tenant user model
- tenant user role
- `TenantUserPermissionCatalog`
- authentication context
- tenant header
- active status

### Laboratory-profile endpoint returns 403

Check:

- the active user role
- `MANAGE_TENANT_PROFILE`
- tenant context
- platform-superadministrator handling
- route middleware
- request authorization

### Profile image upload arrives without a file

Check:

- request body is `FormData`
- field name is `file`
- the API client did not force JSON content type
- browser-generated multipart boundary exists
- file size and extension are allowed

### PDF downloads but logo or signature is missing

First determine whether the report was issued before immutable report-media copying was introduced.

For a newly issued report, inspect:

- `laboratory_snapshot.logo_path`
- `laboratory_snapshot.logo_mime_type`
- `laboratory_snapshot.logo_hash`
- `laboratory_snapshot.director_signature_path`
- `laboratory_snapshot.director_signature_mime_type`
- `laboratory_snapshot.director_signature_hash`
- the corresponding files on the `public` disk
- actual MIME detection
- SHA-256 agreement
- report UUID path ownership

Do not make the renderer fetch current live-profile URLs.

A legacy report without report-owned media metadata may omit the images by design.

### PDF download request fails in the browser

Check:

- authenticated bearer token
- `X-Tenant-Domain`
- report UUID
- tenant isolation
- report-view permission
- response type `blob`
- API error sanitization
- object URL creation and cleanup
### Temporary tenant database remains after a failed test

First verify that its name contains the required automated-test pattern.

Use the project's guarded cleanup logic.

Do not manually run a broad database-drop command.

---

## 22. Secret and Identity Handling

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
