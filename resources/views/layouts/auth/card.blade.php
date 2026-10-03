<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#F9F3E5] text-[#4B150F] antialiased dark:bg-[#211311] dark:text-[#F9F3E5]">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-[#F9F3E5] p-6 md:p-10 dark:bg-[#211311]">
            <div class="flex w-full max-w-md flex-col gap-6">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-9 w-9 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-[#4B150F] dark:text-[#F9F3E5]" />
                    </span>

                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border border-[#EADFCE] bg-[#FFFDF8] text-[#4B150F] shadow-xs dark:border-[#563D35] dark:bg-[#2B1B18] dark:text-[#F9F3E5]">
                        <div class="px-10 py-8">{{ $slot }}</div>
                    </div>
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
