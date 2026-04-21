<?php

namespace App\Http\Controllers\DepartmentChair;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AssignFocalPersonController extends Controller
{
    private array $roles = [];

    public function __construct()
    {
        $this->roles = $this->resolveRoleIds();
    }

    public function index(): Response
    {
        $chair = $this->authorizedChair();

        abort_if(!$this->roles['focal'], 500, 'Focal role not found.');

        $users = $this->getDepartmentFaculty($chair);
        $hasExistingFocal = $this->departmentHasFocalPerson($chair);

        return Inertia::render('DepartmentChair/AssignFaculty', [
            'users'            => $users,
            'focalRoleId'      => $this->roles['focal'],
            'hasExistingFocal' => $hasExistingFocal,
        ]);
    }
    public function toggleFocal(User $user): RedirectResponse
    {
        $chair = $this->authorizedChair();

        abort_if(!$this->roles['focal'], 500, 'Focal role not found.');
        $this->validateTargetUser($user, $chair);

        DB::transaction(function () use ($user, $chair) {
            $user->loadMissing('roles:id,name');

            $user->roles->contains('id', $this->roles['focal'])
                ? $this->revokeFocalRole($user)
                : $this->assignFocalRole($user, $chair);
        });

        return back()->with('success', 'Focal person updated successfully.');
    }

    private function authorizedChair(): User
    {
        /** @var User $chair */
        $chair = Auth::user();

        abort_unless($chair?->department_id, 403, 'Department not found.');
        abort_unless($chair->hasRole('Department Chairperson'), 403, 'Unauthorized action.');

        return $chair;
    }
    private function validateTargetUser(User $user, User $chair): void
    {
        abort_if((int) $user->id === (int) $chair->id, 403, 'You cannot assign yourself.');

        abort_unless(
            (int) $user->department_id === (int) $chair->department_id,
            403,
            'Unauthorized action.'
        );

        abort_unless((int) $user->user_type === 1, 403, 'Only faculty can be assigned.');

        $user->loadMissing('roles:id,name');

        $hasRestrictedRole = $user->roles->contains(
            fn($role) => in_array($role->id, array_filter([$this->roles['chair'], $this->roles['admin']]), true)
        );

        abort_if($hasRestrictedRole, 403, 'This user cannot be assigned as focal person.');
    }
    private function revokeFocalRole(User $user): void
    {
        $user->roles()->detach($this->roles['focal']);
    }
    private function assignFocalRole(User $user, User $chair): void
    {
        $this->clearExistingFocalPersons($chair);

        $user->roles()->syncWithoutDetaching([$this->roles['focal']]);

        Notification::create([
            'user_id' => $user->id,
            'title'   => 'Focal Person Assigned',
            'message' => 'You have been assigned as the Focal Person of your department.',
            'status'  => 'UNREAD',
        
        ]);
    }
    private function clearExistingFocalPersons(User $chair): void
    {
        User::query()
            ->where('id', '!=', $chair->id)
            ->where('department_id', $chair->department_id)
            ->whereHas('roles', fn($q) => $q->where('roles.id', $this->roles['focal']))
            ->lockForUpdate()
            ->get()
            ->each(fn(User $u) => $u->roles()->detach($this->roles['focal']));
    }

    private function getDepartmentFaculty(User $chair)
    {
        return User::query()
            ->where('id', '!=', $chair->id)
            ->where('user_type', 1)
            ->where('department_id', $chair->department_id)
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('roles.id', array_filter([
                $this->roles['chair'],
                $this->roles['admin'],
            ])))
            ->with(['roles:id,name'])
            ->select(['id', 'first_name', 'last_name', 'email', 'expertise', 'department_id', 'user_type'])
            ->paginate(15)
            ->withQueryString();
    }
    private function departmentHasFocalPerson(User $chair): bool
    {
        return User::query()
            ->where('id', '!=', $chair->id)
            ->where('department_id', $chair->department_id)
            ->whereHas('roles', fn($q) => $q->where('roles.id', $this->roles['focal']))
            ->exists();
    }
    private function resolveRoleIds(): array
    {
        $roles = Role::query()
            ->whereIn('name', ['Focal Person', 'DepartmentChairPerson', 'Administrator'])
            ->pluck('id', 'name');

        return [
            'focal' => $roles['Focal Person']       ?? null,
            'chair' => $roles['DepartmentChairPerson'] ?? null,
            'admin' => $roles['Administrator']       ?? null,
        ];
    }
}