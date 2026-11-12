<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepartmentController extends Controller
{
    public function index(Request $request): array
    {
        $this->admin($request);

        return ['departments' => Department::orderBy('name')->get(), 'categories' => Category::orderBy('name')->get()];
    }

    public function store(Request $request): Department
    {
        $this->admin($request);
        $data = $request->validate(['id' => ['required', 'string', 'max:40', 'regex:/^[a-z][a-z0-9-]*$/D', 'unique:departments,id'], 'name' => 'required|string|min:2|max:120']);

        return Department::create($data + ['is_active' => true]);
    }

    public function update(Request $request, Department $department): Department
    {
        $this->admin($request);
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'is_active' => 'required|boolean']);

        return DB::transaction(function () use ($department, $data) {
            $locked = Department::whereKey($department->id)->lockForUpdate()->firstOrFail();
            if (! $data['is_active'] && Category::where('department_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['is_active' => 'Reassign this office’s hazard categories before archiving it. Existing reports keep their assigned office.']);
            }
            $locked->update($data);

            return $locked;
        }, 3);
    }

    public function routeCategory(Request $request, Category $category): Category
    {
        $this->admin($request);
        $data = $request->validate(['department_id' => ['required', Rule::exists('departments', 'id')->where('is_active', true)]]);

        return DB::transaction(function () use ($category, $data) {
            Department::whereKey($data['department_id'])->where('is_active', true)->lockForUpdate()->firstOrFail();
            $category->update($data);

            return $category->fresh();
        }, 3);
    }

    private function admin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
