<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Update last seen for authenticated user across guards
        $user = null;
        $table = null;

        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $table = 'users';
        } elseif (Auth::guard('client')->check()) {
            $user = Auth::guard('client')->user();
            $table = 'clients';
        } elseif (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();
            $table = 'admins';
        }

        if ($user && $table) {
            // Throttle to update at most once every 30 seconds to optimize DB queries
            $lastSeen = $user->last_seen_at;
            if (!$lastSeen || now()->diffInSeconds($lastSeen) >= 30) {
                DB::table($table)->where('id', $user->id)->update([
                    'last_seen_at' => now(),
                ]);
            }
        }

        return $response;
    }
}
