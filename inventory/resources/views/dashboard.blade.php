@extends('layouts.app')
@section('content')
<h1>Store dashboard</h1>
<div class="cards">
    <article><span>Active products</span><strong>{{ $activeProducts }}</strong></article>
    <article><span>Low stock</span><strong>{{ $lowStock }}</strong></article>
    <article><span>Completed sales</span><strong>{{ $completedSales }}</strong></article>
</div>
<div class="actions"><a class="button" href="{{ route('sales.create') }}">Record a sale</a><a class="button secondary" href="{{ route('reports.index') }}">Check reconciliation</a></div>
@endsection
