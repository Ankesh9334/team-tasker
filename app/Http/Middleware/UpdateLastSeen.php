<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $guards = [
            'web' => 'users',
            'client' => 'clients',
            'admin' => 'admins',
        ];

        foreach ($guards as $guard => $table) {
            if (! Auth::guard($guard)->check()) {
                continue;
            }

            $user = Auth::guard($guard)->user();

            // Skip update if the table or column does not exist.
            if (
                ! $user ||
                ! Schema::hasTable($table) ||
                ! Schema::hasColumn($table, 'last_seen_at')
            ) {
                break;
            }

            $lastSeen = $user->last_seen_at;

            // Update at most once every 30 seconds.
            if (
                ! $lastSeen ||
                now()->diffInSeconds($lastSeen) >= 30
            ) {
                DB::table($table)
                ->where('id', $user->id)
                ->update([
                    'last_seen_at' => now(),
                ]);
            }

            break;
        }

        return $response;
    }
}
