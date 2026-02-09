<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tuya Lock Manager')</title>
    <link rel="stylesheet" href="/build/assets/app-CkaXupzG.css">
    <script type="module" src="/build/assets/app-DNg7CCpm.js"></script>
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
</head>

<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <h1 class="text-xl font-bold text-gray-900">🔒 Tuya Lock Manager</h1>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="{{ route('dashboard') }}"
                                class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                Dashboard
                            </a>
                            <a href="{{ route('buildings.index') }}"
                                class="nav-link {{ request()->routeIs('buildings.*') ? 'active' : '' }}">
                                Edificios
                            </a>
                            <a href="{{ route('apartments.index') }}"
                                class="nav-link {{ request()->routeIs('apartments.*') ? 'active' : '' }}">
                                Apartamentos
                            </a>
                            <a href="{{ route('locks.index') }}"
                                class="nav-link {{ request()->routeIs('locks.*') ? 'active' : '' }}">
                                Cerraduras
                            </a>
                            <a href="{{ route('alerts.index') }}"
                                class="nav-link {{ request()->routeIs('alerts.*') ? 'active' : '' }}">
                                Alertas
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-gray-900">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @yield('content')
        </main>
    </div>
    @stack('scripts')
</body>

</html>