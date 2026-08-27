# ============================================================
#  Warehouse Management System - Unified Launcher
#  ------------------------------------------------
#  - Starts Laravel Backend  (php artisan serve)  on :8000
#  - Starts React Frontend   (npm run dev)        on :5173
#  - Prepares first-run setup automatically (idempotent).
#
#  Usage:  .\start.ps1        (PowerShell)
#          start.bat          (double-click / CMD)
# ============================================================

$ErrorActionPreference = 'Continue'

$Root   = Split-Path -Parent $MyInvocation.MyCommand.Path
$LaravelApp = Join-Path $Root 'Warehouses-Laravel'
$ReactApp   = Join-Path $Root 'Warehouse_React'

$BackendUrl = 'http://127.0.0.1:8000/api'
$FrontendDefault = 'http://localhost:5173'
$backendPort = 8000
$frontendPort = 5173

# Free any leftover php/node listener from a previous run on our ports,
# so a stale process can never block the next start.
foreach ($port in @($backendPort, $frontendPort)) {
    Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue | ForEach-Object {
        $proc = Get-Process -Id $_.OwningProcess -ErrorAction SilentlyContinue
        if ($proc -and $proc.ProcessName -in @('php', 'node')) {
            Write-Host ("Freeing port {0} (left over from a previous run) ..." -f $port) -ForegroundColor Yellow
            Stop-Process -Id $_.OwningProcess -Force -ErrorAction SilentlyContinue
        }
    }
}

function Write-Step([string]$Message) {
    Write-Host ''
    Write-Host ("==> " + $Message) -ForegroundColor Cyan
}

function Invoke-Checked([string]$Label, [scriptblock]$Block) {
    Write-Step $Label
    & $Block
    if ($LASTEXITCODE -ne 0) {
        Write-Host ("[ERROR] Step failed: " + $Label) -ForegroundColor Red
        Write-Host 'Fix the reported error and run start.ps1 again.' -ForegroundColor Yellow
        exit 1
    }
}

# ------------------------------------------------------------
#  1) Backend setup (first run only / idempotent afterwards)
# ------------------------------------------------------------
if (-not (Test-Path (Join-Path $LaravelApp 'vendor'))) {
    Invoke-Checked 'Installing Laravel dependencies (composer install) ...' {
        Push-Location $LaravelApp
        composer install --no-interaction --prefer-dist
        Pop-Location
    }
}

if (-not (Test-Path (Join-Path $LaravelApp '.env'))) {
    Write-Step 'Creating Laravel .env from .env.example ...'
    Copy-Item (Join-Path $LaravelApp '.env.example') (Join-Path $LaravelApp '.env')
}

if (-not (Select-String -Path (Join-Path $LaravelApp '.env') -Pattern '^APP_KEY=.+$' -Quiet)) {
    Write-Step 'Generating Laravel APP_KEY ...'
    Push-Location $LaravelApp
    php artisan key:generate
    Pop-Location
}

if (-not (Test-Path (Join-Path $LaravelApp 'database\database.sqlite'))) {
    Write-Step 'Creating SQLite database file ...'
    New-Item -ItemType File -Path (Join-Path $LaravelApp 'database\database.sqlite') -Force | Out-Null
}

Write-Step 'Running Laravel migrations ...'
Push-Location $LaravelApp
php artisan migrate --force
Pop-Location
if ($LASTEXITCODE -ne 0) {
    Write-Host '[ERROR] Migration failed.' -ForegroundColor Red
}

Write-Host ''
Write-Host 'Starting Laravel Backend ...' -ForegroundColor Green
$backendJob = Start-Job -Name 'backend' -ScriptBlock {
    param($app)
    Set-Location $app
    php artisan serve --host=127.0.0.1 --port=8000
} -ArgumentList $LaravelApp

# ------------------------------------------------------------
#  2) Frontend setup (first run only / idempotent afterwards)
# ------------------------------------------------------------
if (-not (Test-Path (Join-Path $ReactApp 'node_modules'))) {
    Write-Host ''
    Write-Host 'Installing React dependencies (npm install)... ' -ForegroundColor Green -NoNewline
    Write-Host 'this can take several minutes the first time.' -ForegroundColor Yellow
    Invoke-Checked 'Installing React dependencies (npm install) ...' {
        Push-Location $ReactApp
        npm install
        Pop-Location
    }
}

# ------------------------------------------------------------
#  3) Launch frontend server as background job (same window)
# ------------------------------------------------------------
Write-Host 'Starting React Frontend ...' -ForegroundColor Green
$frontendJob = Start-Job -Name 'frontend' -ScriptBlock {
    param($app)
    Set-Location $app
    npm run dev
} -ArgumentList $ReactApp

