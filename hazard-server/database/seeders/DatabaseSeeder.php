<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use App\Support\CsvData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $departments = CsvData::read('departments.csv');
        $categories = CsvData::read('categories.csv');
        $badges = CsvData::read('badges.csv');
        $settings = CsvData::read('settings.csv');
        foreach ($departments as $row) {
            Validator::make($row, ['id' => 'required|alpha_dash|max:40', 'name' => 'required|string|max:120'])->validate();
        }
        foreach ($categories as $row) {
            Validator::make($row, ['id' => 'required|alpha_dash|max:40', 'name' => 'required|string|max:120', 'department_id' => ['required', Rule::in(array_column($departments, 'id'))]])->validate();
        }
        foreach ($badges as $row) {
            Validator::make($row, ['threshold' => 'required|integer|min:1', 'name' => 'required|string|max:80', 'color' => 'required|in:bronze,silver,gold,platinum', 'description' => 'required|string|max:1000', 'reward_type' => 'required|in:none'])->validate();
        }
        foreach ($settings as $row) {
            Validator::make($row, ['key' => 'required|in:map,hotlines,dashboard_background', 'value' => 'required|json'])->validate();
        }
        DB::transaction(function () use ($departments, $categories, $badges, $settings) {
            // Fill missing reference data; preserve administrator edits and routing.
            foreach ($departments as $row) {
                Department::firstOrCreate(['id' => $row['id']], $row);
            }
            foreach ($categories as $row) {
                Category::firstOrCreate(['id' => $row['id']], $row);
            }
            foreach ($badges as $row) {
                if (! DB::table('badges')->where('threshold', $row['threshold'])->exists()) {
                    DB::table('badges')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
                }
            }
            foreach ($settings as $row) {
                if (! DB::table('municipal_settings')->where('key', $row['key'])->exists()) {
                    DB::table('municipal_settings')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
                }
            }
            if (collect(['demo_admin_password', 'demo_citizen_one_password', 'demo_citizen_two_password'])->contains(fn ($key) => filled(config('hazards.'.$key)))) {
                $this->call(DemoAccountSeeder::class);
            }
        });
    }
}
