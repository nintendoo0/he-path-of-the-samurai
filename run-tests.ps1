#!/usr/bin/env pwsh
# Test runner for He Path of the Samurai project

param(
    [Parameter(Position=0)]
    [ValidateSet('all', 'rust', 'php', 'python', 'pascal', 'api', 'integration')]
    [string]$Target = 'all'
)

$ErrorActionPreference = "Continue"

function Write-TestHeader {
    param([string]$Message)
    Write-Host "`n===========================================" -ForegroundColor Cyan
    Write-Host "  $Message" -ForegroundColor Cyan
    Write-Host "===========================================`n" -ForegroundColor Cyan
}

function Write-Success {
    param([string]$Message)
    Write-Host "OK $Message" -ForegroundColor Green
}

function Write-Failure {
    param([string]$Message)
    Write-Host "FAIL $Message" -ForegroundColor Red
}

function Write-Info {
    param([string]$Message)
    Write-Host "INFO $Message" -ForegroundColor Yellow
}

function Test-ApiEndpoints {
    Write-TestHeader "Testing API Endpoints"
    
    $endpoints = @(
        @{ Name = "Dashboard"; Url = "http://localhost:8080/" }
        @{ Name = "ISS Tracker"; Url = "http://localhost:8081/iss" }
        @{ Name = "Telemetry"; Url = "http://localhost:8080/telemetry" }
        @{ Name = "Astronomy"; Url = "http://localhost:8080/astronomy" }
        @{ Name = "OSDR"; Url = "http://localhost:8080/osdr" }
    )
    
    $failCount = 0
    foreach ($endpoint in $endpoints) {
        Write-Info "Testing $($endpoint.Name)..."
        try {
            $response = Invoke-WebRequest -Uri $endpoint.Url -UseBasicParsing -TimeoutSec 10
            if ($response.StatusCode -eq 200) {
                Write-Success "$($endpoint.Name) - Status $($response.StatusCode)"
            } else {
                Write-Failure "$($endpoint.Name) - Status: $($response.StatusCode)"
                $failCount++
            }
        } catch {
            Write-Failure "$($endpoint.Name) - Error: $($_.Exception.Message)"
            $failCount++
        }
        Start-Sleep -Milliseconds 500
    }
    
    return $failCount
}

# Main execution
Write-Host "`nTest Suite for He Path of the Samurai`n" -ForegroundColor Magenta

$totalFailures = 0

switch ($Target) {
    'api' { $totalFailures += Test-ApiEndpoints }
    default { 
        Write-Host "Running API tests..." -ForegroundColor Cyan
        $totalFailures += Test-ApiEndpoints 
    }
}

# Summary
Write-Host "`n===========================================" -ForegroundColor Cyan
Write-Host "  Test Summary" -ForegroundColor Cyan
Write-Host "===========================================`n" -ForegroundColor Cyan

if ($totalFailures -eq 0) {
    Write-Success "All tests passed!"
    exit 0
} else {
    Write-Failure "Some tests failed. Total failures: $totalFailures"
    exit 1
}
