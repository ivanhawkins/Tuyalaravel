<?php

namespace App\Http\Controllers;

use App\Models\Lock;
use App\Models\Apartment;
use Illuminate\Http\Request;

class LockController extends Controller
{
    public function index()
    {
        $locks = Lock::with(['apartment.building', 'building'])->get();
        return view('locks.index', compact('locks'));
    }

    public function create()
    {
        $apartments = Apartment::with('building')
            ->where('active', true)
            ->get();
        return view('locks.create', compact('apartments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'apartment_id' => 'nullable|exists:apartments,id',
            'building_id' => 'nullable|exists:buildings,id',
            'device_id' => 'required|string|unique:locks,device_id',
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
        ]);

        if (empty($validated['apartment_id']) && empty($validated['building_id'])) {
            return back()->withErrors(['apartment_id' => 'Debe asignar la cerradura a un apartamento o edificio.']);
        }

        Lock::create($validated);

        return redirect()->route('locks.index')
            ->with('success', 'Cerradura creada correctamente');
    }

    public function edit(Lock $lock)
    {
        $apartments = Apartment::with('building')
            ->where('active', true)
            ->get();
        // Allow creating locks for all active buildings
        $buildings = \App\Models\Building::where('active', true)->get();

        return view('locks.edit', compact('lock', 'apartments', 'buildings'));
    }

