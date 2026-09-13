<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $resource = $this->route('category') ?? $this->route('product') ?? $this->route('customer');

        return $resource !== null && $this->user()?->can('archive', $resource) === true;
    }

    public function rules(): array
    {
        return ['expected_version' => ['required', 'integer', 'min:1']];
    }
}
