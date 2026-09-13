<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Canonical;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    protected $signature = 'user:password {email}';

    protected $description = 'Reset a staff password without exposing it in shell history';

    public function handle(): int
    {
        $user = User::where('email', Canonical::email($this->argument('email')))->first();
        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }
        $password = $this->secret('New password (minimum 12 characters)');
        $confirm = $this->secret('Confirm password');
        if (! is_string($password) || strlen($password) < 12 || $password !== $confirm) {
            $this->error('Passwords do not match or are too short.');

            return self::FAILURE;
        }
        $user->password = Hash::make($password);
        $user->save();
        $this->info("Password reset for user #{$user->id}.");

        return self::SUCCESS;
    }
}
