<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (
            !Auth::guard('web')->validate([
                'email' => Auth::user()->email,
                'password' => $this->password,
            ])
        ) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-slate-600 bg-slate-50 border border-slate-100 shadow-sm rounded px-4 py-3">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form wire:submit="confirmPassword">
        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="text-blue-900" />

            <x-text-input wire:model="password" id="password"
                class="block mt-1 w-full border-slate-100 shadow-sm bg-slate-50 focus:ring-lime-500 focus:border-lime-500"
                type="password" name="password" required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2 text-blue-900" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button class="bg-blue-900 hover:bg-lime-500 border-slate-100 shadow-sm text-white">
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</div>
