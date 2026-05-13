<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentFacultyController extends Controller
{
    private array $roles = [];

    public function __construct()
    {
        $this->roles = $this->resolveRoleIds();
    }

    public function index(Request $request): Response
    {
        $focalPerson = $this->authorizedFocalPerson();

        $department = Department::query()
            ->select(['id', 'name'])
            ->findOrFail($focalPerson->department_id);

        $restrictedRoleIds = array_filter([
            $this->roles['chair'] ?? null,
            $this->roles['admin'] ?? null,
        ]);

        $faculty = User::query()
            ->select([
                'id',
                'first_name',
                'middle_name',
                'last_name',
                'extension_name',
                'email',
                'expertise',
                'college_id',
                'department_id',
                'user_type',
                'status',
                'adviser_is_visible',
            ])
            ->with(['roles:id,name'])
            ->withCount(['assignedProjects as projects_count'])
            ->where('user_type', 1)
            ->where('department_id', $focalPerson->department_id)
            ->whereDoesntHave('roles', function (Builder $query) use ($restrictedRoleIds) {
                $query->whereIn('roles.id', $restrictedRoleIds);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('focalperson/DepartmentFaculty', [
            'department' => $department,
            'faculty' => $faculty,
        ]);
    }

    public function show(User $faculty): RedirectResponse
    {
        $focalPerson = $this->authorizedFocalPerson();

        $this->validateTargetFaculty($faculty, $focalPerson);

        $faculty->forceFill([
            'adviser_is_visible' => true,
        ])->save();

        return back()->with('success', 'Faculty is now visible to students.');
    }

    public function hide(User $faculty): RedirectResponse
    {
        $focalPerson = $this->authorizedFocalPerson();

        $this->validateTargetFaculty($faculty, $focalPerson);

        $faculty->forceFill([
            'adviser_is_visible' => false,
        ])->save();

        return back()->with('success', 'Faculty is now hidden from students.');
    }

    private function authorizedFocalPerson(): User
    {
        $focalPerson = Auth::user();

        abort_unless($focalPerson, 403, 'Unauthorized action.');
        abort_unless($focalPerson->hasRole('Focal Person'), 403, 'Unauthorized action.');
        abort_unless($focalPerson->department_id, 403, 'Department not found.');

        return $focalPerson;
    }

    private function validateTargetFaculty(User $faculty, User $focalPerson): void
    {
        abort_unless(
            (int) $faculty->department_id === (int) $focalPerson->department_id,
            403,
            'This faculty does not belong to your department.'
        );

        abort_unless((int) $faculty->user_type === 1, 403, 'Only faculty can be updated.');

        $faculty->loadMissing('roles:id,name');

        $restrictedRoleIds = array_filter([
            $this->roles['chair'] ?? null,
            $this->roles['admin'] ?? null,
        ]);

        $hasRestrictedRole = $faculty->roles->contains(function ($role) use ($restrictedRoleIds) {
            return in_array((int) $role->id, array_map('intval', $restrictedRoleIds), true);
        });

        abort_if($hasRestrictedRole, 403, 'This user cannot be updated.');
    }

    private function resolveRoleIds(): array
    {
        $roles = Role::query()
            ->whereIn('name', [
                'Focal Person',
                'DepartmentChairPerson',
                'Administrator',
            ])
            ->pluck('id', 'name');

        return [
            'focal' => $roles['Focal Person'] ?? null,
            'chair' => $roles['DepartmentChairPerson'] ?? null,
            'admin' => $roles['Administrator'] ?? null,
        ];
    }
}