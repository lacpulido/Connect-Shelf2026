<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DepartmentChairAssignedMail;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class AssignRoleController extends Controller
{
    public function index(Request $request)
    {
        $chairRole = Role::where('name', 'Department ChairPerson')->firstOrFail();

        $users = User::query()
            ->where('user_type', 1)
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Administrator');
            })
            ->when($request->department, function ($q) use ($request) {
                $q->whereHas('department', function ($d) use ($request) {
                    $d->where('name', $request->department);
                });
            })
            ->with(['department', 'roles:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($user) {
                $user->user_type = 'faculty';
                $user->is_active = (bool) $user->is_active;

                return $user;
            });

        $departmentsWithChair = User::query()
            ->whereHas('roles', function ($q) use ($chairRole) {
                $q->where('roles.id', $chairRole->id);
            })
            ->whereNotNull('department_id')
            ->pluck('department_id')
            ->unique()
            ->values();

        return Inertia::render('Admin/AssignRole', [
            'users' => $users,
            'departments' => Department::orderBy('name')->pluck('name'),
            'filters' => $request->only(['department']),
            'chairRoleId' => $chairRole->id,
            'departmentsWithChair' => $departmentsWithChair,
            'feedbackMessage' => session('message'),
        ]);
    }

    public function toggleChair(Request $request, User $user)
    {
        abort_if((int) $user->user_type !== 1, 403);

        $chairRole = Role::where('name', 'Department Chairperson')->firstOrFail();

        $user->load(['roles', 'department']);

        if ($user->roles->contains('id', $chairRole->id)) {
            $user->roles()->detach($chairRole->id);

            return back()->with([
                'message' => 'Chair role removed.',
            ]);
        }

        $request->validate([
            'department' => ['required', 'string', 'exists:departments,name'],
        ]);

        $assignedData = DB::transaction(function () use ($request, $user, $chairRole) {
            $department = Department::where('name', $request->department)
                ->lockForUpdate()
                ->firstOrFail();

            $existingChair = User::query()
                ->where('department_id', $department->id)
                ->whereHas('roles', function ($q) use ($chairRole) {
                    $q->where('roles.id', $chairRole->id);
                })
                ->where('id', '!=', $user->id)
                ->lockForUpdate()
                ->exists();

            if ($existingChair) {
                return [
                    'success' => false,
                    'message' => 'This department already has a Department Chair.',
                ];
            }

            $user->update([
                'department_id' => $department->id,
            ]);

            $user->roles()->syncWithoutDetaching([
                $chairRole->id,
            ]);

            $assignedUser = $user->fresh(['department', 'roles']);

            return [
                'success' => true,
                'user' => $assignedUser,
                'department' => $assignedUser->department,
                'message' => "Assigned {$assignedUser->first_name} {$assignedUser->last_name} as Department Chair of {$assignedUser->department->name}.",
            ];
        });

        if (! $assignedData['success']) {
            return back()->withErrors([
                'error' => $assignedData['message'],
            ]);
        }

        $assignedUser = $assignedData['user'];
        $department = $assignedData['department'];

      Mail::to($assignedUser->email)->send(
    new DepartmentChairAssignedMail($assignedUser, $department)
);

        return back()->with([
            'message' => $assignedData['message'],
        ]);
    }
}