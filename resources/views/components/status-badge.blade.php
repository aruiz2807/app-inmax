@props(['status'])

@php
    $colors = [
        'Active' => 'text-green-700 bg-green-100',
        'Inactive' => 'text-gray-700 bg-gray-100',
        'Cancelled' => 'text-red-700 bg-red-100',

        'Booked' => 'text-blue-700 bg-blue-100',
        'Cancelled' => 'text-red-700 bg-red-100',
        'No-show' => 'text-red-700 bg-red-100',
        'Completed' => 'text-green-700 bg-green-100',

        '1' => 'text-green-700 bg-green-100',
        '0' => 'text-amber-700 bg-amber-100',
    ];

    $labels = [
        'Active' => __('Activa'),
        'Inactive' => __('Inactiva'),
        'Cancelled' => __('Cancelada'),

        'Booked' => __('Agendada'),
        'Cancelled' => __('Cancelada'),
        'No-show' => __('Falto'),
        'Completed' => __('Atendida'),

        '1' => __('Cubierta'),
        '0' => __('Adicional'),
    ];
@endphp

<span class="px-2 py-1 text-xs font-bold rounded-full {{ $colors[$status] ?? 'text-gray-500 bg-gray-50' }}">
    {{ $labels[$status] ?? __('Unknown') }}
</span>
