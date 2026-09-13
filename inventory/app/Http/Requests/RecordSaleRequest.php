<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;

class RecordSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Sale::class) === true;
    }

    public function rules(): array
    {
        return [
            'request_key' => ['required', 'uuid'],
            'customer_id' => ['nullable', 'integer', 'min:1', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'between:1,1000000'],
            'items.*.expected_unit_price' => ['required', 'string', 'regex:/^(0|[1-9][0-9]{0,7})\.[0-9]{2}$/'],
        ];
    }
}
