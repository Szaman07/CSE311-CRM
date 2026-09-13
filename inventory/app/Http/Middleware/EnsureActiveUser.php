<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson() || $request->is('api/*')
                ? response()->json(['error' => ['code' => 'account_inactive', 'message' => 'The session is no longer active.', 'request_id' => $request->attributes->get('request_id')]], 403)
                : redirect()->route('login')->withErrors(['email' => 'The session is no longer active.']);
        }

        return $next($request);
    }
}
