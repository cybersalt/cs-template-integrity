# Build script for cs-template-integrity
# Produces installable ZIPs at the repo root for every packaged
# extension:
#   - com_csintegrity_v{version}_{YYYYMMDD}_{HHMM}.zip
#   - plg_webservices_csintegrity_v{version}_{YYYYMMDD}_{HHMM}.zip
#
# Naming follows the Joomla Brain taxonomy. Zips are built with 7-Zip
# because Windows' Compress-Archive omits directory entries, which
# breaks Joomla's installer (file_put_contents "No such file or
# directory" on language/index.html and similar).

param(
    [string]$Version = ""
)

$ErrorActionPreference = "Stop"

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$sevenZip  = "C:\Program Files\7-Zip\7z.exe"

if (-not (Test-Path $sevenZip)) {
    throw "7-Zip not found at $sevenZip. Install 7-Zip or update the path in build-package.ps1."
}

$extensions = @(
    @{
        Name       = "com_csintegrity"
        SourceDir  = Join-Path $scriptDir "packages\com_csintegrity"
        Manifest   = "csintegrity.xml"
        Contents   = @("csintegrity.xml", "script.php", "admin", "api")
    },
    @{
        Name       = "plg_webservices_csintegrity"
        SourceDir  = Join-Path $scriptDir "packages\plg_webservices_csintegrity"
        Manifest   = "csintegrity.xml"
        Contents   = @("csintegrity.xml", "services", "src", "language")
    }
)

$timestamp = Get-Date -Format "yyyyMMdd_HHmm"

foreach ($ext in $extensions) {
    $manifestPath = Join-Path $ext.SourceDir $ext.Manifest
    if (-not (Test-Path $manifestPath)) {
        throw "Manifest not found at $manifestPath"
    }

    if ([string]::IsNullOrEmpty($Version)) {
        $manifest = Get-Content $manifestPath -Raw
        if ($manifest -match '<version>([^<]+)</version>') {
            $extVersion = $matches[1]
        } else {
            throw "Could not read <version> from $manifestPath"
        }
    } else {
        $extVersion = $Version
    }

    $zipName = "$($ext.Name)_v${extVersion}_${timestamp}.zip"
    $zipPath = Join-Path $scriptDir $zipName

    Write-Host "Building $($ext.Name) v$extVersion ..." -ForegroundColor Cyan

    Get-ChildItem -Path $scriptDir -Filter "$($ext.Name)_v${extVersion}*.zip" | Remove-Item -Force

    $buildDir = Join-Path $scriptDir "build"
    if (Test-Path $buildDir) { Remove-Item $buildDir -Recurse -Force }
    New-Item -ItemType Directory -Path $buildDir | Out-Null

    foreach ($item in $ext.Contents) {
        $source = Join-Path $ext.SourceDir $item
        if (Test-Path $source) {
            $dest = Join-Path $buildDir $item
            if ((Get-Item $source).PSIsContainer) {
                Copy-Item $source $dest -Recurse
            } else {
                Copy-Item $source $dest
            }
        }
    }

    Push-Location $buildDir
    try {
        & $sevenZip a -tzip $zipPath * | Out-Null
        if ($LASTEXITCODE -ne 0) {
            throw "7-Zip exited with code $LASTEXITCODE for $($ext.Name)"
        }
    } finally {
        Pop-Location
    }

    Remove-Item $buildDir -Recurse -Force

    $sizeKb = [Math]::Round((Get-Item $zipPath).Length / 1KB, 1)
    Write-Host "Created $zipName ($sizeKb KB)" -ForegroundColor Green
}
