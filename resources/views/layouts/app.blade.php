<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('img/logo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('img/logo.png') }}">

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

        @php
            $user = auth()->user();
            $profile = $user?->profile;
            $homeRoute = app(\App\Services\Auth\HomeRouteResolver::class)->routeNameFor($user);
            $reportPermissions = [
                'view.reports.commissions',
                'view.reports.sales',
            ];
            $settingsPermissions = [
                'view.settings.offices',
                'view.settings.coupons',
                'view.settings.services',
                'view.settings.medications',
                'view.settings.specialties',
                'view.settings.plans',
                'view.settings.whatsapp',
                'view.settings.legal',
                'view.settings.parameters',
                'view.settings.permissions',
            ];
            $clerkPermissions = [
                'view.clerk.suppliers',
                'view.clerk.purchases',
                'view.clerk.medications',
            ];
            $showApiTokens = Laravel\Jetstream\Jetstream::hasApiFeatures() && $profile === 'Admin';
            $showReportsGroup = $user?->hasAnyPermission($reportPermissions) ?? false;
            $showSettingsGroup = ($user?->hasAnyPermission($settingsPermissions) ?? false) || $showApiTokens;
            $showPharmacyInventoryGroup = $user?->hasAnyPermission($clerkPermissions) ?? false;
        @endphp

        <x-ui.layout variant="sidebar-main" collapsable>
            <x-ui.sidebar>
                <x-slot name="brand">
                    <a href="{{ route($homeRoute) }}" class="flex items-center gap-2 px-2 py-2">
                        <!-- Logo -->
                        <x-application-mark class="block h-8 w-auto" />
                    </a>
                </x-slot>

                <x-ui.navlist class="mt-2">
                    @switch($profile)
                        @case('Admin')
                            @permission('view.dashboard')
                                <x-ui.navlist.item
                                    icon="home"
                                    :label="__('app.home')"
                                    href="{{ route('dashboard') }}"
                                    :active="request()->routeIs('dashboard')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.admin.preregistrations')
                                <x-ui.navlist.item
                                    icon="paper-airplane"
                                    :label="__('app.preregistration')"
                                    href="{{ route('preregistrations') }}"
                                    :active="request()->routeIs('preregistrations')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.admin.policies')
                                <x-ui.navlist.item
                                    icon="identification"
                                    :label="__('app.policies')"
                                    href="{{ route('policies') }}"
                                    :active="request()->routeIs('policies')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.admin.appointments')
                                <x-ui.navlist.item
                                    icon="calendar"
                                    :label="__('app.appointments')"
                                    href="{{ route('appointments') }}"
                                    :active="request()->routeIs('appointments')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.admin.doctors')
                                <x-ui.navlist.item
                                    icon="users"
                                    :label="__('app.doctors')"
                                    href="{{ route('doctors') }}"
                                    :active="request()->routeIs('doctors')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.admin.users')
                                <x-ui.navlist.item
                                    icon="user-group"
                                    :label="__('app.users')"
                                    href="{{ route('users') }}"
                                    :active="request()->routeIs('users')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.admin.whatsapp_console')
                                <x-ui.navlist.item
                                    icon="chat-bubble-left-right"
                                    :label="__('app.whatsapp_console')"
                                    href="{{ route('whatsapp.console') }}"
                                    :active="request()->routeIs('whatsapp.console')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @if ($showReportsGroup)
                                <x-ui.navlist.group
                                    :label="__('app.reports')"
                                    :collapsable="true"
                                >
                                    @permission('view.reports.commissions')
                                        <x-ui.navlist.item
                                            icon="currency-dollar"
                                            :label="__('app.commissions')"
                                            href="{{ route('reports.commissions') }}"
                                            :active="request()->routeIs('reports.commissions')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission

                                    @permission('view.reports.sales')
                                        <x-ui.navlist.item
                                            icon="document-currency-dollar"
                                            :label="__('app.sales')"
                                            href="{{ route('reports.sales') }}"
                                            :active="request()->routeIs('reports.sales')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission
                                </x-ui.navlist.group>
                            @endif

                            @if ($showPharmacyInventoryGroup)
                                <x-ui.navlist.group
                                        :label="__('app.inventory_pharmacy')"
                                    :collapsable="true"
                                >
                                    @permission('view.clerk.suppliers')
                                        <x-ui.navlist.item
                                            icon="users"
                                            :label="__('app.suppliers')"
                                            href="{{ route('clerk.suppliers') }}"
                                            :active="request()->routeIs('clerk.suppliers')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission

                                    @permission('view.clerk.purchases')
                                        <x-ui.navlist.item
                                            icon="shopping-cart"
                                            :label="__('app.purchases')"
                                            href="{{ route('clerk.purchases') }}"
                                            :active="request()->routeIs('clerk.purchases')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission

                                    @permission('view.clerk.medications')
                                        <x-ui.navlist.item
                                            icon="wallet"
                                            :label="__('app.medications')"
                                            href="{{ route('clerk.medications') }}"
                                            :active="request()->routeIs('clerk.medications')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission
                                </x-ui.navlist.group>
                            @endif

                            @if ($showSettingsGroup)
                                <x-ui.navlist.group
                                    :label="__('app.settings')"
                                    :collapsable="true"
                                >
                                    @permission('view.settings.offices')
                                        <x-ui.navlist.item
                                            icon="building-office"
                                            :label="__('app.offices')"
                                            href="{{ route('offices') }}"
                                            :active="request()->routeIs('offices')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission

                                    @permission('view.settings.coupons')
                                        <x-ui.navlist.item
                                            icon="ticket"
                                            :label="__('app.coupons')"
                                            href="{{ route('coupons') }}"
                                            :active="request()->routeIs('coupons')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission

                                    @permission('view.settings.services')
                                        <x-ui.navlist.item
                                            icon="wallet"
                                            :label="__('app.services')"
                                            href="{{ route('services') }}"
                                            :active="request()->routeIs('services')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission

                                    @permission('view.settings.specialties')
                                        <x-ui.navlist.item
                                            icon="wallet"
                                            :label="__('app.specialties')"
                                            href="{{ route('specialties') }}"
                                            :active="request()->routeIs('specialties')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission

                                    @permission('view.settings.plans')
                                        <x-ui.navlist.item
                                            icon="wallet"
                                            :label="__('app.plans')"
                                            href="{{ route('plans') }}"
                                            :active="request()->routeIs('plans')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission

                                    @permission('view.settings.whatsapp')
                                        <x-ui.navlist.item
                                            icon="chat-bubble-left-right"
                                            :label="__('WhatsApp')"
                                            href="{{ route('settings.whatsapp') }}"
                                            :active="request()->routeIs('settings.whatsapp')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission

                                    @permission('view.settings.legal')
                                        <x-ui.navlist.item
                                            icon="document-text"
                                            :label="__('Legal')"
                                            href="{{ route('settings.legal') }}"
                                            :active="request()->routeIs('settings.legal')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission

                                    @permission('view.settings.parameters')
                                        <x-ui.navlist.item
                                            icon="document"
                                            :label="__('app.parameters')"
                                            href="{{ route('settings.parameters') }}"
                                            :active="request()->routeIs('settings.parameters')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission

                                    @permission('view.settings.permissions')
                                        <x-ui.navlist.item
                                            icon="shield-check"
                                            :label="__('app.permissions')"
                                            href="{{ route('settings.permissions') }}"
                                            :active="request()->routeIs('settings.permissions')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endpermission

                                    @if ($showApiTokens)
                                        <x-ui.navlist.item
                                            icon="key"
                                            :label="__('API Tokens')"
                                            href="{{ route('api-tokens.index') }}"
                                            :active="request()->routeIs('api-tokens.index')"
                                            x-on:click="closeSidebar()"
                                        />
                                    @endif
                                </x-ui.navlist.group>
                            @endif
                        @break

                        @case('Sales')
                            @permission('view.dashboard')
                                <x-ui.navlist.item
                                    icon="home"
                                    :label="__('app.home')"
                                    href="{{ route('dashboard') }}"
                                    :active="request()->routeIs('dashboard')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.admin.preregistrations')
                                <x-ui.navlist.item
                                    icon="paper-airplane"
                                    :label="__('app.preregistration')"
                                    href="{{ route('preregistrations') }}"
                                    :active="request()->routeIs('preregistrations')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.admin.policies')
                                <x-ui.navlist.item
                                    icon="identification"
                                    :label="__('app.policies')"
                                    href="{{ route('policies') }}"
                                    :active="request()->routeIs('policies')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission
                        @break

                        @case('Clerk')
                            @permission('view.clerk.dispensation')
                                <x-ui.navlist.item
                                    icon="beaker"
                                    :label="__('app.dispensation')"
                                    href="{{ route('clerk.dispensation') }}"
                                    :active="request()->routeIs('clerk.dispensation')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission
                        @break

                        @case('Receptionist')
                            @permission('view.receptionist.policies')
                                <x-ui.navlist.item
                                    icon="identification"
                                    :label="__('app.policies')"
                                    href="{{ route('recepcionist.policies') }}"
                                    :active="request()->routeIs('recepcionist.policies')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.receptionist.requests')
                                <x-ui.navlist.item
                                    icon="clipboard-document-list"
                                    :label="'Solicitudes'"
                                    href="{{ route('receptionist.requests') }}"
                                    :active="request()->routeIs('receptionist.requests')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.receptionist.appointments')
                                <x-ui.navlist.item
                                    icon="currency-dollar"
                                    :label="'Check-out'"
                                    href="{{ route('receptionist.appointments') }}"
                                    :active="request()->routeIs('receptionist.appointments')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.receptionist.pending_results')
                                <x-ui.navlist.item
                                    icon="document-magnifying-glass"
                                    :label="'Faltantes de resultados'"
                                    href="{{ route('receptionist.pending-results') }}"
                                    :active="request()->routeIs('receptionist.pending-results')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission
                            

                            <!-- AJUSTE TEMPORAL -->
                            @permission('view.clerk.medications')
                                <x-ui.navlist.item
                                    icon="wallet"
                                    :label="__('app.medications')"
                                    href="{{ route('clerk.medications') }}"
                                    :active="request()->routeIs('clerk.medications')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.clerk.dispensation')
                                <x-ui.navlist.item
                                    icon="beaker"
                                    :label="__('app.dispensation')"
                                    href="{{ route('recepcionist.dispensation') }}"
                                    :active="request()->routeIs('recepcionist.dispensation')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission
                        @break

                        @case('Doctor')
                            @permission('view.doctor.home')
                                <x-ui.navlist.item
                                    icon="home"
                                    :label="__('app.home')"
                                    href="{{ route('doctor.home') }}"
                                    :active="request()->routeIs('doctor.home')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.receptionist.requests')
                                <x-ui.navlist.item
                                    icon="clipboard-document-list"
                                    :label="'Solicitudes'"
                                    href="{{ route('receptionist.requests') }}"
                                    :active="request()->routeIs('receptionist.requests')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.doctor.history')
                                <x-ui.navlist.item
                                    icon="calendar"
                                    :label="'Consultas'"
                                    href="{{ route('doctor.history') }}"
                                    :active="request()->routeIs('doctor.history')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission

                            @permission('view.receptionist.pending_results')
                                <x-ui.navlist.item
                                    icon="document-magnifying-glass"
                                    :label="'Faltantes de resultados'"
                                    href="{{ route('receptionist.pending-results') }}"
                                    :active="request()->routeIs('receptionist.pending-results')"
                                    x-on:click="closeSidebar()"
                                />
                            @endpermission
                        @break

                        @default
                    @endswitch
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
                                        {{ __('app.api_tokens') }}
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

        @livewireScriptConfig
    </body>
</html>
