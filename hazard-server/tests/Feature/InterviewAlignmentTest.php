<?php

namespace Tests\Feature;

use App\Models\HazardReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InterviewAlignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('evidence');
        config(['hazards.photo_disk' => 'evidence']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function record(User $owner, array $extra = []): HazardReport
    {
        $record = HazardReport::create([...[
            'reference_number' => 'HZ-ALIGN-'.uniqid(), 'user_id' => $owner->id,
            'category_id' => 'accident', 'department_id' => 'mdrrmo', 'title' => 'Private accident victim information',
            'description' => 'Private eyewitness description with sensitive information.', 'address' => 'Private exact house location',
            'barangay' => 'Anambongan', 'selected_latitude' => 14.5995, 'selected_longitude' => 120.9842,
            'gps_latitude' => 14.5994, 'gps_longitude' => 120.9841, 'location_source' => 'gps_adjusted',
            'injuries' => 'yes', 'people_affected' => 2,
        ], ...$extra]);
        if (isset($extra['created_at'])) {
            $record->forceFill(['created_at' => $extra['created_at']])->save();
        }

        return $record;
    }

    private function photo(HazardReport $report, string $type = 'incident')
    {
        $path = 'reports/'.uniqid().'.webp';
        $file = UploadedFile::fake()->image('proof.webp', 20, 20);
        $bytes = file_get_contents($file->getRealPath());
        Storage::disk('evidence')->put($path, $bytes);

        return $report->photos()->create(['disk' => 'evidence', 'path' => $path, 'original_name' => 'private-name.webp', 'sha256' => hash('sha256', $bytes), 'bytes' => strlen($bytes), 'photo_type' => $type]);
    }

    public function test_public_reports_search_and_news_never_expose_original_evidence_or_sensitive_details(): void
    {
        $owner = User::factory()->create();
        $report = $this->record($owner, ['status' => 'verified', 'public_title' => 'Municipal accident update', 'public_summary' => 'The response team is attending to an accident. Keep access clear.']);
        $this->photo($report);
        $this->getJson('/api/reports')->assertOk()->assertJsonPath('data.0.title', 'Municipal accident update')
            ->assertJsonPath('data.0.selected_latitude', 14.6)->assertJsonPath('data.0.selected_longitude', 120.98)
            ->assertJsonPath('data.0.approximate_location', true)->assertJsonCount(0, 'data.0.photos')
            ->assertJsonMissingPath('data.0.reporter')->assertJsonMissingPath('data.0.injuries')
            ->assertDontSee('Private eyewitness')->assertDontSee('Private exact house');
        $this->getJson('/api/reports?search=eyewitness')->assertJsonCount(0, 'data');
        $this->getJson('/api/reports?search=Private%20accident')->assertJsonCount(0, 'data');
        $this->getJson('/api/reports/nearby?category=accident&latitude=14.5995&longitude=120.9842')->assertOk()->assertDontSee('Private accident');
        Sanctum::actingAs($this->admin());
        $this->getJson('/api/news')->assertOk()->assertJsonCount(0, 'data.0.report.photos')->assertJsonMissingPath('data.0.report.gps_latitude')->assertDontSee('Private eyewitness');
        $this->getJson('/api/admin/reports')->assertOk()->assertJsonPath('data.0.title', $report->title)->assertJsonPath('data.0.injuries', 'yes');
    }

    public function test_private_evidence_is_scoped_to_reporter_admin_and_report_with_no_cache(): void
    {
        $owner = User::factory()->create();
        $report = $this->record($owner);
        $photo = $this->photo($report);
        $url = '/api/reports/'.$report->id.'/photos/'.$photo->id;
        $this->getJson($url)->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create());
        $this->getJson($url)->assertForbidden();
        Sanctum::actingAs($owner);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeader('Cache-Control', 'no-store, private');
        $other = $this->record($owner);
        $otherPhoto = $this->photo($other);
        $this->getJson('/api/reports/'.$report->id.'/photos/'.$otherPhoto->id)->assertNotFound();
        $this->getJson('/api/reports/'.$report->id)->assertJsonPath('data.selected_latitude', 14.5995)->assertJsonPath('data.photos.0.photo_type', 'incident');
        Sanctum::actingAs($this->admin());
        $this->get($url)->assertOk();
    }

    public function test_verification_alone_does_not_publish_news_and_publication_is_admin_reviewed(): void
    {
        $owner = User::factory()->create();
        $report = $this->record($owner, ['status' => 'verified']);
        $this->getJson('/api/news')->assertJsonCount(0, 'data');
        $this->getJson('/api/news/'.$report->id.'/comments')->assertNotFound();
        Sanctum::actingAs($this->admin());
        $update = ['status' => 'verified', 'severity' => 'high', 'department_id' => 'mdrrmo', 'verified_latitude' => 14.6, 'verified_longitude' => 120.98, 'note' => 'Private response coordination note.', 'public_title' => 'Accident response update', 'public_summary' => 'Municipal responders are attending to a reported road accident.'];
        $this->patchJson('/api/reports/'.$report->id, [...$update, 'public_summary' => ''])->assertUnprocessable();
        $this->patchJson('/api/reports/'.$report->id, $update)->assertOk();
        $this->getJson('/api/news')->assertJsonCount(1, 'data')->assertJsonPath('data.0.report.title', 'Accident response update')->assertDontSee('Private response coordination');
        $this->patchJson('/api/reports/'.$report->id, [...$update, 'public_title' => null, 'public_summary' => null])->assertOk();
        $this->getJson('/api/news')->assertJsonCount(0, 'data');
    }

    public function test_incident_time_and_observation_fields_are_validated_and_cannot_change_publication(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $payload = ['category_id' => 'accident', 'title' => 'Road accident near the school', 'description' => 'Two vehicles involved near the school entrance.', 'address' => 'School entrance', 'barangay' => 'Poblacion', 'selected_latitude' => 14.6, 'selected_longitude' => 121, 'location_source' => 'manual', 'location_confirmed' => 1, 'severity' => 'high', 'injuries' => 'yes', 'people_affected' => 2, 'occurred_at' => '2026-01-02T08:00:00+08:00', 'photos' => [UploadedFile::fake()->image('proof.jpg', 20, 20)], 'public_title' => 'Unauthorized publication', 'public_summary' => 'Citizen cannot publish a municipal statement.'];
        $response = $this->postJson('/api/reports', $payload)->assertCreated()->assertJsonPath('data.injuries', 'yes')->assertJsonPath('data.people_affected', 2)->assertJsonPath('data.public_title', null);
        $this->assertSame('2026-01-02 00:00:00', HazardReport::findOrFail($response->json('data.id'))->occurred_at->format('Y-m-d H:i:s'));
        $this->assertSame('evidence', DB::table('hazard_photos')->first()->disk);
        foreach ([['injuries' => 'diagnosed'], ['people_affected' => -1], ['people_affected' => 1.5], ['occurred_at' => now()->addYear()->toIso8601String()]] as $invalid) {
            $this->postJson('/api/reports', [...$payload, ...$invalid])->assertUnprocessable();
        }
    }

    public function test_resolution_upload_is_admin_only_bounded_and_keeps_incident_photos(): void
    {
        $owner = User::factory()->create();
        $report = $this->record($owner, ['status' => 'responding']);
        $this->photo($report);
        $url = '/api/reports/'.$report->id.'/resolution-photos';
        Sanctum::actingAs($owner);
        $this->postJson($url, ['photos' => [UploadedFile::fake()->image('after.jpg')]])->assertForbidden();
        Sanctum::actingAs($this->admin());
        $this->postJson($url, ['photos' => [UploadedFile::fake()->image('after.jpg')]])->assertCreated();
        $this->assertSame(1, $report->photos()->where('photo_type', 'incident')->count());
        $this->assertSame(1, $report->photos()->where('photo_type', 'resolution')->count());
        $before = count(Storage::disk('evidence')->allFiles());
        $this->postJson($url, ['photos' => array_fill(0, 3, UploadedFile::fake()->image('after.jpg'))])->assertUnprocessable();
        $this->assertCount($before, Storage::disk('evidence')->allFiles());
        $this->postJson($url, ['photos' => [UploadedFile::fake()->image('too-wide.jpg', 1921, 20)]])->assertUnprocessable();
        $report->update(['status' => 'closed']);
        $this->postJson($url, ['photos' => [UploadedFile::fake()->image('after.jpg')]])->assertUnprocessable();
        $this->assertCount($before, Storage::disk('evidence')->allFiles());
        $this->assertDatabaseHas('audit_logs', ['action' => 'resolution_photos_added']);
    }

    public function test_period_totals_cover_all_records_and_use_manila_dates_with_unknown_time_explicit(): void
    {
        $owner = User::factory()->create();
        // January 31 UTC is already February 1 PHT.
        for ($i = 0; $i < 105; $i++) {
            $this->record($owner, ['occurred_at' => '2026-01-31 16:00:00', 'created_at' => '2026-03-01 00:00:00']);
        }
        $this->record($owner, ['occurred_at' => null, 'created_at' => '2026-02-01 04:00:00', 'category_id' => 'tree']);
        $this->record($owner, ['occurred_at' => '2026-01-31 15:59:59']);
        $this->record($owner, ['occurred_at' => '2026-02-28 16:00:00']);
        $this->record($owner, ['occurred_at' => '2026-02-01 00:00:00', 'status' => 'rejected']);
        $deleted = $this->record($owner, ['occurred_at' => '2026-02-01 00:00:00']);
        $deleted->delete();
        Sanctum::actingAs($this->admin());
        $query = '/api/admin/analytics?from=2026-02-01&to=2026-02-28&group=month';
        $this->getJson($query)->assertOk()->assertJsonPath('total', 106)->assertJsonPath('known_time', 105)
            ->assertJsonPath('periods.0.period', '2026-02-01')->assertJsonPath('periods.0.accidents', 105);
        $this->getJson($query.'&category=accident')->assertJsonPath('total', 105);
        $this->getJson('/api/admin/analytics?from=2026-02-01&to=2026-02-28&group=week')->assertOk()->assertJsonPath('periods.0.period', '2026-01-26');
        $this->getJson('/api/admin/analytics?from=2026-04-01&to=2026-04-30&group=month')->assertJsonPath('total', 0)->assertJsonPath('periods.0.total', 0);
    }

    public function test_analytics_requires_admin_and_rejects_unbounded_or_invalid_requests(): void
    {
        $url = '/api/admin/analytics?from=2026-01-01&to=2026-12-31&group=month';
        $this->getJson($url)->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create());
        $this->getJson($url)->assertForbidden();
        Sanctum::actingAs($this->admin());
        foreach (['from=2026-01-01&to=2027-12-31&group=month', 'from=2026-02-01&to=2026-01-01&group=week', 'from=wrong&to=2026-01-01&group=day'] as $invalid) {
            $this->getJson('/api/admin/analytics?'.$invalid)->assertUnprocessable();
        }
    }

    public function test_legacy_public_evidence_repair_preserves_verified_bytes_and_can_be_repeated(): void
    {
        Storage::fake('public');
        $report = $this->record(User::factory()->create());
        $photo = $this->photo($report);
        $bytes = Storage::disk('evidence')->get($photo->path);
        Storage::disk('public')->put($photo->path, $bytes);
        Storage::disk('evidence')->delete($photo->path);
        $photo->update(['disk' => 'public']);
        $this->artisan('hazard:secure-evidence')->assertSuccessful();
        $this->assertSame('public', $photo->fresh()->disk);
        $this->artisan('hazard:secure-evidence --apply')->assertSuccessful();
        Storage::disk('public')->assertMissing($photo->path);
        $this->assertSame($bytes, Storage::disk('evidence')->get($photo->path));
        $this->assertSame('evidence', $photo->fresh()->disk);
        // A crash after row commit may leave a public copy; a retry removes it.
        Storage::disk('public')->put($photo->path, $bytes);
        $this->artisan('hazard:secure-evidence --apply')->assertSuccessful();
        Storage::disk('public')->assertMissing($photo->path);
        $this->assertSame(1, $report->photos()->count());
    }
}
