@extends('layouts.app')
@section('content')
<h1>Customers</h1>
<form method="get" class="filters"><label>Search<input name="q" value="{{ request('q') }}" maxlength="100"></label><button>Search</button></form>
<form method="post" action="{{ route('customers.store') }}" class="grid-form panel">@csrf<label>Name<input name="full_name" required maxlength="120"></label><label>Email<input type="email" name="email" maxlength="255"></label><label>Phone<input name="phone" maxlength="30"></label><button>Add customer</button></form>
<div class="table-wrap"><table><thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>State</th><th>Update</th></tr></thead><tbody>
@forelse($customers as $c)<tr><td colspan="5"><a href="{{ route('customers.show',$c) }}">View history</a><form method="post" action="{{ route('customers.update',$c) }}" class="row-form">@csrf @method('PATCH')<input type="hidden" name="expected_version" value="{{ $c->version }}"><input name="full_name" value="{{ $c->full_name }}" required><input type="email" name="email" value="{{ $c->email }}"><input name="phone" value="{{ $c->phone }}"><span>{{ $c->archived_at ? 'Archived' : 'Active' }}</span><button>Save</button></form>
@if(auth()->user()->isManager())<form method="post" action="{{ $c->archived_at ? route('customers.restore',$c) : route('customers.archive',$c) }}" class="inline-form">@csrf<input type="hidden" name="expected_version" value="{{ $c->version }}"><button class="secondary">{{ $c->archived_at ? 'Restore' : 'Archive' }}</button></form>@endif</td></tr>
@empty<tr><td colspan="5">No customers yet.</td></tr>@endforelse
</tbody></table></div>{{ $customers->links() }}
@endsection
