<x-ui.button variant="outline" color="red" size="xs" {{ $attributes->merge(['type' => 'button']) }}>
    {{ $slot }}
</x-ui.button>
