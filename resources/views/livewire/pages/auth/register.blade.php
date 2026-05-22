<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="bg-slate-50 border border-slate-100 shadow-sm rounded-lg p-8">
    <form wire:submit="register">
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" class="text-blue-900" />
            <x-text-input wire:model="name" id="name"
                class="block mt-1 w-full border-slate-100 focus:border-lime-500 focus:ring-lime-500" type="text"
                name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-lime-500" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" class="text-blue-900" />
            <x-text-input wire:model="email" id="email"
                class="block mt-1 w-full border-slate-100 focus:border-lime-500 focus:ring-lime-500" type="email"
                name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-lime-500" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" class="text-blue-900" />

            <x-text-input wire:model="password" id="password"
                class="block mt-1 w-full border-slate-100 focus:border-lime-500 focus:ring-lime-500" type="password"
                name="password" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2 text-lime-500" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-blue-900" />

            <x-text-input wire:model="password_confirmation" id="password_confirmation"
                class="block mt-1 w-full border-slate-100 focus:border-lime-500 focus:ring-lime-500" type="password"
                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-lime-500" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-blue-900 hover:text-lime-500 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-500"
                href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4 bg-blue-900 hover:bg-lime-500 border-slate-100 shadow-sm">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</div>
