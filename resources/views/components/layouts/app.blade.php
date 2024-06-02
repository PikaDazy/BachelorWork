@php
use App\Enums\Roles;
$adminRole = Roles::admin->name;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Cropper.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />

    {{-- Jquery --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    {{-- apexcharts --}}
    <script async="" src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        window.ApexCharts = require('apexcharts');
    </script>
</head>
<body class="min-h-screen font-sans antialiased bg-base-200/50 dark:bg-base-200">
    {{-- Theme toggle --}}
    <x-theme-toggle class="hidden" />
    {{-- NAVBAR mobile only --}}
    <x-nav sticky class="lg:hidden">
        <x-slot:brand>
            <x-app-brand />
        </x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer" class="lg:hidden mr-3">
                <x-icon name="o-bars-3" class="cursor-pointer" />
            </label>
        </x-slot:actions>
    </x-nav>

    {{-- MAIN --}}
    <x-main w-full mx-auto max-w-screen-2xl>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

            {{-- BRAND --}}
            <x-app-brand class="p-5 pt-3" />

            {{-- MENU --}}
            <x-menu activate-by-route>

                {{-- User --}}
                @if($user = auth()->user())
                    <x-menu-separator />

                    <x-list-item :item="$user" value="name" sub-value="email" no-separator no-hover class="-mx-2 !-my-2 rounded">
                        <x-slot:actions>
                            <x-button icon="o-power" class="btn-circle btn-ghost btn-xs" tooltip-left="logoff" no-wire-navigate link="/logout" />
                        </x-slot:actions>
                    </x-list-item>

                    <x-menu-separator />
                @endif

                <x-menu-item title="Головна" icon="o-sparkles" link="/" />
                {{-- USERS --}}
                <x-menu-sub title="Працівники" icon="o-briefcase">
                    <x-menu-item title="список" icon="o-list-bullet" link="/users" />
                    @if($user->role === $adminRole)
                        <x-menu-item title="створити" icon="o-plus" link="/users/create" />
                    @endif
                </x-menu-sub>
                {{-- Clients --}}
                <x-menu-sub title="Клієнти" icon="o-users">
                    <x-menu-item title="список" icon="o-list-bullet" link="/clients" />
                    <x-menu-item title="створити" icon="o-plus" link="/clients/create" />
                </x-menu-sub>
                {{-- MATERIALS --}}
                <x-menu-sub title="Матеріали" icon="o-square-3-stack-3d">
                    <x-menu-item title="список" icon="o-list-bullet" link="/materials/list" />
                    <x-menu-item title="створити" icon="o-plus" link="/materials/create" />
                </x-menu-sub>
                {{-- PRODUCTS --}}
                <x-menu-sub title="Продукти" icon="o-archive-box">
                    <x-menu-item title="список" icon="o-list-bullet" link="/products/list" />
                    <x-menu-item title="створити" icon="o-plus" link="/products/create" />
                </x-menu-sub>
                {{-- MANUFACTURE --}}
                <x-menu-sub title="Виробництва" icon="o-home-modern">
                    <x-menu-item title="список" icon="o-list-bullet" link="/manufacture/list" />
                    <x-menu-item title="створити" icon="o-plus" link="/manufacture/create" />
                </x-menu-sub>
                {{-- STORAGE --}}
                <x-menu-sub title="Склади" icon="o-building-storefront">
                    <x-menu-item title="список" icon="o-list-bullet" link="/storage/list" />
                    <x-menu-item title="створити" icon="o-plus" link="/storage/create" />
                </x-menu-sub>
                {{-- ORDERS --}}
                <x-menu-sub title="Замовлення" icon="o-shopping-cart">
                    <x-menu-item title="список" icon="o-list-bullet" link="/orders/list" />
                    <x-menu-item title="створити" icon="o-plus" link="/orders/create" />
                </x-menu-sub>
                {{-- SETTINGS --}}
                <x-menu-sub title="Налаштування" icon="o-cog-6-tooth">
                    <x-menu-item title="Theme" icon="o-swatch" @click="$dispatch('mary-toggle-theme')" />
                </x-menu-sub>
            </x-menu>
        </x-slot:sidebar>

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{--  TOAST area --}}
    <x-toast />

    {{-- Spotlight --}}
{{--    <x-spotlight--}}
{{--        shortcut=".slash" />--}}
</body>
</html>
