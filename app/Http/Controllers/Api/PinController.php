<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lock;
use App\Models\TempPassword;
use App\Services\TuyaApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PinController extends Controller
{
    /**
     * Create a new temporary PIN
     * 
     * POST /api/pins
     * {
     *   "lock_id": 1,
     *   "name": "John Doe - Reservation 12345",
     *   "effective_time": "2024-02-10 15:00:00",
     *   "invalid_time": "2024-02-15 11:00:00",
     *   "external_reference": "RES123456"
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lock_id' => 'required|exists:locks,id',
            'name' => 'required|string|max:255',
            'effective_time' => 'required|date',
            'invalid_time' => 'required| date|after:effective_time',
            'external_reference' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $lock = Lock::with('apartment.building')->findOrFail($request->lock_id);

            // Generate random 7-digit PIN
            $pin = str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT);

            // Convert times to epoch seconds
            $effectiveTime = Carbon::parse($request->effective_time)->timestamp;
            $invalidTime = Carbon::parse($request->invalid_time)->timestamp;

            // Call Tuya API
            $tuyaService = new TuyaApiService($lock->apartment->building);
            $result = $tuyaService->createTempPassword(
                $lock->device_id,
                $pin,
                $effectiveTime,
                $invalidTime,
                $request->name
            );

            // Get full details including sn
            $details = $tuyaService->getTempPassword($lock->device_id, (string) $result['id']);

            // Save to database
            $tempPassword = TempPassword::create([
                'lock_id' => $lock->id,
                'name' => $request->name,
                'tuya_password_id' => (string) $result['id'],
                'tuya_sn' => $details['sn'] ?? null,
                'pin' => encrypt($pin), // Encrypted storage
                'effective_time' => Carbon::createFromTimestamp($effectiveTime),
                'invalid_time' => Carbon::createFromTimestamp($invalidTime),
                'status' => 'created_cloud',
                'external_reference' => $request->external_reference,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $tempPassword->id,
                    'pin' => $pin, // Return once for the CRM to share with guest
                    'tuya_password_id' => $tempPassword->tuya_password_id,
                    'effective_time' => $tempPassword->effective_time,
                    'invalid_time' => $tempPassword->invalid_time,
                    'status' => $tempPassword->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create PIN: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update PIN duration (early/late checkout)
     * 
     * PATCH /api/pins/{id}
     * {
     *   "invalid_time": "2024-02-15 14:00:00"
     * }
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'invalid_time' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $tempPassword = TempPassword::with('lock.apartment.building')->findOrFail($id);

            // TODO: Implement Tuya API method to modify PIN duration
            // For now, just update local database
            $tempPassword->update([
                'invalid_time' => Carbon::parse($request->invalid_time),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PIN duration updated (local only - Tuya API update pending)',
                'data' => [
                    'id' => $tempPassword->id,
                    'invalid_time' => $tempPassword->invalid_time,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update PIN: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get PIN details
     */
    public function show(int $id)
    {
        try {
            $tempPassword = TempPassword::with('lock')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $tempPassword->id,
                    'lock_id' => $tempPassword->lock_id,
                    'name' => $tempPassword->name,
                    'effective_time' => $tempPassword->effective_time,
                    'invalid_time' => $tempPassword->invalid_time,
                    'status' => $tempPassword->status,
                    'is_active' => $tempPassword->isActive(),
                    'external_reference' => $tempPassword->external_reference,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'PIN not found'
            ], 404);
        }
    }

    /**
     * Delete/revoke a PIN
     * 
     * DELETE /api/pins/{id}
     */
    public function destroy(int $id)
    {
        try {
            $tempPassword = TempPassword::with('lock.apartment.building')->findOrFail($id);

            // Don't try to delete if already expired
            if ($tempPassword->isExpired()) {
                $tempPassword->update(['status' => 'expired']);
                return response()->json([
                    'success' => true,
                    'message' => 'PIN already expired',
                ]);
            }

            // Call Tuya API to delete
            $tuyaService = new TuyaApiService($tempPassword->lock->apartment->building);
            $tuyaService->deleteTempPassword(
                $tempPassword->lock->device_id,
                $tempPassword->tuya_password_id
            );

            // Update local status
            $tempPassword->update(['status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'PIN revoked successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to revoke PIN: ' . $e->getMessage()
            ], 500);
        }
    }
}
