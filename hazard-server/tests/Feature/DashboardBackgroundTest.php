<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardBackgroundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('branding');
    }

    public function test_only_admin_can_publish_and_only_current_sanitized_image_is_public(): void
    {
        $this->postJson('/api/admin/dashboard-background', [])->assertUnauthorized();
        $this->authenticateUser(User::factory()->create());
        $this->postJson('/api/admin/dashboard-background', ['image' => UploadedFile::fake()->image('background.png')])->assertForbidden();
        $this->authenticateUser(User::factory()->create(['role' => 'admin']));
        $first = $this->postJson('/api/admin/dashboard-background', ['image' => UploadedFile::fake()->image('approved.png', 960, 540)])->assertOk()->json('dashboard_background');
        $this->get($first)->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->getJson('/api/settings')->assertJsonPath('dashboard_background', $first)->assertJsonMissingPath('dashboard_background.path');
        $next = $this->postJson('/api/admin/dashboard-background', ['image' => UploadedFile::fake()->image('next.jpg')])->assertOk()->json('dashboard_background');
        $this->get($first)->assertNotFound();
        $this->get($next)->assertOk();
        $this->assertCount(1, Storage::disk('branding')->allFiles());
        $this->deleteJson('/api/admin/dashboard-background')->assertOk()->assertJsonPath('dashboard_background', null);
        $this->get($next)->assertNotFound();
        $this->assertCount(0, Storage::disk('branding')->allFiles());
        $this->assertDatabaseHas('security_events', ['event' => 'dashboard_background_changed']);
    }

    public function test_background_rejects_script_large_dimensions_and_stale_verification(): void
    {
        $this->authenticateUser(User::factory()->create(['role' => 'admin']));
        $this->postJson('/api/admin/dashboard-background', ['image' => UploadedFile::fake()->createWithContent('script.svg', '<svg onload="alert(1)"/>')])->assertUnprocessable();
        $this->postJson('/api/admin/dashboard-background', ['image' => UploadedFile::fake()->image('large.png', 1921, 100)])->assertUnprocessable();
        $this->travel(6)->minutes();
        $this->postJson('/api/admin/dashboard-background', ['image' => UploadedFile::fake()->image('approved.png')])->assertStatus(428);
        $this->assertCount(0, Storage::disk('branding')->allFiles());
        $this->assertSame('null', DB::table('municipal_settings')->where('key', 'dashboard_background')->value('value'));
    }
}
