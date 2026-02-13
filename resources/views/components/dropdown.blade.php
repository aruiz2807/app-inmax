@props(['align' => 'right', 'width' => '48', 'contentClasses' => '', 'dropdownClasses' => ''])

@php
$position = match ($align) {
    'left' => 'bottom-start',
    'top' => 'top',
    'none', 'false' => 'bottom-center',
    default => 'bottom-end',
};

$widthClass = match ($width) {
    '48' => 'min-w-48',
    '60' => 'min-w-60',
    default => '',
};

$menuClasses = trim($widthClass.' '.$contentClasses.' '.$dropdownClasses);
@endphp

<x-ui.dropdown position="{{ $position }}">
    <x-slot name="button">
        {{ $trigger }}
    </x-slot>

    <x-slot name="menu" class="{{ $menuClasses }}">
        {{ $content }}
    </x-slot>
</x-ui.dropdown>
