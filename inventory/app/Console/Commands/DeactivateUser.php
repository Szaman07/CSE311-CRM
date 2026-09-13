<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Canonical;
use Illuminate\Console\Command;

class DeactivateUser extends Command
{
    protected $signature = 'user:deactivate {email}';

    protected $description = 'Deactivate a staff account without deleting its history';

    public function handle(): int
    {
        $user = User::where('email', Canonical::email($this->argument('email')))->first();
        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }
        $user->is_active = false;
        $user->save();
        $this->info("Deactivated user #{$user->id}.");

        return self::SUCCESS;
    }
}
