<?php

namespace App\Http\Requests;

class CancelSaleRequest extends DomainRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cancel', $this->route('sale')) === true;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:500']];
    }
}
