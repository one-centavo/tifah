@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-blue-900 text-start text-base font-medium text-blue-900 bg-slate-50 focus:outline-none focus:text-lime-500 focus:bg-blue-900 focus:border-lime-500 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-slate-500 hover:text-blue-900 hover:bg-slate-100 hover:border-blue-900 focus:outline-none focus:text-blue-900 focus:bg-slate-100 focus:border-lime-500 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
