@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-slate-600 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm lg:text-base lg:leading-6 dark:bg-[#0F172A] dark:text-slate-200 dark:placeholder-slate-500']) }}>
