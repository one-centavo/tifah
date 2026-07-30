<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::redirect('/', 'login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    Volt::route('categories', 'categories.index')->name('categories.index');
    Volt::route('categories/create', 'categories.create')->name('categories.create');
    Volt::route('categories/{category}/edit', 'categories.edit')->name('categories.edit');

    Volt::route('laboratories', 'laboratories.index')->name('laboratories.index');
    Volt::route('laboratories/create', 'laboratories.create')->name('laboratories.create');
    Volt::route('laboratories/{laboratory}/edit', 'laboratories.edit')->name('laboratories.edit');

    Volt::route('sanitary-registries', 'sanitary-registries.index')->name('sanitary-registries.index');
    Volt::route('sanitary-registries/create', 'sanitary-registries.create')->name('sanitary-registries.create');
    Volt::route('sanitary-registries/{sanitary_registry}/edit', 'sanitary-registries.edit')->name('sanitary-registries.edit');
});

require __DIR__.'/auth.php';
