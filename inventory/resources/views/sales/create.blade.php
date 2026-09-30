@extends('layouts.app')
@section('content')
<h1>Record sale</h1>
<p>Stock is checked when you submit. Retry an unchanged cart to recover its original sale. If a price changes, review the displayed prices and choose “Use current prices” to start a new request.</p>
<noscript><div class="alert error">Enable JavaScript to select prices and edit the sale cart.</div></noscript>
<form id="sale-form" method="post" action="{{ route('sales.store') }}" class="panel stack">
    @csrf
    <input type="hidden" name="request_key" value="{{ $requestKey }}">
    <label>Customer (optional)
        <select name="customer_id">
            <option value="">Anonymous</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected($selectedCustomer === (string) $customer->id)>{{ $customer->full_name }}</option>
            @endforeach
        </select>
    </label>
    <div id="cart-rows" class="stack">
        @foreach($cartItems as $index => $item)
            @include('sales.cart-row', ['index' => $index, 'item' => $item])
        @endforeach
    </div>
    <div class="actions">
        <button type="button" id="add-cart-row" class="secondary">Add product</button>
        <button type="button" id="refresh-cart-prices" class="secondary">Use current prices</button>
        <button type="submit">Record sale</button>
    </div>
    <p id="cart-status" role="status" aria-live="polite"></p>
</form>
<template id="cart-row-template">
    @include('sales.cart-row', ['index' => '__INDEX__', 'item' => ['quantity' => 1]])
</template>
@endsection
