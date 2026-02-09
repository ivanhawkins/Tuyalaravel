@extends('layouts.app')

@section('title', 'Cerraduras')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Cerraduras</h2>
            <a href="{{ route('locks.create') }}" class="btn-primary">
                + Nueva Cerradura
            </a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ubicación</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batería</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($locks ?? [] as $lock)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $lock->name }}</td>
                            <td class="px-6 py-4">{{ $lock->location_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-medium {{ $lock->is_online ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $lock->is_online ? 'Online' : 'Offline' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $lock->battery }}%</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="{{ route('locks.codes', ['lock' => $lock->id]) }}"
                                    class="text-indigo-600 hover:text-indigo-900">Códigos</a>
                                <a href="{{ route('locks.edit', ['lock' => $lock->id]) }}"
                                    class="text-blue-600 hover:text-blue-900">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No hay cerraduras registradas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection