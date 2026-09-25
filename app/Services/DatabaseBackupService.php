<?php

namespace App\Services;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    /**
     * Folder penyimpanan backup database di dalam storage/app/backups.
     */
    public static function getBackupDirectory(): string
    {
        $dir = storage_path('app/backups');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
            // Simpan .gitignore agar backup tidak pernah terunggah ke repositori
            File::put($dir.'/.gitignore', "*\n!.gitignore\n");
        }

        return $dir;
    }

    /**
     * Buat backup database SiPintu (MySQL/MariaDB) secara terkompresi (.sql.gz).
     *
     * @param  int  $retentionDays  Jumlah hari retensi file backup (default: 7 hari)
     * @param  int|string|null  $triggeredByUserId  ID user jika ditrigger manual via web admin
     */
    public function createBackup(int $retentionDays = 7, int|string|null $triggeredByUserId = null): array
    {
        $backupDir = self::getBackupDirectory();
        $timestamp = now()->format('Y-m-d_His');
        $filename = "sipintu_backup_{$timestamp}.sql.gz";
        $filepath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        $method = 'mysqldump';
        $success = false;
        $errorMessage = null;

        try {
            // Jalankan dump menggunakan mysqldump/mariadb-dump terlebih dahulu
            $success = $this->dumpUsingMysqldump($filepath);
            if (! $success) {
                // Fallback ke PDO Dump jika mysqldump gagal
                $method = 'pdo_fallback';
                $success = $this->dumpUsingPdo($filepath);
            }
        } catch (\Throwable $e) {
            // Coba fallback PDO jika ada exception pada mysqldump
            Log::warning('mysqldump gagal, mencoba metode fallback PDO: '.$e->getMessage());
            try {
                $method = 'pdo_fallback';
                $success = $this->dumpUsingPdo($filepath);
            } catch (\Throwable $pdoException) {
                $success = false;
                $errorMessage = $pdoException->getMessage();
            }
        }

        if (! $success || ! file_exists($filepath) || filesize($filepath) === 0) {
            if (file_exists($filepath)) {
                @unlink($filepath);
            }

            Log::error('SiPintu Database Backup Gagal: '.($errorMessage ?? 'File backup kosong atau proses dump gagal.'));

            return [
                'success' => false,
                'filename' => null,
                'filepath' => null,
                'filesize' => 0,
                'filesize_human' => '0 B',
                'method' => $method,
                'deleted_old_count' => 0,
                'message' => 'Gagal membuat backup database: '.($errorMessage ?? 'Proses dump tidak menghasilkan data.'),
            ];
        }

        $filesize = filesize($filepath);
        $filesizeHuman = $this->formatBytes($filesize);

        // Hapus backup lama sesuai batas retensi hari
        $deletedCount = $this->cleanOldBackups($retentionDays);

        // Catat ke AuditLog
        try {
            AuditLogger::log('database_backup_created', [
                'filename' => $filename,
                'filesize' => $filesize,
                'filesize_human' => $filesizeHuman,
                'method' => $method,
                'retention_days' => $retentionDays,
                'deleted_old_backups' => $deletedCount,
            ], $triggeredByUserId);
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat audit log backup: '.$e->getMessage());
        }

        Log::info("SiPintu Database Backup berhasil dibuat: {$filename} ({$filesizeHuman}) via {$method}. Dihapus {$deletedCount} backup lama.");

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'filesize' => $filesize,
            'filesize_human' => $filesizeHuman,
            'method' => $method,
            'deleted_old_count' => $deletedCount,
            'message' => "Backup database berhasil dibuat ({$filesizeHuman}) dan disimpan.",
        ];
    }

    /**
     * Dump database menggunakan binary mysqldump dengan kompresi streaming gz.
     */
    protected function dumpUsingMysqldump(string $targetGzPath): bool
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (! $config || ! in_array($config['driver'] ?? '', ['mysql', 'mariadb'])) {
            return false;
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = (string) ($config['port'] ?? '3306');
        $database = $config['database'];
        $username = $config['username'];
        $password = (string) ($config['password'] ?? '');
        $socket = $config['unix_socket'] ?? null;

        $command = [
            'mysqldump',
            '--user='.$username,
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--routines',
            '--triggers',
        ];

        if ($socket) {
            $command[] = '--socket='.$socket;
        } else {
            $command[] = '--host='.$host;
            $command[] = '--port='.$port;
        }

        $command[] = $database;

        // Buka file target dengan gzopen mode write binary (kompresi level 9)
        $gz = gzopen($targetGzPath, 'wb9');
        if (! $gz) {
            return false;
        }

        $env = $_ENV;
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }

        $process = new Process($command, null, $env);
        $process->setTimeout(600); // 10 menit batas waktu maksimal dump
        $hasOutput = false;

        $process->run(function ($type, $buffer) use ($gz, &$hasOutput) {
            if ($type === Process::OUT && strlen($buffer) > 0) {
                $hasOutput = true;
                gzwrite($gz, $buffer);
            }
        });

        gzclose($gz);

        if (! $process->isSuccessful() || ! $hasOutput) {
            @unlink($targetGzPath);

            return false;
        }

        return true;
    }

    /**
     * Fallback dump menggunakan koneksi PDO native Laravel.
     * Sangat aman dan berjalan di semua environment termasuk shared hosting tanpa mysqldump.
     */
    protected function dumpUsingPdo(string $targetGzPath): bool
    {
        $gz = gzopen($targetGzPath, 'wb9');
        if (! $gz) {
            return false;
        }

        $timestamp = now()->toDateTimeString();
        $header = "-- =====================================================\n"
            ."-- SiPintu Database Backup (PDO Fallback Engine)\n"
            ."-- Created at: {$timestamp}\n"
            .'-- Database: '.config('database.connections.mysql.database')."\n"
            ."-- =====================================================\n\n"
            ."SET FOREIGN_KEY_CHECKS=0;\n"
            ."SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n"
            ."SET time_zone = '+00:00';\n\n";

        gzwrite($gz, $header);

        // Ambil semua tabel dasar
        $rawTables = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        $tables = [];
        foreach ($rawTables as $row) {
            $arrayRow = (array) $row;
            $tables[] = reset($arrayRow);
        }

        foreach ($tables as $table) {
            // Write Drop & Create Table Structure
            gzwrite($gz, "\n-- -----------------------------------------------------\n");
            gzwrite($gz, "-- Table structure for `{$table}`\n");
            gzwrite($gz, "-- -----------------------------------------------------\n");
            gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n");

            $createRow = DB::select("SHOW CREATE TABLE `{$table}`");
            if (! empty($createRow)) {
                $createData = (array) $createRow[0];
                $createSql = $createData['Create Table'] ?? ($createData['Create View'] ?? null);
                if ($createSql) {
                    gzwrite($gz, $createSql.";\n\n");
                }
            }

            // Dump data tabel dalam chunk untuk menghemat memori
            gzwrite($gz, "-- Dumping data for table `{$table}`\n");
            $hasData = false;

            DB::table($table)->orderBy(DB::raw('1'))->chunk(200, function ($rows) use ($gz, $table, &$hasData) {
                if ($rows->isEmpty()) {
                    return;
                }

                $hasData = true;
                $valuesStatements = [];

                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $rowValues = [];
                    foreach ($rowArray as $val) {
                        if ($val === null) {
                            $rowValues[] = 'NULL';
                        } elseif (is_numeric($val) && ! is_string($val)) {
                            $rowValues[] = $val;
                        } else {
                            $rowValues[] = DB::getPdo()->quote((string) $val);
                        }
                    }
                    $valuesStatements[] = '('.implode(', ', $rowValues).')';
                }

                if (! empty($valuesStatements)) {
                    $sql = "INSERT INTO `{$table}` VALUES \n".implode(",\n", $valuesStatements).";\n";
                    gzwrite($gz, $sql);
                }
            });

            if (! $hasData) {
                gzwrite($gz, "-- (No data in `{$table}`)\n\n");
            } else {
                gzwrite($gz, "\n");
            }
        }

        $footer = "\nSET FOREIGN_KEY_CHECKS=1;\n"
            ."-- =====================================================\n"
            ."-- Backup Completed Successfully\n"
            ."-- =====================================================\n";
        gzwrite($gz, $footer);

        gzclose($gz);

        return true;
    }

    /**
     * Dapatkan daftar seluruh file backup yang tersimpan di storage/app/backups.
     */
    public function listBackups(): array
    {
        $backupDir = self::getBackupDirectory();
        $files = File::files($backupDir);
        $backups = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();
            if ($filename === '.gitignore' || ! str_starts_with($filename, 'sipintu_backup_')) {
                continue;
            }

            $size = $file->getSize();
            $mtime = $file->getMTime();
            $createdAt = Carbon::createFromTimestamp($mtime);

            $backups[] = [
                'filename' => $filename,
                'path' => $file->getPathname(),
                'size' => $size,
                'size_human' => $this->formatBytes($size),
                'created_at' => $createdAt,
                'created_at_human' => $createdAt->locale('id')->diffForHumans(),
                'created_at_formatted' => $createdAt->timezone('Asia/Jakarta')->format('d M Y H:i:s').' WIB',
            ];
        }

        // Urutkan dari yang terbaru
        usort($backups, function ($a, $b) {
            return $b['created_at']->timestamp <=> $a['created_at']->timestamp;
        });

        return $backups;
    }

    /**
     * Hapus file backup yang lebih tua dari $retentionDays hari.
     */
    public function cleanOldBackups(int $retentionDays = 7): int
    {
        $backupDir = self::getBackupDirectory();
        $files = File::files($backupDir);
        $thresholdTimestamp = now()->subDays($retentionDays)->timestamp;
        $deletedCount = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();
            if ($filename === '.gitignore' || ! str_starts_with($filename, 'sipintu_backup_')) {
                continue;
            }

            if ($file->getMTime() < $thresholdTimestamp) {
                if (@unlink($file->getPathname())) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }

    /**
     * Validasi dan ambil absolute path file backup untuk di-download.
     */
    public function getBackupPath(string $filename): ?string
    {
        // Sanitasi nama file untuk mencegah path traversal
        if (! preg_match('/^sipintu_backup_[0-9_\-]+\.sql(\.gz)?$/', $filename)) {
            return null;
        }

        $filepath = self::getBackupDirectory().DIRECTORY_SEPARATOR.$filename;

        return file_exists($filepath) ? $filepath : null;
    }

    /**
     * Hapus sebuah file backup secara spesifik.
     */
    public function deleteBackup(string $filename): bool
    {
        $filepath = $this->getBackupPath($filename);
        if ($filepath && file_exists($filepath)) {
            return @unlink($filepath);
        }

        return false;
    }

    /**
     * Restore database dari file backup .sql.gz.
     *
     * @param  string  $filename  Nama file backup (misal: sipintu_backup_2026-09-25_080617.sql.gz)
     * @param  int|string|null  $triggeredByUserId  ID user admin yang mengeksekusi restore
     */
    public function restoreBackup(string $filename, int|string|null $triggeredByUserId = null): array
    {
        $filepath = $this->getBackupPath($filename);
        if (! $filepath) {
            return [
                'success' => false,
                'message' => 'File backup database tidak ditemukan atau nama file tidak valid.',
            ];
        }

        $method = 'mysql_cli';
        $success = false;
        $errorMessage = null;

        try {
            $success = $this->restoreUsingMysqlCli($filepath);
            if (! $success) {
                $method = 'pdo_fallback';
                $success = $this->restoreUsingPdo($filepath);
            }
        } catch (\Throwable $e) {
            Log::warning('Restore mysql CLI gagal, mencoba metode fallback PDO: '.$e->getMessage());
            try {
                $method = 'pdo_fallback';
                $success = $this->restoreUsingPdo($filepath);
            } catch (\Throwable $pdoException) {
                $success = false;
                $errorMessage = $pdoException->getMessage();
            }
        }

        if (! $success) {
            Log::error("Restore database SiPintu dari {$filename} gagal: ".($errorMessage ?? 'Gagal memproses eksekusi SQL.'));

            return [
                'success' => false,
                'message' => 'Gagal memulihkan database: '.($errorMessage ?? 'Terjadi kesalahan saat mengeksekusi file backup.'),
            ];
        }

        AuditLogger::log('database_backup_restored', [
            'filename' => $filename,
            'method' => $method,
        ], $triggeredByUserId);

        Log::info("SiPintu Database berhasil di-restore dari: {$filename} (metode: {$method}).");

        return [
            'success' => true,
            'message' => "Database SiPintu berhasil dipulihkan dari cadangan {$filename} via {$method}.",
            'method' => $method,
        ];
    }

    /**
     * Restore menggunakan binary client mysql CLI.
     */
    protected function restoreUsingMysqlCli(string $filepath): bool
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (! $config || ! in_array($config['driver'] ?? '', ['mysql', 'mariadb'])) {
            return false;
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = (string) ($config['port'] ?? '3306');
        $database = $config['database'];
        $username = $config['username'];
        $password = (string) ($config['password'] ?? '');
        $socket = $config['unix_socket'] ?? null;

        $command = [
            'mysql',
            '--user='.$username,
            '--default-character-set=utf8mb4',
        ];

        if ($socket) {
            $command[] = '--socket='.$socket;
        } else {
            $command[] = '--host='.$host;
            $command[] = '--port='.$port;
        }

        $command[] = $database;

        $gz = gzopen($filepath, 'rb');
        if (! $gz) {
            return false;
        }

        $env = $_ENV;
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }

        $process = new Process($command, null, $env);
        $process->setTimeout(900); // 15 menit maksimal waktu restore

        // Uncompress and feed into input stream
        $sqlContent = '';
        while (! gzeof($gz)) {
            $sqlContent .= gzread($gz, 1024 * 1024); // 1MB chunks
        }
        gzclose($gz);

        if (empty($sqlContent)) {
            return false;
        }

        $process->setInput($sqlContent);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Restore menggunakan PDO connection Laravel.
     */
    protected function restoreUsingPdo(string $filepath): bool
    {
        $gz = gzopen($filepath, 'rb');
        if (! $gz) {
            return false;
        }

        $sql = '';
        while (! gzeof($gz)) {
            $sql .= gzread($gz, 1024 * 1024);
        }
        gzclose($gz);

        if (empty($sql)) {
            return false;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        try {
            DB::unprepared($sql);
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

            return true;
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
            throw $e;
        }
    }

    /**
     * Format byte ke satuan yang mudah dibaca (KB, MB, GB).
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(log($bytes, 1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), $precision).' '.$units[$pow];
    }
}
