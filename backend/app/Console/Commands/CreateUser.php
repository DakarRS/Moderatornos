<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateUser extends Command
{
    protected $signature = 'user:create {--name=} {--username=} {--password=} {--role= : admin or editor}';

    protected $description = 'Create an account. The first account on a site always becomes an admin.';

    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?? text('Name', required: true),
            'username' => $this->option('username') ?? text('Username', required: true),
            'password' => $this->option('password') ?? password('Password', required: true),
        ];
        if ($this->option('role') !== null) {
            $data['role'] = $this->option('role');
        }

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'username' => User::usernameRules(),
            'password' => ['required', User::passwordRule()],
            'role' => [Rule::enum(UserRole::class)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create($data);
        $this->info("Created {$user->username} ({$user->role->value}). Log in at ".url('/admin/login'));

        return self::SUCCESS;
    }
}
