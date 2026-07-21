<button
    {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-slate-50 border border-slate-100 rounded-md font-semibold text-xs text-blue-900 uppercase tracking-widest shadow-sm hover:bg-blue-900 hover:text-white focus:outline-none focus:ring-2 focus:ring-lime-500 focus:ring-offset-2 focus:ring-offset-slate-50 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
