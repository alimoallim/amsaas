<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    protected $signature = 'amsaas:reset-password
                            {email : User email address}
                            {password : New plaintext password (min 6 chars)}';

    protected $description = 'Reset a user password in production (ops / first deploy).';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = (string) $this->argument('password');

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters.');

            return self::FAILURE;
        }

        $user = User::withoutGlobalScopes()
            ->where('email', $email)
            ->first();

        if (! $user) {
            $this->error("No user found for email: {$email}");
            $this->line('Production uses a separate database — create the company admin via /onboarding/company or reset an existing user.');

            return self::FAILURE;
        }

        $user->password = Hash::make($password);
        $user->is_active = true;
        $user->save();

        $this->info("Password updated for {$email} (role: {$user->role}).");

        return self::SUCCESS;
    }
}
