<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckBlockedIp;
use App\Models\BlockedIp;
use App\Models\User;
use App\Services\SecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SecurityServiceAndBlockedIpTest extends TestCase
{
    use RefreshDatabase;

    protected SecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = app(SecurityService::class);
        Cache::flush();
    }

    public function test_temporary_timeout_stores_string_and_does_not_crash_on_get_active_block(): void
    {
        $testIp = '203.0.113.42';

        // Record failed logins up to limit
        for ($i = 0; $i < SecurityService::MAX_IP_ATTEMPTS; $i++) {
            $this->securityService->recordFailedLogin($testIp, 'attacker@example.com');
        }

        $this->assertTrue($this->securityService->isIpBlocked($testIp));

        $block = $this->securityService->getActiveBlock($testIp);
        $this->assertNotNull($block);
        $this->assertInstanceOf(BlockedIp::class, $block);
        $this->assertInstanceOf(\DateTimeInterface::class, $block->expires_at);
        $this->assertTrue($block->expires_at->isFuture());
    }

    public function test_get_active_block_handles_incomplete_class_gracefully(): void
    {
        $testIp = '203.0.113.99';

        // Simulate legacy cache entry serialized before the fix with restricted allowed_classes
        $serialized = serialize(Carbon::now()->addMinutes(15));
        $incompleteClass = unserialize($serialized, ['allowed_classes' => false]);
        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $incompleteClass);

        Cache::put("security:ip_timeout:{$testIp}", $incompleteClass, 900);

        // Must not throw TypeError: preg_match(): Argument #2 ($subject) must be of type string, __PHP_Incomplete_Class given
        $block = $this->securityService->getActiveBlock($testIp);

        $this->assertNotNull($block);
        $this->assertInstanceOf(BlockedIp::class, $block);
        $this->assertInstanceOf(\DateTimeInterface::class, $block->expires_at);
    }

    public function test_blocked_ip_model_mutator_safely_handles_incomplete_class(): void
    {
        $serialized = serialize(Carbon::now()->addMinutes(10));
        $incompleteClass = unserialize($serialized, ['allowed_classes' => false]);

        $model = new BlockedIp([
            'ip_address' => '198.51.100.1',
            'reason' => 'Test timeout',
            'expires_at' => $incompleteClass,
            'is_active' => true,
        ]);

        $this->assertNotNull($model->expires_at);
        $this->assertInstanceOf(\DateTimeInterface::class, $model->expires_at);
    }

    public function test_check_blocked_ip_middleware_returns_403_for_timed_out_ip(): void
    {
        $testIp = '203.0.113.50';

        // Set timeout
        Cache::put("security:ip_timeout:{$testIp}", Carbon::now()->addMinutes(15)->toIso8601String(), 900);

        $request = Request::create('/login', 'GET', server: ['REMOTE_ADDR' => $testIp]);

        $middleware = app(CheckBlockedIp::class);
        $response = $middleware->handle($request, function () {
            return response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_expired_timeout_is_cleaned_up_automatically(): void
    {
        $testIp = '203.0.113.77';

        // Put an already expired timestamp
        Cache::put("security:ip_timeout:{$testIp}", Carbon::now()->subMinutes(5)->toIso8601String(), 900);

        $this->assertFalse($this->securityService->isIpBlocked($testIp));
        $this->assertNull($this->securityService->getActiveBlock($testIp));
        $this->assertEquals(0, $this->securityService->getIpTimeoutRemainingMinutes($testIp));
    }
}
