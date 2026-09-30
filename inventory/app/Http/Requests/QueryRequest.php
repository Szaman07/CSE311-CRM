<?php

namespace App\Http\Requests;

use App\Rules\WholeNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['sometimes', new WholeNumber(1, 1_000_000)],
            'per_page' => ['sometimes', new WholeNumber(1, 100)],
            'state' => ['sometimes', Rule::in(['active', 'archived', 'all'])],
            'sort' => ['sometimes', Rule::in(['name', 'sku', 'stock', 'price'])],
            'status' => ['nullable', Rule::in(['completed', 'cancelled'])],
            'limit' => ['sometimes', new WholeNumber(1, 100)],
        ];
    }
}
