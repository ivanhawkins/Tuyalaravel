<?php

namespace App\Jobs;

use App\Models\Lock;
use App\Models\AlertLog;
use App\Services\TuyaApiService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('Starting alerts sync');

        $locks = Lock::with('apartment.building')
            ->where('active', true)
            ->get();

        foreach ($locks as $lock) {
            try {
                $this->syncLockAlerts($lock);
            } catch (\Exception $e) {
                Log::error("Failed to sync alerts for lock {$lock->id}: " . $e->getMessage());
            }
        }

        Log::info('Alerts sync completed');
    }

    private function syncLockAlerts(Lock $lock): void
    {
        $tuyaService = new TuyaApiService($lock->apartment->building);

        // Sync each alert type separately
        $alertCodes = ['doorbell', 'alarm_lock', 'hijack'];

        foreach ($alertCodes as $code) {
            try {
                $result = $tuyaService->getAlertRecords(
                    $lock->device_id,
                    [$code]
                );

                if (empty($result['logs'])) {
                    continue;
                }

                foreach ($result['logs'] as $alertData) {
                    // Tuya alert_time format may vary, handle accordingly
                    $alertTime = isset($alertData['active_time'])
                        ? Carbon::createFromTimestamp($alertData['active_time'])
                        : now();

                    // Avoid duplicates (check by time + code)
                    $exists = AlertLog::where('lock_id', $lock->id)
                        ->where('alert_code', $code)
                        ->where('alert_time', $alertTime)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    AlertLog::create([
                        'lock_id' => $lock->id,
                        'alert_code' => $code,
                        'alert_time' => $alertTime,
                        'raw_data' => $alertData,
                        'notified' => false,
                    ]);
                }

                Log::info("Synced alerts for lock {$lock->id} ({$code}): " . count($result['logs']));

            } catch (\Exception $e) {
                Log::warning("Failed to sync {$code} alerts for lock {$lock->id}: " . $e->getMessage());
            }
        }
    }
}
