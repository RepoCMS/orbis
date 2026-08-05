# Valida que todos los importers listados en CONTEXT.md existan fisicamente
# Equivalente PowerShell de validate_importers.sh (para Windows sin WSL)

$expected = @(
    @{ Bc = 'Client';        Name = 'ImportClients' },
    @{ Bc = 'Client';        Name = 'ImportTerminals' },
    @{ Bc = 'Client';        Name = 'ImportTerminalModels' },
    @{ Bc = 'Inventory';     Name = 'ImportProducts' },
    @{ Bc = 'Inventory';     Name = 'ImportSerializedStock' },
    @{ Bc = 'Inventory';     Name = 'ImportNonserializedStock' },
    @{ Bc = 'Organization';  Name = 'ImportEmployees' },
    @{ Bc = 'Identity';      Name = 'ImportUsers' },
    @{ Bc = 'Inventory';     Name = 'ImportPurchaseOrders' },
    @{ Bc = 'Expense';       Name = 'ImportOperatingExpenses' },
    @{ Bc = 'Client';        Name = 'ImportModelParts' }
)

$parts = @('FileParser', 'RowValidator')

Write-Host "=== Importers en CONTEXT.md ==="
foreach ($e in $expected) {
    Write-Host "- [$($e.Bc)] $($e.Name)"
}

Write-Host ""
Write-Host "=== Verificacion fisica ==="
$missing = 0
foreach ($e in $expected) {
    $handler = Join-Path "src\app\$($e.Bc)\Application\Command\$($e.Name)" "$($e.Name)Handler.php"
    if (Test-Path -LiteralPath $handler) {
        Write-Host "[OK] $($e.Name) -> Handler OK"
    }
    else {
        Write-Host "[MISSING] $($e.Name) (esperado: $handler)"
        $missing = 1
    }
    foreach ($part in $parts) {
        $found = Get-ChildItem -Path "src\app\$($e.Bc)\Application\Command\$($e.Name)" -Filter "*$part.php" -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($found) {
            Write-Host "     -> $part OK ($($found.Name))"
        }
    }
}

Write-Host ""
Write-Host "=== Importers en disco pero no en CONTEXT.md ==="
$found = Get-ChildItem -Path src\app -Recurse -Filter "Import*Handler.php" | Where-Object { $_.Name -notlike "*Message*" } | Sort-Object Name
foreach ($f in $found) {
    $bc = $f.FullName -replace '.*\\src\\app\\', '' -replace '\\.*', ''
    $name = $f.Directory.Name
    $inExpected = $expected | Where-Object { $_.Bc -eq $bc -and $_.Name -eq $name }
    if (-not $inExpected) {
        Write-Host "[EXTRA] $bc/$name"
    }
}

if ($missing -eq 0) {
    Write-Host ""
    Write-Host "OK - Todos los importers de CONTEXT.md existen en disco"
}
else {
    Write-Host ""
    Write-Host "ERROR - Hay importers faltantes"
    exit 1
}
