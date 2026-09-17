<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_and_optimize_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Budi Siswa',
            'email' => 'budi.avatar@smkn1bangsri.sch.id',
            'avatar' => null,
        ]);

        $file = UploadedFile::fake()->image('my_photo.jpg', 600, 400);

        $response = $this->actingAs($user)
            ->post(route('profile.avatar.update'), [
                'avatar' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertStringStartsWith('avatars/', $user->avatar);
        $this->assertStringEndsWith('.webp', $user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_user_can_delete_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Budi Siswa',
            'email' => 'budi.delete@smkn1bangsri.sch.id',
            'avatar' => 'avatars/existing_photo.webp',
        ]);
        Storage::disk('public')->put('avatars/existing_photo.webp', 'dummy-data');

        $response = $this->actingAs($user)
            ->delete(route('profile.avatar.destroy'));

        $response->assertRedirect();
        $response->assertSessionHas('info');

        $user->refresh();
        $this->assertNull($user->avatar);
        Storage::disk('public')->assertMissing('avatars/existing_photo.webp');
    }

    public function test_avatar_upload_fails_for_invalid_file_type(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user)
            ->post(route('profile.avatar.update'), [
                'avatar' => $file,
            ]);

        $response->assertSessionHasErrors(['avatar']);
        $this->assertNull($user->refresh()->avatar);
    }
}
