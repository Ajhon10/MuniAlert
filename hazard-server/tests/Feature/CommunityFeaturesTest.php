<?php

namespace Tests\Feature;

use App\Models\HazardReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommunityFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
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

    private function report(User $owner, string $status): HazardReport
    {
        return HazardReport::create(['reference_number' => 'HZ-COM-'.uniqid(), 'user_id' => $owner->id, 'category_id' => 'tree', 'department_id' => 'engineering', 'title' => 'Confirmed tree hazard', 'public_title' => $status === 'verified' ? 'Confirmed tree hazard' : null, 'public_summary' => $status === 'verified' ? 'A fallen tree has been confirmed by the municipality.' : null, 'description' => 'A synthetic hazard report for API testing.', 'address' => 'Test Road', 'barangay' => 'Anambongan', 'selected_latitude' => 14.5995, 'selected_longitude' => 120.9842, 'gps_latitude' => 14.6, 'gps_longitude' => 120.98, 'location_source' => 'gps_adjusted', 'status' => $status, 'verified_at' => $status === 'verified' ? now() : null, 'fact_checked_at' => $status === 'verified' ? now() : null, 'published_at' => $status === 'verified' ? now() : null]);
    }

    public function test_only_admin_can_publish_settings_and_badge_rewards(): void
    {
        $settings = ['map' => ['latitude' => 15.2, 'longitude' => 121.3, 'zoom' => 11], 'hotlines' => [['name' => 'Municipal rescue', 'number' => '+63 123 456 7890']]];
        $this->authenticateUser($this->citizen());
        $this->putJson('/api/admin/settings', $settings)->assertForbidden();
        $this->patchJson('/api/admin/badges/1', ['name' => 'Bronze', 'description' => 'Test reward', 'reward_type' => 'cash', 'cash_amount' => 500])->assertForbidden();
        $this->authenticateUser($this->admin());
        $this->putJson('/api/admin/settings', $settings)->assertOk()->assertJsonPath('map.latitude', 15.2)->assertJsonPath('hotlines.0.name', 'Municipal rescue');
        $this->patchJson('/api/admin/badges/1', ['name' => 'Bronze responder', 'description' => 'Fifty verified reports.', 'reward_type' => 'cash', 'cash_amount' => 500])->assertOk()->assertJsonPath('threshold', 50)->assertJsonPath('reward_type', 'cash');
        $this->getJson('/api/badges')->assertOk()->assertJsonPath('0.threshold', 50);
    }

    public function test_news_only_exposes_confirmed_reports_and_scopes_reactions_comments(): void
    {
        $owner = $this->citizen();
        $pending = $this->report($owner, 'submitted');
        $verified = $this->report($owner, 'verified');
        $this->getJson('/api/news')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.report.id', $verified->id)->assertJsonMissingPath('data.0.report.gps_latitude')->assertJsonMissingPath('data.0.report.reporter');
        $this->getJson('/api/news/'.$pending->id.'/comments')->assertNotFound();
        $this->authenticateUser($this->citizen());
        $this->postJson('/api/news/'.$pending->id.'/reactions', ['type' => 'helpful'])->assertNotFound();
        $this->postJson('/api/news/'.$verified->id.'/reactions', ['type' => 'helpful'])->assertOk();
        $this->postJson('/api/news/'.$verified->id.'/reactions', ['type' => 'support'])->assertOk();
        $this->assertDatabaseCount('news_reactions', 1);
        $this->postJson('/api/news/'.$verified->id.'/comments', ['body' => 'Thank you for the update.'])->assertCreated();
        $this->getJson('/api/news/'.$verified->id.'/comments')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.has_more', false);
        $this->authenticateUser($this->admin());
        $this->postJson('/api/news/'.$verified->id.'/comments', ['body' => 'Admin comment'])->assertForbidden();
        $comment = DB::table('news_comments')->first();
        $this->deleteJson('/api/news/'.$verified->id.'/comments/'.$comment->id)->assertNoContent();
    }

    public function test_comments_are_paginated_without_losing_later_entries_or_crossing_report_boundaries(): void
    {
        $owner = $this->citizen();
        $report = $this->report($owner, 'verified');
        $other = $this->report($owner, 'verified');
        foreach (range(1, 108) as $number) {
            DB::table('news_comments')->insert(['hazard_report_id' => $report->id, 'user_id' => $owner->id, 'body' => 'Synthetic comment '.$number, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('news_comments')->insert(['hazard_report_id' => $other->id, 'user_id' => $owner->id, 'body' => 'Other report comment', 'created_at' => now(), 'updated_at' => now()]);
        $seen = [];
        $cursor = 0;
        do {
            $response = $this->getJson('/api/news/'.$report->id.'/comments?after='.$cursor)->assertOk();
            $this->assertLessThanOrEqual(25, count($response->json('data')));
            $seen = [...$seen, ...array_column($response->json('data'), 'id')];
            $cursor = $response->json('meta.next_cursor');
        } while ($response->json('meta.has_more'));
        $this->assertCount(108, array_unique($seen));
        $this->assertSame(108, count($seen));
        $this->getJson('/api/news/'.$report->id.'/comments?after=-1')->assertUnprocessable();
        $this->getJson('/api/news/'.$other->id.'/comments')->assertJsonCount(1, 'data')->assertJsonPath('data.0.body', 'Other report comment');
    }

    public function test_profile_badges_count_verified_reports_and_password_change_requires_current_password(): void
    {
        $user = $this->citizen();
        for ($i = 0; $i < 50; $i++) {
            $this->report($user, 'verified');
        }
        $this->report($user, 'rejected');
        $this->authenticateUser($user);
        $this->getJson('/api/profile')->assertOk()->assertJsonPath('verified_reports', 50)->assertJsonPath('badges.0.earned', true)->assertJsonPath('badges.1.earned', false);
        $this->patchJson('/api/profile', ['name' => 'Updated Citizen', 'email' => $user->email, 'password' => 'a-different-password', 'password_confirmation' => 'a-different-password', 'current_password' => 'wrong'])->assertUnprocessable();
        $this->patchJson('/api/profile', ['name' => 'Updated Citizen', 'email' => $user->email])->assertOk()->assertJsonPath('user.name', 'Updated Citizen');
    }

    public function test_only_owner_can_delete_unverified_report_and_soft_delete_removes_it_from_news_and_lists(): void
    {
        $owner = $this->citizen();
        $other = $this->citizen();
        $pending = $this->report($owner, 'submitted');
        $verified = $this->report($owner, 'verified');
        $this->authenticateUser($other);
        $this->deleteJson('/api/reports/'.$pending->id)->assertForbidden();
        $this->authenticateUser($owner);
        $this->deleteJson('/api/reports/'.$verified->id)->assertForbidden();
        $this->deleteJson('/api/reports/'.$pending->id)->assertNoContent();
        $this->getJson('/api/reports/'.$pending->id)->assertNotFound();
        $this->getJson('/api/reports/mine')->assertOk()->assertJsonCount(1, 'data');
        $this->assertSoftDeleted($pending);
        $this->assertDatabaseHas('audit_logs', ['hazard_report_id' => $pending->id, 'action' => 'deleted']);
    }

    public function test_public_browsing_does_not_exhaust_registration_limit(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $this->getJson('/api/settings')->assertOk();
        }
        $this->postJson('/api/auth/register', ['name' => 'New Citizen', 'email' => 'new-citizen@example.test', 'password' => 'a-long-safe-test-password'])->assertCreated();
    }
}
