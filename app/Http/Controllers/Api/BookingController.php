<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Lock;
use App\Models\Building;
use App\Models\Apartment;
use App\Services\TuyaApiService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    /**
     * Create a new booking and generate a PIN.
     * Expected JSON input:
     * {
     *   "guest_name": "Paco",
     *   "building_name": "Hawkins Suites",
     *   "apartment_name": "1A", // or number
     *   "check_in": "2026-02-08",
     *   "check_out": "2026-02-09"
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'guest_name' => 'required|string',
            'building_name' => 'required|string',
            'apartment_name' => 'required|string',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        try {
            // 1. Resolve Lock
            $lock = $this->findLock($validated['building_name'], $validated['apartment_name']);

            if (!$lock) {
                return response()->json(['error' => 'Lock not found for specified Building/Apartment'], 404);
            }

            // 2. Calculate Times
            $earlyCheckIn = $request->boolean('early_check_in');
            $lateCheckOut = $request->boolean('late_check_out');

            $checkIn = Carbon::parse($validated['check_in']);
            if (!$earlyCheckIn) {
                $checkIn->setTime(15, 0, 0);
            }
            // If early check-in, we use the provided time in validated['check_in']

            $checkOut = Carbon::parse($validated['check_out']);
            if (!$lateCheckOut) {
                $checkOut->setTime(11, 0, 0);
            }

            // 3. Generate PIN (7 digits seems required for this lock)
            $pin = str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);

            // 4. Send to Tuya
            $tuya = $this->getTuyaService($lock);
            $passwordName = "Reserva: " . substr($validated['guest_name'], 0, 20);

            // Create temp password and get result (array with 'id')
            $tuyaResult = $tuya->createTempPassword(
                $lock->device_id,
                $pin,
                $checkIn->timestamp,
                $checkOut->timestamp,
                $passwordName
            );

            // 5. Save Booking
            $booking = Booking::create([
                'lock_id' => $lock->id,
                'guest_name' => $validated['guest_name'],
                'pin' => $pin,
                'tuya_password_id' => $tuyaResult['id'] ?? null, // Save Tuya ID
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'booking_id' => $booking->id,
                'pin' => $pin,
                'valid_from' => $checkIn->toDateTimeString(),
                'valid_to' => $checkOut->toDateTimeString(),
                'instructions' => "Tu código de acceso es {$pin}. Válido desde {$checkIn->format('d/m H:i')} hasta {$checkOut->format('d/m H:i')}.",
            ], 201);

        } catch (\Exception $e) {
            Log::error("Booking Error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create booking', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Update booking dates.
     */
    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'check_in' => 'sometimes|date',
            'check_out' => 'sometimes|date|after:check_in',
        ]);

        try {
            // Early/Late flags for update as well
            $earlyCheckIn = $request->boolean('early_check_in');
            $lateCheckOut = $request->boolean('late_check_out');

            if ($request->has('check_in')) {
                $cIn = Carbon::parse($validated['check_in']);
                if (!$earlyCheckIn) {
                    $cIn->setTime(15, 0, 0);
                }
                $booking->check_in = $cIn;
            }

            if ($request->has('check_out')) {
                $cOut = Carbon::parse($validated['check_out']);
                if (!$lateCheckOut) {
                    $cOut->setTime(11, 0, 0);
                }
                $booking->check_out = $cOut;
            }

            $booking->save();

            // Update Tuya Password (DELETE + CREATE Strategy)
            if ($booking->tuya_password_id) {
                $lock = $booking->lock;
                if ($lock) {
                    $tuya = $this->getTuyaService($lock);
                    $name = "Reserva: " . substr($booking->guest_name, 0, 20);

                    // 1. DELETE existing password
                    try {
                        $tuya->deleteTempPassword($lock->device_id, $booking->tuya_password_id);
                    } catch (\Exception $e) {
                        // Log error but proceed to create new one (maybe it was already deleted)
                        Log::warning("Failed to delete old Tuya password during update: " . $e->getMessage());
                    }

                    // 2. Validate/Regenerate PIN (Ensure 7 digits)
                    $pinToUse = $booking->pin;
                    if (strlen($pinToUse) < 7) {
                        $pinToUse = str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
                        $booking->pin = $pinToUse;
                        $booking->save();
                        Log::info("Booking {$booking->id}: Regenerated PIN to 7 digits for Tuya compliance.");
                    }

                    // 3. CREATE new password (same PIN if valid, or new one)
                    try {
                        $tuyaResult = $tuya->createTempPassword(
                            $lock->device_id,
                            $pinToUse,
                            $booking->check_in->timestamp,
                            $booking->check_out->timestamp,
                            $name
                        );

                        // 3. Update ID in DB
                        $booking->tuya_password_id = $tuyaResult['id'];
                        $booking->save();

                    } catch (\Exception $e) {
                        Log::error("Failed to re-create Tuya password during update: " . $e->getMessage());
                        // We return success for local update but with a warning? Or 500?
                        // Better to warn that sync failed.
                        return response()->json([
                            'success' => true,
                            'message' => 'Booking updated locally, but failed to sync with Tuya.',
                            'tuya_error' => $e->getMessage()
                        ], 200);
                    }
                }
            }

            return response()->json(['success' => true, 'message' => 'Booking updated and synced with Tuya.']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update booking', 'details' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $booking = Booking::findOrFail($id);

            // Delete from Tuya
            if ($booking->tuya_password_id) {
                $lock = $booking->lock;
                if ($lock) {
                    $tuya = $this->getTuyaService($lock);
                    try {
                        $tuya->deleteTempPassword($lock->device_id, $booking->tuya_password_id);
                    } catch (\Exception $e) {
                        Log::warning("Failed to delete Tuya password during booking destruction: " . $e->getMessage());
                        // Continue to delete local record even if Tuya fails (avoid orphan records)
                    }
                }
            }

            // Delete Local
            $booking->delete();

            return response()->json(['success' => true, 'message' => 'Booking deleted successfully.']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete booking', 'details' => $e->getMessage()], 500);
        }
    }

    private function findLock($buildingName, $apartmentName)
    {
        // 1. Find Building (exact match?)
        $building = Building::where('name', $buildingName)->first();
        if (!$building) {
            // Try fuzzy?
            $building = Building::where('name', 'LIKE', "%{$buildingName}%")->first();
        }

        if (!$building)
            return null;

        // 2. Find Apartment
        $apartment = Apartment::where('building_id', $building->id)
            ->where('number', $apartmentName) // "1A"
            ->first();

        if ($apartment) {
            // Return lock associated with apartment
            return Lock::where('apartment_id', $apartment->id)->first();
        }

        return null;
    }

    private function getTuyaService(Lock $lock)
    {
        $building = $lock->apartment ? $lock->apartment->building : $lock->building;
        return new TuyaApiService($building);
    }
}
