<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#FFD54F">
    <meta name="description" content="DuitMoo: Aplikasi pencatatan keuangan pribadi dan pembagian anggaran otomatis 60/30/10 untuk HP dan desktop.">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="DuitMoo">

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <title>{{ $title ?? 'DuitMoo: Your Money, Your Rules!' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#FCF6EB] text-[#263238] min-h-screen flex flex-col font-sans antialiased selection:bg-[#FFD54F] selection:text-[#263238]">
    {{ $slot }}

    @livewireScripts
</body>
</html>
