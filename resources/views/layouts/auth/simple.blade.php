<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#F9F3E5] text-[#4B150F] antialiased dark:bg-[#211311] dark:text-[#F9F3E5]">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-[#F9F3E5] p-6 md:p-10 dark:bg-[#211311]">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="mb-1 flex size-32 items-center justify-center rounded-md">
                        <img src="{{ asset('images/image-Photoroom.png') }}" alt="{{ config('app.name', 'Sin Jo Ku') }}" class="size-32 object-contain" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
