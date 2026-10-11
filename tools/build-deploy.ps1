<#
.SYNOPSIS
    Empaqueta el proyecto en los ZIP listos para subir al servidor de la UMSS
    por net2ftp.

.DESCRIPTION
    Genera dos archivos en la carpeta de salida:

      1_raiz.zip          -> app/, bootstrap/, config/, database/, resources/,
                             routes/, storage/ (limpio), artisan, composer.json,
                             composer.lock, server.php
      2_public_html.zip   -> public_html/ con el contenido de public/

    Los dos se descomprimen en la RAIZ del FTP (ver DEPLOY.md).

    Por que un script y no comprimir a mano: Compress-Archive de PowerShell 5.1
    escribe los separadores de carpeta como '\', y el descompresor de net2ftp
    (PHP sobre Linux) los toma como parte del nombre del archivo en vez de como
    separador de directorio. El resultado es una estructura de carpetas rota.
    Este script escribe siempre '/'.

.PARAMETER OutputDir
    Carpeta donde se generan los ZIP. Por defecto .\deploy

.PARAMETER IncludeEnv
    Incluir tambien un .env dentro de 1_raiz.zip. Solo para el primer
    despliegue: el archivo lleva la contrasena de la base, asi que conviene
    editarlo una vez en el servidor con el boton Edit de net2ftp y no volver a
    subirlo.

.PARAMETER EnvFile
    Archivo a empaquetar como .env cuando se pasa -IncludeEnv. Por defecto
    deploy\.env.server, que es el .env de produccion (credenciales del
    servidor, APP_DEBUG=false). Si no existe, se cae al .env local de la raiz.

.EXAMPLE
    .\tools\build-deploy.ps1
    Genera .\deploy\1_raiz.zip y .\deploy\2_public_html.zip

.EXAMPLE
    .\tools\build-deploy.ps1 -IncludeEnv
    Igual, pero ademas empaqueta deploy\.env.server (primer despliegue).
#>
[CmdletBinding()]
param(
    [string]$OutputDir = 'deploy',
    [string]$EnvFile = '',
    [switch]$IncludeEnv
)

$ErrorActionPreference = 'Stop'

# --- Resolucion de rutas ---------------------------------------------------

$root      = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$output    = if ([System.IO.Path]::IsPathRooted($OutputDir)) { $OutputDir } else { Join-Path $root $OutputDir }
$staging   = Join-Path ([System.IO.Path]::GetTempPath()) ("t1-deploy-" + [guid]::NewGuid().ToString('N').Substring(0, 8))
$stgRaiz   = Join-Path $staging 'raiz'
$stgPublic = Join-Path $staging 'public_html'

# --- Utilidades ------------------------------------------------------------

function Write-Step([string]$message) {
    Write-Host "==> $message" -ForegroundColor Cyan
}

function Write-Warn([string]$message) {
    Write-Host "    ! $message" -ForegroundColor Yellow
}

