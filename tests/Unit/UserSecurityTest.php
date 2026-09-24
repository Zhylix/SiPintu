<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSecurityTest extends TestCase
{
    public function test_user_security_helper_methods(): void
    {
        $user = new User([
            'password' => Hash::make('password'),
            'phone' => null,
        ]);

        $this->assertTrue($user->isUsingDefaultPassword());
        $this->assertTrue($user->needsPasswordChange());
        $this->assertTrue($user->needsWhatsAppPhone());
        $this->assertTrue($user->needsSecurityOnboarding());

        // When password is changed
        $user->password = Hash::make('SuperSecret123!');
        $this->assertFalse($user->isUsingDefaultPassword());
        $this->assertFalse($user->needsPasswordChange());
        $this->assertTrue($user->needsSecurityOnboarding()); // still needs phone

        // When phone is added
        $user->phone = '081234567890';
        $this->assertFalse($user->needsWhatsAppPhone());
        $this->assertFalse($user->needsSecurityOnboarding());
    }
}
