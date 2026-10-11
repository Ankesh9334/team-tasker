<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        if (! \Auth::check()) {
            return redirect()->route('loginPage');
        }

        $user = \Auth::user();

        // Block clients from accessing ANY admin routes (except client portal)
        if (isset($user->role_id) && $user->role_id == 3 && $request->is('admin/*')) {
            abort(403, 'Unauthorized access to admin area.');
        }

        if (! $user->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
