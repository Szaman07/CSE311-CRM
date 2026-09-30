<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Rules\WholeNumber;

class CategoryRequest extends DomainRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category
            ? $this->user()?->can('update', $category) === true
            : $this->user()?->can('create', Category::class) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'expected_version' => [$this->isMethod('post') ? 'nullable' : 'required', new WholeNumber(1, PHP_INT_MAX)],
        ];
    }
}
