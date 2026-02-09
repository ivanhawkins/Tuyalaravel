@extends('layouts.app')

@section('title', 'Gestión de Códigos')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Códigos de Acceso</h2>
                <p class="text-gray-600">Gestión para: {{ $lock->name }} ({{ $lock->location_name }})</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="openModal()" class="btn-primary">
                    + Nueva Reserva
                </button>
                <a href="{{ route('locks.index') }}" class="btn-secondary">
                    Volver
                </a>
            </div>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <!-- Modal (Hidden by default) -->
        <div id="reservationModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal()"></div>

                <!-- Modal panel -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Generar Reserva / Código
                                </h3>
                                <div class="mt-2">
                                    <form id="reservationForm" action="{{ route('locks.codes.store', $lock) }}" method="POST" class="space-y-4">
                                        @csrf
                                        <div>
                                            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                                            <input type="text" name="name" id="name" required placeholder="Ej: Invitado"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label for="password" class="block text-sm font-medium text-gray-700">Código (7 dígitos)</label>
                                            <div class="flex space-x-2">
                                                <input type="number" name="password" id="password" required min="1000000" max="9999999"
                                                    placeholder="Ej: 1234567"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                <button type="button" onclick="generateRandomPin()" class="text-xs text-blue-600 hover:text-blue-800">Generar</button>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label for="start_date" class="block text-sm font-medium text-gray-700">Inicio (15:00)</label>
                                                <input type="date" name="start_date" id="start_date" required value="{{ date('Y-m-d') }}"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label for="end_date" class="block text-sm font-medium text-gray-700">Fin (11:00)</label>
                                                <input type="date" name="end_date" id="end_date" required
                                                    value="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                        </div>
                                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                Guardar Reserva
                                            </button>
                                            <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                                Cancelar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Codes List -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Códigos Activos</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contraseña</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Válido Desde</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Válido Hasta</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($codes as $code)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $code['name'] ?? 'Sin Nombre' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-mono">
                                {{ $code['pin_visible'] ?? '******' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ isset($code['effective_time']) ? date('d/m/Y H:i', $code['effective_time']) : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ isset($code['invalid_time']) ? date('d/m/Y H:i', $code['invalid_time']) : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <button
                                        onclick="openModalWithData('{{ $code['pin_visible'] ?? '' }}', '{{ $code['name'] ?? '' }}', '{{ route('locks.codes.destroy', [$lock, $code['id']]) }}')"
                                        class="text-blue-600 hover:text-blue-900">
                                        Modificar
                                    </button>
                                    <form action="{{ route('locks.codes.destroy', [$lock, $code['id']]) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('¿Eliminar código?')">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No hay códigos registrados</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endsection

    @push('scripts')
    <script>
        function openModal() {
            document.getElementById('reservationModal').classList.remove('hidden');
            // Clear inputs for new reservation
            document.getElementById('name').value = '';
            document.getElementById('password').value = '';
            // Reset dates to default
            document.getElementById('start_date').value = new Date().toISOString().split('T')[0];
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('end_date').value = tomorrow.toISOString().split('T')[0];
        }

        function openModalWithData(pin, name, deleteUrl) {
            // 1. Open Modal first (instant feedback)
            document.getElementById('reservationModal').classList.remove('hidden');

            // Pre-fill data
            if (pin && pin !== '******') {
                document.getElementById('password').value = pin;
            } else {
                document.getElementById('password').value = '';
            }

            if (name) {
                document.getElementById('name').value = name;
            }

            // Focus on start date as that's what user likely wants to change
            document.getElementById('start_date').focus();

            // 2. Delete the original reservation in the background
            if (deleteUrl) {
                console.log('Deleting original reservation in background:', deleteUrl);
                
                fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (response.ok) {
                        console.log('Reserva original eliminada con éxito (background).');
                        // Optional: Visual feedback like fading out the row could be added here, 
                        // but user asked to keep the screen as is.
                        // We could add a small toast notification?
                    } else {
                        console.error('Error al eliminar reserva en segundo plano:', response.statusText);
                    }
                })
                .catch(error => {
                    console.error('Error de red al eliminar:', error);
                });
            }
        }

        function closeModal() {
            document.getElementById('reservationModal').classList.add('hidden');
        }

        function generateRandomPin() {
            const pin = Math.floor(1000000 + Math.random() * 9000000);
            document.getElementById('password').value = pin;
        }

        // Close on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                closeModal();
            }
        });
    </script>
@endpush