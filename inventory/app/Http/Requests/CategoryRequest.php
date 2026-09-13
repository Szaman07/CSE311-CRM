<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
        return ['name' => ['required', 'string', 'max:80']];
    }
}
