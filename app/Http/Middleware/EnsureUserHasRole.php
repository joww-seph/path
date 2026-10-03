<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Allow the request through only when the user has one of the given roles.
     *
     * Usage: ->middleware('role:partner') or ->middleware('role:tourism_officer,admin')
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = array_map(fn (string $role) => Role::from($role), $roles);

        abort_unless($user !== null && $user->hasRole(...$allowed), 403);

        return $next($request);
    }
}
