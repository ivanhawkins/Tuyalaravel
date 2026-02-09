<?php

namespace App\Http\Controllers;

use App\Models\Lock;
use App\Models\Building;
use App\Services\TuyaApiService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PlaygroundController extends Controller
{
    public function index()
    {
        $locks = Lock::with('apartment.building', 'building')->get();
        return view('playground.index', compact('locks'));
    }

    public function run(Request $request)
    {
        $request->validate([
            'lock_id' => 'required|exists:locks,id',
            'action' => 'required|string',
        ]);

        $lock = Lock::findOrFail($request->lock_id);
        $action = $request->action;
        $result = [];
        $error = null;

        try {
            $building = $lock->apartment ? $lock->apartment->building : $lock->building;
            if (!$building) {
                throw new \Exception('Lock has no building assigned.');
            }

            $tuya = new TuyaApiService($building);

            switch ($action) {
                case 'create_temp_password':
                    $name = $request->input('name', 'Test Code');
                    $pin = $request->input('pin', '123456');
                    // Default to now + 1 hour
                    $start = now()->addMinute()->timestamp;
                    $end = now()->addHour()->timestamp;

                    $result = $tuya->createTempPassword(
                        $lock->device_id,
                        $pin,
                        $start,
                        $end,
                        $name
                    );

                    // Add context to result
                    $result['_context'] = [
                        'pin' => $pin,
                        'name' => $name,
                        'start' => date('Y-m-d H:i:s', $start),
                        'end' => date('Y-m-d H:i:s', $end),
                    ];
                    break;

                case 'get_temp_passwords':
                    $result = $tuya->getTempPasswords($lock->device_id);
                    break;

                case 'get_lock_details':
                    // Just test fetching direct device details if we had that method, 
                    // otherwise just return lock info
                    $result = [
                        'device_id' => $lock->device_id,
                        'name' => $lock->name,
                        'status_data' => $lock->status_data
                    ];
                    break;

                default:
                    throw new \Exception("Unknown action: $action");
            }

        } catch (\Exception $e) {
            $error = $e->getMessage() . "\n" . $e->getTraceAsString();
        }

        $locks = Lock::with('apartment.building', 'building')->get();
        return view('playground.index', compact('locks', 'result', 'error', 'action', 'lock'));
    }
}
