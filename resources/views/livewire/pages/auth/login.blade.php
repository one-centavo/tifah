<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4 text-slate-600 bg-slate-50 border border-slate-100 shadow-sm rounded px-4 py-3"
        :status="session('status')" />

    <form wire:submit="login">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-blue-900" />
            <x-text-input wire:model="form.email" id="email"
                class="block mt-1 w-full border-slate-100 shadow-sm bg-slate-50 focus:ring-lime-500 focus:border-lime-500"
                type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2 text-blue-900" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" class="text-blue-900" />

            <x-text-input wire:model="form.password" id="password"
                class="block mt-1 w-full border-slate-100 shadow-sm bg-slate-50 focus:ring-lime-500 focus:border-lime-500"
                type="password" name="password" required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2 text-blue-900" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox"
                    class="rounded bg-slate-50 border-slate-100 text-blue-900 shadow-sm focus:ring-lime-500"
                    name="remember">
                <span class="ms-2 text-sm text-slate-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-blue-900 hover:text-lime-500 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-500"
                    href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button
                class="ms-3 bg-blue-900 hover:bg-lime-500 border-slate-100 shadow-sm text-white cursor-pointer">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</div>
