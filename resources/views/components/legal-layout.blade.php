<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>
        @include('partials.assets')
    </head>
    <body class="font-sans antialiased bg-bg text-text-primary">
        <div class="fixed top-4 right-4 z-50">
            <x-theme-toggle />
        </div>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10 sm:py-14">

            <a href="{{ route('landing') }}" class="inline-block mb-8 text-sm text-text-secondary hover:text-text-primary transition">
                ← Volver al inicio
            </a>

            <div class="mb-6">
                <span class="font-serif italic text-base">Perfecta<span class="text-gold">MENTE</span></span>
                <span class="text-xs text-text-secondary tracking-[0.3em] uppercase ml-1">Coach</span>
            </div>

            @if (! empty($disclaimer))
                <div class="mb-8 p-4 bg-parcial/10 border border-parcial/30 rounded-xl text-sm text-text-primary">
                    <strong class="text-parcial uppercase tracking-wider text-xs block mb-1">Borrador legal</strong>
                    {{ $disclaimer }}
                </div>
            @endif

            <h1 class="font-serif text-3xl sm:text-4xl mb-2">{{ $title }}</h1>
            <p class="text-xs text-text-secondary tracking-wider uppercase mb-8">
                Última actualización: {{ $updatedAt ?? now()->isoFormat('D [de] MMMM [de] YYYY') }}
            </p>

            <article class="prose prose-invert max-w-none text-sm leading-relaxed space-y-6 text-text-primary">
                {{ $slot }}
            </article>

            <p class="mt-12 text-xs text-text-secondary/60 italic font-serif text-center">
                Cualquier duda, escríbanos a <a href="mailto:hola@planperfectamente.com" class="text-gold underline">hola@planperfectamente.com</a>.
            </p>
        </div>
    </body>
</html>
