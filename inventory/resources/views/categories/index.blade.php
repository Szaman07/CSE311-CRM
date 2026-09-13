@extends('layouts.app')
@section('content')
<h1>Categories</h1>
@if(auth()->user()->isManager())
<form method="post" action="{{ route('categories.store') }}" class="inline-form panel">@csrf<label>New category<input name="name" required maxlength="80"></label><button>Add</button></form>
@endif
<div class="table-wrap"><table><thead><tr><th>Name</th><th>Products</th><th>State</th><th>Version</th><th>Actions</th></tr></thead><tbody>
@forelse($categories as $category)
<tr><td>@if(auth()->user()->isManager())<form method="post" action="{{ route('categories.update',$category) }}" class="inline-form">@csrf @method('PATCH')<input name="name" value="{{ $category->name }}" maxlength="80" required><input type="hidden" name="expected_version" value="{{ $category->version }}"><button>Save</button></form>@else{{ $category->name }}@endif</td><td>{{ $category->products_count }}</td><td>{{ $category->archived_at ? 'Archived' : 'Active' }}</td><td>{{ $category->version }}</td><td>
@if(auth()->user()->isManager())<form method="post" action="{{ $category->archived_at ? route('categories.restore',$category) : route('categories.archive',$category) }}">@csrf<input type="hidden" name="expected_version" value="{{ $category->version }}"><button class="secondary">{{ $category->archived_at ? 'Restore' : 'Archive' }}</button></form>@endif
</td></tr>
@empty<tr><td colspan="5">No categories yet.</td></tr>@endforelse
</tbody></table></div>{{ $categories->links() }}
@endsection
