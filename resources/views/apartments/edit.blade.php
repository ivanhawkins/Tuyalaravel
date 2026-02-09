@extends('layouts.app')

@section('title', 'Editar Apartamento')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-6">Editar Apartamento</h2>

            <form action="{{ route('apartments.update', $apartment) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="building_id" class="block text-gray-700 text-sm font-bold mb-2">
                        Edificio
                    </label>
                    <select name="building_id" id="building_id"
                        class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        required>
                        <option value="">Seleccione un edificio</option>
                        @foreach($buildings as $building)
                            <option value="{{ $building->id }}" {{ $apartment->building_id == $building->id ? 'selected' : '' }}>
                                {{ $building->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="number" class="block text-gray-700 text-sm font-bold mb-2">
                        Número / Puerta
                    </label>
                    <input type="text" name="number" id="number" value="{{ $apartment->number }}"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        required>
                </div>

                <div class="mb-4">
                    <label for="floor" class="block text-gray-700 text-sm font-bold mb-2">
                        Planta (Opcional)
                    </label>
                    <input type="text" name="floor" id="floor" value="{{ $apartment->floor }}"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="active" value="1" {{ $apartment->active ? 'checked' : '' }}
                            class="form-checkbox h-5 w-5 text-blue-600">
                        <span class="ml-2 text-gray-700">Activo</span>
                    </label>
                </div>

                <div class="flex items-center justify-end">
                    <a href="{{ route('apartments.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancelar</a>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection