#!/usr/bin/env bash
# Valida el estado global del proyecto — delega en bin/validate-project.php
# (implementación única cross-platform: reglas Apéndice A + secrets + wiring + tablero).
# Exit 0 = OK · 1 = errores bloqueantes · 2 = warnings (no bloquean).

set -euo pipefail
cd "$(dirname "$0")"

echo "VALIDANDO PROYECTO ORBIS - $(date +%Y-%m-%d\ %H:%M)"
echo ""

php bin/validate-project.php
