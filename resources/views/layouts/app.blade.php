<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DELPRO')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @stack('head')
</head>
<body class="@yield('body_class', 'bg-gray-100 font-sans')" x-data='@yield("root_data", "{ sidebarOpen: true }")'>
    <div class="flex h-screen overflow-hidden">
        @include('partials.sidebar')

        <main class="@yield('main_class', 'flex-1 flex flex-col overflow-y-auto')">
            @if(!trim($__env->yieldContent('hide_header')))
                @include('partials.topbar')
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
