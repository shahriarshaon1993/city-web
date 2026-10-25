<!DOCTYPE html>
{{-- Public root (ARC-008). Light only, no dark-mode script (DEC-28); the admin uses admin.blade.php. --}}
@php($seo = $page['props']['seo'] ?? null)
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- HERO-030/031: the LCP image, first load only (not managed by <Head>). --}}
        @foreach ($seo['preload'] ?? [] as $preload)
            <link rel="preload" as="image" imagesrcset="{{ $preload['srcset'] }}" imagesizes="{{ $preload['sizes'] }}" type="{{ $preload['type'] }}" fetchpriority="high" @if ($preload['media']) media="{{ $preload['media'] }}" @endif>
        @endforeach

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])

        {{-- SEO-REN-01: same keys as SeoHead.vue, so client navigation replaces these tags. --}}
        <x-inertia::head>
            @if ($seo)
                <title data-inertia>{{ $seo['title'] }}</title>
                @if ($seo['description'])
                    <meta data-inertia="description" name="description" content="{{ $seo['description'] }}">
                @endif
                <meta data-inertia="robots" name="robots" content="{{ $seo['robots'] }}">
                @if ($seo['canonical'])
                    <link data-inertia="canonical" rel="canonical" href="{{ $seo['canonical'] }}">
                @endif
                <meta data-inertia="og:type" property="og:type" content="{{ $seo['og']['type'] }}">
                <meta data-inertia="og:title" property="og:title" content="{{ $seo['og']['title'] }}">
                @if ($seo['og']['description'])
                    <meta data-inertia="og:description" property="og:description" content="{{ $seo['og']['description'] }}">
                @endif
                <meta data-inertia="og:url" property="og:url" content="{{ $seo['og']['url'] }}">
                @if ($seo['og']['image'])
                    <meta data-inertia="og:image" property="og:image" content="{{ $seo['og']['image'] }}">
                    <meta data-inertia="og:image:alt" property="og:image:alt" content="{{ $seo['og']['image_alt'] }}">
                @endif
                <meta data-inertia="og:site_name" property="og:site_name" content="{{ $seo['og']['site_name'] }}">
                <meta data-inertia="og:locale" property="og:locale" content="{{ $seo['og']['locale'] }}">
                <meta data-inertia="twitter:card" name="twitter:card" content="{{ $seo['twitter_card'] }}">
                @foreach ($seo['json_ld'] as $index => $graph)
                    <script data-inertia="json-ld-{{ $index }}" type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
                @endforeach
            @else
                <title data-inertia>{{ config('app.name') }}</title>
            @endif
        </x-inertia::head>
    </head>
    <body class="bg-background font-sans text-foreground antialiased">
        <x-inertia::app />
    </body>
</html>
