[CmdletBinding()]
param(
    [Parameter(Mandatory)]
    [string] $TenantDomain,

    [Parameter(Mandatory)]
    [string] $AnalyzerUuid,

    [Parameter(Mandatory)]
    [string] $MachineToken,

    [Parameter(Mandatory)]
    [string] $SampleId,

    [Parameter(Mandatory)]
    [string] $TestCode,

    [Parameter(Mandatory)]
    [string] $ParameterCode,

    [Parameter(Mandatory)]
    [string] $ParameterName,

    [Parameter(Mandatory)]
    [string] $Value,

    [string] $Unit = '',

    [string] $ReferenceRange = '',

    [string] $Flag = '',

    [string] $ApiBase = 'http://127.0.0.1:8000/api',

    [string] $MessageId = '',

    [string] $IdempotencyKey = ''
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

if ([string]::IsNullOrWhiteSpace($MessageId)) {
    $MessageId = 'sim-' + [guid]::NewGuid().ToString('N')
}

if ([string]::IsNullOrWhiteSpace($IdempotencyKey)) {
    $IdempotencyKey = $MessageId
}

$Endpoint = (
    $ApiBase.TrimEnd('/') +
    '/analyzer-machine/messages'
)

$Headers = @{
    'X-Tenant-Domain' = $TenantDomain
    'X-Analyzer-Id' = $AnalyzerUuid
    'X-Rezhel-Idempotency-Key' = $IdempotencyKey
    'Authorization' = "Bearer $MachineToken"
    'Accept' = 'application/json'
}

$Observation = [ordered]@{
    code = $ParameterCode
    name = $ParameterName
    value = $Value
}

if (-not [string]::IsNullOrWhiteSpace($Unit)) {
    $Observation.unit = $Unit
}

if (-not [string]::IsNullOrWhiteSpace($ReferenceRange)) {
    $Observation.reference_range = $ReferenceRange
}

if (-not [string]::IsNullOrWhiteSpace($Flag)) {
    $Observation.flag = $Flag
}

$Payload = [ordered]@{
    schema = 'rezhel.analyzer-result.v1'
    message_id = $MessageId
    sent_at = (Get-Date).ToString('o')
    results = @(
        [ordered]@{
            result_key = "$SampleId`:$TestCode"
            sample_id = $SampleId
            test_code = $TestCode
            observations = @(
                $Observation
            )
        }
    )
}

$Body = $Payload |
    ConvertTo-Json -Depth 8

Write-Host ''
Write-Host 'REZHEL ANALYZER CANONICAL SIMULATOR'
Write-Host "Endpoint        : $Endpoint"
Write-Host "Tenant domain   : $TenantDomain"
Write-Host "Analyzer UUID   : $AnalyzerUuid"
Write-Host "Sample          : $SampleId"
Write-Host "Test code       : $TestCode"
Write-Host "Idempotency key : $IdempotencyKey"
Write-Host ''

try {
    $Response = Invoke-RestMethod `
        -Method Post `
        -Uri $Endpoint `
        -Headers $Headers `
        -ContentType 'application/json' `
        -Body $Body

    Write-Host 'SIMULATOR REQUEST ACCEPTED.'
    $Response |
        ConvertTo-Json -Depth 10
}
catch {
    Write-Host 'SIMULATOR REQUEST FAILED.'
    throw
}
