@extends('layouts.app')
@section('content')
<div class="heading"><div><h1>Receipt {{ $sale->receipt_number }}</h1><p>{{ $sale->created_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }} Asia/Dhaka · {{ $sale->customer?->full_name ?? 'Anonymous customer' }}</p></div><span class="badge">{{ $sale->status }}</span></div>
<div class="table-wrap"><table><thead><tr><th>SKU snapshot</th><th>Product snapshot</th><th>Quantity</th><th>Unit price</th></tr></thead><tbody>@foreach($sale->items as $item)<tr><td>{{ $item->product_sku }}</td><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>BDT {{ $item->unit_price }}</td></tr>@endforeach</tbody><tfoot><tr><th colspan="3">Recorded total</th><th>BDT {{ $total }}</th></tr></tfoot></table></div>
<p>Recorded by {{ $sale->creator->name }}. This receipt records a sale; it is not proof of payment.</p>
@if($sale->status==='cancelled')<div class="alert">Cancelled by {{ $sale->canceller->name }} at {{ $sale->cancelled_at->timezone(config('nexastock.display_timezone'))->format('Y-m-d H:i:s T') }}: {{ $sale->cancel_reason }}</div>
@elseif(auth()->user()->isManager())<form method="post" action="{{ route('sales.cancel',$sale) }}" class="inline-form panel">@csrf<label>Cancellation reason<input name="reason" required maxlength="500"></label><button class="danger">Cancel and restore all stock</button></form>@endif
@endsection
