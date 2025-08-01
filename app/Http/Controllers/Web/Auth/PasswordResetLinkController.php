<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Web\WebController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use function __;
use function back;
use function view;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends WebController
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('web.auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'dni' => 'required|string|exists:users,dni',
        ]);

        $user = User::where('dni', $request->dni)->first();

        if (!$user || !$user->email) {
            return back()->withErrors(['dni' => 'No se encontró un usuario con ese DNI o el usuario no tiene email asignado.']);
        }

        // Validar existencia y roles
        if (! $user || ! in_array($user->role->code, ['admin', 'manager'])) {
            throw ValidationException::withMessages([
                'dni' => ['No tienes permisos para acceder al panel de administración.'],
            ]);
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(['email' => $user->email]);

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('dni'))
                            ->withErrors(['dni' => __($status)]);
    }
}
