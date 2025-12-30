<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'PlanForge' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @filamentStyles
    @vite('resources/css/filament/admin/theme.css')
</head>
<body class="antialiased bg-gray-50">
    @filamentScripts
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white flex-shrink-0">
            <div class="p-4">
                <a href="{{ route('projects.index') }}" wire:navigate class="flex items-center gap-2">
                    <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span class="text-xl font-bold">PlanForge</span>
                </a>
            </div>

            <nav class="mt-6 px-3">
                <a href="{{ route('projects.index') }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('projects.index') ? 'bg-gray-800 text-white' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    Projects
                </a>
            </nav>

            @if(isset($sidebar))
                <div class="mt-6 px-3">
                    {{ $sidebar }}
                </div>
            @endif
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
