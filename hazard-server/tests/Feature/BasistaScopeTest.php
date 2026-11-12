<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BasistaScopeTest extends TestCase
{
    use RefreshDatabase;

    private function payload(string $barangay): array
    {
        return ['category_id' => 'fire', 'title' => 'Synthetic fire for scope verification', 'description' => 'A fictional event used only in the isolated test database.', 'address' => 'Synthetic landmark', 'barangay' => $barangay, 'selected_latitude' => 15.865448, 'selected_longitude' => 120.400132, 'location_source' => 'manual', 'location_confirmed' => 1, 'severity' => 'high', 'photos' => [UploadedFile::fake()->image('scope.jpg', 20, 20)]];
    }

    public function test_new_reports_accept_basista_names_and_reject_another_municipality_barangay(): void
    {
        $this->seed();
        $this->authenticateUser(User::factory()->create());
        $this->postJson('/api/reports', $this->payload('Anambongan'))->assertCreated()->assertJsonPath('data.barangay', 'Anambongan');
        $this->postJson('/api/reports', $this->payload('San Antonio'))->assertUnprocessable()->assertJsonValidationErrors('barangay');
        $this->assertDatabaseCount('hazard_reports', 1);
    }

    public function test_ascii_and_case_variants_are_normalized_to_the_user_approved_name(): void
    {
        $this->seed();
        $this->authenticateUser(User::factory()->create());
        $this->postJson('/api/reports', $this->payload(' osmena sr. '))->assertCreated()->assertJsonPath('data.barangay', 'Osmeña Sr.');
    }

    public function test_scope_contains_exactly_the_thirteen_user_selected_barangays(): void
    {
        $this->assertCount(13, config('municipality.barangays'));
        $this->assertSame(['Anambongan', 'Bayoyong', 'Cabeldatan', 'Dumpay', 'Malimpec East', 'Mapolopolo', 'Nalneran', 'Navatat', 'Obong', 'Osmeña Sr.', 'Palma', 'Patacbo', 'Poblacion'], config('municipality.barangays'));
        $this->assertSame('Basista Municipal Hall', config('municipality.office'));
    }

    public function test_camera_migration_changes_only_the_generic_default_and_keeps_custom_settings(): void
    {
        $migration = require database_path('migrations/2026_11_09_000001_center_default_map_on_basista.php');
        DB::table('municipal_settings')->where('key', 'map')->update(['value' => json_encode(['latitude' => 14.5995, 'longitude' => 120.9842, 'zoom' => 13])]);
        $migration->up();
        $this->getJson('/api/settings')->assertOk()->assertJsonPath('map.latitude', 15.865448);
        $custom = ['latitude' => 15.85, 'longitude' => 120.39, 'zoom' => 16];
        DB::table('municipal_settings')->where('key', 'map')->update(['value' => json_encode($custom)]);
        $migration->up();
        $this->getJson('/api/settings')->assertOk()->assertJsonPath('map', $custom);
    }
}
