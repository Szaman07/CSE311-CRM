@extends('layouts.app')
@section('content')
<div class="heading"><h1>Sales</h1><a class="button" href="{{ route('sales.create') }}">Record sale</a></div>
<form class="filters"><label>Search receipt, customer, product or SKU<input name="q" value="{{ request('q') }}" maxlength="100"></label><label>Status<select name="status"><option value="">All</option><option value="completed" @selected(request('status')==='completed')>Completed</option><option value="cancelled" @selected(request('status')==='cancelled')>Cancelled</option></select></label><button>Apply</button></form>
<div class="table-wrap"><table><thead><tr><th>Receipt</th><th>Time (Asia/Dhaka)</th><th>Customer</th><th>Actor</th><th>Status</th><th>Total</th></tr></thead><tbody>
@forelse($sales as $sale)<tr><td><a href="{{ route('sales.show',$sale) }}">{{ $sale->receipt_number }}</a></td><td>{{ $sale->created_at->timezone(config('nexastock.display_timezone'))->format('Y-m-d H:i:s T') }}</td><td>{{ $sale->customer?->full_name ?? 'Anonymous' }}</td><td>{{ $sale->creator->name }}</td><td>{{ $sale->status }}</td><td>BDT {{ app(\App\Services\SaleService::class)->total($sale) }}</td></tr>@empty<tr><td colspan="6">No sales yet.</td></tr>@endforelse
</tbody></table></div>{{ $sales->links() }}
@endsection
