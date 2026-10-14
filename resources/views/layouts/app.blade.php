<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Export Geotag to CSV')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, 'Segoe UI', Roboto, sans-serif;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50 text-slate-700 antialiased flex flex-col">

    <main class="flex-1 w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div
            class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 text-xs text-slate-500 sm:px-6 lg:px-8">
            <span>Askarasoft Geotag Export</span>
            <span>v1.0.0</span>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>
