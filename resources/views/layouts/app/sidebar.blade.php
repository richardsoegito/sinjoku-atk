<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        @stack('css')
    </head>
    <body class="min-h-screen bg-[#F9F3E5] text-[#4B150F] dark:bg-[#211311] dark:text-[#F9F3E5]">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-[#EADFCE] bg-[#FFFDF8] dark:border-[#563D35] dark:bg-[#2B1B18]">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Menu utama')" class="grid">
                    @if (auth()->user()->hasRole('customer'))
                        <flux:sidebar.item icon="shopping-cart" :href="route('customer.transactions', ['type' => 'active'])" :current="request()->routeIs('customer.transactions') && request()->route('type') === 'active'" wire:navigate>
                            {{ __('Transaksi') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clock" :href="route('customer.transactions', ['type' => 'history'])" :current="request()->routeIs('customer.transactions') && request()->route('type') === 'history'" wire:navigate>
                            {{ __('History transaksi') }}
                        </flux:sidebar.item>
                    @else
                        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Dasbor') }}
                        </flux:sidebar.item>
                        @can('roles.manage')
                            <flux:sidebar.item icon="users" :href="route('settings.users')" :current="request()->routeIs('settings.users*')" wire:navigate>
                                {{ __('User') }}
                            </flux:sidebar.item>
                        @endcan
                        @canany(['products.view', 'products.manage'])
                            <flux:sidebar.item icon="archive-box" :href="route('barang.index')" :current="request()->routeIs('barang.*')" wire:navigate>
                                {{ __('Barang') }}
                            </flux:sidebar.item>
                        @endcan
                        <flux:sidebar.item icon="shopping-cart" :href="route('sales.index')" :current="request()->routeIs('sales.*')" wire:navigate>
                            {{ __('Penjualan') }}
                        </flux:sidebar.item>
                        @if (auth()->user()->can('roles.manage') || auth()->user()->can('permissions.manage'))
                            <flux:sidebar.group :heading="__('Pengaturan akses')" class="grid">
                                @can('roles.manage')
                                    <flux:sidebar.item icon="user-group" :href="route('settings.roles')" :current="request()->routeIs('settings.roles')" wire:navigate>
                                        {{ __('Peran') }}
                                    </flux:sidebar.item>
                                @endcan
                                @can('permissions.manage')
                                    <flux:sidebar.item icon="key" :href="route('settings.permissions')" :current="request()->routeIs('settings.permissions')" wire:navigate>
                                        {{ __('Izin') }}
                                    </flux:sidebar.item>
                                @endcan
                            </flux:sidebar.group>
                        @endif
                    @endif
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate text-xs font-medium text-[#6C3429] dark:text-[#E1A88C]">{{ auth()->user()->roleLabel() }}</flux:text>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Pengaturan') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Keluar') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
