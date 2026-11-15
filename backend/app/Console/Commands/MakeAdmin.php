<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeAdmin extends Command
{
    protected $signature = 'tatlas:make-admin {email} {--name=Admin} {--password= : Leave empty to generate one}';

    protected $description = 'Create an admin account, or promote an existing user to admin';

    public function handle(): int
    {
        $email = mb_strtolower($this->argument('email'));
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $password = $this->option('password') ?: Str::password(16, symbols: false);
            $user = User::create(['name' => $this->option('name'), 'email' => $email, 'password' => $password]);
            $user->forceFill(['email_verified_at' => now()]);
            if (! $this->option('password')) {
                $this->warn("Generated password: {$password}");
            }
        }

        $user->forceFill(['role' => UserRole::Admin])->save();
        $this->info("{$email} is now an admin. Log in at ".url('/admin'));

        return self::SUCCESS;
    }
}
