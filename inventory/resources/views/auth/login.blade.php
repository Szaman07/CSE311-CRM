@extends('layouts.app')
@section('content')
<section class="auth-card">
    <h1>Sign in to CRM</h1>
    <p>Use a locally provisioned manager or sales clerk account.</p>
    <form method="post" action="{{ route('login.store') }}" class="stack">
        @csrf
        <label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"></label>
        <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
        <button type="submit">Sign in</button>
    </form>
</section>
@endsection
