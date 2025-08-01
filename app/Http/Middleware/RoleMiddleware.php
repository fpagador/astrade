<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure  $next
     * @param  string  $roles  Pipe-separated string of roles, e.g., "admin|editor"
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, $roles): Response
    {
        // Convert the roles string into an array (e.g., "admin|manager" => ['admin', 'manager'])
        $roles = is_array($roles) ? $roles : explode('|', $roles);

        // Get the currently authenticated user
        $user = auth()->user();

        // 1. Ensure the user is authenticated
        if (!$user) {
            abort(401, 'Not authenticated.');
        }

        // 2. Ensure the user has a role assigned
        if (!$user->role) {
            abort(403, 'No tienes un rol asignado.');
        }

        // 3. Check if the user's role is in the allowed list
        if (in_array($user->role->role_name, $roles)) {
            return $next($request); // Authorized
        }

        // 4. The user does not have permission
        abort(403, 'No tienes permisos para acceder.');
    }
}
