@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="bg-white dark:bg-neutral-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
        <div class="sm:flex sm:items-start">
            <div class="mx-auto shrink-0 flex items-center justify-center size-12 rounded-full bg-red-50 border border-red-200 dark:bg-red-500/10 dark:border-red-400/30 sm:mx-0 sm:size-10">
                <svg class="size-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>

            <div class="mt-3 text-center sm:mt-0 sm:ms-4 sm:text-start">
                <x-ui.heading size="md">
                    {{ $title }}
                </x-ui.heading>

                <x-ui.description class="mt-3">
                    {{ $content }}
                </x-ui.description>
            </div>
        </div>
    </div>

    <div class="flex flex-row justify-end px-6 py-4 bg-neutral-50 dark:bg-neutral-900 text-end border-t border-black/10 dark:border-white/10">
        {{ $footer }}
    </div>
</x-modal>
