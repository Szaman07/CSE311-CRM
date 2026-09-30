<?php

namespace App\Http\Requests;

use App\Models\Sale;
use App\Rules\WholeNumber;

class RecordSaleRequest extends DomainRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Sale::class) === true;
    }

    public function rules(): array
    {
        return [
            'request_key' => ['required', 'uuid'],
            'customer_id' => ['nullable', new WholeNumber(1, PHP_INT_MAX), 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*' => ['required', 'array:product_id,quantity,expected_unit_price'],
            'items.*.product_id' => ['required', new WholeNumber(1, PHP_INT_MAX)],
            'items.*.quantity' => ['required', new WholeNumber(1, 1_000_000)],
            'items.*.expected_unit_price' => ['required', 'string', 'regex:/^(0|[1-9][0-9]{0,7})\.[0-9]{2}$/'],
        ];
    }
}
