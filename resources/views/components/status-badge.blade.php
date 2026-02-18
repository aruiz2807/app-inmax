@props(['status'])

@php
    $colors = [
        'Active' => 'text-green-700 bg-green-100',
        'Inactive' => 'text-gray-700 bg-gray-100',
        'Cancelled' => 'text-red-700 bg-red-100',
    ];

    $labels = [
        'Active' => __('Activa'),
        'Inactive' => __('Inactiva'),
        'Cancelled' => __('Cancelada'),
    ];
@endphp

<span class="px-2 py-1 text-xs font-bold rounded-full {{ $colors[$status] ?? 'text-gray-500 bg-gray-50' }}">
    {{ $labels[$status] ?? __('Unknown') }}
</span>
