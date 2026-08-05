<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a user with options successfully', function () {
    $this->artisan('app:create-user', [
        '--first-name' => 'John',
        '--last-name' => 'Doe',
        '--phone-number' => '1234567890',
        '--email' => 'john.doe@example.com',
        '--password' => 'secret123',
        '--role' => 'admin',
    ])
    ->assertExitCode(0);

    $this->assertDatabaseHas('users', [
        'email' => 'john.doe@example.com',
        'role' => 'admin',
    ]);
});

it('creates a user interactively successfully', function () {
    $this->artisan('app:create-user')
        ->expectsQuestion('Enter First Name', 'John')
        ->expectsQuestion('Enter Last Name', 'Doe')
        ->expectsQuestion('Enter Phone Number', '1234567890')
        ->expectsQuestion('Enter Email', 'john.doe.interactive@example.com')
        ->expectsQuestion('Enter Password', 'secret123')
        ->expectsQuestion('Confirm Password', 'secret123')
        ->expectsChoice('Select Role', 'warehouse_assistant', ['warehouse_assistant', 'admin'])
        ->assertExitCode(0);

    $this->assertDatabaseHas('users', [
        'email' => 'john.doe.interactive@example.com',
        'role' => 'warehouse_assistant',
    ]);
});

it('fails when email is invalid', function () {
    $this->artisan('app:create-user', [
        '--first-name' => 'John',
        '--last-name' => 'Doe',
        '--phone-number' => '1234567890',
        '--email' => 'invalid-email',
        '--password' => 'secret123',
        '--role' => 'admin',
    ])
    ->expectsOutputToContain('invalid')
    ->assertExitCode(1);
});

it('fails when email already exists', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->artisan('app:create-user', [
        '--first-name' => 'John',
        '--last-name' => 'Doe',
        '--phone-number' => '1234567890',
        '--email' => 'existing@example.com',
        '--password' => 'secret123',
        '--role' => 'admin',
    ])
    ->expectsOutputToContain('already exists')
    ->assertExitCode(1);
});
