@extends('layouts.app')

@section('title', 'Apartamentos')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Apartamentos</h2>
            <a href="{{ route('apartments.create') }}" class="btn-primary">
                + Nuevo Apartamento
            </a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Edificio</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($apartments ?? [] as $apartment)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $apartment->name }}</td>
                            <td class="px-6 py-4">{{ $apartment->building->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                {{-- <a href="{{ route('apartments.edit', $apartment) }}"
                                    class="text-blue-600 hover:text-blue-900">Editar</a> --}}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">No hay apartamentos registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection