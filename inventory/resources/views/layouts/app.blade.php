<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CRM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<header class="topbar">
    <a class="brand" href="{{ route('dashboard') }}">CRM</a>
    @auth
        <nav aria-label="Primary">
            <a href="{{ route('products.index') }}">Catalog</a>
            <a href="{{ route('categories.index') }}">Categories</a>
            <a href="{{ route('customers.index') }}">Customers</a>
            <a href="{{ route('sales.index') }}">Sales</a>
            <a href="{{ route('reports.index') }}">Reports</a>
        </nav>
        <div class="user">{{ auth()->user()->name }} · {{ str_replace('_', ' ', auth()->user()->role) }}
            <form method="post" action="{{ route('logout') }}">@csrf<button class="link-button">Logout</button></form>
        </div>
    @endauth
</header>
<main class="container">
    @if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert error" role="alert"><strong>Please review:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
</body>
</html>
