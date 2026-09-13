<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Customer;
use App\Queries\ReportQuery;
use App\Services\CustomerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::when($request->filled('q'), fn (Builder $q) => $q->where(fn (Builder $x) => $x->where('full_name', 'like', '%'.$request->string('q')->limit(100).'%')->orWhere('email', 'like', '%'.$request->string('q')->limit(100).'%')))
            ->orderBy('full_name')->orderBy('id')->paginate(min(100, max(1, (int) $request->input('per_page', 20))))->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function store(CustomerRequest $request, CustomerService $service): RedirectResponse
    {
        $service->create($request->user(), $request->validated());

        return back()->with('success', 'Customer created.');
    }

    public function show(Request $request, Customer $customer, ReportQuery $reports): View
    {
        return view('customers.show', ['customer' => $customer, 'sales' => $reports->customerHistory($customer, (int) $request->input('per_page', 20))]);
    }

    public function update(CustomerRequest $request, Customer $customer, CustomerService $service): RedirectResponse
    {
        $service->update($request->user(), $customer, $request->validated(), (int) $request->validated('expected_version'));

        return back()->with('success', 'Customer updated.');
    }

    public function archive(VersionRequest $request, Customer $customer, CustomerService $service): RedirectResponse
    {
        $service->setArchived($request->user(), $customer, true, (int) $request->validated('expected_version'));

        return back()->with('success', 'Customer archived.');
    }

    public function restore(VersionRequest $request, Customer $customer, CustomerService $service): RedirectResponse
    {
        $service->setArchived($request->user(), $customer, false, (int) $request->validated('expected_version'));

        return back()->with('success', 'Customer restored.');
    }
}
