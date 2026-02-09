<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lock;
use App\Services\TuyaApiService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LockController extends Controller
{
    /**
     * Get lock status (battery, online, etc.)
     * 
     * GET /api/locks/{id}/status
     */
    public function status(int $id)
    {
        try {
            $lock = Lock::with('apartment.building')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'lock_id' => $lock->id,
                    'device_id' => $lock->device_id,
                    'name' => $lock->name,
                    'apartment' => $lock->apartment->number,
                    'building' => $lock->apartment->building->name,
                    'active' => $lock->active,
                    'online' => $lock->is_online,
                    'battery_level' => $lock->battery_level,
                    'last_sync' => $lock->last_sync,
                    'status_data' => $lock->status_data,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Lock not found'
            ], 404);
        }
    }

    /**
     * Get unlock logs for a lock
     * 
     * GET /api/locks/{id}/logs?start_date=2024-01-01&end_date=2024-01-31
     */
    public function logs(Request $request, int $id)
    {
        try {
            $lock = Lock::with('apartment.building')->findOrFail($id);

            $startDate = $request->input('start_date', Carbon::now()->subDays(7)->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

            $logs = $lock->unlockLogs()
                ->whereBetween('unlocked_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ])
                ->with('tempPassword')
                ->orderBy('unlocked_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $logs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'unlock_method' => $log->unlock_method,
                        'unlocked_at' => $log->unlocked_at,
                        'guest_name' => $log->nick_name,
                        'temp_password_id' => $log->temp_password_id,
                        'external_reference' => $log->tempPassword->external_reference ?? null,
                    ];
                }),
                'meta' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total' => $logs->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch logs: ' . $e->getMessage()
            ], 500);
        }
    }
}
