<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('app:create-user {--first-name= : First name of the user} {--middle-name= : Middle name of the user (optional)} {--last-name= : Last name of the user} {--second-last-name= : Second last name of the user (optional)} {--phone-number= : Phone number of the user} {--email= : Email address of the user} {--password= : Password for the user} {--role= : Role (admin or warehouse_assistant)}')]
#[Description('Create a new user in the system securely')]
class CreateUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $firstName = $this->askRequired('Enter First Name', 'first-name', $this->option('first-name'));
            $middleName = $this->option('middle-name');
            $lastName = $this->askRequired('Enter Last Name', 'last-name', $this->option('last-name'));
            $secondLastName = $this->option('second-last-name');
            $phoneNumber = $this->askRequired('Enter Phone Number', 'phone-number', $this->option('phone-number'));
            $email = $this->askEmail($this->option('email'));
            $password = $this->askPassword($this->option('password'));
            $role = $this->askRole($this->option('role'));

            $user = User::create([
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'second_last_name' => $secondLastName,
                'phone_number' => $phoneNumber,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => $role,
            ]);

            $this->info('User created successfully!');
            $this->table(
                ['ID', 'Name', 'Email', 'Role', 'Phone Number'],
                [[
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role,
                    $user->phone_number,
                ]]
            );

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function askRequired(string $question, string $name, ?string $optionValue): string
    {
        if (!empty($optionValue)) {
            return $optionValue;
        }

        if (!$this->input->isInteractive()) {
            throw new \RuntimeException("The --{$name} option is required in non-interactive mode.");
        }

        do {
            $value = $this->ask($question);
            if (empty($value)) {
                $this->error(ucfirst(str_replace('-', ' ', $name)) . ' is required.');
            }
        } while (empty($value));

        return $value;
    }

    private function askEmail(?string $optionValue): string
    {
        if (!empty($optionValue)) {
            if (!filter_var($optionValue, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException("The provided email '{$optionValue}' is invalid.");
            }
            if (User::where('email', $optionValue)->exists()) {
                throw new \RuntimeException("A user with email '{$optionValue}' already exists.");
            }
            return $optionValue;
        }

        if (!$this->input->isInteractive()) {
            throw new \RuntimeException("The --email option is required in non-interactive mode.");
        }

        do {
            $email = $this->ask('Enter Email');
            if (empty($email)) {
                $this->error('Email is required.');
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error('Please enter a valid email address.');
                $email = null;
                continue;
            }
            if (User::where('email', $email)->exists()) {
                $this->error('A user with this email already exists.');
                $email = null;
                continue;
            }
        } while (empty($email));

        return $email;
    }

    private function askPassword(?string $optionValue): string
    {
        if (!empty($optionValue)) {
            if (strlen($optionValue) < 8) {
                throw new \RuntimeException('The password must be at least 8 characters long.');
            }
            return $optionValue;
        }

        if (!$this->input->isInteractive()) {
            throw new \RuntimeException("The --password option is required in non-interactive mode.");
        }

        do {
            $password = $this->secret('Enter Password');
            if (empty($password)) {
                $this->error('Password is required.');
                continue;
            }
            if (strlen($password) < 8) {
                $this->error('Password must be at least 8 characters long.');
                $password = null;
                continue;
            }
            $confirmPassword = $this->secret('Confirm Password');
            if ($password !== $confirmPassword) {
                $this->error('Passwords do not match.');
                $password = null;
                continue;
            }
        } while (empty($password));

        return $password;
    }

    private function askRole(?string $optionValue): string
    {
        $allowedRoles = ['warehouse_assistant', 'admin'];

        if (!empty($optionValue)) {
            if (!in_array($optionValue, $allowedRoles, true)) {
                throw new \RuntimeException("Invalid role '{$optionValue}'. Allowed roles: " . implode(', ', $allowedRoles));
            }
            return $optionValue;
        }

        if (!$this->input->isInteractive()) {
            return 'warehouse_assistant';
        }

        return $this->choice('Select Role', $allowedRoles, 0);
    }
}
