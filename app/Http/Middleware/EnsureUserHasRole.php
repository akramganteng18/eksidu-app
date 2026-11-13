<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Pembatas akses berbasis role (multi-role authorization), dicek di SERVER.
     * Alias 'role' didaftarkan di app/Http/Kernel.php.
     * Pemakaian di route: ->middleware('role:admin') atau ->middleware('role:admin,siswa')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
