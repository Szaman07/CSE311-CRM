<?php

namespace App\Http\Requests;

use App\Rules\WholeNumber;

class VersionRequest extends DomainRequest
{
    public function authorize(): bool
    {
        $resource = $this->route('category') ?? $this->route('product') ?? $this->route('customer');

        return $resource !== null && $this->user()?->can('archive', $resource) === true;
    }

    public function rules(): array
    {
        return ['expected_version' => ['required', new WholeNumber(1, PHP_INT_MAX)]];
    }
}
