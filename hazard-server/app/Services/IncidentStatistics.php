<?php

namespace App\Services;

use App\Models\Category;
use App\Models\HazardReport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IncidentStatistics
{
    public function summarize(Request $request): array
    {
        $data = $request->validate([
            'from' => 'required|date_format:Y-m-d|after_or_equal:1900-01-01',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from|before_or_equal:2100-12-31',
            'group' => ['required', Rule::in(['day', 'week', 'month', 'year'])],
            'category' => 'nullable|exists:categories,id', 'basis' => ['sometimes', Rule::in(['submitted', 'incident'])],
        ]);
        $from = CarbonImmutable::parse($data['from'], 'Asia/Manila')->startOfDay();
        $to = CarbonImmutable::parse($data['to'], 'Asia/Manila')->endOfDay();
        $limit = $data['group'] === 'year' ? 20 * 366 : 366;
        if ($from->diffInDays($to) >= $limit || ($data['group'] === 'year' && $to->year - $from->year >= 20)) {
            throw ValidationException::withMessages(['to' => $data['group'] === 'year' ? 'Choose no more than 20 calendar years.' : 'Choose a range of no more than 366 calendar days.']);
        }
        $basis = $data['basis'] ?? 'incident';
        $timestamp = $basis === 'submitted' ? 'created_at' : 'COALESCE(occurred_at, created_at)';
        $base = HazardReport::whereNotIn('status', ['rejected'])->whereRaw($timestamp.' BETWEEN ? AND ?', [$from->utc(), $to->utc()])
            ->when($data['category'] ?? null, fn ($query, $id) => $query->where('category_id', $id));
        $format = $data['group'] === 'year' ? '%Y-01-01' : '%Y-%m-%d';
        $bucket = DB::getDriverName() === 'sqlite'
            ? "strftime('{$format}', {$timestamp}, '+8 hours')"
            : "DATE_FORMAT(DATE_ADD({$timestamp}, INTERVAL 8 HOUR), '{$format}')";
        $rows = $base->selectRaw($bucket.' AS bucket, category_id, COUNT(*) AS total, COUNT(occurred_at) AS known_time')
            ->groupByRaw($bucket.', category_id')->orderBy('bucket')->toBase()->get();
        $categories = Category::orderBy('name')->when($data['category'] ?? null, fn ($query, $id) => $query->where('id', $id))->get(['id', 'name']);
        $zero = array_fill_keys($categories->pluck('id')->all(), 0);
        $periods = [];
        $date = $from;
        while ($date->lte($to)) {
            $key = $this->start($date, $data['group'])->toDateString();
            $periods[$key] ??= ['period' => $key, 'total' => 0, 'accidents' => 0, 'known_time' => 0, 'categories' => $zero];
            $date = $data['group'] === 'year' ? $date->startOfYear()->addYear() : $date->addDay();
        }
        $totals = $zero;
        foreach ($rows as $row) {
            $key = $this->start(CarbonImmutable::parse($row->bucket, 'Asia/Manila'), $data['group'])->toDateString();
            $count = (int) $row->total;
            $periods[$key]['total'] += $count;
            $periods[$key]['known_time'] += (int) $row->known_time;
            $periods[$key]['categories'][$row->category_id] = $count + ($periods[$key]['categories'][$row->category_id] ?? 0);
            $periods[$key]['accidents'] += $row->category_id === 'accident' ? $count : 0;
            $totals[$row->category_id] = $count + ($totals[$row->category_id] ?? 0);
        }

        return ['from' => $data['from'], 'to' => $data['to'], 'group' => $data['group'], 'basis' => $basis, 'timezone' => 'Asia/Manila',
            'category' => $data['category'] ?? null,
            'date_basis' => ($basis === 'submitted' ? 'Submission time (PHT).' : 'Incident time when supplied; submission time otherwise.').' Includes unverified reports; excludes rejected and deleted records.',
            'total' => (int) $rows->sum('total'), 'known_time' => (int) $rows->sum('known_time'),
            'categories' => $categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name, 'total' => $totals[$category->id] ?? 0])->all(),
            'periods' => array_values($periods)];
    }

    private function start(CarbonImmutable $date, string $group): CarbonImmutable
    {
        return match ($group) {
            'week' => $date->startOfWeek(), 'month' => $date->startOfMonth(), 'year' => $date->startOfYear(), default => $date,
        };
    }
}
