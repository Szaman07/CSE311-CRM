<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnforceSessionLifetime
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $now = now()->timestamp;
        $started = (int) $request->session()->get('auth_started_at', $now);
        $last = (int) $request->session()->get('auth_last_seen_at', $now);
        $idleSeconds = (int) config('nexastock.session_idle_minutes', 30) * 60;
        $absoluteSeconds = (int) config('nexastock.session_absolute_minutes', 480) * 60;

        if (($now - $last) > $idleSeconds || ($now - $started) > $absoluteSeconds) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson() || $request->is('api/*')
                ? response()->json(['error' => ['code' => 'session_expired', 'message' => 'Sign in again.', 'request_id' => $request->attributes->get('request_id')]], 401)
                : redirect()->route('login')->withErrors(['email' => 'Your session expired. Sign in again.']);
        }

        $request->session()->put('auth_started_at', $started);
        $request->session()->put('auth_last_seen_at', $now);

        return $next($request);
    }
}
