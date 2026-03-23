{{--
    Social Icons Component
    
    Displays small circular social media icons with gradient backgrounds.
    Perfect for quick visual reference to available contact methods.
--}}

@props([
    'links' => [],
])

@php
    $iconMap = [
        'email' => [
            'svg' => 'M224,48H32a8,8,0,0,0-8,8V192a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A8,8,0,0,0,224,48Zm-96,85.15L52.57,64H203.43ZM98.71,128,40,181.81V74.19Zm11.84,10.85,12,11.05a8,8,0,0,0,10.82,0l12-11.05,58,53.15H52.57ZM157.29,128,216,74.18V181.82Z',
            'gradient' => 'from-red-500 to-red-600',
            'href' => fn($value) => 'mailto:' . $value
        ],
        'whatsapp' => [
            'svg' => 'M187.58,144.84l-32-16a8,8,0,0,0-8,.5l-14.69,9.8a40.55,40.55,0,0,1-16-16l9.8-14.69a8,8,0,0,0,.5-8l-16-32A8,8,0,0,0,104,64a40,40,0,0,0-40,40,88.1,88.1,0,0,0,88,88,40,40,0,0,0,40-40A8,8,0,0,0,187.58,144.84ZM152,176a72.08,72.08,0,0,1-72-72A24,24,0,0,1,99.29,80.46l11.48,23L101,118a8,8,0,0,0-.73,7.51,56.47,56.47,0,0,0,30.15,30.15A8,8,0,0,0,138,155l14.61-9.74,23,11.48A24,24,0,0,1,152,176ZM128,24A104,104,0,0,0,36.18,176.88L24.83,210.93a16,16,0,0,0,20.24,20.24l34.05-11.35A104,104,0,1,0,128,24Zm0,192a87.87,87.87,0,0,1-44.06-11.81,8,8,0,0,0-6.54-.67L40,216,52.47,178.6a8,8,0,0,0-.66-6.54A88,88,0,1,1,128,216Z',
            'gradient' => 'from-green-500 to-green-600',
            'href' => fn($value) => 'https://wa.me/' . preg_replace('/\D/', '', $value)
        ],
        'instagram' => [
            'svg' => 'M227.36,28.78a13,13,0,0,0-7.1-7.1C215.72,20.54,204,22,128,22s-87.72-1.46-92.26.68A13,13,0,0,0,28.65,29.78C20.54,34.28,22,46,22,128s-1.46,87.72.65,92.26a13,13,0,0,0,7.1,7.1c4.54,2.13,15.25.68,92.26.68s87.72,1.46,92.26-.68a13,13,0,0,0,7.1-7.1c2.12-4.54.67-15.25.67-92.26S229.48,34.28,227.36,28.78ZM128,188A60,60,0,1,1,188,128,60,60,0,0,1,128,188Zm66-102a14,14,0,1,1,14-14A14,14,0,0,1,194,86Z',
            'gradient' => 'from-pink-500 via-purple-500 to-indigo-500',
            'href' => fn($value) => str_starts_with($value, 'http') ? $value : 'https://instagram.com/' . str_replace('@', '', $value)
        ],
        'tiktok' => [
            'svg' => 'M16.708 0.027c1.745-0.027 3.48-0.011 5.213-0.027 0.105 2.041 0.839 4.12 2.333 5.563 1.491 1.479 3.6 2.156 5.652 2.385v5.369c-1.923-0.063-3.855-0.463-5.6-1.291-0.76-0.344-1.468-0.787-2.161-1.24-0.009 3.896 0.016 7.787-0.025 11.667-0.104 1.864-0.719 3.719-1.803 5.255-1.744 2.557-4.771 4.224-7.88 4.276-1.907 0.109-3.812-0.411-5.437-1.369-2.693-1.588-4.588-4.495-4.864-7.615-0.032-0.667-0.043-1.333-0.016-1.984 0.24-2.537 1.495-4.964 3.443-6.615 2.208-1.923 5.301-2.839 8.197-2.297 0.027 1.975-0.052 3.948-0.052 5.923-1.323-0.428-2.869-0.308-4.025 0.495-0.844 0.547-1.485 1.385-1.819 2.333-0.276 0.676-0.197 1.427-0.181 2.145 0.317 2.188 2.421 4.027 4.667 3.828 1.489-0.016 2.916-0.88 3.692-2.145 0.251-0.443 0.532-0.896 0.547-1.417 0.131-2.385 0.079-4.76 0.095-7.145 0.011-5.375-0.016-10.735 0.025-16.093z',
            'gradient' => 'from-slate-900 to-slate-700',
            'href' => fn($value) => str_starts_with($value, 'http') ? $value : 'https://tiktok.com/@' . str_replace('@', '', $value)
        ],
        'maps' => [
            'svg' => 'M128,16a88.1,88.1,0,0,0-88,88c0,53.91,88,136,88,136s88-82.09,88-136A88.1,88.1,0,0,0,128,16Zm0,120a32,32,0,1,1,32-32A32,32,0,0,1,128,136Z',
            'gradient' => 'from-orange-500 to-orange-600',
            'href' => fn($value) => str_starts_with($value, 'http') ? $value : 'https://maps.google.com/?q=' . urlencode($value)
        ],
    ];

    // Filter only: email, whatsapp, instagram, tiktok in that order
    $allowedTypes = ['email', 'whatsapp', 'instagram', 'tiktok'];
    $filteredLinks = $links->filter(function($link) use ($allowedTypes) {
        return in_array(strtolower($link->key), $allowedTypes);
    })->sortBy(function($link) use ($allowedTypes) {
        return array_search(strtolower($link->key), $allowedTypes);
    });

    $displayLinks = $filteredLinks->isNotEmpty() ? $filteredLinks : collect();
@endphp

<div class="flex justify-center items-center gap-4 py-6">
    @forelse ($displayLinks as $link)
        @php
            $type = strtolower($link->key);
            $icon = $iconMap[$type] ?? null;
            $href = $icon ? ($icon['href']($link->value) ?? '#') : '#';
        @endphp
        
        @if ($icon)
            <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" class="group cursor-pointer relative">
                <div class="absolute -inset-1 bg-gradient-to-br {{ $icon['gradient'] }} rounded-full opacity-0 group-hover:opacity-100 blur transition-opacity duration-300"></div>
                
                @if (str_contains($icon['svg'], 'M16.708'))
                    <div class="relative w-12 h-12 rounded-full bg-gradient-to-br {{ $icon['gradient'] }} flex items-center justify-center text-white shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 33 33">
                            <path d="{{ $icon['svg'] }}"></path>
                        </svg>
                    </div>
                @else
                    <div class="relative w-12 h-12 rounded-full bg-gradient-to-br {{ $icon['gradient'] }} flex items-center justify-center text-white shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256">
                            <path d="{{ $icon['svg'] }}"></path>
                        </svg>
                    </div>
                @endif
                
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 bg-neutral-900 dark:bg-neutral-800 text-white text-xs font-medium rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none whitespace-nowrap">
                    {{ $link->description }}
                </div>
            </a>
        @endif
    @empty
    @endforelse
</div>

