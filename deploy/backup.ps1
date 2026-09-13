param(
    [string]$Database = 'nexastock_dev',
    [string]$User = 'nexastock_backup',
    [Parameter(Mandatory=$true)][string]$OutputFile
)
$ErrorActionPreference = 'Stop'
$dump = 'C:\xampp\mysql\bin\mysqldump.exe'
if (-not (Test-Path -LiteralPath $dump)) { throw 'XAMPP mysqldump was not found.' }
$full = [IO.Path]::GetFullPath($OutputFile)
$public = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\inventory\public'))
if ($full.StartsWith($public, [StringComparison]::OrdinalIgnoreCase)) { throw 'A backup cannot be written under the public web root.' }
& $dump --protocol=tcp --host=127.0.0.1 --port=3306 --user=$User --password --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4 --result-file=$full $Database
if ($LASTEXITCODE -ne 0) { throw "mysqldump failed with exit code $LASTEXITCODE" }
Get-Item -LiteralPath $full | Select-Object FullName, Length, LastWriteTimeUtc
