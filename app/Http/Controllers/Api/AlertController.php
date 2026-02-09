<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlertLog;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * Get all alerts
     * 
     * GET /api/alerts?lock_id=1&code=doorbell
     */
    public function index(Request $request)
    {
        $query = AlertLog::with('lock.apartment.building');

        if ($request->has('lock_id')) {
            $query->where('lock_id', $request->lock_id);
        }

        if ($request->has('code')) {
            $query->where('alert_code', $request->code);
        }

        $alerts = $query->orderBy('alert_time', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $alerts->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'lock_id' => $alert->lock_id,
                    'device_id' => $alert->lock->device_id,
                    'apartment' => $alert->lock->apartment->number,
                    'building' => $alert->lock->apartment->building->name,
                    'alert_code' => $alert->alert_code,
                    'alert_time' => $alert->alert_time,
                    'notified' => $alert->notified,
                ];
            }),
            'meta' => [
                'total' => $alerts->count(),
            ],
        ]);
    }

    /**
     * Get pending (unnotified) alerts
     * 
     * GET /api/alerts/pending
     */
    public function pending()
    {
        $alerts = AlertLog::pending()
            ->with('lock.apartment.building')
            ->get();

        // Mark as notified
        AlertLog::whereIn('id', $alerts->pluck('id'))->update([
            'notified' => true,
            'notified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $alerts->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'lock_id' => $alert->lock_id,
                    'device_id' => $alert->lock->device_id,
                    'apartment' => $alert->lock->apartment->number,
                    'building' => $alert->lock->apartment->building->name,
                    'alert_code' => $alert->alert_code,
                    'alert_time' => $alert->alert_time,
                ];
            }),
            'meta' => [
                'total' => $alerts->count(),
                'marked_as_notified' => true,
            ],
        ]);
    }
}