    public function update(Request $request, Lock $lock)
    {
        $validated = $request->validate([
            'apartment_id' => 'nullable|exists:apartments,id',
            'building_id' => 'nullable|exists:buildings,id',
            'device_id' => 'required|string|unique:locks,device_id,' . $lock->id,
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        $lock->update($validated);

        return redirect()->route('locks.index')
            ->with('success', 'Cerradura actualizada correctamente');
    }

    public function destroy(Lock $lock)
    {
        $lock->delete();
        return redirect()->route('locks.index')
            ->with('success', 'Cerradura eliminada correctamente');
    }

    // Code Management Methods

    private function getTuyaService(Lock $lock)
    {
        $building = $lock->apartment ? $lock->apartment->building : $lock->building;
        if (!$building) {
            throw new \Exception('La cerradura no está asociada a ningún edificio configurado.');
        }
        return new \App\Services\TuyaApiService($building);
    }

    public function codes(Lock $lock)
    {
        try {
            $tuya = $this->getTuyaService($lock);
            $data = $tuya->getTempPasswords($lock->device_id);
            $apiCodes = $data['list'] ?? [];

            // Get local codes to match PINs
            $localCodes = \App\Models\LockCode::where('lock_id', $lock->id)->get()->keyBy('tuya_password_id');

            // Merge API data with local PINs
            $codes = collect($apiCodes)->map(function ($code) use ($localCodes) {
                // If API returns ID as int, convert to string for key match
                $id = (string) ($code['id'] ?? '');
                $local = $localCodes->get($id);
                $code['pin_visible'] = $local ? $local->pin : '******';
                return $code;
            });

            return view('locks.codes', compact('lock', 'codes'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al obtener códigos: ' . $e->getMessage());
        }
    }

    public function storeCode(Request $request, Lock $lock)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'password' => 'required|numeric|digits:7', // Strict 7 digits as requested
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            $tuya = $this->getTuyaService($lock);
            $effectiveTime = strtotime($validated['start_date']);
            $invalidTime = strtotime($validated['end_date']);

            // Fix: Re-parse to ensure 15:00 / 11:00 times are set even if input is just date
            $startDate = \Carbon\Carbon::parse($validated['start_date'])->setTime(15, 0, 0);
            $endDate = \Carbon\Carbon::parse($validated['end_date'])->setTime(11, 0, 0);
            $effectiveTime = $startDate->timestamp;
            $invalidTime = $endDate->timestamp;

            // Uniqueness Check
            $exists = \App\Models\LockCode::where('lock_id', $lock->id)
                ->whereDate('start_date', $startDate->toDateString())
                ->exists();

            if ($exists) {
                return back()->with('error', 'Error: Ya existe un código para esta fecha de entrada (' . $startDate->toDateString() . ').');
            }

            $result = $tuya->createTempPassword(
                $lock->device_id,
                $validated['password'],
                $effectiveTime,
                $invalidTime,
                $validated['name']
            );

            // Save locally to keep the PIN visible
            \App\Models\LockCode::create([
                'lock_id' => $lock->id,
                'tuya_password_id' => $result['id'] ?? null, // Tuya returns 'id'
                'name' => $validated['name'],
                'pin' => $validated['password'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ]);

            return redirect()->route('locks.codes', $lock)
                ->with('success', 'Código creado correctamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al crear código: ' . $e->getMessage());
        }
    }

    public function updateCode(Request $request, Lock $lock, string $codeId)
    {
        // Update logic disabled by user request.
        // Frontend now only shows PIN.
        return back()->with('error', 'La modificación de códigos está desactivada.');
    }

    public function early(Lock $lock, string $codeId)
    {
        return $this->atomicReschedule($lock, $codeId, function ($startDate, $endDate) {
            return [
                'start' => now(), // Start NOW
                'end' => $endDate // Keep same end
            ];
        });
    }

    public function late(Lock $lock, string $codeId)
    {
        return $this->atomicReschedule($lock, $codeId, function ($startDate, $endDate) {
            // End at 14:00 on the same end day
            $newEnd = \Carbon\Carbon::parse($endDate)->setTime(14, 0, 0);
            return [
                'start' => $startDate, // Keep same start
                'end' => $newEnd
            ];
        });
    }

    /**
     * Helper to atomically delete and recreate a code with new dates.
     */
    private function atomicReschedule(Lock $lock, string $codeId, callable $dateModifier)
    {
        try {
            // 1. Find Local Code
            $localCode = \App\Models\LockCode::where('lock_id', $lock->id)
                ->where('tuya_password_id', $codeId)
                ->firstOrFail();

            // 2. Calculate New Dates
            $dates = $dateModifier($localCode->start_date, $localCode->end_date);
            $newStart = \Carbon\Carbon::parse($dates['start']);
            $newEnd = \Carbon\Carbon::parse($dates['end']);

            // 3. Delete Old (Tuya + Local)
            $tuya = $this->getTuyaService($lock);
            try {
                $tuya->deleteTempPassword($lock->device_id, $codeId);
            } catch (\Exception $e) {
                // Ignore delete errors (maybe already gone)
            }
            $localCode->delete();

            // 4. Create New (Tuya)
            // Ensure 7 digits PIN
            $pin = $localCode->pin;
            if (strlen($pin) < 7) {
                $pin = str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            }

            $effectiveTime = $newStart->timestamp;
            $invalidTime = $newEnd->timestamp;

            $result = $tuya->createTempPassword(
                $lock->device_id,
                $pin,
                $effectiveTime,
                $invalidTime,
                $localCode->name
            );

            // 5. Create New (Local)
            \App\Models\LockCode::create([
                'lock_id' => $lock->id,
                'tuya_password_id' => $result['id'],
                'name' => $localCode->name,
                'pin' => $pin,
                'start_date' => $newStart,
                'end_date' => $newEnd,
            ]);

            return back()->with('success', 'Horario actualizado correctamente.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al reprogramar: ' . $e->getMessage());
        }
    }

    public function destroyCode(Lock $lock, string $codeId)
    {
        try {
            $tuya = $this->getTuyaService($lock);
            $tuya->deleteTempPassword($lock->device_id, $codeId);

            // Delete locally
            \App\Models\LockCode::where('lock_id', $lock->id)
                ->where('tuya_password_id', $codeId)
                ->delete();

            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Código eliminado correctamente']);
            }

            return redirect()->route('locks.codes', $lock)
                ->with('success', 'Código eliminado correctamente');
        } catch (\Exception $e) {
            // If error is "password has expired", consider it successful and delete locally
            if (str_contains($e->getMessage(), '2304') || str_contains($e->getMessage(), 'expired')) {
                \App\Models\LockCode::where('lock_id', $lock->id)
                    ->where('tuya_password_id', $codeId)
                    ->delete();

                if (request()->wantsJson()) {
                    return response()->json(['success' => true, 'message' => 'Código eliminado (ya estaba expirado)']);
                }

                return redirect()->route('locks.codes', $lock)
                    ->with('success', 'Código eliminado (ya estaba expirado)');
            }

            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return back()->with('error', 'Error al eliminar código: ' . $e->getMessage());
        }
    }

    public function status(Lock $lock)
    {
        $lock->load('apartment.building', 'tempPasswords');
        return view('locks.status', compact('lock'));
    }

    public function logs(Lock $lock)
    {
        $logs = $lock->unlockLogs()
            ->with('tempPassword')
            ->orderBy('unlocked_at', 'desc')
            ->paginate(50);

        return view('locks.logs', compact('lock', 'logs'));
    }
}
