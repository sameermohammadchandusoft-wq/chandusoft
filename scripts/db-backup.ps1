# === CONFIG ===
$DBHost = "localhost"
$DBName = "chandusoft"
$DBUser = "root"
$DBPass = ""   # leave empty if not needed
$TestDBName = "chandusoft_test"   # ✅ Backup test DB name

# === PATH SETUP ===
$BackupDir = Join-Path (Split-Path $PSScriptRoot -Parent) "storage\backups"
$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$FileName = "db-$Timestamp.sql"
$FullPath = Join-Path $BackupDir $FileName

# === CREATE BACKUP DIRECTORY ===
if (!(Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir | Out-Null
}

Write-Host "Backing up database '$DBName' to $FullPath ..."

# === RUN BACKUP (Avoid PowerShell > redirection — use cmd instead) ===
if ($DBPass -ne "") {
    $backupCmd = "mysqldump -h $DBHost -u $DBUser -p$DBPass $DBName > `"$FullPath`""
} else {
    $backupCmd = "mysqldump -h $DBHost -u $DBUser $DBName > `"$FullPath`""
}
cmd /c $backupCmd

# === VERIFY RESULT ===
if ((Test-Path $FullPath) -and ((Get-Item $FullPath).Length -gt 0)) {
    Write-Host "✅ Backup completed successfully!"
    Write-Host "File saved to: $FullPath"

    # === RESTORE TO TEST DATABASE ===
    Write-Host "`n🔄 Restoring to test database '$TestDBName'..."

    $dropCreateSQL = @"
DROP DATABASE IF EXISTS $TestDBName;
CREATE DATABASE $TestDBName;
"@

    $dropCreateCmd = if ($DBPass -ne "") {
        "mysql -h $DBHost -u $DBUser -p$DBPass -e `"$dropCreateSQL`""
    } else {
        "mysql -h $DBHost -u $DBUser -e `"$dropCreateSQL`""
    }
    Invoke-Expression $dropCreateCmd

    # === IMPORT BACKUP INTO TEST DB ===
    if ($DBPass -ne "") {
        $importCmd = "mysql -h $DBHost -u $DBUser -p$DBPass $TestDBName < `"$FullPath`""
    } else {
        $importCmd = "mysql -h $DBHost -u $DBUser $TestDBName < `"$FullPath`""
    }

    # Use cmd /c to allow < redirection to work
    cmd /c $importCmd

    Write-Host "✅ Test database '$TestDBName' is now synchronized."
} else {
    Write-Host "❌ Backup failed or file is empty!"
    Remove-Item $FullPath -ErrorAction SilentlyContinue
}
# === CLEANUP: Keep only latest 5 backup files ===
$BackupDir = "C:\laragon\www\chandusoft\storage\backups"
$maxBackups = 3
$backupFiles = Get-ChildItem -Path $BackupDir -Filter "db-*.sql" | Sort-Object LastWriteTime -Descending

if ($backupFiles.Count -gt $maxBackups) {
    $filesToDelete = $backupFiles | Select-Object -Skip $maxBackups
    foreach ($file in $filesToDelete) {
        Remove-Item $file.FullName -Force
        Write-Host "Would delete old backup: $($file.Name)"
    }
} else {
    Write-Host "No backups need deleting. Total backups: $($backupFiles.Count)"
}