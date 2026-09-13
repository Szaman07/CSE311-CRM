<?php

namespace App\Http\Controllers;

use App\Support\Canonical;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): Response
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $ok = Auth::attempt(['email' => Canonical::email($data['email']), 'password' => $data['password'], 'is_active' => 1], false);
        if (! $ok) {
            return $request->expectsJson()
                ? response()->json(['error' => ['code' => 'invalid_credentials', 'message' => 'The supplied credentials are invalid.', 'request_id' => $request->attributes->get('request_id')]], 422)
                : back()->withInput($request->only('email'))->withErrors(['email' => 'The supplied credentials are invalid.']);
        }
        $request->session()->regenerate();
        $request->session()->put(['auth_started_at' => now()->timestamp, 'auth_last_seen_at' => now()->timestamp]);

        return $request->expectsJson()
            ? response()->json(['data' => ['user' => ['id' => (string) $request->user()->id, 'name' => $request->user()->name, 'email' => $request->user()->email, 'role' => $request->user()->role], 'csrf_token' => $request->session()->token()]])
            : redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson() ? response()->json(null, 204) : redirect()->route('login');
    }

    public function current(Request $request): Response
    {
        $user = $request->user();

        return response()->json(['data' => ['user' => ['id' => (string) $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role], 'csrf_token' => $request->session()->token()]]);
    }
}
