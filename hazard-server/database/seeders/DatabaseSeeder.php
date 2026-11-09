<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Category;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([['engineering', 'Municipal Engineering'], ['mdrrmo', 'Disaster Risk Reduction Office'], ['fire', 'Fire & Emergency Response'], ['environment', 'Environment & Sanitation'], ['general', 'General Services']] as [$id,$name]) {
            Department::updateOrCreate(['id' => $id], ['name' => $name]);
        }
        foreach ([['accident', 'Road accident', 'mdrrmo'], ['tree', 'Fallen tree', 'engineering'], ['flood', 'Flooding', 'mdrrmo'], ['fire', 'Fire', 'fire'], ['road', 'Road damage', 'engineering'], ['vegetation', 'Overgrown vegetation', 'general'], ['streetlight', 'Broken streetlight', 'engineering'], ['electrical', 'Electrical hazard', 'engineering'], ['waste', 'Waste / obstruction', 'environment'], ['other', 'Other hazard', 'general']] as [$id,$name,$department]) {
            Category::updateOrCreate(['id' => $id], ['name' => $name, 'department_id' => $department]);
        }
    }
}
