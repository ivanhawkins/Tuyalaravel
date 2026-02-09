@extends('layouts.app')

@section('title', 'Nueva Cerradura')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-6">Nueva Cerradura</h2>

            <form action="{{ route('locks.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Ubicación
                    </label>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="location_type" value="apartment" class="form-radio" checked
                                onclick="toggleLocation('apartment')">
                            <span class="ml-2">Apartamento</span>
                        </label>
                        <label class="inline-flex items-center ml-6">
                            <input type="radio" name="location_type" value="building" class="form-radio"
                                onclick="toggleLocation('building')">
                            <span class="ml-2">Puerta Principal (Edificio)</span>
                        </label>
                    </div>
                </div>

                <div id="apartment_select" class="mb-4">
                    <label for="apartment_id" class="block text-gray-700 text-sm font-bold mb-2">
                        Apartamento
                    </label>
                    <select name="apartment_id" id="apartment_id"
                        class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Seleccione un apartamento</option>
                        @foreach($apartments as $apartment)
                            <option value="{{ $apartment->id }}">
                                {{ $apartment->building->name }} - {{ $apartment->number }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="building_select" class="mb-4 hidden">
                    <label for="building_id" class="block text-gray-700 text-sm font-bold mb-2">
                        Edificio
                    </label>
                    <select name="building_id" id="building_id"
                        class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Seleccione un edificio</option>
                        @foreach(\App\Models\Building::where('active', true)->get() as $building)
                            <option value="{{ $building->id }}">
                                {{ $building->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">
                        Nombre
                    </label>
                    <input type="text" name="name" id="name"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        required>
                </div>

                <div class="mb-4">
                    <label for="device_id" class="block text-gray-700 text-sm font-bold mb-2">
                        Device ID (Tuya)
                    </label>
                    <input type="text" name="device_id" id="device_id"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        required>
                </div>

                <div class="mb-6">
                    <label for="model" class="block text-gray-700 text-sm font-bold mb-2">
                        Modelo (Opcional)
                    </label>
                    <input type="text" name="model" id="model"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="flex items-center justify-end">
                    <a href="{{ route('locks.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancelar</a>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleLocation(type) {
            if (type === 'apartment') {
                document.getElementById('apartment_select').classList.remove('hidden');
                document.getElementById('building_select').classList.add('hidden');
                document.getElementById('building_id').value = '';
            } else {
                document.getElementById('apartment_select').classList.add('hidden');
                document.getElementById('building_select').classList.remove('hidden');
                document.getElementById('apartment_id').value = '';
            }
        }
    </script>
@endsection