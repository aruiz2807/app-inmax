@props(['submit'])

<div {{ $attributes->merge(['class' => 'md:grid md:grid-cols-3 md:gap-6']) }}>
    <x-section-title>
        <x-slot name="title">{{ $title }}</x-slot>
        <x-slot name="description">{{ $description }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <form wire:submit="{{ $submit }}">
            <div class="px-4 py-5 sm:p-6 bg-white dark:bg-neutral-900 border border-black/10 dark:border-white/10 rounded-lg shadow-sm {{ isset($actions) ? 'rounded-b-none' : '' }}">
                <div class="grid grid-cols-6 gap-6">
                    {{ $form }}
                </div>
            </div>

            @if (isset($actions))
                <div class="flex items-center justify-end px-4 py-3 bg-neutral-50 dark:bg-neutral-900 text-end sm:px-6 border border-t-0 border-black/10 dark:border-white/10 rounded-b-lg">
                    {{ $actions }}
                </div>
            @endif
        </form>
    </div>
</div>