function New-ZipFromDirectory {
    <#
        Crea un ZIP usando '/' como separador de ruta.
        Windows Compress-Archive y .NET ZipFile.CreateFromDirectory no lo
        garantizan en .NET Framework, y PHP ZipArchive del lado Linux depende
        de que las entradas usen '/'.
    #>
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
    $zip  = [System.IO.Compression.ZipFile]::Open($Destination, 'Create')

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

function Assert-Exists([string]$relativePath, [string]$hint) {
    $path = Join-Path $root $relativePath
    if (-not (Test-Path -LiteralPath $path)) {
        throw "Falta '$relativePath'. $hint"
    }
}

# --- Comprobaciones previas -----------------------------------------------

Write-Step 'Comprobando que el proyecto este completo'

Assert-Exists 'composer.json'    'No podes empaquetar sin esto: Laravel lo lee en cada request web.'
Assert-Exists 'vendor\autoload.php' 'Ejecuta composer install.'
Assert-Exists 'public\build\manifest.json' 'Ejecuta npm install y npm run build. Sin el manifest, @vite lanza "Vite manifest not found" en el servidor.'

foreach ($dir in @('app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'storage', 'public')) {
    Assert-Exists $dir 'Revisa que el clon del repositorio este completo.'
}

# --- Preparacion del staging ----------------------------------------------

Write-Step 'Preparando los archivos a empaquetar'

New-Item -ItemType Directory -Path $stgRaiz   -Force | Out-Null
New-Item -ItemType Directory -Path $stgPublic -Force | Out-Null

foreach ($dir in @('app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'storage')) {
    Copy-Item -LiteralPath (Join-Path $root $dir) -Destination (Join-Path $stgRaiz $dir) -Recurse -Force
}

foreach ($file in @('artisan', 'server.php', 'composer.json', 'composer.lock')) {
    $source = Join-Path $root $file
    if (Test-Path -LiteralPath $source) {
        Copy-Item -LiteralPath $source -Destination (Join-Path $stgRaiz $file) -Force
    }
}

# --- Exclusiones ------------------------------------------------------------
#
# Cosas que viven en el repo pero no son parte de la aplicacion web. Sin esto
# el ZIP se lleva 8 MB de basura al hosting (AnyDesk.exe) y una base SQLite que
# el servidor ni usa: alla la base es PostgreSQL.

Write-Step 'Excluyendo archivos que no van al servidor'

$excluir = @(
    'database\db_server',        # AnyDesk.exe, gcapi.dll, locks: es la caja de la BD, no el sitio
    'database\database.sqlite'   # el servidor usa PostgreSQL (ver DEPLOY.md seccion 8)
)

foreach ($ruta in $excluir) {
    $destino = Join-Path $stgRaiz $ruta
    if (Test-Path -LiteralPath $destino) {
        Remove-Item -LiteralPath $destino -Recurse -Force
        Write-Warn "No se incluye '$ruta'"
    }
}

# bootstrap/cache/ es del GenerateManifest del local. Laravel lo regenera en el
# servidor, y un services.php viejo puede apuntar a rutas que ya no existen.
Get-ChildItem -LiteralPath (Join-Path $stgRaiz 'bootstrap\cache') -File -Force -ErrorAction SilentlyContinue |
    Where-Object { $_.Name -ne '.gitignore' } |
    Remove-Item -Force -ErrorAction SilentlyContinue

# El .env local apunta a la base de desarrollo, no a la del servidor. Se
# empaqueta solo si el usuario lo pide, y entonces se toma de $EnvFile
# (por defecto deploy\.env.server, el de produccion).
if ($IncludeEnv) {
    $candidates = @()

    if ($EnvFile -ne '') {
        $candidates += if ([System.IO.Path]::IsPathRooted($EnvFile)) { $EnvFile } else { Join-Path $root $EnvFile }
    }

    $candidates += @(
        (Join-Path $root 'deploy\.env.server'),
        (Join-Path $root '.env')
    )

    $envSource = $candidates | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1

    if ($envSource) {
        Copy-Item -LiteralPath $envSource -Destination (Join-Path $stgRaiz '.env') -Force
        Write-Warn "Se incluyo '$envSource' como .env del servidor. Contiene credenciales: subilo una vez y editalo en el servidor."
    }
    else {
        Write-Warn 'Se pidio -IncludeEnv pero no hay ni deploy\.env.server ni .env local.'
    }
}

# public/ -> public_html/ (el doc root del hosting se llama public_html)
Get-ChildItem -LiteralPath (Join-Path $root 'public') -Force |
    ForEach-Object { Copy-Item -LiteralPath $_.FullName -Destination $stgPublic -Recurse -Force }

# --- Limpieza de storage ---------------------------------------------------
#
# No se suben vistas compiladas de Blade ni logs: son de la maquina local, el
# servidor recompila sus propias, y las fechas de modificacion no sobreviven al
# unzip, asi que Laravel las considers vencidas y las vuelve a compilar.
# storage/framework/views SIEMPRE tiene que ser escribible en el servidor.

Write-Step 'Limpiando storage/ (vistas compiladas, logs, sesiones, cache)'

$clean = @(
    (Join-Path $stgRaiz 'storage\framework\views'),
    (Join-Path $stgRaiz 'storage\logs'),
    (Join-Path $stgRaiz 'storage\framework\sessions'),
    (Join-Path $stgRaiz 'storage\framework\cache\data')
)

foreach ($dir in $clean) {
    if (Test-Path -LiteralPath $dir) {
        Get-ChildItem -LiteralPath $dir -File -Force -ErrorAction SilentlyContinue |
            Where-Object { $_.Name -ne '.gitignore' } |
            Remove-Item -Force -ErrorAction SilentlyContinue
    }
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
}

# --- Empaquetado -----------------------------------------------------------

Write-Step 'Generando los ZIP'

New-Item -ItemType Directory -Path $output -Force | Out-Null

$zipRaiz   = Join-Path $output '1_raiz.zip'
$zipPublic = Join-Path $output '2_public_html.zip'

New-ZipFromDirectory -Source $stgRaiz   -Destination $zipRaiz
New-ZipFromDirectory -Source $stgPublic -Destination $zipPublic -Prefix 'public_html'

# --- Verificacion ----------------------------------------------------------

Write-Step 'Verificando los ZIP generados'

Add-Type -AssemblyName System.IO.Compression.FileSystem

$problems = @()

foreach ($zipPath in @($zipRaiz, $zipPublic)) {
    $archive = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
    try {
        $names = $archive.Entries | ForEach-Object { $_.FullName }
        $bad = $names | Where-Object { $_ -like '*\*' }
        if ($bad) {
            $problems += "$(Split-Path $zipPath -Leaf): $($bad.Count) entrada(s) con '\' en vez de '/'"
        }
    }
    finally {
        $archive.Dispose()
    }
}

# .env nunca debe quedar en el ZIP salvo que se haya pedido.
$archive = [System.IO.Compression.ZipFile]::OpenRead($zipRaiz)
try {
    $hasEnv = [bool]($archive.Entries | Where-Object { $_.FullName -eq '.env' })
}
finally {
    $archive.Dispose()
}

if ($problems) {
    $problems | ForEach-Object { Write-Host "    x $_" -ForegroundColor Red }
    throw 'Los ZIP salieron mal formados. No los subas.'
}

Remove-Item -LiteralPath $staging -Recurse -Force -ErrorAction SilentlyContinue

# --- Resumen ---------------------------------------------------------------

Write-Host ''
Write-Host 'Listo. Archivos para subir por net2ftp:' -ForegroundColor Green
Get-ChildItem $output -Filter *.zip |
    Select-Object Name, @{ Name = 'KB'; Expression = { [math]::Round($_.Length / 1KB, 1) } } |
    Format-Table -AutoSize

Write-Host "1. Sube 1_raiz.zip         -> descomprimir en la RAIZ del FTP"
Write-Host "2. Sube 2_public_html.zip  -> descomprimir en la RAIZ del FTP"
Write-Host "3. Entiende a https://techone.tis.cs.umss.edu.bo/"
Write-Host ''

if ($hasEnv) {
    Write-Warn 'El ZIP incluye el .env. Editalo en el servidor y no lo vuelvas a subir.'
}
else {
    Write-Host 'El .env NO va en el ZIP: subilo una sola vez con -IncludeEnv (o crealo'
    Write-Host 'con el boton New/Edit de net2ftp). Plantilla: deploy\.env.server y'
    Write-Host 'seccion 4 de DEPLOY.md.'
}
