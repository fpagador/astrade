<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Web\WebController;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use function redirect;
use function view;
use App\Models\User;

class AuthenticatedSessionController extends WebController
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('web.auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $user = User::with('role')->where('dni', $request->dni)->first();

        if (!$user) {
            return back()->withErrors([
                'dni' => 'El DNI no está registrado.',
            ])->onlyInput('dni');
        }

        if (!in_array($user->role->role_name, ['admin', 'manager'])) {
            return back()->withErrors([
                'dni' => 'No tienes permisos para acceder.',
            ])->onlyInput('dni');
        }

        if (!Auth::attempt(['dni' => $request->dni, 'password' => $request->password], $request->boolean('remember'))) {
            return back()->withErrors([
                'password' => 'La contraseña es incorrecta.',
            ])->onlyInput('dni');
        }

        $request->session()->regenerate();

        return redirect()->intended('/admin/dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
