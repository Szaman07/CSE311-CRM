<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Rules\WholeNumber;

class CustomerRequest extends DomainRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof Customer
            ? $this->user()?->can('update', $customer) === true
            : $this->user()?->can('create', Customer::class) === true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'expected_version' => [$this->isMethod('post') ? 'nullable' : 'required', new WholeNumber(1, PHP_INT_MAX)],
        ];
    }
}
