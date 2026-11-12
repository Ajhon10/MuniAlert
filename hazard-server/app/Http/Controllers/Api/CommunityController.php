<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HazardResource;
use App\Models\HazardReport;
use App\Models\User;
use App\Services\HazardAchievements;
use App\Services\SecurityEvents;
use App\Services\SecuritySessions;
use App\Support\PasswordInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CommunityController extends Controller
{
    private const CONFIRMED = ['verified', 'assigned', 'responding', 'resolved', 'closed'];

    private function admin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }

    public function settings()
    {
        return DB::table('municipal_settings')->pluck('value', 'key')->map(function ($value, $key) {
            $decoded = json_decode($value, true);

            return $key === 'dashboard_background' ? ($decoded ? '/api/dashboard-background/'.$decoded['id'] : null) : $decoded;
        });
    }

    public function saveSettings(Request $request)
    {
        $this->admin($request);
        $data = $request->validate([
            'map.latitude' => 'required|numeric|between:-90,90', 'map.longitude' => 'required|numeric|between:-180,180', 'map.zoom' => 'required|numeric|between:3,19',
            'hotlines' => 'present|array|max:20', 'hotlines.*.name' => 'required|string|min:2|max:80', 'hotlines.*.number' => ['required', 'string', 'max:30', 'regex:/^[+0-9() -]+$/'],
            'hotlines.*' => 'array:name,number,service,area,address,latitude,longitude',
            'hotlines.*.service' => ['nullable', Rule::in(['police', 'fire', 'medical', 'rescue', 'other'])],
            'hotlines.*.area' => 'nullable|string|max:80', 'hotlines.*.address' => 'nullable|string|max:160',
            'hotlines.*.latitude' => 'nullable|required_with:hotlines.*.longitude|numeric|between:-90,90',
            'hotlines.*.longitude' => 'nullable|required_with:hotlines.*.latitude|numeric|between:-180,180',
        ]);
        DB::transaction(function () use ($data, $request) {
            foreach (['map', 'hotlines'] as $key) {
                DB::table('municipal_settings')->updateOrInsert(['key' => $key], ['value' => json_encode($data[$key]), 'updated_at' => now()]);
            }
            app(SecurityEvents::class)->record('municipal_settings_changed', $request->user(), $request);
        });

        return $this->settings();
    }

    public function badges()
    {
        return DB::table('badges')->orderBy('threshold')->get();
    }

    public function saveBadge(Request $request, int $badge)
    {
        $this->admin($request);
        abort_unless(DB::table('badges')->where('id', $badge)->exists(), 404);
        $data = $request->validate([
            'name' => 'required|string|min:2|max:80', 'description' => 'required|string|min:5|max:1000',
            'reward_type' => ['required', Rule::in(['none', 'cash', 'gift'])],
            'cash_amount' => 'nullable|required_if:reward_type,cash|numeric|min:0|max:1000000',
            'reward_description' => 'nullable|required_if:reward_type,gift|string|max:255',
        ]);
        if ($data['reward_type'] !== 'cash') {
            $data['cash_amount'] = null;
        }
        if ($data['reward_type'] !== 'gift') {
            $data['reward_description'] = null;
        }
        DB::transaction(function () use ($data, $badge, $request) {
            DB::table('badges')->where('id', $badge)->update([...$data, 'updated_at' => now()]);
            app(SecurityEvents::class)->record('badge_settings_changed', $request->user(), $request);
        });

        return DB::table('badges')->where('id', $badge)->first();
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $count = HazardReport::communityVisible()->where('user_id', $user->id)->count();

        return ['user' => $user, 'verified_reports' => $count, 'hazard_achievements' => app(HazardAchievements::class)->forUser($user), 'badges' => DB::table('badges')->orderBy('threshold')->get()->map(fn ($badge) => [...(array) $badge, 'earned' => $count >= $badge->threshold])];
    }

    public function updateProfile(Request $request)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)], 'current_password' => ['required_with:password', ...PasswordInput::rules()], 'password' => ['nullable', 'confirmed', ...PasswordInput::rules(true)]]);
        $user = DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $changingPassword = ! empty($data['password']);
            $changingEmail = $data['email'] !== $user->email;
            if ($changingPassword && (! $user->has_password || ! Hash::check($data['current_password'] ?? '', $user->password))) {
                throw ValidationException::withMessages(['current_password' => 'Current password is incorrect.']);
            }
            $user->update(array_filter(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'] ?? null]));
            if ($changingEmail) {
                $user->forceFill(['local_email_verified_at' => null, 'email_verified_at' => null])->save();
            }
            if ($changingPassword || $changingEmail) {
                app(SecuritySessions::class)->invalidate($user, $request);
                app(SecurityEvents::class)->record($changingPassword ? 'password_changed' : 'email_changed', $user, $request);
            }

            return $user;
        });
        $request->setUserResolver(fn () => $user);
        if (! empty($data['password']) && $request->hasSession()) {
            Auth::guard('web')->setRememberDuration(config('hazards.session_days') * 1440)->login($user, true);
            $request->session()->regenerate();
        }

        return $this->profile($request);
    }

    public function news(Request $request)
    {
        $filter = $request->validate(['report' => 'nullable|uuid']);
        $reports = HazardReport::with(['category', 'department'])->withCount('confirmations')->published()->when($filter['report'] ?? null, fn ($query, $id) => $query->whereKey($id))->latest('published_at')->paginate(20);
        $items = $reports->getCollection();
        $ids = $items->pluck('id');
        $reactionCounts = DB::table('news_reactions')->whereIn('hazard_report_id', $ids)->selectRaw('hazard_report_id, type, count(*) as total')->groupBy('hazard_report_id', 'type')->get()->groupBy('hazard_report_id');
        $commentCounts = DB::table('news_comments')->whereIn('hazard_report_id', $ids)->selectRaw('hazard_report_id, count(*) as total')->groupBy('hazard_report_id')->pluck('total', 'hazard_report_id');
        $mine = $request->user() ? DB::table('news_reactions')->where('user_id', $request->user()->id)->whereIn('hazard_report_id', $ids)->pluck('type', 'hazard_report_id') : collect();

        return ['data' => $items->map(fn ($report) => ['report' => (new HazardResource($report))->resolve($request), 'reactions' => $reactionCounts->get($report->id, collect())->pluck('total', 'type'), 'comments_count' => (int) ($commentCounts[$report->id] ?? 0), 'my_reaction' => $mine[$report->id] ?? null]), 'meta' => ['current_page' => $reports->currentPage(), 'last_page' => $reports->lastPage(), 'total' => $reports->total()]];
    }

    public function comments(Request $request, HazardReport $report)
    {
        $this->confirmed($report);
        $data = $request->validate(['after' => 'nullable|integer|min:0']);
        $rows = DB::table('news_comments')->join('users', 'users.id', '=', 'news_comments.user_id')->where('hazard_report_id', $report->id)
            ->when($data['after'] ?? null, fn ($query, $after) => $query->where('news_comments.id', '>', $after))
            ->orderBy('news_comments.id')->select('news_comments.id', 'news_comments.user_id', 'users.name', 'news_comments.body', 'news_comments.created_at')->limit(26)->get();
        $items = $rows->take(25)->values();

        return ['data' => $items, 'meta' => ['has_more' => $rows->count() > 25, 'next_cursor' => $items->last()?->id]];
    }

    public function comment(Request $request, HazardReport $report)
    {
        $this->citizen($request);
        $this->confirmed($report);
        $data = $request->validate(['body' => 'required|string|min:2|max:1000']);
        DB::table('news_comments')->insert(['hazard_report_id' => $report->id, 'user_id' => $request->user()->id, 'body' => $data['body'], 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['message' => 'Comment posted.'], 201);
    }

    public function deleteComment(Request $request, HazardReport $report, int $comment)
    {
        $row = DB::table('news_comments')->where('id', $comment)->where('hazard_report_id', $report->id)->first();
        abort_unless($row, 404);
        abort_unless($row->user_id === $request->user()->id || $request->user()->role === 'admin', 403);
        DB::table('news_comments')->where('id', $comment)->delete();

        return response()->noContent();
    }

    public function react(Request $request, HazardReport $report)
    {
        $this->citizen($request);
        $this->confirmed($report);
        $data = $request->validate(['type' => ['required', Rule::in(['helpful', 'support'])]]);
        $query = DB::table('news_reactions')->where('hazard_report_id', $report->id)->where('user_id', $request->user()->id);
        if ($query->value('type') === $data['type']) {
            $query->delete();
        } else {
            DB::table('news_reactions')->updateOrInsert(['hazard_report_id' => $report->id, 'user_id' => $request->user()->id], ['type' => $data['type'], 'updated_at' => now(), 'created_at' => now()]);
        }

        return ['ok' => true];
    }

    private function confirmed(HazardReport $report): void
    {
        abort_unless($report->isPublished(), 404);
    }

    private function citizen(Request $request): void
    {
        abort_unless($request->user()?->role === 'citizen', 403);
    }
}
