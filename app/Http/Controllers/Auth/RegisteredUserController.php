<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Models\College;
use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\NotificationService;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Register', [
            'colleges' => College::select('id', 'name')->get(),
            'departments' => Department::select('id', 'name', 'college_id')->get(),
        ]);
    }

    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'first_name'     => $validated['first_name'],
            'middle_name'    => $validated['middle_name'] ?? null,
            'last_name'      => $validated['last_name'],
            'extension_name' => $validated['extension_name'] ?? null,
            'expertise'      => $validated['expertise'] ?? null,
            'college_id'     => $validated['college_id'],
            'department_id'  => $validated['department_id'],
            'user_type'      => (int) $validated['user_type'],
            'email'          => $validated['email'],
            'password'       => Hash::make($validated['password']),
        ]);

        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Administrator');
        })->get();

        foreach ($admins as $admin) {
            NotificationService::send(
                $admin->id,
                'New User Registered',
                $user->first_name . ' ' . $user->last_name . ' has registered an account.',
                'User ID: ' . $user->id
            );
        }

        event(new Registered($user));
        Auth::login($user);
        

        return redirect()->route('login')->with('registered', true);
    }
}