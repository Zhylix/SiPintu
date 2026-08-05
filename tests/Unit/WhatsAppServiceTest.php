<?php

namespace Tests\Unit;

use App\Services\WhatsAppService;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    protected WhatsAppService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WhatsAppService;
    }

    public function test_format_phone_number_standard_indonesian_08(): void
    {
        $input = '08123456789';
        $expected = '628123456789';
        $this->assertEquals($expected, $this->service->formatPhoneNumber($input));
    }

    public function test_format_phone_number_with_plus_prefix(): void
    {
        $input = '+628123456789';
        $expected = '628123456789';
        $this->assertEquals($expected, $this->service->formatPhoneNumber($input));
    }

    public function test_format_phone_number_already_62(): void
    {
        $input = '628123456789';
        $expected = '628123456789';
        $this->assertEquals($expected, $this->service->formatPhoneNumber($input));
    }

    public function test_format_phone_number_with_spaces_and_dashes(): void
    {
        $input = '0812-3456 789';
        $expected = '628123456789';
        $this->assertEquals($expected, $this->service->formatPhoneNumber($input));
    }

    public function test_format_phone_number_starts_with_8(): void
    {
        $input = '8123456789';
        $expected = '628123456789';
        $this->assertEquals($expected, $this->service->formatPhoneNumber($input));
    }

    public function test_format_phone_number_invalid_or_empty_returns_null(): void
    {
        $this->assertNull($this->service->formatPhoneNumber(null));
        $this->assertNull($this->service->formatPhoneNumber(''));
        $this->assertNull($this->service->formatPhoneNumber('abc'));
        $this->assertNull($this->service->formatPhoneNumber('12345'));
    }

    public function test_format_announcement_message_template(): void
    {
        $message = $this->service->formatAnnouncementMessage('Budi Santoso', 'Besok sekolah libur.');
        $this->assertStringContainsString('📢 PENGUMUMAN', $message);
        $this->assertStringContainsString('Halo, Budi Santoso', $message);
        $this->assertStringContainsString('Besok sekolah libur.', $message);
        $this->assertStringContainsString('Terima kasih.', $message);
    }
}
