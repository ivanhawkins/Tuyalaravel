<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tuya Lock Manager</title>
    <link rel="stylesheet" href="/build/assets/app-CkaXupzG.css">
    <script type="module" src="/build/assets/app-DNg7CCpm.js"></script>
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
</head>

<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-2xl w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">🔒 Tuya Lock Manager</h1>
            <p class="text-gray-600 mt-2">Panel de Administración</p>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-6">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    Email
                </label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    Contraseña
                </label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="remember"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-600">Recordarme</span>
                </label>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200">
                Iniciar Sesión
            </button>
        </form>
    </div>
</body>

</html>