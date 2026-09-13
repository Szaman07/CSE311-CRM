<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Canonical;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProvisionUser extends Command
{
    protected $signature = 'user:provision {email} {--name=} {--role=sales_clerk}';

    protected $description = 'Provision a local staff user without exposing a password in shell history';

    public function handle(): int
    {
        $data = ['email' => Canonical::email($this->argument('email')), 'name' => $this->option('name') ?: $this->ask('Display name'), 'role' => $this->option('role')];
        $validator = Validator::make($data, ['email' => ['required', 'email', 'max:150', 'unique:users,email'], 'name' => ['required', 'string', 'max:100'], 'role' => ['required', Rule::in(['manager', 'sales_clerk'])]]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        $password = $this->secret('Password (minimum 12 characters)');
        $confirm = $this->secret('Confirm password');
        if (! is_string($password) || strlen($password) < 12 || $password !== $confirm) {
            $this->error('Passwords do not match or are too short.');

            return self::FAILURE;
        }
        $user = User::create([...$data, 'password' => Hash::make($password), 'is_active' => true]);
        $this->info("Provisioned user #{$user->id}.");

        return self::SUCCESS;
    }
}
