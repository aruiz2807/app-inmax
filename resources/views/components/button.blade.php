<x-ui.button variant="outline" color="zinc" size="xs" {{ $attributes->merge(['type' => 'submit']) }}>
    {{ $slot }}
</x-ui.button>
