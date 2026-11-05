<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'AfProsPos') }}</title>
    
    <link rel="icon" href="{{ app(\Domain\Shared\Application\Queries\SiteFaviconQuery::class)->url() }}">
    <link rel="apple-touch-icon" href="{{ app(\Domain\Shared\Application\Queries\SiteLogoQuery::class)->url() }}">

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    {{-- laravel-vite-plugin's font self-hosting (vite.config.js's bunny()
         entries) emits fonts-manifest.json/fonts-*.css as build ASSETS, not
         a Vite entry point, so @vite() above never links them on its own —
         this call is the one thing that actually renders the font preload
         + @font-face <style> block. Without it, every self-hosted family
         (Inter, Glass Antiqua, etc.) silently falls back to the browser's
         default font in production, even though the files exist on disk
         and build without error. --}}
    {{ \Illuminate\Support\Facades\Vite::fonts() }}
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
