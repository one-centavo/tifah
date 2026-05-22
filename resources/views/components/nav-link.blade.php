@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-blue-900 text-sm font-medium leading-5 text-blue-900 bg-slate-50 focus:outline-none focus:border-lime-500 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-slate-500 hover:text-blue-900 hover:border-blue-900 focus:outline-none focus:text-blue-900 focus:border-lime-500 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
