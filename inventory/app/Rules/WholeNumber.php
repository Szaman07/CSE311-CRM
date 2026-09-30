<?php

namespace App\Rules;

use App\Support\Canonical;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

final class WholeNumber implements ValidationRule
{
    public function __construct(private int $minimum, private int $maximum) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            Canonical::integer($value, $this->minimum, $this->maximum);
        } catch (InvalidArgumentException) {
            $fail("The :attribute field must be a whole number between {$this->minimum} and {$this->maximum}.");
        }
    }
}
