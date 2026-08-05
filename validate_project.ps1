# Valida el estado global del proyecto: archivos esenciales + scripts de validación
# Equivalente PowerShell de validate_project.sh (para Windows sin WSL)

$ErrorActionPreference = 'SilentlyContinue'

Write-Host "VALIDANDO PROYECTO ORBIS - $(Get-Date -Format 'yyyy-MM-dd HH:mm')"
Write-Host ""

# 1. Archivos esenciales
Write-Host "1. Archivos esenciales..."
$required = @('CONTEXT.md', 'TODOS.md', 'AGENTS.md', 'HANDOFF.md', 'composer.json')
foreach ($f in $required) {
    if (Test-Path -LiteralPath $f) {
        Write-Host "   [OK] $f"
    }
    else {
        Write-Host "   [MISSING] $f - FALTANTE"
        exit 1
    }
}

# 2. Directorios críticos
Write-Host ""
Write-Host "2. Directorios criticos..."
$dirs = @('src', 'specs', 'tests', 'config', 'templates', 'migrations', 'assets')
foreach ($d in $dirs) {
    if (Test-Path -LiteralPath $d) {
        Write-Host "   [OK] $d/"
    }
    else {
        Write-Host "   [WARN] $d/ no existe"
    }
}

# 3. Artefactos no deseados en repo
Write-Host ""
Write-Host "3. Artefactos no deseados..."
foreach ($a in @('node_modules', 'vendor', '.env', '.env.local')) {
    if (Test-Path -LiteralPath $a) {
        Write-Host "   [WARN] $a presente (debe estar en .gitignore, no en repo)"
    }
    else {
        Write-Host "   [OK] $a ausente"
    }
}

# 4. Validar reportes e importers
Write-Host ""
Write-Host "4. Validando reportes..."
if (Test-Path -LiteralPath '.\validate_reports.ps1') {
    & .\validate_reports.ps1
}
else {
    Write-Host "   [WARN] validate_reports.ps1 no existe"
}

Write-Host ""
Write-Host "5. Validando importers..."
if (Test-Path -LiteralPath '.\validate_importers.ps1') {
    & .\validate_importers.ps1
}
else {
    Write-Host "   [WARN] validate_importers.ps1 no existe"
}

Write-Host ""
Write-Host "OK - Validacion completada"
