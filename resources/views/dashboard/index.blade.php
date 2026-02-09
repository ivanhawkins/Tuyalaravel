@extends('layouts.app')

@section('title', 'Dashboard - Tuya Lock Manager')

@section('content')
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <h2 class="text-3xl font-bold text-gray-900">Panel de Control</h2>
            <div class="text-sm text-gray-500">
                {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>

        <!-- Buildings Loop -->
        @php
            $buildings = \App\Models\Building::with([
                'apartments.lock.bookings' => function ($q) {
                    // Get active or future bookings
                    $q->where('status', 'active')
                        ->where('check_out', '>', now())
                        ->orderBy('check_in');
                },
                'locks.bookings' => function ($q) {
                    $q->where('status', 'active')
                        ->where('check_out', '>', now())
                        ->orderBy('check_in');
                }
            ])->where('active', true)->get();
        @endphp

        @foreach($buildings as $building)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-gray-100 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">{{ $building->name }}</h3>
                    <span class="text-sm text-gray-500">{{ $building->address }}</span>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {{-- 1. Main Entrances (Locks directly assigned to Building) --}}
                    @foreach($building->locks as $lock)
                        @include('dashboard.partials.lock-card', ['lock' => $lock, 'title' => 'Puerta Principal'])
                    @endforeach

                    {{-- 2. Apartments --}}
                    @foreach($building->apartments as $apartment)
                        @if($apartment->lock)
                            @include('dashboard.partials.lock-card', ['lock' => $apartment->lock, 'title' => 'Apt ' . $apartment->number])
                        @else
                            {{-- Apartment without lock --}}
                            <div class="bg-gray-50 rounded border border-gray-200 p-4 opacity-50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-bold text-gray-700">Apt {{ $apartment->number }}</h4>
                                        <p class="text-xs text-red-500">Sin cerradura asignada</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach

        @if($buildings->isEmpty())
            <div class="text-center py-12">
                <p class="text-gray-500">No hay edificios configurados.</p>
                <a href="{{ route('buildings.create') }}" class="text-blue-600 hover:underline mt-2 inline-block">crear uno
                    ahora</a>
            </div>
        @endif
    </div>
@endsection