<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>

    <body class="font-sans antialiased text-neutral-900 bg-neutral-50 dark:text-neutral-50 dark:bg-neutral-950">
        <x-banner />

        <x-ui.layout variant="sidebar-main" collapsable>
            <x-ui.sidebar>
                <x-slot name="brand">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-2 py-2">
                        <!-- Logo -->
                        <x-application-mark class="block h-8 w-auto" />
                    </a>
                </x-slot>

                <x-ui.navlist class="mt-2">
                    <x-ui.navlist.item
                        icon="home"
                        :label="__('app.home')"
                        href="{{ route('dashboard') }}"
                        :active="request()->routeIs('dashboard')"
                        x-on:click="closeSidebar()"
                    />

                    <x-ui.navlist.item
                        icon="identification"
                        :label="__('app.policies')"
                        href="{{ route('policies') }}"
                        :active="request()->routeIs('policies')"
                        x-on:click="closeSidebar()"
                    />

                    <x-ui.navlist.item
                        icon="users"
                        :label="__('app.doctors')"
                        href="{{ route('doctors') }}"
                        :active="request()->routeIs('doctors')"
                        x-on:click="closeSidebar()"
                    />

                    @if (auth()->user()?->profile === 'Admin')
                        <x-ui.navlist.item
                            icon="user-group"
                            :label="__('Users')"
                            href="{{ route('users') }}"
                            :active="request()->routeIs('users')"
                            x-on:click="closeSidebar()"
                        />
                    @endif

                    <x-ui.navlist.group
                        :label="__('app.settings')"
                        :collapsable="true"
                    >
                        <x-ui.navlist.item
                            icon="wallet"
                            :label="__('app.services')"
                            href="{{ route('services') }}"
                            :active="request()->routeIs('services')"
                            x-on:click="closeSidebar()"
                        />

                        <x-ui.navlist.item
                            icon="wallet"
                            :label="__('app.specialties')"
                            href="{{ route('specialties') }}"
                            :active="request()->routeIs('specialties')"
                            x-on:click="closeSidebar()"
                        />

                        <x-ui.navlist.item
                            icon="wallet"
                            :label="__('app.plans')"
                            href="{{ route('plans') }}"
                            :active="request()->routeIs('plans')"
                            x-on:click="closeSidebar()"
                        />

                        @if (auth()->user()?->profile === 'Admin')
                            <x-ui.navlist.item
                                icon="chat-bubble-left-right"
                                :label="__('WhatsApp')"
                                href="{{ route('settings.whatsapp') }}"
                                :active="request()->routeIs('settings.whatsapp')"
                                x-on:click="closeSidebar()"
                            />

                            <x-ui.navlist.item
                                icon="document-text"
                                :label="__('Legal')"
                                href="{{ route('settings.legal') }}"
                                :active="request()->routeIs('settings.legal')"
                                x-on:click="closeSidebar()"
                            />
                        @endif

                        @if (Laravel\Jetstream\Jetstream::hasApiFeatures() && auth()->user()?->profile === 'Admin')
                            <x-ui.navlist.item
                                icon="key"
                                :label="__('API Tokens')"
                                href="{{ route('api-tokens.index') }}"
                                :active="request()->routeIs('api-tokens.index')"
                                x-on:click="closeSidebar()"
                            />
                        @endif
                    </x-ui.navlist.group>
                </x-ui.navlist>

                <x-ui.sidebar.push />
            </x-ui.sidebar>

            <x-ui.layout.main>
                <x-ui.layout.header>
                    <div class="flex items-center w-full gap-3 px-2">
                        <x-ui.sidebar.toggle class="lg:hidden" />

                        @if (isset($header))
                            <x-ui.heading size="md">
                                {{ $header }}
                            </x-ui.heading>
                        @endif

                        <div class="flex-1"></div>

                        <div class="flex items-center gap-2">
                            <x-ui.theme-switcher variant="dropdown" />

                            <x-ui.dropdown>
                                <x-slot:button
                                    class="cursor-pointer hover:opacity-80 transition"
                                    role="button"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                    aria-controls="theme-menu"
                                >
                                    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                        <img class="size-7 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                                    @else
                                        <x-ui.icon name="user-circle" variant="mini" class="inline-flex"/>
                                    @endif
                                </x-slot:button>

                                <x-slot:menu>
                                    <x-ui.dropdown.item
                                        icon="user"
                                        iconVariant="mini"
                                        href="{{ route('profile.show') }}"
                                    >
                                        {{ __('app.profile') }}
                                    </x-ui.dropdown.item>

                                    @if (Laravel\Jetstream\Jetstream::hasApiFeatures() && auth()->user()?->profile === 'Admin')
                                    <x-ui.dropdown.item
                                        icon="key"
                                        iconVariant="mini"
                                        href="{{ route('api-tokens.index') }}"
                                    >
                                        {{ __('API Tokens') }}
                                    </x-ui.dropdown.item>
                                    @endif

                                    <x-ui.dropdown.separator />

                                    <x-ui.dropdown.item
                                        icon="arrow-left-start-on-rectangle"
                                        iconVariant="mini"
                                    >
                                        <form method="POST" action="{{ route('logout') }}" x-data>
                                            @csrf
                                             <button
                                                type="submit"
                                                class="flex w-full items-center gap-2 text-left"
                                            >
                                                {{ __('app.logout') }}
                                            </button>
                                        </form>
                                    </x-ui.dropdown.item>
                                </x-slot:menu>
                            </x-ui.dropdown>
                        </div>
                    </div>
                </x-ui.layout.header>

                <div class="p-6">
                    {{ $slot }}
                </div>
            </x-ui.layout.main>
        </x-ui.layout>

        <x-ui.toast position="bottom-right" maxToasts="5" />

        @stack('modals')

        @livewireScripts
    </body>
</html>
