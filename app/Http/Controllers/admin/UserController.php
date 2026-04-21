<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function users(Request $request): Response
    {
        $users = User::query()
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Administrator');
            })
            ->when($request->department, function ($q) use ($request) {
                $q->whereHas('department', fn ($d) =>
                    $d->where('name', $request->department)
                );
            })
            ->when(
                in_array($request->user_type, ['faculty', 'student']),
                fn ($q) =>
                $q->where(
                    'user_type',
                    $request->user_type === 'faculty' ? 1 : 2
                )
            )
            ->with(['department', 'roles:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($user) {
                $originalUserType = (int) $user->user_type;

                $user->user_type = match ($originalUserType) {
                    1 => 'faculty',
                    2 => 'student',
                    default => null,
                };

                $user->is_active = (bool) ($user->is_active ?? true);

                if ($originalUserType === 1) {
                    $user->status = $user->is_active ? 'Active' : 'Inactive';
                } else {
                    $user->status = $user->status ?: 'Active';
                }

                return $user;
            });

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'departments' => Department::orderBy('name')->pluck('name'),
            'filters' => $request->only(['department', 'user_type']),
            'feedbackMessage' => session('message'),
        ]);
    }

    public function deactivatePage(Request $request): Response
    {
        $users = User::query()
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Administrator');
            })
            ->where('user_type', 1) // faculty only
            ->when($request->department, function ($q) use ($request) {
                $q->whereHas('department', fn ($d) =>
                    $d->where('name', $request->department)
                );
            })
            ->with(['department', 'roles:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($user) {
                $user->user_type = 'faculty';
                $user->is_active = (bool) ($user->is_active ?? true);
                $user->status = $user->is_active ? 'Active' : 'Inactive';

                return $user;
            });

        return Inertia::render('Admin/DeactivateUser', [
            'users' => $users,
            'departments' => Department::orderBy('name')->pluck('name'),
            'filters' => [
                'department' => $request->department,
                'user_type' => 'faculty',
            ],
            'feedbackMessage' => session('message'),
        ]);
    }

    public function toggleActive(User $user): RedirectResponse
    {
        if ((int) $user->user_type !== 1) {
            abort(403, 'Only faculty accounts can be activated or deactivated.');
        }

        $newIsActive = !$user->is_active;

        $user->update([
            'is_active' => $newIsActive,
            'status' => $newIsActive ? 'Active' : 'Inactive',
            'deactivated_at' => $newIsActive ? null : now(),
        ]);

        return back()->with([
            'message' => $newIsActive
                ? 'Faculty account has been reactivated.'
                : 'Faculty account has been deactivated.',
        ]);
    }

    public function deactivate(User $user): RedirectResponse
    {
        if ((int) $user->user_type !== 1) {
            abort(403, 'Only faculty accounts can be deactivated.');
        }

        $user->update([
            'is_active' => false,
            'status' => 'Inactive',
            'deactivated_at' => now(),
        ]);

        return back()->with([
            'message' => 'Faculty account has been deactivated.',
        ]);
    }

    public function reactivate(User $user): RedirectResponse
    {
        if ((int) $user->user_type !== 1) {
            abort(403, 'Only faculty accounts can be reactivated.');
        }

        $user->update([
            'is_active' => true,
            'status' => 'Active',
            'deactivated_at' => null,
        ]);

        return back()->with([
            'message' => 'Faculty account has been reactivated.',
        ]);
    }

    private function getUsersPageData(Request $request): array
    {
        $users = User::query()
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Administrator');
            })
            ->when($request->department, function ($q) use ($request) {
                $q->whereHas('department', fn ($d) =>
                    $d->where('name', $request->department)
                );
            })
            ->when(
                in_array($request->user_type, ['faculty', 'student']),
                fn ($q) =>
                $q->where(
                    'user_type',
                    $request->user_type === 'faculty' ? 1 : 2
                )
            )
            ->with(['department', 'roles:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($user) {
                $originalUserType = (int) $user->user_type;

                $user->user_type = match ($originalUserType) {
                    1 => 'faculty',
                    2 => 'student',
                    default => null,
                };

                $user->is_active = (bool) ($user->is_active ?? true);

                if ($originalUserType === 1) {
                    $user->status = $user->is_active ? 'Active' : 'Inactive';
                } else {
                    $user->status = $user->status ?: 'Active';
                }

                return $user;
            });

        return [
            'users' => $users,
            'departments' => Department::orderBy('name')->pluck('name'),
            'filters' => $request->only(['department', 'user_type']),
            'feedbackMessage' => session('message'),
        ];
    }
}