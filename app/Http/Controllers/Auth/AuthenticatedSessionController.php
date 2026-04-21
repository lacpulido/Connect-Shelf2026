<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): Response
    {
        $download = $request->query('download');

        if ($this->isSafeRelativePath($download)) {
            $request->session()->put('auth.download_url', $download);
        }

        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
            'download' => $request->session()->get('auth.download_url'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $downloadUrl = $request->input('download') ?: $request->session()->pull('auth.download_url');

        if ($this->isSafeRelativePath($downloadUrl)) {
            return redirect()->to(
                route('dashboard', absolute: false) . '?' . http_build_query([
                    'download_ready' => 1,
                    'download_url'   => $downloadUrl,
                ])
            );
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function isSafeRelativePath(?string $path): bool
    {
        if (! is_string($path) || trim($path) === '') {
            return false;
        }

        return str_starts_with($path, '/')
            && ! str_starts_with($path, '//')
            && ! preg_match('/[\r\n]/', $path);
    }
}