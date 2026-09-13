@extends('layouts.app')
@section('content')
<h1>Record sale</h1><p>Stock is checked and locked when you submit. A displayed price change rejects the whole cart for review.</p>
<form id="sale-form" method="post" action="{{ route('sales.store') }}" class="panel stack">@csrf
<input type="hidden" name="request_key" value="{{ old('request_key',\Illuminate\Support\Str::uuid()) }}">
<label>Customer (optional)<select name="customer_id"><option value="">Anonymous</option>@foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->full_name }}</option>@endforeach</select></label>
<div id="cart-rows">
@for($i=0;$i<3;$i++)<div class="cart-row"><label>Product<select name="items[{{ $i }}][product_id]" class="product-choice"><option value="">Choose product</option>@foreach($products as $p)<option value="{{ $p->id }}" data-price="{{ $p->unit_price }}">{{ $p->name }} · {{ $p->stock_on_hand }} available · BDT {{ $p->unit_price }}</option>@endforeach</select></label><label>Quantity<input type="number" name="items[{{ $i }}][quantity]" min="1" max="1000000" value="1"></label><input type="hidden" name="items[{{ $i }}][expected_unit_price]" class="expected-price"></div>@endfor
</div><button type="submit">Commit sale</button></form>
<script>
document.querySelectorAll('.product-choice').forEach(select => select.addEventListener('change', () => {
  select.closest('.cart-row').querySelector('.expected-price').value = select.selectedOptions[0]?.dataset.price || '';
}));
document.getElementById('sale-form').addEventListener('submit', event => {
  document.querySelectorAll('.cart-row').forEach(row => {
    if (!row.querySelector('.product-choice').value) row.querySelectorAll('input,select').forEach(control => control.disabled = true);
  });
  event.submitter.disabled = true;
});
</script>
@endsection
