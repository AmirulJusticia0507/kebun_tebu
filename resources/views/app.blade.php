<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <link rel="icon" type="image/png" href="/logo-kebun-tebu.png">
        <link rel="apple-touch-icon" href="/logo-kebun-tebu.png">
        <link rel="manifest" href="/build/manifest.webmanifest">
        <meta name="theme-color" content="#059669">

        <title inertia>{{ config('app.name', 'Kebun Tebu MVP') }}</title>

        <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts & Styles -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-slate-900 text-slate-100 min-h-full selection:bg-emerald-500 selection:text-white">
        @inertia
    </body>
</html>
