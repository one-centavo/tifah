@props(['status'])

@if ($status)
    <div
        {{ $attributes->merge(['class' => 'font-medium text-sm text-lime-500 bg-slate-50 border border-slate-100 shadow-sm px-3 py-2 rounded']) }}>
        {{ $status }}
    </div>
@endif
