# Valida el estado global del proyecto — delega en bin/validate-project.php
# (implementación única cross-platform: reglas Apéndice A + secrets + wiring + tablero).
# Equivalente PowerShell de validate_project.sh (ambos invocan el mismo PHP).
# Exit 0 = OK · 1 = errores bloqueantes · 2 = warnings (no bloquean).

$ErrorActionPreference = 'Stop'

Write-Host "VALIDANDO PROYECTO ORBIS - $(Get-Date -Format 'yyyy-MM-dd HH:mm')"
Write-Host ""

php bin/validate-project.php
exit $LASTEXITCODE