Write-Host 'Press Ctrl+C to stop both servers.' -ForegroundColor Yellow

$printed = @{ backend = 0; frontend = 0 }
$urlFound = $false
$ready = @{ backend = $false; frontend = $false }
$bannerShown = $false
$failed = $false
$readyDeadline = (Get-Date).AddSeconds(120)

# Vite binds to "localhost" (here resolved to IPv6 ::1), NOT to 127.0.0.1,
# so readiness is checked across the hostnames Vite actually serves on.
# The Laravel server listens on 127.0.0.1, which is found by the same helper.
function Test-Port([int]$Port) {
    foreach ($hostName in @('localhost', '::1', '127.0.0.1')) {
        $client = New-Object System.Net.Sockets.TcpClient
        try {
            $client.Connect($hostName, $Port)
            if ($client.Connected) { return $true }
        }
        catch { }
        finally { $client.Dispose() }
    }
    return $false
}

try {
    while ($true) {
        $runningJobs = @(Get-Job -State Running)

        foreach ($job in @('backend', 'frontend')) {
            $jb = Get-Job -Name $job
            if (-not $jb) { continue }
            $out = @(Receive-Job -Job $jb -Keep)
            if ($out.Count -gt $printed[$job]) {
                for ($i = $printed[$job]; $i -lt $out.Count; $i++) {
                    $line = "$($out[$i])"
                    if ($line.Trim().Length -gt 0) {
                        Write-Host ("[ {0} ] {1}" -f $job, $line)
                    }
                    if ($job -eq 'frontend' -and -not $urlFound) {
                        $m = [regex]::Match($line, 'http://localhost:\d+')
                        if ($m.Success) {
                            $FrontendDefault = $m.Value.TrimEnd('/')
                            $frontendPort = [int]($FrontendDefault -split ':')[2]
                            $urlFound = $true
                        }
                    }
                }
                $printed[$job] = $out.Count
            }
        }

        if ($bannerShown -and $runningJobs.Count -eq 0) {
            Write-Host ''
            Write-Host 'Both servers were up and have now exited. Shutting down.' -ForegroundColor Red
            break
        }

        if (-not $bannerShown) {
            if ($runningJobs.Count -eq 0) {
                $failed = $true
                break
            }
            if ((Get-Date) -ge $readyDeadline) {
                $failed = $true
                break
            }
        }

        if (-not $ready.backend  -and (Test-Port $backendPort))  { $ready.backend  = $true }
        if (-not $ready.frontend -and (Test-Port $frontendPort)) { $ready.frontend = $true }

        if ($ready.backend -and $ready.frontend -and -not $bannerShown) {
            $bannerShown = $true
            Write-Host ''
            Write-Host '==================================================' -ForegroundColor Green
            Write-Host '  Both servers are UP' -ForegroundColor Green
            Write-Host ('  Frontend : ' + $FrontendDefault) -ForegroundColor Green
            Write-Host ('  Backend  : ' + $BackendUrl)      -ForegroundColor Green
            Write-Host '  Press Ctrl+C to stop.'            -ForegroundColor Yellow
            Write-Host '==================================================' -ForegroundColor Green
        }

        Start-Sleep -Milliseconds 800
    }

    if ($failed) {
        Write-Host ''
        Write-Host '[ERROR] Servers did not become ready within 120 seconds.' -ForegroundColor Red
        Write-Host ('  Backend  checked at : http://127.0.0.1:8000  (Laravel)')       -ForegroundColor Yellow
        Write-Host ('  Frontend checked at : ' + $FrontendDefault + '  (Vite)')         -ForegroundColor Yellow
        Write-Host '  Review the startup log above for the real error, then run again.' -ForegroundColor Yellow
        Get-Job | ForEach-Object {
            Write-Host ('  [{0}] state: {1}' -f $_.Name, $_.State)
        }
    }
}
finally {
    Write-Host ''
    Write-Host 'Stopping servers ...' -ForegroundColor Gray
    Get-Job | Stop-Job -ErrorAction SilentlyContinue
    Get-Job | Remove-Job -Force -ErrorAction SilentlyContinue
    Start-Sleep -Milliseconds 1500
    foreach ($port in @($backendPort, $frontendPort)) {
        Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue | ForEach-Object {
            Stop-Process -Id $_.OwningProcess -Force -ErrorAction SilentlyContinue
        }
    }
}

if ($failed) {
    exit 1
}