@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="px-6 py-5">
        <x-ui.heading size="md">
            {{ $title }}
        </x-ui.heading>

        <x-ui.description class="mt-3">
            {{ $content }}
        </x-ui.description>
    </div>

    <div class="flex flex-row justify-end px-6 py-4 bg-neutral-50 dark:bg-neutral-900 text-end border-t border-black/10 dark:border-white/10">
        {{ $footer }}
    </div>
</x-modal>
