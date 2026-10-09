param(
    [string]$RehearsalDatabase = 'kebun_tebu_rehearsal_20261009'
)

$ErrorActionPreference = 'Stop'

if ($RehearsalDatabase -notmatch '^kebun_tebu_rehearsal_[0-9]{8}$') {
    throw 'Nama database rehearsal harus mengikuti pola kebun_tebu_rehearsal_YYYYMMDD.'
}

$projectRoot = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $projectRoot '.env'
$envLines = Get-Content -LiteralPath $envFile

function Read-ProjectEnv([string]$Key, [string]$Fallback = '') {
    $escapedKey = [regex]::Escape($Key)
    $match = $envLines | Where-Object { $_ -match "^${escapedKey}=" } | Select-Object -Last 1
    if (-not $match) {
        return $Fallback
    }

    $value = ($match -split '=', 2)[1].Trim()
    return $value.Trim('"').Trim("'")
}

$dbHost = Read-ProjectEnv 'DB_HOST' '127.0.0.1'
$dbPort = Read-ProjectEnv 'DB_PORT' '5432'
$dbName = Read-ProjectEnv 'DB_DATABASE' 'kebun_tebu'
$dbUser = Read-ProjectEnv 'DB_USERNAME' 'postgres'
$dbPassword = Read-ProjectEnv 'DB_PASSWORD'

if ($dbName -eq $RehearsalDatabase) {
    throw 'Database sumber dan database rehearsal tidak boleh sama.'
}

$env:PGPASSWORD = $dbPassword
$dumpPath = Join-Path ([IO.Path]::GetTempPath()) 'kebun_tebu-prelaunch.dump'
$createdRehearsalDatabase = $false

try {
    $existingDatabase = & psql -h $dbHost -p $dbPort -U $dbUser -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname='$RehearsalDatabase'"
    if ($existingDatabase -eq '1') {
        throw "Database rehearsal sudah ada; proses berhenti tanpa menimpa: $RehearsalDatabase"
    }

    & pg_dump -h $dbHost -p $dbPort -U $dbUser -d $dbName --format=custom --file=$dumpPath
    if ($LASTEXITCODE -ne 0) { throw 'pg_dump gagal.' }

    & createdb -h $dbHost -p $dbPort -U $dbUser $RehearsalDatabase
    if ($LASTEXITCODE -ne 0) { throw 'createdb gagal.' }
    $createdRehearsalDatabase = $true

    & pg_restore -h $dbHost -p $dbPort -U $dbUser -d $RehearsalDatabase --no-owner --no-privileges $dumpPath
    if ($LASTEXITCODE -ne 0) { throw 'pg_restore gagal.' }

    foreach ($table in @('users', 'reports', 'categories', 'blocks', 'notifications', 'push_subscriptions')) {
        $sourceCount = & psql -h $dbHost -p $dbPort -U $dbUser -d $dbName -tAc "SELECT count(*) FROM $table"
        $restoredCount = & psql -h $dbHost -p $dbPort -U $dbUser -d $RehearsalDatabase -tAc "SELECT count(*) FROM $table"
        if ($sourceCount -ne $restoredCount) {
            throw "Jumlah baris tidak sama untuk tabel $table."
        }
        Write-Output "$table source=$sourceCount restored=$restoredCount"
    }

    $env:DB_DATABASE = $RehearsalDatabase
    Remove-Item Env:DATABASE_URL -ErrorAction SilentlyContinue

    & php artisan migrate --force
    if ($LASTEXITCODE -ne 0) { throw 'Migration rehearsal gagal.' }

    & php artisan migrate:rollback --step=1 --force
    if ($LASTEXITCODE -ne 0) { throw 'Rollback rehearsal gagal.' }

    & php artisan migrate --force
    if ($LASTEXITCODE -ne 0) { throw 'Migration ulang rehearsal gagal.' }

    Write-Output 'MIGRATION_REHEARSAL=PASS'
    Write-Output "DUMP_BYTES=$((Get-Item -LiteralPath $dumpPath).Length)"
}
finally {
    Remove-Item Env:DB_DATABASE -ErrorAction SilentlyContinue

    if ($createdRehearsalDatabase) {
        & dropdb -h $dbHost -p $dbPort -U $dbUser $RehearsalDatabase
    }

    if (Test-Path -LiteralPath $dumpPath) {
        Remove-Item -LiteralPath $dumpPath -Force
    }

    Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
}
