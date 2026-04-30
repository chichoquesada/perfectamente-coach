<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        @include('partials.assets')
    </head>
    <body class="font-sans antialiased bg-bg text-text-primary">
        <div class="min-h-screen">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-white/[0.06]">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
