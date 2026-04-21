<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    /**
     * Display Departments Page
     */
    public function index(Request $request): Response
    {
        $departments = Department::with('college:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'college_name' => $department->college->name ?? 'N/A',
                    'total_users' => $department->users_count,
                    'created_at' => optional($department->created_at)->format('M d, Y'),
                ];
            });

        $colleges = College::select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Departments', [
            'departments' => $departments,
            'colleges' => $colleges,
            'defaultCollegeId' => $colleges->first()?->id,
            'flash' => [
                'success' => session('success'),
            ],
        ]);
    }

    /**
     * Store a New Department
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'college_id' => 'required|exists:colleges,id',
        ]);

        $exists = Department::where('name', $validated['name'])
            ->where('college_id', $validated['college_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'name' => 'Department already exists in the selected college.',
            ]);
        }

        Department::create($validated);

        return redirect()
            ->route('admin.departments')
            ->with('success', 'Department added successfully.');
    }
}