<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            ? $this->user()?->can('update', $product) === true
            : $this->user()?->can('create', Product::class) === true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'min:1', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'unit_price' => ['required', 'string', 'regex:/^(0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?$/'],
            'reorder_level' => ['required', 'integer', 'between:0,1000000000'],
            'expected_version' => [$this->isMethod('post') ? 'nullable' : 'required', 'integer', 'min:1'],
        ];
    }
}
