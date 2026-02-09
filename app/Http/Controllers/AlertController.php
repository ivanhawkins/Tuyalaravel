<?php

namespace App\Http\Controllers;

use App\Models\AlertLog;
use App\Models\Building;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $query = AlertLog::with('lock.apartment.building');

        if ($request->filled('building_id')) {
            $query->whereHas('lock.apartment', function ($q) use ($request) {
                $q->where('building_id', $request->building_id);
            });
        }

        if ($request->filled('alert_code')) {
            $query->where('alert_code', $request->alert_code);
        }

        $alerts = $query->orderBy('alert_time', 'desc')->paginate(50);
        $buildings = Building::all();

        return view('alerts.index', compact('alerts', 'buildings'));
    }
}
