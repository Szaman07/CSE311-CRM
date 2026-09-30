<?php

namespace App\Http\Requests;

use App\Rules\WholeNumber;

class StockChangeRequest extends DomainRequest
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
            $adjustment ? 'quantity_delta' : 'quantity' => ['required', new WholeNumber($adjustment ? -1_000_000 : 1, 1_000_000), 'not_in:0'],
            'expected_version' => [$adjustment ? 'required' : 'nullable', new WholeNumber(1, PHP_INT_MAX)],
            'note' => ['required', 'string', 'max:500'],
        ];
    }
}
