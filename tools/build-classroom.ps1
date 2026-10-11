<#
.SYNOPSIS
    Empaqueta el proyecto en un ZIP para entregar en classroom.

.DESCRIPTION
    Genera un ZIP con solo el código fuente necesario para ejecutar el sistema.
    Excluye archivos de desarrollo, tests, vendor/, node_modules/, etc.
    El docente solo necesita ejecutar composer install y npm install.

.EXAMPLE
    .\tools\build-classroom.ps1
    Genera .\deploy\classroom.zip
#>
[CmdletBinding()]
param(
    [string]$OutputDir = 'deploy'
)

$ErrorActionPreference = 'Stop'

$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$output = if ([System.IO.Path]::IsPathRooted($OutputDir)) { $OutputDir } else { Join-Path $root $OutputDir }
$staging = Join-Path ([System.IO.Path]::GetTempPath()) ("t1-classroom-" + [guid]::NewGuid().ToString('N').Substring(0, 8))

function Write-Step([string]$message) {
    Write-Host "==> $message" -ForegroundColor Cyan
}

function New-ZipFromDirectory {
    param(
        [Parameter(Mandatory = $true)][string]$Source,
        [Parameter(Mandatory = $true)][string]$Destination,
        [string]$Prefix = ''
    )

    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem

    if (Test-Path -LiteralPath $Destination) {
        Remove-Item -LiteralPath $Destination -Force
    }

    $base = (Resolve-Path -LiteralPath $Source).Path.TrimEnd('\')
    $zip = [System.IO.Compression.ZipFile]::Open($Destination, 'Create')

    try {
        foreach ($file in Get-ChildItem -LiteralPath $base -Recurse -File -Force) {
            $entryName = $file.FullName.Substring($base.Length).TrimStart('\') -replace '\\', '/'
            if ($Prefix -ne '') { $entryName = "$Prefix/$entryName" }

            $entry = $zip.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
            $entry.LastWriteTime = $file.LastWriteTime

            $stream = $entry.Open()
            try {
                $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
                $stream.Write($bytes, 0, $bytes.Length)
            }
            finally {
                $stream.Dispose()
            }
        }

        foreach ($dir in Get-ChildItem -LiteralPath $base -Recurse -Directory -Force) {
            $entryName = (($dir.FullName.Substring($base.Length).TrimStart('\') -replace '\\', '/') + '/')
            if ($Prefix -ne '') { $entryName = "$Prefix/$entryName" }
            [void]$zip.CreateEntry($entryName)
        }
    }
    finally {
        $zip.Dispose()
    }
}

Write-Step 'Preparando archivos para classroom'

New-Item -ItemType Directory -Path $staging -Force | Out-Null

$dirs = @('app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes')
foreach ($dir in $dirs) {
    $src = Join-Path $root $dir
    if (Test-Path -LiteralPath $src) {
        Copy-Item -LiteralPath $src -Destination (Join-Path $staging $dir) -Recurse -Force
    }
}

$files = @('artisan', 'server.php', 'composer.json', 'composer.lock', '.env.example', 'README.md')
foreach ($file in $files) {
    $src = Join-Path $root $file
    if (Test-Path -LiteralPath $src) {
        Copy-Item -LiteralPath $src -Destination (Join-Path $staging $file) -Force
    }
}

Write-Step 'Limpiando archivos innecesarios'

$exclude = @(
    'storage\framework\views',
    'storage\logs',
    'storage\framework\sessions',
    'storage\framework\cache\data'
)

foreach ($dir in $exclude) {
    $path = Join-Path $staging $dir
    if (Test-Path -LiteralPath $path) {
        Get-ChildItem -LiteralPath $path -File -Force -ErrorAction SilentlyContinue |
            Where-Object { $_.Name -ne '.gitignore' } |
            Remove-Item -Force -ErrorAction SilentlyContinue
    }
}

Write-Step 'Generando ZIP'

New-Item -ItemType Directory -Path $output -Force | Out-Null
$zipPath = Join-Path $output 'classroom.zip'

New-ZipFromDirectory -Source $staging -Destination $zipPath

Write-Step 'Verificando ZIP'

Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    $names = $archive.Entries | ForEach-Object { $_.FullName }
    $bad = $names | Where-Object { $_ -like '*\*' }
    if ($bad) {
        Write-Host "    x $($bad.Count) entrada(s) con '\' en vez de '/'" -ForegroundColor Red
        throw 'El ZIP salió mal formado.'
    }
}
finally {
    $archive.Dispose()
}

Remove-Item -LiteralPath $staging -Recurse -Force -ErrorAction SilentlyContinue

Write-Host ''
Write-Host 'Listo. Archivo para classroom:' -ForegroundColor Green
Get-ChildItem $output -Filter *.zip |
    Select-Object Name, @{ Name = 'KB'; Expression = { [math]::Round($_.Length / 1KB, 1) } } |
    Format-Table -AutoSize

Write-Host 'Sube classroom.zip al classroom de tu docente.'
Write-Host 'El docente necesita ejecutar: composer install && npm install && php artisan migrate --seed'
