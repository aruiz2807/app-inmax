@props([
    'disabled' => false,
    'clearable' => null,
    'revealable' => null,
    'copyable' => null,
])

@php
    $type = $attributes->get('type', 'text');
    $isReadonly = $attributes->has('readonly');

    $clearable ??= in_array($type, ['text', 'email', 'search', 'url', 'tel'], true) && !$isReadonly;
    $revealable ??= $type === 'password';
    $copyable ??= $isReadonly;
@endphp

<x-ui.input
    :clearable="$clearable"
    :revealable="$revealable"
    :copyable="$copyable"
    {{ $attributes->merge(['disabled' => $disabled]) }}
/>
