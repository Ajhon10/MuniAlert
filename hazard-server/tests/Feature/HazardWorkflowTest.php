<?php

namespace Tests\Feature;

use App\Models\HazardReport;
use App\Models\User;
use App\Support\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HazardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('evidence');
        config(['hazards.photo_disk' => 'evidence']);
    }

    private function citizen(): User
    {
        return User::factory()->create();
    }

    private function admin(): User
    {
        $user = $this->citizen();
        $user->role = 'admin';
        $user->save();

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['category_id' => 'tree', 'title' => 'Fallen tree blocking the road', 'description' => 'A large tree is blocking half of the road near the school.', 'address' => 'Riverside Road', 'barangay' => 'Anambongan', 'selected_latitude' => 14.5995, 'selected_longitude' => 120.9842, 'location_source' => 'manual', 'location_confirmed' => 1, 'severity' => 'high', 'photos' => [UploadedFile::fake()->image('hazard.jpg', 400, 300)]], $overrides);
    }

    private function report(User $user, array $extra = []): HazardReport
    {
        return HazardReport::create(array_replace(['reference_number' => 'HZ-TEST-'.uniqid(), 'user_id' => $user->id, 'category_id' => 'tree', 'department_id' => 'engineering', 'title' => 'Fallen tree across the road', 'description' => 'A synthetic report for an automated test.', 'address' => 'Riverside Road', 'barangay' => 'Anambongan', 'selected_latitude' => 14.5995, 'selected_longitude' => 120.9842, 'location_source' => 'manual', 'severity' => 'high', 'status' => 'submitted'], $extra));
    }

    public function test_manual_report_requires_confirmed_location_and_compresses_photos(): void
    {
        $user = $this->citizen();
        Sanctum::actingAs($user);
        $this->postJson('/api/reports', $this->payload(['location_confirmed' => 0]))->assertUnprocessable()->assertJsonValidationErrors('location_confirmed');
        $response = $this->postJson('/api/reports', $this->payload())->assertCreated()->assertJsonPath('data.location_source', 'manual')->assertJsonPath('data.gps_latitude', null);
        $report = HazardReport::findOrFail($response->json('data.id'));
        $photo = $report->photos()->firstOrFail();
        $this->assertLessThanOrEqual(1024 * 1024, $photo->bytes);
        $this->assertStringEndsWith('.webp', $photo->path);
        Storage::disk('evidence')->assertExists($photo->path);
        $this->assertDatabaseHas('report_status_history', ['hazard_report_id' => $report->id, 'status' => 'submitted']);
        $this->assertDatabaseHas('audit_logs', ['hazard_report_id' => $report->id, 'action' => 'created']);
    }

    public function test_gps_adjustment_is_saved_and_flagged_without_rejection(): void
    {
        Sanctum::actingAs($this->citizen());
        $this->postJson('/api/reports', $this->payload(['location_source' => 'gps_adjusted', 'gps_latitude' => 14.7, 'gps_longitude' => 121.0, 'gps_accuracy' => 9.2]))->assertCreated()->assertJsonPath('data.status', 'submitted')->assertJsonPath('data.gps_accuracy', 9.2)->assertJsonPath('data.location_review_required', true);
    }

    public function test_gps_source_cannot_hide_a_changed_pin_and_coordinates_are_required(): void
    {
        Sanctum::actingAs($this->citizen());
        $this->postJson('/api/reports', $this->payload(['location_source' => 'gps', 'gps_latitude' => 14.7, 'gps_longitude' => 121.0, 'gps_accuracy' => 12]))->assertUnprocessable()->assertJsonValidationErrors('location_source');
        $this->postJson('/api/reports', $this->payload(['selected_latitude' => null]))->assertUnprocessable()->assertJsonValidationErrors('selected_latitude');
        $this->postJson('/api/reports', $this->payload(['selected_longitude' => 190]))->assertUnprocessable()->assertJsonValidationErrors('selected_longitude');
    }

    public function test_photo_count_size_and_decoded_type_are_checked(): void
    {
        Sanctum::actingAs($this->citizen());
        $this->postJson('/api/reports', $this->payload(['photos' => array_fill(0, 4, UploadedFile::fake()->image('photo.jpg'))]))->assertUnprocessable();
        $this->postJson('/api/reports', $this->payload(['photos' => [UploadedFile::fake()->image('large.jpg')->size(1025)]]))->assertUnprocessable();
        $this->postJson('/api/reports', $this->payload(['photos' => [UploadedFile::fake()->create('not-an-image.jpg', 1, 'text/plain')]]))->assertUnprocessable();
        $this->postJson('/api/reports', $this->payload(['photos' => [UploadedFile::fake()->image('wide.jpg', 1921, 50)]]))->assertUnprocessable();
    }

    public function test_citizens_cannot_change_status_or_view_private_details_of_others(): void
    {
        $report = $this->report($this->citizen(), ['gps_latitude' => 14.59, 'gps_longitude' => 120.98, 'gps_accuracy' => 8, 'location_source' => 'gps_adjusted']);
        $this->getJson('/api/reports')->assertOk()->assertJsonMissingPath('data.0.gps_latitude')->assertJsonMissingPath('data.0.reporter');
        Sanctum::actingAs($this->citizen());
        $this->getJson('/api/reports/'.$report->id)->assertForbidden();
        $this->patchJson('/api/reports/'.$report->id, ['status' => 'verified'])->assertForbidden();
        $this->getJson('/api/admin/reports')->assertForbidden();
    }

    public function test_admin_workflow_preserves_original_locations_and_notifies_reporter(): void
    {
        $citizen = $this->citizen();
        $report = $this->report($citizen, ['gps_latitude' => 14.599, 'gps_longitude' => 120.984, 'gps_accuracy' => 9, 'location_source' => 'gps_adjusted']);
        Sanctum::actingAs($this->admin());
        $update = ['severity' => 'high', 'department_id' => 'engineering', 'note' => 'Municipal team reviewed this report.', 'verified_latitude' => 14.6001, 'verified_longitude' => 120.9843];
        $this->patchJson('/api/reports/'.$report->id, [...$update, 'status' => 'resolved'])->assertUnprocessable();
        foreach (['under_verification', 'verified', 'assigned', 'responding', 'resolved', 'closed'] as $status) {
            $this->patchJson('/api/reports/'.$report->id, [...$update, 'status' => $status, 'assigned_responder' => 'Engineering team 1'])->assertOk()->assertJsonPath('data.status', $status);
        }
        $report->refresh();
        $this->assertSame(14.599, $report->gps_latitude);
        $this->assertSame(14.5995, $report->selected_latitude);
        $this->assertSame(14.6001, $report->verified_latitude);
        $this->assertNotNull($report->resolved_at);
        $this->assertSame(6, DB::table('notifications')->where('notifiable_id', $citizen->id)->count());
        $this->assertSame(6, DB::table('audit_logs')->where('hazard_report_id', $report->id)->count());
        $this->patchJson('/api/reports/'.$report->id, [...$update, 'status' => 'closed'])->assertUnprocessable();
    }

    public function test_nearby_detection_and_confirmations_are_scoped_and_idempotent(): void
    {
        $owner = $this->citizen();
        $near = $this->report($owner);
        $this->report($owner, ['selected_latitude' => 15.0]);
        $this->report($owner, ['category_id' => 'flood']);
        $this->report($owner, ['status' => 'resolved']);
        $this->getJson('/api/reports/nearby?category=tree&latitude=14.5995&longitude=120.9842')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $near->id);
        Sanctum::actingAs($this->citizen());
        $this->postJson('/api/reports/'.$near->id.'/confirm')->assertOk();
        $this->postJson('/api/reports/'.$near->id.'/confirm')->assertOk();
        $this->assertDatabaseCount('report_confirmations', 1);
        $near->update(['status' => 'resolved']);
        $this->postJson('/api/reports/'.$near->id.'/confirm')->assertUnprocessable();
    }

    public function test_registration_never_accepts_an_admin_role_and_mobile_tokens_can_be_revoked(): void
    {
        $response = $this->postJson('/api/auth/register', ['name' => 'Test Citizen', 'email' => 'citizen@example.test', 'password' => 'a-long-test-password', 'role' => 'admin'])->assertCreated()->assertJsonPath('user.role', 'citizen');
        $token = $response->json('token');
        $this->withToken($token)->getJson('/api/auth/me')->assertOk();
        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_haversine_handles_zero_and_known_distance(): void
    {
        $this->assertSame(0.0, Location::distance(14.6, 121, 14.6, 121));
        $this->assertEqualsWithDelta(111195, Location::distance(0, 0, 1, 0), 2);
    }
}
