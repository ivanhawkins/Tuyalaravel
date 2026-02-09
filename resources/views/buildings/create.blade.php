@extends('layouts.app')

@section('title', 'Crear Edificio')

@section('content')
    <div class="max-w-2xl space-y-6">
        <h2 class="text-2xl font-bold text-gray-900">Nuevo Edificio</h2>

        <div class="bg-white rounded-lg shadow p-6">
            <form action="{{ route('buildings.store') }}" method="POST">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                        <textarea id="address" name="address" rows="2"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('address') }}</textarea>
                    </div>

                    <div>
                        <label for="tuya_client_id" class="block text-sm font-medium text-gray-700 mb-2">Tuya Client ID
                            *</label>
                        <input type="text" id="tuya_client_id" name="tuya_client_id" value="{{ old('tuya_client_id') }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="tuya_client_secret" class="block text-sm font-medium text-gray-700 mb-2">Tuya Client
                            Secret *</label>
                        <input type="password" id="tuya_client_secret" name="tuya_client_secret"
                            value="{{ old('tuya_client_secret') }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="submit" class="btn-primary">
                            Crear Edificio
                        </button>
                        <a href="{{ route('buildings.index') }}" class="btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection