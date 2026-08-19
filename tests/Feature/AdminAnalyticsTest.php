<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_analytics_dashboard_without_errors(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole($adminRole);

        SyncLog::create([
            'sync_type' => 'sijuna_students',
            'status' => 'success',
            'records_processed' => 150,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(9),
        ]);

        SyncLog::create([
            'sync_type' => 'sijuna_teachers',
            'status' => 'success',
            'records_processed' => 50,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now()->subMinutes(4),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.analytics.index'));

        $response->assertStatus(200);
        $response->assertSee('Analitik Penggunaan SSO & Sistem', false);
        $response->assertSee('Data Diproses (Terakhir)');
        $response->assertSee('Total Data Diproses');
        $response->assertSee('200 Record');
    }
}
