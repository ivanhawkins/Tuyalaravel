<div class="bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow p-4 relative">
    <div class="flex justify-between items-start mb-2">
        <h4 class="font-bold text-lg text-gray-800">{{ $title }}</h4>
        <div class="flex items-center space-x-2">
            {{-- Battery --}}
            @if(isset($lock->battery))
                <span class="text-xs font-semibold {{ $lock->battery < 20 ? 'text-red-500' : 'text-green-600' }}">
                    <i class="fas fa-battery-half"></i> {{ $lock->battery }}%
                </span>
            @endif
            {{-- Status --}}
            <span class="h-3 w-3 rounded-full {{ $lock->is_online ? 'bg-green-500' : 'bg-red-500' }}"
                title="{{ $lock->is_online ? 'Online' : 'Offline' }}"></span>
        </div>
    </div>

    <p class="text-xs text-gray-500 mb-3">{{ $lock->name }}</p>

    {{-- Booking Status --}}
    @php
        $currentBooking = $lock->bookings->first(function ($b) {
            return $b->check_in <= now() && $b->check_out > now();
        });

        $nextBooking = $lock->bookings->first(function ($b) {
            return $b->check_in > now();
        });
    @endphp

    <div class="mb-4">
        @if($currentBooking)
            <div class="bg-green-50 border border-green-200 rounded p-2 mb-2">
                <p class="text-xs text-green-800 font-semibold uppercase">Ocupado</p>
                <p class="text-sm font-bold">{{ $currentBooking->guest_name }}</p>
                <p class="text-xs text-gray-600">Salida: {{ $currentBooking->formatted_check_out }}</p>
                <div class="mt-1 flex justify-between items-center">
                    <code class="bg-white px-2 py-0.5 rounded border text-xs font-mono">{{ $currentBooking->pin }}</code>
                </div>
            </div>
        @elseif($nextBooking)
            <div class="bg-blue-50 border border-blue-200 rounded p-2 mb-2">
                <p class="text-xs text-blue-800 font-semibold uppercase">Próxima Entrada</p>
                <p class="text-sm font-bold">{{ $nextBooking->guest_name }}</p>
                <p class="text-xs text-gray-600">Entrada: {{ $nextBooking->formatted_check_in }}</p>
                <div class="mt-1 flex justify-between items-center">
                    <code class="bg-white px-2 py-0.5 rounded border text-xs font-mono">{{ $nextBooking->pin }}</code>
                </div>
            </div>
        @else
            <div class="bg-gray-50 border border-gray-200 rounded p-2 mb-2">
                <p class="text-xs text-gray-500 font-semibold uppercase">Disponible</p>
                <p class="text-xs text-gray-400">Sin reservas próximas</p>
            </div>
        @endif
    </div>

    <div class="mt-auto pt-2 border-t border-gray-100 flex justify-between">
        <a href="{{ route('locks.codes', $lock) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Ver
            Códigos</a>
        <a href="{{ route('locks.edit', $lock) }}" class="text-xs text-gray-400 hover:text-gray-600">Config</a>
    </div>
</div>