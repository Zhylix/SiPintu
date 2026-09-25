<?php

namespace App\Services;

use App\Models\Jurusan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserImportService
{
    /**
     * Download or return CSV Template content for User Import.
     */
    public function getCsvTemplate(): string
    {
        $headers = [
            'name',
            'email',
            'username',
            'external_id',
            'role',
            'jurusan',
            'classroom',
            'phone',
            'password',
            'force_change_password',
        ];

        $sampleRows = [
            [
                'Ahmad Fauzi',
                'ahmad.fauzi@smkn1bangsri.sch.id',
                'ahmadfauzi',
                '2122001',
                'student',
                'PPLG',
                'XII PPLG 1',
                '081234567890',
                'password123',
                'ya',
            ],
            [
                'Siti Rahmawati',
                'siti.rahma@smkn1bangsri.sch.id',
                'sitirahma',
                '2122002',
                'student',
                'AKL',
                'XII AKL 2',
                '081234567891',
                'password123',
                'ya',
            ],
            [
                'Budi Santoso, S.Kom',
                'budi.santoso@smkn1bangsri.sch.id',
                'budisantoso',
                '198501012010011001',
                'teacher',
                'PPLG',
                '',
                '081298765432',
                'GuruBangsri2026!',
                'tidak',
            ],
            [
                'PT Inovasi Digital Mandiri',
                'hrd@inovasidigital.co.id',
                'inovasidigital',
                'DUDI-001',
                'dudi',
                '',
                '',
                '082155566677',
                'MitraDudi2026',
                'ya',
            ],
            [
                'Rian Pratama',
                'rian.alumni@gmail.com',
                'rianpratama',
                '2021055',
                'alumni',
                'TO',
                'Alumni 2024',
                '085678901234',
                'password123',
                'tidak',
            ],
        ];

        $output = fopen('php://temp', 'r+');
        // Add UTF-8 BOM so Excel opens it with proper encoding
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers, ',', '"', '\\');

        foreach ($sampleRows as $row) {
            fputcsv($output, $row, ',', '"', '\\');
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Process import from an uploaded CSV file.
     */
    public function importFromCsv(UploadedFile $file, bool $updateExisting = true, bool $forcePasswordChangeAll = true): array
    {
        $realPath = $file->getRealPath();
        if (! file_exists($realPath) || ! is_readable($realPath)) {
            return [
                'success' => false,
                'message' => 'File CSV tidak dapat dibaca.',
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['File tidak dapat dibaca di server.'],
            ];
        }

        $content = file_get_contents($realPath);
        // Strip UTF-8 BOM if present
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^{$bom}/", '', $content);

        // Detect delimiter (comma vs semicolon vs tab)
        $firstLine = strtok($content, "\r\n");
        $delimiter = ',';
        if ($firstLine !== false) {
            $semicolons = substr_count($firstLine, ';');
            $commas = substr_count($firstLine, ',');
            $tabs = substr_count($firstLine, "\t");

            if ($semicolons > $commas && $semicolons > $tabs) {
                $delimiter = ';';
            } elseif ($tabs > $commas && $tabs > $semicolons) {
                $delimiter = "\t";
            }
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines) || count($lines) < 2) {
            return [
                'success' => false,
                'message' => 'File CSV kosong atau tidak memiliki baris data.',
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['File tidak memiliki baris data minimal 1 baris di bawah header.'],
            ];
        }

        // Parse header
        $rawHeaders = str_getcsv(array_shift($lines), $delimiter);
        $headers = array_map(function ($h) {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $h)));
        }, $rawHeaders);

        // Cache Jurusan lookup table
        $jurusans = Jurusan::all();
        $jurusanMap = [];
        foreach ($jurusans as $j) {
            $jurusanMap[strtoupper(trim($j->kode_jurusan))] = $j->id;
            $jurusanMap[strtolower(trim($j->nama_jurusan))] = $j->id;
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($lines as $index => $line) {
                $rowNumber = $index + 2; // Line 1 is header, 1-indexed
                if (trim($line) === '') {
                    continue;
                }

                $rowValues = str_getcsv($line, $delimiter);
                if (empty($rowValues) || (count($rowValues) === 1 && trim($rowValues[0]) === '')) {
                    continue;
                }

                // Combine row values with headers
                $row = [];
                foreach ($headers as $colIdx => $colName) {
                    $row[$colName] = isset($rowValues[$colIdx]) ? trim($rowValues[$colIdx]) : null;
                }

                // Name validation
                $name = $row['name'] ?? $row['nama'] ?? null;
                if (! $name) {
                    $errors[] = "Baris {$rowNumber}: Kolom 'name' (nama) wajib diisi.";
                    $skipped++;

                    continue;
                }

                // Email validation
                $email = strtolower($row['email'] ?? '');
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Baris {$rowNumber}: Format email '{$email}' tidak valid.";
                    $skipped++;

                    continue;
                }

                // Username & External ID
                $username = ! empty($row['username']) ? trim($row['username']) : null;
                $externalId = ! empty($row['external_id']) ? trim($row['external_id']) : (! empty($row['nis']) ? trim($row['nis']) : (! empty($row['nip']) ? trim($row['nip']) : null));

                // Role mapping & normalization
                $rawRole = strtolower($row['role'] ?? 'student');
                $role = match ($rawRole) {
                    'guru', 'teacher', 'pengajar', 'pendidik' => 'teacher',
                    'dudi', 'industri', 'mitra' => 'dudi',
                    'alumni' => 'alumni',
                    'admin', 'administrator' => 'admin',
                    default => 'student',
                };

                // Jurusan mapping
                $jurusanId = null;
                $rawJurusan = strtoupper(trim($row['jurusan'] ?? ''));
                if ($rawJurusan !== '') {
                    $jurusanId = $jurusanMap[$rawJurusan] ?? ($jurusanMap[strtolower($rawJurusan)] ?? null);
                }

                $classroom = ! empty($row['classroom']) ? trim($row['classroom']) : (! empty($row['kelas']) ? trim($row['kelas']) : null);
                $phone = ! empty($row['phone']) ? trim($row['phone']) : (! empty($row['no_telepon']) ? trim($row['no_telepon']) : (! empty($row['no_hp']) ? trim($row['no_hp']) : null));

                // Sanitize phone
                if ($phone) {
                    $phone = preg_replace('/[^0-9]/', '', $phone);
                    if (str_starts_with($phone, '08')) {
                        $phone = '628'.substr($phone, 2);
                    }
                }

                // Password logic
                $rawPassword = $row['password'] ?? null;
                $mustChangePass = $forcePasswordChangeAll;

                if (! empty($row['force_change_password'])) {
                    $val = strtolower(trim($row['force_change_password']));
                    $mustChangePass = in_array($val, ['ya', 'yes', 'true', '1', 'y']);
                }

                if (! $rawPassword) {
                    // Default password fallback: external_id or 'password123'
                    $rawPassword = $externalId ?: 'password123';
                    $mustChangePass = true;
                }

                // Check existing user by email or external_id
                $existingUser = User::where('email', $email)
                    ->orWhere(function ($q) use ($externalId) {
                        if ($externalId) {
                            $q->where('external_id', $externalId);
                        }
                    })
                    ->first();

                if ($existingUser) {
                    if (! $updateExisting) {
                        $skipped++;

                        continue;
                    }

                    // Update existing user
                    $updateData = [
                        'name' => $name,
                        'role' => $role,
                        'status' => 'active',
                    ];

                    if ($username && $existingUser->username !== $username) {
                        // Check if username is already taken by another user
                        $usernameTaken = User::where('username', $username)->where('id', '!=', $existingUser->id)->exists();
                        if (! $usernameTaken) {
                            $updateData['username'] = $username;
                        }
                    }

                    if ($externalId) {
                        $updateData['external_id'] = $externalId;
                    }
                    if ($jurusanId) {
                        $updateData['jurusan_id'] = $jurusanId;
                    }
                    if ($classroom) {
                        $updateData['classroom'] = $classroom;
                    }
                    if ($phone) {
                        $updateData['phone'] = $phone;
                    }

                    // Update password only if explicit non-default password provided in file
                    if (! empty($row['password'])) {
                        $updateData['password'] = Hash::make($rawPassword);
                        $updateData['must_change_password'] = $mustChangePass;
                    }

                    $existingUser->update($updateData);

                    // Sync Spatie role
                    if (class_exists(Role::class)) {
                        $roleObj = Role::where('name', $role)->first();
                        if ($roleObj) {
                            $existingUser->syncRoles([$roleObj]);
                        }
                    }

                    $updated++;
                } else {
                    // Create new user
                    // Check username clash
                    $finalUsername = $username;
                    if (! $finalUsername) {
                        $finalUsername = Str::slug(explode('@', $email)[0], '');
                    }
                    if (User::where('username', $finalUsername)->exists()) {
                        $finalUsername = $finalUsername.'_'.rand(100, 999);
                    }

                    $newUser = User::create([
                        'name' => $name,
                        'email' => $email,
                        'username' => $finalUsername,
                        'external_id' => $externalId,
                        'password' => Hash::make($rawPassword),
                        'role' => $role,
                        'jurusan_id' => $jurusanId,
                        'classroom' => $classroom,
                        'phone' => $phone,
                        'status' => 'active',
                        'wa_notify' => true,
                        'must_change_password' => $mustChangePass,
                        'email_verified_at' => now(),
                    ]);

                    // Assign Spatie role
                    if (class_exists(Role::class)) {
                        $roleObj = Role::where('name', $role)->first();
                        if ($roleObj) {
                            $newUser->assignRole($roleObj);
                        }
                    }

                    $imported++;
                }
            }

            DB::commit();

            AuditLogger::log('users_imported_csv', [
                'imported_count' => $imported,
                'updated_count' => $updated,
                'skipped_count' => $skipped,
                'error_count' => count($errors),
            ], auth()->id());

            return [
                'success' => true,
                'message' => "Import selesai: {$imported} user baru ditambahkan, {$updated} user diperbarui, {$skipped} dilewati.",
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => array_slice($errors, 0, 10), // Return max 10 errors for clean UI
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Gagal memproses import data: '.$e->getMessage(),
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [$e->getMessage()],
            ];
        }
    }
}
