<?php

namespace App\Services;

use App\Models\Category;
use App\Models\HazardReport;
use App\Models\User;

class HazardAchievements
{
    public function forUser(User $user): array
    {
        $counts = HazardReport::communityVisible()->where('user_id', $user->id)->selectRaw('category_id, count(*) as total')->groupBy('category_id')->pluck('total', 'category_id');

        return Category::orderBy('name')->get()->map(function ($category) use ($counts) {
            $count = (int) ($counts[$category->id] ?? 0);
            $thresholds = config('hazard_achievements.thresholds');
            $names = config('hazard_achievements.names.'.$category->id, config('hazard_achievements.names.other'));

            return ['category' => ['id' => $category->id, 'name' => $category->name], 'verified_count' => $count,
                'next_threshold' => collect($thresholds)->first(fn ($threshold) => $count < $threshold),
                'levels' => collect($thresholds)->map(fn ($threshold, $index) => ['id' => $category->id.'-'.$threshold, 'name' => $names[$index], 'threshold' => $threshold, 'earned' => $count >= $threshold])->all(),
            ];
        })->all();
    }
}
