<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class DomainRequest extends FormRequest
{
    public function after(): array
    {
        return [function (Validator $validator): void {
            $allowed = array_unique(array_map(fn ($field) => explode('.', $field)[0], array_keys($this->rules())));
            foreach (array_diff(array_keys($this->except(['_token', '_method'])), $allowed) as $field) {
                $validator->errors()->add($field, 'This field is not accepted.');
            }
        }];
    }
}
