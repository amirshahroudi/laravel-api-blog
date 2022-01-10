<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class APICheckUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return AuthenticationException
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next)
    {
//        if ($request->expectsJson()) {
//            dd('slam');
//        }
        if (auth()->check()) {
            if (auth()->user()->type == 'admin') {
                return $next($request);
            }
        }
        throw new AuthorizationException('Unauthenticated.');
    }
}
