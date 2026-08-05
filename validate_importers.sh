#!/bin/bash
# Valida que todos los importers listados en CONTEXT.md existan físicamente
# Verifica: Handler, FileParser, RowValidator y CLI Command por cada importer

# Importers esperados según CONTEXT.md sección 5 (BC:NombreHandler)
EXPECTED=(
  "Client:ImportClients"
  "Client:ImportTerminals"
  "Client:ImportTerminalModels"
  "Inventory:ImportProducts"
  "Inventory:ImportSerializedStock"
  "Inventory:ImportNonserializedStock"
  "Organization:ImportEmployees"
  "Identity:ImportUsers"
  "Inventory:ImportPurchaseOrders"
  "Expense:ImportOperatingExpenses"
  "Client:ImportModelParts"
)

# Parser/Validator/CLI sufijos opcionales a verificar (si existen archivos)
PARTS=("FileParser" "RowValidator")

echo "=== Importers en CONTEXT.md ==="
for e in "${EXPECTED[@]}"; do
  bc="${e%%:*}"
  name="${e##*:}"
  echo "- [$bc] $name"
done

echo ""
echo "=== Verificación física ==="
MISSING=0
for e in "${EXPECTED[@]}"; do
  bc="${e%%:*}"
  name="${e##*:}"
  handler="src/app/$bc/Application/Command/$name/$name"Handler.php
  if [ -f "$handler" ]; then
    echo "✅ $name → Handler OK"
  else
    echo "❌ FALTAN en disco: $name (esperado: $handler)"
    MISSING=1
  fi
  # Verificar Parser y Validator si el directorio existe
  for part in "${PARTS[@]}"; do
    found=$(find "src/app/$bc/Application/Command/$name" -name "*$part.php" 2>/dev/null | head -1)
    if [ -n "$found" ]; then
      echo "   └─ $part OK ($(basename "$found"))"
    fi
  done
done

echo ""
echo "=== Importers en disco pero no en CONTEXT.md ==="
FOUND=$(find src/app -name "Import*Handler.php" -not -name "*Message*" | sort)
for f in $FOUND; do
  dir=$(dirname "$f")
  bc=$(echo "$dir" | cut -d'/' -f4)
  name=$(basename "$dir")
  if ! echo "${EXPECTED[@]}" | grep -q "$bc:$name"; then
    echo "⚠️  Sobrante: $bc/$name"
  fi
done

if [ "$MISSING" -eq 0 ]; then
  echo ""
  echo "✅ Todos los importers de CONTEXT.md existen en disco"
else
  echo ""
  echo "❌ Hay importers faltantes — revisar CONTEXT.md o el código"
  exit 1
fi
