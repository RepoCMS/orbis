#!/bin/bash
# Valida que todos los reportes listados en CONTEXT.md existan físicamente

# Extract report paths from CONTEXT.md
REPORTS=$(grep -oP 'src/app/\S+/Application/Report/\S+/\K[^/]+(?=\.php)' CONTEXT.md | sort -u)

# Actual file scan in the repo
ACTUAL=$(find src/app/ -path "*/Report/*ReportGenerator.php" -printf "%f\n" | sed 's/ReportGenerator//' | sort -u)

echo "=== Reportes en CONTEXT.md ==="
for r in $REPORTS; do echo "- $r"; done

echo ""
echo "=== Reportes en disco ==="
for a in $ACTUAL; do echo "- $a"; done

echo ""
echo "=== Reportes faltantes en disco ==="
for r in $REPORTS; do
  found=$(find src/app/ -name "${r}ReportGenerator.php" 2>/dev/null | head -1)
  if [ -z "$found" ]; then
    echo "❌ FALTAN en disco: $r"
  fi
done

echo ""
echo "=== Reportes en disco pero no en CONTEXT.md ==="
for a in $ACTUAL; do
  if ! echo "$REPORTS" | grep -q "^${a}$"; then
    echo "⚠️  Sobrante: $a"
  fi
done
