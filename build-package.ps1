# Build script for com_csintegrity
# Produces an installable Joomla component ZIP at the repo root.
#
# Usage: .\build-package.ps1 [-Version "0.1.1"]
# (version auto-reads from packages/com_csintegrity/csintegrity.xml if omitted)
#
# Naming follows the Joomla Brain taxonomy:
#   com_csintegrity_v{version}_{YYYYMMDD}_{HHMM}.zip
# ZIP is built with 7-Zip — Windows' Compress-Archive omits directory
# entries and breaks Joomla's installer (file_put_contents "No such
# file or directory" error on language/index.html).

param(
    [string]$Version = ""
)

$ErrorActionPreference = "Stop"

$scriptDir    = Split-Path -Parent $MyInvocation.MyCommand.Path
$componentDir = Join-Path $scriptDir "packages\com_csintegrity"
$manifestPath = Join-Path $componentDir "csintegrity.xml"
$packageName  = "com_csintegrity"
$sevenZip     = "C:\Program Files\7-Zip\7z.exe"

if (-not (Test-Path $manifestPath)) {
    throw "Manifest not found at $manifestPath"
}
if (-not (Test-Path $sevenZip)) {
    throw "7-Zip not found at $sevenZip. Install 7-Zip or update the path in build-package.ps1."
}

if ([string]::IsNullOrEmpty($Version)) {
    $manifest = Get-Content $manifestPath -Raw
    if ($manifest -match '<version>([^<]+)</version>') {
        $Version = $matches[1]
    } else {
        throw "Could not read <version> from $manifestPath"
    }
}

$timestamp = Get-Date -Format "yyyyMMdd_HHmm"
$zipName   = "${packageName}_v${Version}_${timestamp}.zip"
$zipPath   = Join-Path $scriptDir $zipName

Write-Host "Building $packageName v$Version ..." -ForegroundColor Cyan

Get-ChildItem -Path $scriptDir -Filter "${packageName}_v${Version}*.zip" | Remove-Item -Force

$buildDir = Join-Path $scriptDir "build"
if (Test-Path $buildDir) { Remove-Item $buildDir -Recurse -Force }
New-Item -ItemType Directory -Path $buildDir | Out-Null

Copy-Item $manifestPath $buildDir
Copy-Item (Join-Path $componentDir "script.php") $buildDir
Copy-Item (Join-Path $componentDir "admin") (Join-Path $buildDir "admin") -Recurse
Copy-Item (Join-Path $componentDir "api")   (Join-Path $buildDir "api")   -Recurse

Push-Location $buildDir
try {
    & $sevenZip a -tzip $zipPath * | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "7-Zip exited with code $LASTEXITCODE"
    }
} finally {
    Pop-Location
}

Remove-Item $buildDir -Recurse -Force

$sizeKb = [Math]::Round((Get-Item $zipPath).Length / 1KB, 1)
Write-Host "Created $zipName ($sizeKb KB)" -ForegroundColor Green
