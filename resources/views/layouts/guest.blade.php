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
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-10 sm:pt-0 px-4">
            <a href="{{ route('landing') }}" class="block text-center mb-8">
                <span class="font-serif italic text-2xl">Perfecta<span class="text-gold">MENTE</span></span>
                <span class="block text-xs tracking-[0.3em] text-text-secondary mt-1 uppercase">Coach</span>
            </a>

            <div class="w-full sm:max-w-md px-6 py-8 bg-bg-card border border-white/[0.06] rounded-2xl shadow-2xl">
                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-text-secondary/60 italic font-serif text-center max-w-md">
                "La fidelidad diaria es lo que separa a los campeones del resto." — @chichoqv
            </p>
        </div>
    </body>
</html>
