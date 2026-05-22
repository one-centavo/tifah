@props(['on'])

<div x-data="{ shown: false, timeout: null }" x-init="@this.on('{{ $on }}', () => { clearTimeout(timeout);
    shown = true;
    timeout = setTimeout(() => { shown = false }, 2000); })" x-show.transition.out.opacity.duration.1500ms="shown"
    x-transition:leave.opacity.duration.1500ms style="display: none;"
    {{ $attributes->merge(['class' => 'text-sm text-blue-900 bg-lime-500 border border-slate-100 shadow-sm font-medium px-3 py-2 rounded']) }}>
    {{ $slot->isEmpty() ? __('Guardado.') : $slot }}
</div>
