<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Admin/LoginForm');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->with('roles')
            ->where('email', $validated['email'])
            ->where('user_type', 1)
            ->where('is_active', true)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Administrator');
            })
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return back()
                ->withErrors([
                    'email' => 'Invalid credentials or you are not authorized as administrator.',
                ])
                ->withInput();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->regenerate();

        $request->session()->put('admin_user_id', $user->id);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_user_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}