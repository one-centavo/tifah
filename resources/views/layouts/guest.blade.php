<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-gray-900 antialiased">
    <div
        class="relative flex min-h-screen w-full items-center justify-center overflow-hidden bg-lime-50 bg-linear-to-br from-lime-200/50 via-transparent to-transparent">
        <div class="pointer-events-none absolute inset-0 z-0 select-none">
            <div class="absolute right-1/4 top-12 h-48 w-48 rotate-12 rounded-3xl bg-lime-500/20 blur-sm"></div>
            <div class="absolute -right-12 top-24 h-72 w-72 -rotate-6 rounded-3xl bg-lime-500/15"></div>
            <div class="absolute right-1/5 top-1/3 h-40 w-40 rotate-45 rounded-2xl bg-lime-500/25"></div>
            <div class="absolute bottom-1/4 right-1/3 h-56 w-56 -rotate-12 rounded-3xl bg-lime-500/20"></div>
            <div class="absolute -bottom-8 right-12 h-64 w-64 rotate-12 rounded-3xl bg-lime-500/10"></div>
        </div>
        <div
            class="relative z-10 w-full max-w-md rounded-2xl border border-slate-100 bg-white/90 p-8 shadow-sm backdrop-blur-md flex flex-col items-center">
            <div>
                <a href="/" wire:navigate>
                    <x-application-logo class="w-50 h-20 fill-current text-blue-900" />
                </a>
            </div>
            <div class="w-full mt-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>

</html>
