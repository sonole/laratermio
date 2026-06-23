@php $hasFastfetch = app(\App\Terminal\CommandRegistry::class)->resolve('fastfetch') !== null; @endphp
<!--
██╗  ░██████╗███████╗███████╗  ██╗░░░██╗░█████╗░██╗░░░██╗
██║  ██╔════╝██╔════╝██╔════╝  ╚██╗░██╔╝██╔══██╗██║░░░██║
██║  ╚█████╗░█████╗░░█████╗░░  ░╚████╔╝░██║░░██║██║░░░██║
██║  ░╚═══██╗██╔══╝░░██╔══╝░░  ░░╚██╔╝░░██║░░██║██║░░░██║
██║  ██████╔╝███████╗███████╗  ░░░██║░░░╚█████╔╝╚██████╔╝
╚═╝  ╚═════╝░╚══════╝╚══════╝  ░░░╚═╝░░░░╚════╝░░╚═════╝░
{{ $hasFastfetch ? "\n  curious? run `fastfetch` in the terminal." : '' }}
-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ \App\Facades\Settings::get('seo_description', '') }}">
    <title>{{ \App\Facades\Settings::get('seo_title', config('app.name')) }}</title>
    <link rel="shortcut icon" href="{{ \App\Facades\Settings::faviconUrl() }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            let t = localStorage.getItem('terminal-theme');
            if (!t || t === 'system') {
                t = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            }
            if (t === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    @livewireStyles
</head>
<body>
    <livewire:terminal />
    @livewireScripts
</body>
</html>
