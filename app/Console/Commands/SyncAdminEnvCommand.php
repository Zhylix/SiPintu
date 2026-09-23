<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SyncAdminEnvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:sync {--password-only : Hanya perbarui kata sandi admin dari .env}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronkan data akun Administrator SiPintu dengan konfigurasi yang ada di file .env';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('===============================================================');
        $this->info('   SINKRONISASI AKUN ADMINISTRATOR SIPINTU DARI .ENV           ');
        $this->info('===============================================================');
        $this->newLine();

        $config = config('auth.admin', [
            'name' => env('ADMIN_NAME', 'Administrator SiPintu'),
            'username' => env('ADMIN_USERNAME', 'admin'),
            'email' => env('ADMIN_EMAIL', 'admin@smkn1bangsri.sch.id'),
            'password' => env('ADMIN_PASSWORD', 'password'),
        ]);

        $name = $config['name'] ?? 'Administrator SiPintu';
        $username = $config['username'] ?? 'admin';
        $email = $config['email'] ?? 'admin@smkn1bangsri.sch.id';
        $password = $config['password'] ?? 'password';

        // Cari akun admin yang sudah ada
        $admin = User::where('role', 'admin')
            ->orWhereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->orWhere('email', 'admin@gateway.sekolah.id')
            ->orWhere('email', $email)
            ->first();

        // Pastikan role admin tersedia di sistem
        Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['slug' => 'admin']
        );

        if (! $admin) {
            $admin = User::create([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'status' => 'active',
            ]);
            $admin->syncRoles(['admin']);

            $this->components->info("Akun admin baru berhasil dibuat berdasarkan file .env!");
        } else {
            if ($this->option('password-only')) {
                $admin->update([
                    'password' => Hash::make($password),
                ]);
                $this->components->info("Kata sandi akun admin ({$admin->email}) berhasil diperbarui dari .env!");
            } else {
                $admin->update([
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role' => 'admin',
                    'status' => 'active',
                ]);
                $admin->syncRoles(['admin']);
                $this->components->info("Data akun admin berhasil diperbarui & disinkronkan dari file .env!");
            }
        }

        $this->newLine();
        $this->table(
            ['Atribut', 'Nilai Saat Ini (Aktif)'],
            [
                ['ID User', $admin->id],
                ['Nama Lengkap', $admin->name],
                ['Username', $admin->username],
                ['Email', $admin->email],
                ['Role', $admin->role],
                ['Status', $admin->status],
                ['Status Kata Sandi', 'Tersinkronisasi dengan ADMIN_PASSWORD di .env'],
            ]
        );

        $this->newLine();
        $this->line('<fg=green;options=bold>✓ Kredensial admin sekarang dapat digunakan langsung untuk login.</>');
        $this->line("<fg=gray>Catatan: Jika Anda mengubah ADMIN_* di .env nanti, jalankan kembali perintah `php artisan admin:sync`.</>");
        $this->newLine();

        return self::SUCCESS;
    }
}
