@extends('layouts.app')

@section('title', 'Nuevo Apartamento')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-6">Nuevo Apartamento</h2>

            <form action="{{ route('apartments.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label for="building_id" class="block text-gray-700 text-sm font-bold mb-2">
                        Edificio
                    </label>
                    <select name="building_id" id="building_id"
                        class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        required>
                        <option value="">Seleccione un edificio</option>
                        @foreach($buildings as $building)
                            <option value="{{ $building->id }}">
                                {{ $building->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="number" class="block text-gray-700 text-sm font-bold mb-2">
                        Número / Puerta
                    </label>
                    <input type="text" name="number" id="number"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        required placeholder="Ej: 1A">
                </div>

                <div class="mb-6">
                    <label for="floor" class="block text-gray-700 text-sm font-bold mb-2">
                        Planta (Opcional)
                    </label>
                    <input type="text" name="floor" id="floor"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        placeholder="Ej: 1">
                </div>

                <div class="flex items-center justify-end">
                    <a href="{{ route('apartments.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancelar</a>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection