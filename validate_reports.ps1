# Valida que todos los reportes listados en CONTEXT.md existan fisicamente
# Equivalente PowerShell de validate_reports.sh (para Windows sin WSL)

Write-Host "=== Reportes en CONTEXT.md ==="
$reports = Get-ChildItem -Path src\app -Recurse -Filter "*ReportGenerator.php" -Name |
    ForEach-Object { ($_ -split '[\\/]')[-1] -replace 'ReportGenerator\.php$', '' } | Sort-Object -Unique
foreach ($r in $reports) { Write-Host "- $r" }

Write-Host ""
Write-Host "=== Reportes en disco (by BC) ==="
Get-ChildItem -Path src\app -Recurse -Filter "*ReportGenerator.php" | ForEach-Object {
    $bc = $_.FullName -replace '.*\\src\\app\\', '' -replace '\\.*', ''
    Write-Host "- [$bc] $($_.BaseName -replace 'ReportGenerator$', '')"
}

Write-Host ""
Write-Host "=== Validacion ==="
$disk = Get-ChildItem -Path src\app -Recurse -Filter "*ReportGenerator.php" |
    ForEach-Object { $_.BaseName -replace 'ReportGenerator$', '' } | Sort-Object -Unique

# Referencias en CONTEXT.md
$contextReports = Select-String -Path CONTEXT.md -Pattern 'Report/\S+/(\w+ReportGenerator)\.php' -AllMatches |
    ForEach-Object { $_.Matches } | ForEach-Object { $_.Groups[1].Value -replace 'ReportGenerator$', '' } | Sort-Object -Unique

$missing = $contextReports | Where-Object { $_ -notin $disk }
foreach ($m in $missing) {
    Write-Host "[MISSING] $m"
}
if ($missing.Count -eq 0) {
    Write-Host "[OK] Todos los reportes de CONTEXT.md existen en disco"
}

$extras = $disk | Where-Object { $_ -notin $contextReports }
foreach ($x in $extras) {
    Write-Host "[EXTRA] $x (en disco pero no referenciado en CONTEXT.md)"
}
