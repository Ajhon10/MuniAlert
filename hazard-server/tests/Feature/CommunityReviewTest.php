<?php

namespace Tests\Feature;

use App\Models\HazardReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommunityReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function record(User $owner, string $category = 'fire', string $status = 'submitted', bool $accepted = false, bool $published = false): HazardReport
    {
        return HazardReport::create([
            'user_id' => $owner->id, 'reference_number' => 'HZ-REVIEW-'.uniqid(), 'category_id' => $category, 'department_id' => 'mdrrmo',
            'title' => 'PRIVATE_RAW_TITLE', 'description' => 'PRIVATE_RAW_DETAILS', 'address' => 'PRIVATE_EXACT_ADDRESS', 'barangay' => 'Anambongan',
            'selected_latitude' => 15.864, 'selected_longitude' => 120.399, 'gps_latitude' => 15.8641, 'gps_longitude' => 120.3999, 'location_source' => 'gps_adjusted',
            'status' => $status, 'fact_checked_at' => $accepted ? now() : null, 'verified_at' => $accepted ? now() : null,
            'public_title' => $published ? 'Approved community update' : null, 'public_summary' => $published ? 'The municipality has confirmed this hazard. Please follow the approved advice.' : null,
            'published_at' => $published ? now() : null,
        ]);
    }

    private function verification(): array
    {
        return ['confirmed_happened' => true, 'method' => 'responder_confirmation', 'verification_note' => 'PRIVATE_FACT_NOTE: confirmed independently with the municipal response team.', 'verified_latitude' => 15.865, 'verified_longitude' => 120.4];
    }

    private function postData(): array
    {
        return ['public_title' => 'Verified fire response update', 'public_summary' => 'The municipal team has confirmed a fire incident. Keep access clear for responders.', 'privacy_reviewed' => true];
    }

    public function test_lists_statistics_search_and_nearby_never_reveal_unaccepted_submissions(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $own = $this->record($member);
        $pending = $this->record($other);
        $legacy = $this->record($other, status: 'verified', published: true);
        $accepted = $this->record($other, status: 'verified', accepted: true);
        $accepted->forceFill(['public_title' => 'UnapprovedDraftHeadline', 'public_summary' => 'UnapprovedDraftSummary'])->save();
        foreach (['/api/reports', '/api/reports?status=submitted', '/api/reports?search=PRIVATE_RAW_TITLE'] as $url) {
            $result = $this->getJson($url)->assertOk();
            $result->assertDontSee($own->id)->assertDontSee($pending->id)->assertDontSee($legacy->id)->assertDontSee('PRIVATE_RAW_DETAILS');
        }
        $this->getJson('/api/reports')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $accepted->id)->assertJsonMissingPath('data.0.reporter');
        $this->getJson('/api/reports')->assertDontSee('UnapprovedDraft');
        $this->getJson('/api/reports?search=UnapprovedDraftHeadline')->assertJsonCount(0, 'data');
        $this->getJson('/api/stats')->assertJsonPath('total', 1)->assertJsonPath('pending', 0);
        $this->getJson('/api/reports/nearby?category=fire&latitude=15.864&longitude=120.399')->assertJsonCount(1)->assertJsonPath('0.id', $accepted->id);
        $this->getJson('/api/news')->assertJsonCount(0, 'data');
        $this->authenticateUser($member);
        $this->getJson('/api/reports')->assertJsonCount(1, 'data')->assertDontSee($pending->id);
        $this->getJson('/api/reports/mine')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id)->assertJsonPath('data.0.description', 'PRIVATE_RAW_DETAILS');
        $this->getJson('/api/reports/'.$pending->id)->assertForbidden();
        $this->postJson('/api/reports/'.$pending->id.'/confirm')->assertNotFound();
        $this->getJson('/api/admin/stats')->assertForbidden();
        $this->authenticateUser(User::factory()->create(['role' => 'admin']));
        $this->getJson('/api/admin/reports')->assertJsonCount(4, 'data');
        $this->getJson('/api/admin/stats')->assertJsonPath('total', 4)->assertJsonPath('pending', 2);
        $this->getJson('/api/stats')->assertJsonPath('total', 1);
    }

    public function test_acceptance_requires_admin_attestation_and_keeps_the_review_record_private(): void
    {
        $owner = User::factory()->create();
        $report = $this->record($owner);
        $this->authenticateUser($owner);
        $this->postJson('/api/admin/reports/'.$report->id.'/verify', $this->verification())->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->authenticateUser($admin);
        $this->postJson('/api/admin/reports/'.$report->id.'/verify', [...$this->verification(), 'confirmed_happened' => false])->assertUnprocessable();
        $this->postJson('/api/admin/reports/'.$report->id.'/verify', [...$this->verification(), 'verification_note' => 'short'])->assertUnprocessable();
        $this->patchJson('/api/reports/'.$report->id, ['status' => 'verified', 'severity' => 'high', 'department_id' => 'fire', 'note' => 'Attempt to skip fact checking.', 'verified_latitude' => 15.865, 'verified_longitude' => 120.4])->assertUnprocessable();
        $this->postJson('/api/admin/reports/'.$report->id.'/verify', $this->verification())->assertOk()->assertJsonPath('data.status', 'verified')->assertJsonPath('data.fact_check_method', 'responder_confirmation');
        $this->assertSame($admin->id, $report->fresh()->fact_checked_by);
        $this->assertSame(15.8641, $report->fresh()->gps_latitude);
        $this->assertSame(15.864, $report->fresh()->selected_latitude);
        $this->assertSame(15.865, $report->fresh()->verified_latitude);
        $this->getJson('/api/reports')->assertJsonCount(1, 'data')->assertDontSee('PRIVATE_FACT_NOTE');
        $this->getJson('/api/news')->assertJsonCount(0, 'data');
        $this->postJson('/api/admin/reports/'.$report->id.'/verify', $this->verification())->assertUnprocessable();
        $this->assertDatabaseCount('notifications', 1);
        $this->assertStringNotContainsString('PRIVATE_FACT_NOTE', json_encode(DB::table('notifications')->get()));
        $this->authenticateUser($owner);
        $this->getJson('/api/reports/'.$report->id)->assertOk()->assertJsonMissingPath('data.fact_check_note')->assertJsonMissingPath('data.fact_check_method');
        $this->getJson('/api/profile')->assertJsonPath('verified_reports', 1);
    }

    public function test_only_fact_checked_reports_can_be_explicitly_published_and_shared_without_private_fields(): void
    {
        $owner = User::factory()->create();
        $report = $this->record($owner);
        $this->authenticateUser(User::factory()->create(['role' => 'admin']));
        $this->postJson('/api/admin/reports/'.$report->id.'/post', $this->postData())->assertUnprocessable();
        $this->get('/news/'.$report->id)->assertNotFound();
        $this->postJson('/api/admin/reports/'.$report->id.'/verify', $this->verification())->assertOk();
        $this->postJson('/api/admin/reports/'.$report->id.'/post', [...$this->postData(), 'privacy_reviewed' => false])->assertUnprocessable();
        $this->postJson('/api/admin/reports/'.$report->id.'/post', [...$this->postData(), 'public_title' => 'Verified fire <script>alert(1)</script>'])->assertOk();
        $this->getJson('/api/news')->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.report.reporter')->assertJsonMissingPath('data.0.report.gps_latitude')->assertJsonCount(0, 'data.0.report.photos')->assertDontSee('PRIVATE_FACT_NOTE')->assertDontSee('PRIVATE_RAW_DETAILS');
        $this->get('/news/'.$report->id)->assertOk()->assertSee('og:title', false)->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false)->assertDontSee('PRIVATE_RAW_TITLE')->assertDontSee('PRIVATE_EXACT_ADDRESS')->assertDontSee('PRIVATE_FACT_NOTE')->assertHeader('Cache-Control', 'no-store, private');
        $this->authenticateUser($owner);
        $this->postJson('/api/admin/reports/'.$report->id.'/post', $this->postData())->assertForbidden();
        $this->deleteJson('/api/admin/reports/'.$report->id.'/post')->assertForbidden();
    }

    public function test_unpublishing_and_rejection_remove_public_posts_and_revoke_ineligible_progress(): void
    {
        $owner = User::factory()->create();
        $report = $this->record($owner, status: 'verified', accepted: true, published: true);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->authenticateUser($admin);
        $this->deleteJson('/api/admin/reports/'.$report->id.'/post')->assertOk();
        $this->getJson('/api/news')->assertJsonCount(0, 'data');
        $this->get('/news/'.$report->id)->assertNotFound();
        $this->getJson('/api/news/'.$report->id.'/comments')->assertNotFound();
        $this->getJson('/api/reports')->assertJsonCount(1, 'data');
        $this->postJson('/api/admin/reports/'.$report->id.'/post', $this->postData())->assertOk();
        $this->patchJson('/api/reports/'.$report->id, ['status' => 'rejected', 'severity' => 'high', 'department_id' => 'fire', 'note' => 'Review found this acceptance incorrect.'])->assertOk();
        $this->assertNull($report->fresh()->published_at);
        $this->getJson('/api/reports')->assertJsonCount(0, 'data');
        $this->getJson('/api/news')->assertJsonCount(0, 'data');
        $this->get('/news/'.$report->id)->assertNotFound();
        $this->postJson('/api/admin/reports/'.$report->id.'/post', $this->postData())->assertUnprocessable();
        $this->authenticateUser($owner);
        $this->getJson('/api/profile')->assertJsonPath('verified_reports', 0);
    }

    public function test_category_achievements_count_only_own_accepted_reports_once_in_each_category(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->record($owner, status: 'verified', accepted: true);
        }
        $this->record($owner, 'flood', 'resolved', true);
        $this->record($owner, status: 'submitted');
        $this->record($owner, status: 'verified');
        $this->record($owner, status: 'rejected', accepted: true);
        $this->record($owner, status: 'verified', accepted: true)->delete();
        $otherReport = $this->record($other, status: 'verified', accepted: true);
        $otherReport->confirmations()->create(['user_id' => $owner->id]);
        $this->authenticateUser($owner);
        $profile = $this->getJson('/api/profile?user_id='.$other->id)->assertOk()->assertJsonPath('verified_reports', 6)->assertJsonCount(10, 'hazard_achievements')->json();
        $fire = collect($profile['hazard_achievements'])->first(fn ($track) => $track['category']['id'] === 'fire');
        $flood = collect($profile['hazard_achievements'])->first(fn ($track) => $track['category']['id'] === 'flood');
        $this->assertSame(5, $fire['verified_count']);
        $this->assertSame(15, $fire['next_threshold']);
        $this->assertSame([true, true, false], array_column($fire['levels'], 'earned'));
        $this->assertSame('Firewatch Scout', $fire['levels'][0]['name']);
        $this->assertSame(1, $flood['verified_count']);
        $this->assertSame([true, false, false], array_column($flood['levels'], 'earned'));
        $this->authenticateUser($other);
        $this->getJson('/api/profile')->assertJsonPath('verified_reports', 1);
    }

    public function test_my_reports_includes_every_own_status_and_never_another_members_submission(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $statuses = ['submitted', 'under_verification', ...HazardReport::ACCEPTED_STATUSES, 'rejected'];
        foreach ($statuses as $status) {
            $this->record($owner, status: $status, accepted: in_array($status, HazardReport::ACCEPTED_STATUSES, true));
        }
        $foreign = $this->record($other);
        $this->record($owner)->delete();
        $this->authenticateUser($owner);
        $items = $this->getJson('/api/reports/mine?user_id='.$other->id)->assertOk()->assertJsonCount(8, 'data')->assertDontSee($foreign->id)->json('data');
        $this->assertEqualsCanonicalizing($statuses, array_column($items, 'status'));
        foreach ($statuses as $status) {
            $this->getJson('/api/reports/mine?status='.$status)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', $status)->assertJsonPath('data.0.user_id', $owner->id);
        }
    }

    public function test_public_posts_use_reviewed_name_and_incident_time_snapshots_without_contact_or_evidence(): void
    {
        $name = '<b>Fixture Reporter</b>';
        $owner = User::factory()->create(['name' => $name]);
        $report = $this->record($owner, status: 'verified', accepted: true);
        $report->forceFill(['occurred_at' => '2026-11-09 03:45:00'])->save();
        $this->authenticateUser(User::factory()->create(['role' => 'admin']));
        $post = '/api/admin/reports/'.$report->id.'/post';
        $this->postJson($post, $this->postData())->assertOk()->assertJsonPath('data.public_reporter_name', null);
        $this->postJson($post, [...$this->postData(), 'include_reporter_name' => 'invalid'])->assertUnprocessable();
        $this->postJson($post, [...$this->postData(), 'include_reporter_name' => true, 'public_reporter_name' => 'Forged attribution', 'public_occurred_at' => '2000-01-01'])->assertOk()->assertJsonPath('data.public_reporter_name', $name);
        $this->assertTrue($report->fresh()->public_occurred_at->equalTo($report->occurred_at));
        $owner->update(['name' => 'Changed private account name']);
        $report->forceFill(['occurred_at' => '2026-11-08 01:00:00'])->save();
        $item = $this->getJson('/api/news?report='.$report->id)->assertOk()->assertJsonPath('data.0.report.public_reporter_name', $name)
            ->assertJsonMissingPath('data.0.report.reporter')->assertJsonMissingPath('data.0.report.user_id')->assertJsonMissingPath('data.0.report.occurred_at')
            ->assertDontSee($owner->email)->assertDontSee('PRIVATE_EXACT_ADDRESS')->assertDontSee('Changed private account name')->json('data.0.report');
        $this->assertStringStartsWith('2026-11-09', $item['public_occurred_at']);
        $this->get('/news/'.$report->id)->assertOk()->assertSee('&lt;b&gt;Fixture Reporter&lt;/b&gt;', false)->assertDontSee($name, false)
            ->assertSee('Incident details')->assertSee('Priority')->assertDontSee($owner->email)->assertDontSee('PRIVATE_RAW_DETAILS');
        $this->postJson($post, [...$this->postData(), 'include_reporter_name' => false])->assertOk();
        $this->getJson('/api/news?report='.$report->id)->assertJsonPath('data.0.report.public_reporter_name', null);
        $this->get('/news/'.$report->id)->assertSee('Community member (name withheld)')->assertDontSee('Fixture Reporter');
        $this->deleteJson($post)->assertOk();
        $this->assertNull($report->fresh()->public_reporter_name);
        $this->assertNull($report->fresh()->public_occurred_at);
        $this->get('/news/'.$report->id)->assertNotFound();
    }
}
