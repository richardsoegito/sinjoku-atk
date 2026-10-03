<!DOCTYPE html>
<html lang="id">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#F4F5F0] text-[#20312D] antialiased">
        {{ $slot }}

        @fluxScripts
    </body>
</html>