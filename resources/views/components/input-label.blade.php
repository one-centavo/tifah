@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-blue-900']) }}>
    {{ $value ?? $slot }}
</label>
