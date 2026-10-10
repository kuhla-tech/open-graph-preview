<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdmin extends Command
{
    protected $signature = 'opengraph:admin {--email= : Administrator email} {--name= : Administrator name} {--renew-setup : Renew the setup link for an account that has never set a password}';

    protected $description = 'Create an administrator and print a one-use password setup link (valid for 60 minutes)';

    public function handle(): int
    {
        $email = strtolower($this->option('email') ?: $this->ask('Administrator email'));
        if ($this->option('renew-setup')) {
            $user = User::where('email', $email)->where('is_admin', true)->where('password_setup_pending', true)->first();
            if (! $user) {
                $this->error('Only a pending administrator can receive a renewed setup link.');

                return self::FAILURE;
            }
        } else {
            $name = $this->option('name') ?: $this->ask('Administrator name');
            $validator = Validator::make(compact('email', 'name'), ['email' => 'required|email|max:255|unique:users,email', 'name' => 'required|string|max:100']);
            if ($validator->fails()) {
                $this->error($validator->errors()->first());

                return self::FAILURE;
            }
            $user = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make(Str::random(64))]);
            $user->forceFill(['is_admin' => true, 'password_setup_pending' => true])->save();
        }
        $token = Password::broker()->createToken($user);
        $this->info('Administrator created. Open this private, one-use link to choose a password:');
        $this->line(URL::temporarySignedRoute('admin.setup', now()->addMinutes(60), ['user' => $user->id, 'token' => $token]));

        return self::SUCCESS;
    }
}
