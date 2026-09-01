@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm lg:text-base lg:leading-6 text-gray-700 dark:text-slate-300']) }}>
    {{ $value ?? $slot }}
</label>
