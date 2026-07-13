<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PjBL')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        html { font-size: 17px; }
    </style>
    @stack('head')
</head>
<body class="@yield('body_class', 'bg-gray-100 font-sans')" x-data='@yield("root_data", "{ sidebarOpen: true }")'>
    <div class="flex min-h-screen">
        @include('partials.sidebar')

        <main class="@yield('main_class', 'flex-1 flex flex-col min-w-0')">
            @if(!trim($__env->yieldContent('hide_header')))
                @include('partials.topbar')
            @endif

            @if(!empty($selected_project) && ($selected_project['can_access_pjbl'] ?? false))
                @include('partials.project-workflow-tabs')

                @if(!empty($stage_overview) && !empty($active_stage))
                    @include('partials.stage-actions')
                @endif
            @endif

            <div class="@yield('content_wrapper_class', 'w-full min-w-0 flex-1 p-4 lg:p-5')">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
