<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white dark:bg-[#0F172A] border border-gray-300 dark:border-slate-600 rounded-md font-semibold text-xs lg:text-sm leading-4 lg:leading-5 text-gray-700 dark:text-slate-300 uppercase tracking-widest lg:tracking-normal shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
