<?php

use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the database successfully in development/testing environment', function () {
    expect(User::count())->toBe(0);

    $this->artisan('db:seed')
        ->assertExitCode(0);

    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});

it('skips database seeding when in production environment', function () {
    App::detectEnvironment(fn () => 'production');

    expect(User::count())->toBe(0);

    $this->artisan('db:seed', ['--force' => true])
        ->expectsOutputToContain('Skipping development seeders in production environment.')
        ->assertExitCode(0);

    expect(User::count())->toBe(0);
});
