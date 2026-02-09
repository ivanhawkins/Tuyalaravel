@extends('layouts.app')

@section('title', 'API Playground')

@section('content')
    <div class="space-y-6">
        <h2 class="text-2xl font-bold text-gray-900">API Playground 🛠️</h2>
        <p class="text-gray-600">Entorno de pruebas para la API de Tuya.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Controls -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium mb-4">Configurar Petición</h3>
                <form action="{{ route('playground.run') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cerradura Objetivo</label>
                        <select name="lock_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($locks as $l)
                                <option value="{{ $l->id }}" {{ (isset($lock) && $lock->id == $l->id) ? 'selected' : '' }}>
                                    {{ $l->name }} ({{ $l->device_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Acción</label>
                        <select name="action"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="create_temp_password" {{ (isset($action) && $action == 'create_temp_password') ? 'selected' : '' }}>Crear Código Temporal (Test)</option>
                            <option value="get_temp_passwords" {{ (isset($action) && $action == 'get_temp_passwords') ? 'selected' : '' }}>Listar Códigos</option>
                            <option value="get_lock_details" {{ (isset($action) && $action == 'get_lock_details') ? 'selected' : '' }}>Ver Detalles DB</option>
                        </select>
                    </div>

                    <div class="p-4 bg-gray-50 rounded text-sm">
                        <p class="font-bold">Parámetros para "Crear Código":</p>
                        <div class="grid grid-cols-2 gap-4 mt-2">
                            <div>
                                <label>PIN (6 dígitos)</label>
                                <input type="number" name="pin" value="123456"
                                    class="w-full text-xs rounded border-gray-300">
                            </div>
                            <div>
                                <label>Nombre</label>
                                <input type="text" name="name" value="Test Playground"
                                    class="w-full text-xs rounded border-gray-300">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full btn-primary justify-center">Ejecutar</button>
                </form>
            </div>

            <!-- Output -->
            <div class="bg-gray-900 rounded-lg shadow p-6 text-green-400 font-mono text-sm overflow-auto max-h-[600px]">
                <h3 class="text-white font-medium mb-2 border-b border-gray-700 pb-2">Console Output >_</h3>

                @if(isset($error))
                    <div class="text-red-400 whitespace-pre-wrap">{{ $error }}</div>
                @elseif(isset($result))
                    <div class="whitespace-pre-wrap">{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                    </div>
                @else
                    <div class="text-gray-500 italic">Esperando ejecución...</div>
                @endif
            </div>
        </div>
    </div>
@endsection