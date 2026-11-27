<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupAndRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected DatabaseBackupService $backupService;

    protected string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupService = app(DatabaseBackupService::class);
        $this->backupDir = DatabaseBackupService::getBackupDirectory();
    }

    public function test_database_backup_command_executes_successfully_and_creates_backup(): void
    {
        $exitCode = $this->artisan('sipintu:backup --retention=7');
        $exitCode->assertExitCode(0);

        $backups = $this->backupService->listBackups();
        $this->assertNotEmpty($backups, 'File backup harus tersedia setelah command sipintu:backup dieksekusi.');
        $this->assertStringStartsWith('sipintu_backup_', $backups[0]['filename']);
        $this->assertFileExists($backups[0]['path']);
    }

    public function test_old_backup_files_exceeding_one_week_are_automatically_deleted(): void
    {
        // 1. Buat file dummy berumur 10 hari (lebih dari 1 minggu / 7 hari)
        $oldDate = now()->subDays(10)->format('Y-m-d_His');
        $oldFilename = "sipintu_backup_{$oldDate}.sql.gz";
        $oldFilePath = $this->backupDir.DIRECTORY_SEPARATOR.$oldFilename;
        File::put($oldFilePath, 'dummy old compressed backup content');
        touch($oldFilePath, now()->subDays(10)->timestamp);

        // 2. Buat file dummy berumur 3 hari (masih di dalam batas 1 minggu / 7 hari)
        $recentDate = now()->subDays(3)->format('Y-m-d_His');
        $recentFilename = "sipintu_backup_{$recentDate}.sql.gz";
        $recentFilePath = $this->backupDir.DIRECTORY_SEPARATOR.$recentFilename;
        File::put($recentFilePath, 'dummy recent backup content');
        touch($recentFilePath, now()->subDays(3)->timestamp);

        $this->assertFileExists($oldFilePath);
        $this->assertFileExists($recentFilePath);

        // 3. Jalankan pembersihan retensi 7 hari
        $deletedCount = $this->backupService->cleanOldBackups(7);

        // 4. Verifikasi file > 1 minggu dihapus, file < 1 minggu dipertahankan
        $this->assertGreaterThanOrEqual(1, $deletedCount);
        $this->assertFileDoesNotExist($oldFilePath, 'File backup berusia 10 hari (> 1 minggu) harus otomatis dihapus.');
        $this->assertFileExists($recentFilePath, 'File backup berusia 3 hari (< 1 minggu) harus tetap tersimpan.');

        // Bersihkan dummy recent file
        @unlink($recentFilePath);
    }

    public function test_prune_command_cleans_backups_exceeding_one_week(): void
    {
        // Buat file dummy berumur 8 hari
        $expiredDate = now()->subDays(8)->format('Y-m-d_His');
        $expiredFilename = "sipintu_backup_{$expiredDate}.sql.gz";
        $expiredPath = $this->backupDir.DIRECTORY_SEPARATOR.$expiredFilename;
        File::put($expiredPath, 'dummy expired backup content');
        touch($expiredPath, now()->subDays(8)->timestamp);

        $this->assertFileExists($expiredPath);

        // Jalankan artisan sipintu:prune
        $this->artisan('sipintu:prune')
            ->expectsOutputToContain('file backup database lama (> 7 hari / 1 minggu)')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($expiredPath, 'Command sipintu:prune harus otomatis menghapus backup > 1 minggu.');
    }

    public function test_admin_can_download_and_delete_backup_via_controller(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Buat file dummy untuk ditest via controller
        $filename = 'sipintu_backup_'.now()->format('Y-m-d_His').'.sql.gz';
        $filepath = $this->backupDir.DIRECTORY_SEPARATOR.$filename;
        File::put($filepath, 'dummy download content');

        // Test download
        $downloadResponse = $this->actingAs($admin)
            ->get(route('admin.monitoring.backup.download', $filename));
        $downloadResponse->assertOk();

        // Test delete via controller
        $deleteResponse = $this->actingAs($admin)
            ->delete(route('admin.monitoring.backup.delete', $filename));
        $deleteResponse->assertRedirect();
        $this->assertFileDoesNotExist($filepath, 'File backup harus terhapus setelah admin melakukan delete.');
    }
}
