@props(['label' => null, 'description' => null])

@php
    $label ??= trim($slot);
@endphp

<x-ui.checkbox :label="$label" :description="$description" {{ $attributes }} />
