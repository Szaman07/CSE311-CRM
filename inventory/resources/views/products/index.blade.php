@extends('layouts.app')
@section('content')
<div class="heading"><h1>Product catalog</h1><a class="button" href="{{ route('sales.create') }}">Record sale</a></div>
<form method="get" class="filters"><label>Search<input name="q" value="{{ request('q') }}" maxlength="100"></label><label>State<select name="state"><option value="active">Active</option><option value="all" @selected(request('state')==='all')>All</option><option value="archived" @selected(request('state')==='archived')>Archived</option></select></label><label>Sort<select name="sort"><option value="name">Name</option><option value="sku">SKU</option><option value="stock">Stock</option><option value="price">Price</option></select></label><button>Apply</button></form>
@if(auth()->user()->isManager())
<details class="panel"><summary>Add product</summary><form method="post" action="{{ route('products.store') }}" class="grid-form">@csrf
<label>Category<select name="category_id" required>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></label>
<label>SKU<input name="sku" required maxlength="64"></label><label>Name<input name="name" required maxlength="150"></label>
<label>Unit price (BDT)<input name="unit_price" required inputmode="decimal" placeholder="10.00"></label><label>Reorder level<input type="number" name="reorder_level" min="0" max="1000000000" value="0" required></label><button>Add with zero stock</button></form></details>
@endif
<div class="table-wrap"><table><thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>State</th></tr></thead><tbody>
@forelse($products as $p)<tr><td>{{ $p->sku }}</td><td><a href="{{ route('products.show',$p) }}">{{ $p->name }}</a></td><td>{{ $p->category->name }}</td><td>BDT {{ $p->unit_price }}</td><td>{{ $p->stock_on_hand }} @if($p->stock_on_hand <= $p->reorder_level)<span class="badge">low</span>@endif</td><td>{{ $p->archived_at ? 'Archived' : 'Active' }}</td></tr>
@empty<tr><td colspan="6">No matching products.</td></tr>@endforelse
</tbody></table></div>{{ $products->links() }}
@endsection
