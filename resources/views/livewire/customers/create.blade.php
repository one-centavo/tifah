<?php

declare(strict_types=1);

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Services\CustomerService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $nit = '';

    public string $dv = '';

    public string $name = '';

    public string $city = '';

    public string $address = '';

    public string $phone_number = '';

    public string $email = '';

    public bool $is_active = true;

    /**
     * Get the validation rules.
     */
    protected function rules(): array
    {
        return (new StoreCustomerRequest)->rules();
    }

    /**
     * Get the custom validation error messages.
     */
    protected function messages(): array
    {
        return (new StoreCustomerRequest)->messages();
    }

    /**
     * Reactively calculate DV on the backend side if needed.
     */
    public function updatedNit(string $value): void
    {
        $cleanNit = preg_replace('/[^\d]/', '', $value);
        if ($cleanNit === '') {
            $this->dv = '';

            return;
        }

        $weights = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        $sum = 0;
        $reversedNit = strrev($cleanNit);

        for ($i = 0; $i < strlen($reversedNit); $i++) {
            if ($i < count($weights)) {
                $sum += (int) $reversedNit[$i] * $weights[$i];
            }
        }

        $remainder = $sum % 11;

        if ($remainder > 1) {
            $this->dv = (string) (11 - $remainder);
        } else {
            $this->dv = (string) $remainder;
        }
    }

    /**
     * Save the new customer.
     */
    public function save(CustomerService $customerService): void
    {
        // Enforce DV calculation check before backend validation
        $this->updatedNit($this->nit);

        $validated = $this->validate();

        $customerService->create($validated);

        session()->flash('success', 'El cliente ha sido registrado con éxito.');

        $this->redirect(route('customers.index'), navigate: true);
    }
}; ?>

<div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header/Back Navigation -->
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('customers.index') }}" wire:navigate
            class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-blue-900 transition-colors gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"></path>
            </svg>
            <span>Volver a Clientes</span>
        </a>
    </div>

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-blue-900 dark:text-white">Nuevo Cliente</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
            Registra farmacias y clientes institucionales para organizar despachos y agilizar la facturación.
        </p>
    </div>

    <!-- Card Form -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-6 md:p-8"
        x-data="{
            nit: @entangled('nit'),
            dv: @entangled('dv'),
            calculateDv() {
                let clean = this.nit.replace(/[^\d]/g, '');
                if (!clean) {
                    this.dv = '';
                    return;
                }
                let weights = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
                let sum = 0;
                let reversed = clean.split('').reverse().join('');
                for (let i = 0; i < reversed.length; i++) {
                    if (i < weights.length) {
                        sum += parseInt(reversed[i]) * weights[i];
                    }
                }
                let remainder = sum % 11;
                if (remainder > 1) {
                    this.dv = String(11 - remainder);
                } else {
                    this.dv = String(remainder);
                }
            }
        }"
        x-init="$watch('nit', value => calculateDv())">
        <form wire:submit="save" class="space-y-6">

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-6">
                <!-- NIT -->
                <div class="sm:col-span-3">
                    <x-input-label for="nit" value="NIT (Número de Identificación Tributaria)" class="text-blue-900 font-semibold mb-1" />
                    <x-text-input wire:model.live="nit" id="nit" type="text" class="block w-full mt-1" placeholder="Ej. 900.123.456" required autofocus />
                    <x-input-error :messages="$errors->get('nit')" class="mt-2" />
                </div>

                <!-- DV (Verification Digit) -->
                <div>
                    <x-input-label for="dv" value="DV" class="text-blue-900 font-semibold mb-1" />
                    <input type="text" id="dv" x-model="dv" readonly disabled
                        class="border-slate-200 bg-slate-100 text-slate-500 font-bold rounded-md shadow-sm block w-full mt-1 p-2 text-center text-sm border focus:outline-none cursor-not-allowed">
                    <x-input-error :messages="$errors->get('dv')" class="mt-2" />
                </div>
            </div>

            <!-- Razón Social -->
            <div>
                <x-input-label for="name" value="Razón Social (Nombre Completo)" class="text-blue-900 font-semibold mb-1" />
                <x-text-input wire:model="name" id="name" type="text" class="block w-full mt-1" placeholder="Ej. Farmacia Cruz Verde Central S.A.S." required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Ciudad -->
                <div>
                    <x-input-label for="city" value="Ciudad" class="text-blue-900 font-semibold mb-1" />
                    <x-text-input wire:model="city" id="city" type="text" class="block w-full mt-1" placeholder="Ej. Bogotá" required />
                    <x-input-error :messages="$errors->get('city')" class="mt-2" />
                </div>

                <!-- Teléfono -->
                <div>
                    <x-input-label for="phone_number" value="Teléfono" class="text-blue-900 font-semibold mb-1" />
                    <x-text-input wire:model="phone_number" id="phone_number" type="text" class="block w-full mt-1" placeholder="Ej. 3151234567 o 6011234567" required />
                    <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Correo Electrónico -->
                <div>
                    <x-input-label for="email" value="Correo Electrónico" class="text-blue-900 font-semibold mb-1" />
                    <x-text-input wire:model="email" id="email" type="email" class="block w-full mt-1" placeholder="Ej. facturacion@cliente.com" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Dirección de Entrega -->
                <div>
                    <x-input-label for="address" value="Dirección de Entrega" class="text-blue-900 font-semibold mb-1" />
                    <x-text-input wire:model="address" id="address" type="text" class="block w-full mt-1" placeholder="Ej. Calle 100 # 15-30, Bodega 4" required />
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>
            </div>

            <!-- Estado (Active Toggle/Checkbox) -->
            <div class="bg-slate-50 border border-slate-100/50 rounded-xl p-4 md:p-6">
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input wire:model="is_active" id="is_active" type="checkbox" 
                            class="w-4 h-4 rounded bg-slate-100 border-slate-200 text-blue-900 shadow-sm focus:ring-lime-500">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_active" class="font-medium text-blue-900">Cliente Activo</label>
                        <p class="text-xs text-slate-600">Desactiva esta opción para marcar al cliente como "Inactivo" e impedir nuevas ventas o despachos sin eliminar su registro.</p>
                    </div>
                </div>
            </div>

            <!-- Submit buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-100">
                <a href="{{ route('customers.index') }}" wire:navigate class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors">
                    Cancelar
                </a>
                <x-primary-button class="bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-medium px-6 py-2.5 rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer">
                    {{ __('Registrar Cliente') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
