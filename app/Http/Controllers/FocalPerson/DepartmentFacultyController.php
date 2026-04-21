<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DepartmentFacultyController extends Controller
{
    public function index()
    {
        $focal = Auth::user();

        abort_unless($focal->department_id, 403, 'Department not found.');

        $roles = $this->getRoleIds();

        $faculties = User::query()
            ->where('user_type', 1)
            ->where('department_id', $focal->department_id)
            ->whereDoesntHave('roles', function ($query) use ($roles) {
                $query->whereIn('roles.id', [
                    $roles['chair'],
                    $roles['admin'],
                ]);
            })
            ->with([
                'department:id,name',
                'roles:id,name'
            ])
            ->paginate(12);

        return Inertia::render('focalperson/DepartmentFaculty', [
            'faculties' => $faculties
        ]);
    }

    private function getRoleIds(): array
    {
        $roles = Role::whereIn('name', [
            'Focal Person',
            'Department Chairperson',
            'Administrator',
        ])->pluck('id', 'name');

        return [
            'focal' => $roles['Focal Person'] ?? null,
            'chair' => $roles['Department Chairperson'] ?? null,
            'admin' => $roles['Administrator'] ?? null,
        ];
    }
}