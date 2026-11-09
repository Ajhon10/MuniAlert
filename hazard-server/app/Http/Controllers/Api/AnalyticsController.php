<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HazardReport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate(['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from', 'group' => ['required', Rule::in(['week', 'month'])], 'category' => 'nullable|exists:categories,id']);
        $from = CarbonImmutable::parse($data['from'], 'Asia/Manila')->startOfDay();
        $to = CarbonImmutable::parse($data['to'], 'Asia/Manila')->endOfDay();
        if ($from->diffInDays($to) >= 366) {
            throw ValidationException::withMessages(['to' => 'Choose a range of no more than 366 calendar days.']);
        }
        $base = HazardReport::whereNotIn('status', ['rejected'])->whereRaw('COALESCE(occurred_at, created_at) BETWEEN ? AND ?', [$from->utc(), $to->utc()])
            ->when($data['category'] ?? null, fn ($query, $id) => $query->where('category_id', $id));
        // Aggregate in SQL, then regroup at most 366 daily rows. No paginated-page totals.
        $day = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', COALESCE(occurred_at, created_at), '+8 hours')"
            : 'DATE(DATE_ADD(COALESCE(occurred_at, created_at), INTERVAL 8 HOUR))';
        $daily = (clone $base)->selectRaw($day." AS day, COUNT(*) AS total, COUNT(occurred_at) AS known_time, SUM(CASE WHEN category_id = 'accident' THEN 1 ELSE 0 END) AS accidents")->groupByRaw($day)->orderBy('day')->get();
        $periods = [];
        for ($date = $from; $date->lte($to); $date = $date->addDay()) {
            $key = ($data['group'] === 'week' ? $date->startOfWeek() : $date->startOfMonth())->toDateString();
            $periods[$key] ??= ['period' => $key, 'total' => 0, 'accidents' => 0, 'known_time' => 0];
        }
        foreach ($daily as $row) {
            $date = CarbonImmutable::parse($row->day, 'Asia/Manila');
            $key = ($data['group'] === 'week' ? $date->startOfWeek() : $date->startOfMonth())->toDateString();
            foreach (['total', 'accidents', 'known_time'] as $field) {
                $periods[$key][$field] += (int) $row->$field;
            }
        }

        return ['from' => $data['from'], 'to' => $data['to'], 'group' => $data['group'], 'timezone' => 'Asia/Manila',
            'date_basis' => 'Incident time when supplied; submission time otherwise. Includes unverified reports; excludes rejected and deleted records.',
            'total' => (int) $daily->sum('total'), 'known_time' => (int) $daily->sum('known_time'), 'periods' => array_values($periods)];
    }
}
