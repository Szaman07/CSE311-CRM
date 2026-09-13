<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('adjust', $this->route('product')) === true;
    }

    public function rules(): array
    {
        $adjustment = $this->routeIs('*.adjustments');

        return [
            'request_key' => ['required', 'uuid'],
            $adjustment ? 'quantity_delta' : 'quantity' => ['required', 'integer', $adjustment ? 'between:-1000000,1000000' : 'between:1,1000000', $adjustment ? 'not_in:0' : 'min:1'],
            'expected_version' => [$adjustment ? 'required' : 'nullable', 'integer', 'min:1'],
            'note' => ['required', 'string', 'max:500'],
        ];
    }
}
