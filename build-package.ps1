# Build script for cs-template-integrity
# Produces a single installable package at the repo root:
#   pkg_csintegrity_v{version}_{YYYYMMDD}_{HHMM}.zip
# which wraps both the component and the webservices plugin. Install
# this one zip; Joomla unpacks it and installs both child extensions.
# The package's script.php auto-enables the webservices plugin on
# postflight so the API routes are live immediately.
#
# Child extension zips use stable (non-timestamped) names inside the
# package so the package manifest can reference them reliably.
#
# Zips are built with 7-Zip because Windows' Compress-Archive omits
# directory entries, which breaks Joomla's installer.

param(
    [string]$Version = ""
)

$ErrorActionPreference = "Stop"

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$sevenZip  = "C:\Program Files\7-Zip\7z.exe"

if (-not (Test-Path $sevenZip)) {
    throw "7-Zip not found at $sevenZip. Install 7-Zip or update the path in build-package.ps1."
}

$pkgDir         = Join-Path $scriptDir "packages\pkg_csintegrity"
$pkgManifest    = Join-Path $pkgDir "pkg_csintegrity.xml"

if (-not (Test-Path $pkgManifest)) {
    throw "Package manifest not found at $pkgManifest"
}

if ([string]::IsNullOrEmpty($Version)) {
    $manifestContents = Get-Content $pkgManifest -Raw
    if ($manifestContents -match '<version>([^<]+)</version>') {
        $Version = $matches[1]
    } else {
        throw "Could not read <version> from $pkgManifest"
    }
}

$childExtensions = @(
    @{
        Name      = "com_csintegrity"
        SourceDir = Join-Path $scriptDir "packages\com_csintegrity"
        Contents  = @("csintegrity.xml", "script.php", "admin", "api", "media")
    },
    @{
        Name      = "plg_webservices_csintegrity"
        SourceDir = Join-Path $scriptDir "packages\plg_webservices_csintegrity"
        Contents  = @("csintegrity.xml", "services", "src", "language")
    }
)

$timestamp   = Get-Date -Format "yyyyMMdd_HHmm"
$pkgZipName  = "pkg_csintegrity_v${Version}_${timestamp}.zip"
$pkgZipPath  = Join-Path $scriptDir $pkgZipName

# Clean old builds
Get-ChildItem -Path $scriptDir -Filter "pkg_csintegrity_v${Version}*.zip" | Remove-Item -Force
Get-ChildItem -Path $scriptDir -Filter "com_csintegrity_v${Version}*.zip" | Remove-Item -Force
Get-ChildItem -Path $scriptDir -Filter "plg_webservices_csintegrity_v${Version}*.zip" | Remove-Item -Force

$pkgStage = Join-Path $scriptDir "build"
if (Test-Path $pkgStage) { Remove-Item $pkgStage -Recurse -Force }
New-Item -ItemType Directory -Path $pkgStage | Out-Null
New-Item -ItemType Directory -Path (Join-Path $pkgStage "packages") | Out-Null
New-Item -ItemType Directory -Path (Join-Path $pkgStage "language\en-GB") | Out-Null

Write-Host "Building pkg_csintegrity v$Version ..." -ForegroundColor Cyan

# 1. Build each child extension into the staging packages/ folder with
#    a stable, non-timestamped filename.
foreach ($ext in $childExtensions) {
    Write-Host "  Child: $($ext.Name)" -ForegroundColor DarkCyan

    $childStage = Join-Path $scriptDir "build-child"
    if (Test-Path $childStage) { Remove-Item $childStage -Recurse -Force }
    New-Item -ItemType Directory -Path $childStage | Out-Null

    foreach ($item in $ext.Contents) {
        $source = Join-Path $ext.SourceDir $item
        if (Test-Path $source) {
            $dest = Join-Path $childStage $item
            if ((Get-Item $source).PSIsContainer) {
                Copy-Item $source $dest -Recurse
            } else {
                Copy-Item $source $dest
            }
        }
    }

    $childZipPath = Join-Path $pkgStage "packages\$($ext.Name).zip"

    Push-Location $childStage
    try {
        & $sevenZip a -tzip $childZipPath * | Out-Null
        if ($LASTEXITCODE -ne 0) { throw "7-Zip failed on $($ext.Name) (exit $LASTEXITCODE)" }
    } finally {
        Pop-Location
    }

    Remove-Item $childStage -Recurse -Force
}

# 2. Copy the package manifest, script, and language files into the staging root.
Copy-Item $pkgManifest (Join-Path $pkgStage "pkg_csintegrity.xml")
Copy-Item (Join-Path $pkgDir "script.php") (Join-Path $pkgStage "script.php")
Copy-Item (Join-Path $pkgDir "language\en-GB\pkg_csintegrity.sys.ini") (Join-Path $pkgStage "language\en-GB\pkg_csintegrity.sys.ini")

# 3. Zip the whole package.
Push-Location $pkgStage
try {
    & $sevenZip a -tzip $pkgZipPath * | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "7-Zip failed on package (exit $LASTEXITCODE)" }
} finally {
    Pop-Location
}

Remove-Item $pkgStage -Recurse -Force

$sizeKb = [Math]::Round((Get-Item $pkgZipPath).Length / 1KB, 1)
Write-Host "Created $pkgZipName ($sizeKb KB)" -ForegroundColor Green
