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
        if (!User::where('dni', $request->dni)->exists()) {
            return back()->withErrors([
                'dni' => 'El DNI no está registrado.',
            ])->onlyInput('dni');
        }

        if (!Auth::attempt(['dni' => $request->dni, 'password' => $request->password], $request->boolean('remember'))) {
            return back()->withErrors([
                'password' => 'La contraseña es incorrecta.',
            ])->onlyInput('dni');
        }

        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
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
