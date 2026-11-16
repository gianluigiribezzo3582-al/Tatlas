# Local setup in one command (Windows PowerShell, Docker Desktop running).
# Usage:  powershell -ExecutionPolicy Bypass -File scripts\dev-setup.ps1 -Ip 192.168.1.20 [-NoDemo]
param(
    [string]$Ip = "",
    [switch]$NoDemo
)
$ErrorActionPreference = "Stop"
Set-Location (Join-Path $PSScriptRoot "..")

if (-not (Test-Path "backend\.env")) { Copy-Item "backend\.env.example" "backend\.env" }
if ($Ip -ne "") {
    (Get-Content "backend\.env") -replace '^APP_URL=.*', "APP_URL=http://${Ip}:8000" | Set-Content "backend\.env"
}

function Run([string[]]$cmd) {
    docker compose exec -T app @cmd
    if ($LASTEXITCODE -ne 0) { throw "Command failed: $($cmd -join ' ')" }
}

Write-Host "> Building and starting containers (first run takes a few minutes)..."
docker compose up -d --build
if ($LASTEXITCODE -ne 0) { throw "docker compose failed. Is Docker Desktop running?" }

Write-Host "> Installing PHP dependencies..."
Run @("composer", "install", "--no-interaction")
if (-not (Select-String -Path "backend\.env" -Pattern '^APP_KEY=base64' -Quiet)) {
    Run @("php", "artisan", "key:generate", "--force")
}
Write-Host "> Database and style taxonomy..."
Run @("php", "artisan", "migrate", "--seed", "--force")
docker compose exec -T app php artisan storage:link | Out-Null

Write-Host "> Importing tattoo studios from OpenStreetMap (Bari area)..."
docker compose exec -T app php artisan tatlas:import-osm
if ($LASTEXITCODE -ne 0) { Write-Warning "OSM import failed (Overpass busy?). Retry later: docker compose exec app php artisan tatlas:import-osm" }

if (-not $NoDemo) {
    Write-Host "> Fictional demo artists (remove with: docker compose exec app php artisan tatlas:demo-data --purge)..."
    Run @("php", "artisan", "tatlas:demo-data")
}

$host_ = if ($Ip) { $Ip } else { "localhost" }
Write-Host ""
Write-Host "Ready."
Write-Host "  API:         http://${host_}:8000/api/v1/styles"
Write-Host "  Admin panel: http://localhost:8000/admin"
Write-Host "               (create an admin: docker compose exec app php artisan tatlas:make-admin you@example.com --password=...)"
Write-Host "  Emails:      http://localhost:8025"
Write-Host "  In the app:  Settings -> turn off demo mode -> server ${host_}:8000 -> Save and test"
