<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Lock;
use App\Models\TempPassword;
use App\Models\AlertLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_buildings' => Building::where('active', true)->count(),
            'total_locks' => Lock::where('active', true)->count(),
            'active_pins' => TempPassword::active()->count(),
            'pending_alerts' => AlertLog::pending()->count(),
        ];

        $recentAlerts = AlertLog::with('lock.apartment.building')
            ->orderBy('alert_time', 'desc')
            ->limit(10)
            ->get();

        $recentPins = TempPassword::with('lock.apartment.building')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact('stats', 'recentAlerts', 'recentPins'));
    }
}
