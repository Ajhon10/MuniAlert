<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HazardResource;
use App\Models\HazardReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class CommunityController extends Controller
{
    private const CONFIRMED = ['verified', 'assigned', 'responding', 'resolved', 'closed'];

    private function admin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }

    public function settings()
    {
        return DB::table('municipal_settings')->pluck('value', 'key')->map(fn ($value) => json_decode($value, true));
    }

    public function saveSettings(Request $request)
    {
        $this->admin($request);
        $data = $request->validate([
            'map.latitude' => 'required|numeric|between:-90,90', 'map.longitude' => 'required|numeric|between:-180,180', 'map.zoom' => 'required|numeric|between:3,19',
            'hotlines' => 'present|array|max:20', 'hotlines.*.name' => 'required|string|min:2|max:80', 'hotlines.*.number' => ['required', 'string', 'max:30', 'regex:/^[+0-9() -]+$/'],
        ]);
        foreach (['map', 'hotlines'] as $key) {
            DB::table('municipal_settings')->updateOrInsert(['key' => $key], ['value' => json_encode($data[$key]), 'updated_at' => now()]);
        }

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
        DB::table('badges')->where('id', $badge)->update([...$data, 'updated_at' => now()]);

        return DB::table('badges')->where('id', $badge)->first();
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $count = HazardReport::where('user_id', $user->id)->whereIn('status', self::CONFIRMED)->count();

        return ['user' => $user, 'verified_reports' => $count, 'badges' => DB::table('badges')->orderBy('threshold')->get()->map(fn ($badge) => [...(array) $badge, 'earned' => $count >= $badge->threshold])];
    }

    public function updateProfile(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)], 'current_password' => 'required_with:password|string', 'password' => 'nullable|string|min:12|confirmed']);
        $user = DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $changingPassword = ! empty($data['password']);
            if ($changingPassword && (! $user->has_password || ! Hash::check($data['current_password'] ?? '', $user->password))) {
                throw ValidationException::withMessages(['current_password' => 'Current password is incorrect.']);
            }
            $user->update(array_filter(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'] ?? null]));
            if ($changingPassword) {
                $currentToken = $request->user()->currentAccessToken();
                $user->tokens()->when($currentToken instanceof PersonalAccessToken,
                    fn ($query) => $query->whereKeyNot($currentToken->id))->delete();
                $user->forceFill(['remember_token' => Str::random(60)])->save();
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
        $reports = HazardReport::with(['category', 'department'])->withCount('confirmations')->whereIn('status', self::CONFIRMED)->whereNotNull('public_title')->whereNotNull('public_summary')->when($filter['report'] ?? null, fn ($query, $id) => $query->whereKey($id))->latest('verified_at')->paginate(20);
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
        abort_unless(in_array($report->status, self::CONFIRMED, true) && $report->public_title && $report->public_summary, 404);
    }

    private function citizen(Request $request): void
    {
        abort_unless($request->user()?->role === 'citizen', 403);
    }
}
