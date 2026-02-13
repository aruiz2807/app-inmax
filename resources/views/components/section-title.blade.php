<div class="md:col-span-1 flex justify-between">
    <div class="px-4 sm:px-0">
        <x-ui.heading size="md">{{ $title }}</x-ui.heading>

        <x-ui.description class="mt-1">
            {{ $description }}
        </x-ui.description>
    </div>

    <div class="px-4 sm:px-0">
        {{ $aside ?? '' }}
    </div>
</div>
