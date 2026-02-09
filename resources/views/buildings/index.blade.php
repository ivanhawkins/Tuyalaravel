@extends('layouts.app')

@section('title', 'Edificios')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Edificios</h2>
            <a href="{{ route('buildings.create') }}" class="btn-primary">
                + Nuevo Edificio
            </a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dirección</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Apartamentos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($buildings as $building)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $building->name }}</td>
                            <td class="px-6 py-4">{{ $building->address }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $building->apartments_count }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-medium {{ $building->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $building->active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="{{ route('buildings.edit', $building) }}"
                                    class="text-blue-600 hover:text-blue-900">Editar</a>
                                <form action="{{ route('buildings.destroy', $building) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900"
                                        onclick="return confirm('¿Eliminar edificio?')">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No hay edificios registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection