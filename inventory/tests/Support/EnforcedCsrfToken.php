<?php

namespace Tests\Support;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

final class EnforcedCsrfToken extends ValidateCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}
