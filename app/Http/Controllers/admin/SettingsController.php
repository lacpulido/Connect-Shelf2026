<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    protected function getAdmin(Request $request): User
    {
        $adminId = $request->session()->get('admin_user_id');

        if (!$adminId) {
            abort(403, 'Admin session not found.');
        }

        $admin = User::query()
            ->where('id', $adminId)
            ->where('user_type', 1)
            ->where('is_active', true)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Administrator');
            })
            ->first();

        if (!$admin) {
            $request->session()->forget('admin_user_id');
            abort(403, 'Authenticated administrator not found.');
        }

        return $admin;
    }

    public function index(Request $request): Response
    {
        $admin = $this->getAdmin($request);

        return Inertia::render('Admin/Settings', [
            'admin' => [
                'id' => $admin->id,
                'first_name' => $admin->first_name,
                'middle_name' => $admin->middle_name,
                'last_name' => $admin->last_name,
                'extension_name' => $admin->extension_name,
                'email' => $admin->email,
            ],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $admin = $this->getAdmin($request);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'extension_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($admin->id),
            ],
        ]);

        $admin->first_name = $validated['first_name'];
        $admin->middle_name = $validated['middle_name'] ?: null;
        $admin->last_name = $validated['last_name'];
        $admin->extension_name = $validated['extension_name'] ?: null;
        $admin->email = $validated['email'];

        $admin->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $admin = $this->getAdmin($request);

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        if (!Hash::check($validated['current_password'], $admin->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $admin->password = Hash::make($validated['password']);
        $admin->save();

        return back()->with('success', 'Password updated successfully.');
    }
}