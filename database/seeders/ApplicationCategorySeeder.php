<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\ApplicationCategory;
use Illuminate\Database\Seeder;

class ApplicationCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Akademik & Pembelajaran',
                'slug' => 'akademik-pembelajaran',
                'icon' => 'academic-cap',
                'description' => 'Aplikasi pembelajaran online, e-library, dan portal nilai siswa.',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Ujian & Evaluasi',
                'slug' => 'ujian-evaluasi',
                'icon' => 'document-check',
                'description' => 'Sistem CBT (Computer Based Test), tryout, dan evaluasi hasil belajar.',
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Industri & Karir',
                'slug' => 'industri-karir',
                'icon' => 'briefcase',
                'description' => 'Portal kemitraan DUDI, bursa kerja, dan sertifikasi keahlian.',
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Administrasi & Keuangan',
                'slug' => 'administrasi-keuangan',
                'icon' => 'banknotes',
                'description' => 'Layanan absensi, administrasi sekolah, dan pembayaran SPP/keuangan.',
                'display_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Utilitas & Pengembang',
                'slug' => 'utilitas-pengembang',
                'icon' => 'code-bracket',
                'description' => 'Aplikasi pendukung, dokumentasi OAuth, dan tool pengembang internal.',
                'display_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $cat) {
            ApplicationCategory::firstOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }

        // Categorize existing applications if any
        $academicCat = ApplicationCategory::where('slug', 'akademik-pembelajaran')->first();
        $utilityCat = ApplicationCategory::where('slug', 'utilitas-pengembang')->first();
        $industryCat = ApplicationCategory::where('slug', 'industri-karir')->first();

        $apps = Application::all();
        foreach ($apps as $app) {
            if (! $app->category_id) {
                if (str_contains(strtolower($app->name), 'sijuna') || str_contains(strtolower($app->name), 'demo')) {
                    $app->category_id = $utilityCat?->id;
                } elseif (str_contains(strtolower($app->name), 'dudi') || str_contains(strtolower($app->name), 'mitra')) {
                    $app->category_id = $industryCat?->id;
                } else {
                    $app->category_id = $academicCat?->id;
                }
                $app->save();
            }
        }
    }
}
