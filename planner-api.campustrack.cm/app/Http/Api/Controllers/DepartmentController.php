<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of the departments.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Department::class);
        $query = Department::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('code', 'like', '%'.$request->search.'%');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $departments = $query->withCount('users')->with(['courses.teachers', 'courseClasses'])->paginate($request->per_page ?? 9);

        return $this->success(
            ['departments' => $departments],
            __('resources.departments.list'),
            200
        );
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Department::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
        ]);

        $department = Department::create($validated);

        return $this->success(
            ['department' => $department],
            __('resources.departments.created'),
            201
        );
    }

    /**
     * Display the specified department.
     */
    public function show(int $id): JsonResponse
    {
        $department = Department::withCount('users')->findOrFail($id);
        $this->authorize('view', $department);

        return $this->success(
            ['department' => $department],
            __('resources.departments.retrieved'),
            200
        );
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $department = Department::findOrFail($id);
        $this->authorize('update', $department);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:departments,code,'.$id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
        ]);

        $department->update($validated);

        return $this->success(
            ['department' => $department],
            __('resources.departments.updated'),
            200
        );
    }

    /**
     * Remove the specified department.
     */
    public function destroy(int $id): JsonResponse
    {
        $department = Department::findOrFail($id);
        $this->authorize('delete', $department);

        // Check if department has users
        if ($department->users()->count() > 0) {
            return $this->error(
                null,
                __('resources.departments.cannot_delete_with_users'),
                400
            );
        }

        $department->delete();

        return $this->success(null, __('resources.departments.deleted'), 200);
    }

    /**
     * Get users in department.
     */
    public function getUsers(int $id): JsonResponse
    {
        $department = Department::findOrFail($id);
        $this->authorize('view', $department);
        $users = $department->users()->with('roles')->paginate(15);

        return $this->success(
            ['users' => $users],
            __('resources.departments.users_retrieved'),
            200
        );
    }

    /**
     * Get department statistics.
     */
    public function getStats(int $id): JsonResponse
    {
        $department = Department::findOrFail($id);
        $this->authorize('view', $department);

        $stats = [
            'total_users' => $department->users()->count(),
            'users_by_role' => [],
        ];

        return $this->success(
            ['stats' => $stats],
            __('resources.departments.stats_retrieved'),
            200
        );
    }
}
