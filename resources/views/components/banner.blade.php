@props(['style' => session('flash.bannerStyle', 'success'), 'message' => session('flash.banner')])

@php
    $alertType = match ($style) {
        'success' => 'success',
        'danger' => 'error',
        'warning' => 'warning',
        default => 'info',
    };
@endphp

<div
    x-data="{{ json_encode(['show' => true, 'style' => $style, 'message' => $message]) }}"
    style="display: none;"
    x-show="show && message"
    x-on:banner-message.window="
        style = event.detail.style;
        message = event.detail.message;
        show = true;
    "
>
    <div class="max-w-screen-xl mx-auto py-3 px-4 sm:px-6 lg:px-8">
        <x-ui.alerts :type="$alertType" class="flex items-center justify-between gap-4">
            <x-slot name="heading">
                <span class="text-sm font-medium text-neutral-900 dark:text-neutral-50" x-text="message"></span>
            </x-slot>

            <x-slot name="actions">
                <x-ui.button variant="ghost" size="xs" x-on:click="show = false" aria-label="Dismiss">
                    <x-ui.icon name="x-mark" variant="mini" />
                </x-ui.button>
            </x-slot>
        </x-ui.alerts>
    </div>
</div>
