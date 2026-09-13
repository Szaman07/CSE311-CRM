param(
    [Parameter(Mandatory=$true)][string]$Database,
    [Parameter(Mandatory=$true)][string]$DumpFile,
    [string]$User = 'nexastock_migrator',
    [Parameter(Mandatory=$true)][string]$Confirmation
)
$ErrorActionPreference = 'Stop'
if ($Database -notmatch '^nexastock_restore_[a-z0-9_]+$') { throw 'Restore drills are restricted to an explicitly created nexastock_restore_* database.' }
if ($Confirmation -cne "RESTORE INTO $Database") { throw "Confirmation must exactly equal: RESTORE INTO $Database" }
$file = [IO.Path]::GetFullPath($DumpFile)
if (-not (Test-Path -LiteralPath $file -PathType Leaf)) { throw 'Dump file does not exist.' }
$client = 'C:\xampp\mysql\bin\mysql.exe'
& $client --protocol=tcp --host=127.0.0.1 --port=3306 --user=$User --password --database=$Database --execute="SOURCE $($file.Replace('\','/'));"
if ($LASTEXITCODE -ne 0) { throw "Restore failed with exit code $LASTEXITCODE" }
& $client --protocol=tcp --host=127.0.0.1 --port=3306 --user=$User --password --database=$Database --table --execute="SELECT 'users' entity,COUNT(*) rows_count FROM users UNION ALL SELECT 'sales',COUNT(*) FROM sales UNION ALL SELECT 'stock_movements',COUNT(*) FROM stock_movements; SELECT COUNT(*) reconciliation_differences FROM inventory_reconciliation WHERE difference<>0;"
