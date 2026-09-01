@props(['on'])

<div x-data="{ shown: false, timeout: null }"
     x-init="@this.on('{{ $on }}', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, 2000); })"
     x-show.transition.out.opacity.duration.1500ms="shown"
     x-transition:leave.opacity.duration.1500ms
     style="display: none;"
    {{ $attributes->merge(['class' => 'text-sm lg:text-base lg:leading-6 text-gray-600 dark:text-leaf-200 dark:bg-leaf-300/15']) }}>
    {{ $slot->isEmpty() ? __('Saved.') : $slot }}
</div>
