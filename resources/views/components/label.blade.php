@props(['value'])

<label {{ $attributes->merge(['class' => 'text-sm text-start font-medium select-none text-neutral-800 dark:text-white']) }}>
    {{ $value ?? $slot }}
</label>
