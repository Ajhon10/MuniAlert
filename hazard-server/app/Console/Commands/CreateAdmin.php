<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\PasswordInput;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'hazard:admin {email}';

    protected $description = 'Create a municipal administrator using a hidden password prompt';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        if (Validator::make(['email' => $email], ['email' => 'required|email|max:255'])->fails()) {
            $this->error('Enter a plain email address, for example: admin@example.test');

            return self::FAILURE;
        }
        if (User::where('email', $email)->exists()) {
            $this->error('An account with that email already exists. No changes made.');

            return self::FAILURE;
        }
        $name = $this->ask('Administrator name', 'Municipal Administrator');
        $password = $this->secret('Password (at least 12 characters)');
        $validator = Validator::make(compact('name', 'email', 'password'), ['name' => 'required|string|min:2|max:80', 'email' => 'required|email', 'password' => ['required', ...PasswordInput::rules(true)]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $user = new User(compact('name', 'email', 'password'));
        $user->role = 'admin';
        $user->mfa_enrollment_until = now()->addMinutes(15);
        $user->save();
        $this->info('Administrator created. Complete authenticator enrollment within 15 minutes.');

        return self::SUCCESS;
    }
}
