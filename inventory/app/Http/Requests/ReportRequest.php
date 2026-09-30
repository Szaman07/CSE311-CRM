<?php

namespace App\Http\Requests;

class ReportRequest extends QueryRequest
{
    protected function prepareForValidation(): void
    {
        $today = now(config('nexastock.display_timezone'));
        $this->merge([
            'start' => $this->input('start') ?? $today->copy()->subDays(29)->toDateString(),
            'end' => $this->input('end') ?? $today->toDateString(),
        ]);
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start'],
        ];
    }
}
