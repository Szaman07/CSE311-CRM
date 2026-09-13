@extends('layouts.app')
@section('content')
<h1>{{ $customer->full_name }}</h1>
<p>{{ $customer->email ?? 'No email' }} · {{ $customer->phone ?? 'No phone' }} · {{ $customer->archived_at ? 'Archived' : 'Active' }}</p>
<h2>Linked sales history</h2>
<div class="table-wrap"><table><thead><tr><th>Receipt</th><th>Time (Asia/Dhaka)</th><th>Status</th><th>Recorded total</th></tr></thead><tbody>
@forelse($sales as $sale)<tr><td><a href="{{ route('sales.show',$sale->id) }}">NS-{{ $sale->id }}</a></td><td>{{ \Carbon\CarbonImmutable::parse($sale->created_at,'UTC')->timezone(config('nexastock.display_timezone'))->format('Y-m-d H:i:s T') }}</td><td>{{ $sale->status }}</td><td>BDT {{ $sale->total }}</td></tr>@empty<tr><td colspan="4">No linked sales. Anonymous sales never appear here.</td></tr>@endforelse
</tbody></table></div>{{ $sales->links() }}
@endsection
