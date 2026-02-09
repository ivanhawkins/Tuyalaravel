<?php

namespace App\Jobs;

use App\Models\Lock;
use App\Models\TempPassword;
use App\Models\UnlockLog;
use App\Services\TuyaApiService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncUnlockLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('Starting unlock logs sync');

        $locks = Lock::with('apartment.building')
            ->where('active', true)
            ->get();

        foreach ($locks as $lock) {
            try {
                $this->syncLockLogs($lock);
            } catch (\Exception $e) {
                Log::error("Failed to sync logs for lock {$lock->id}: " . $e->getMessage());
            }
        }

        Log::info('Unlock logs sync completed');
    }

    private function syncLockLogs(Lock $lock): void
    {
        // Get logs from last 7 days
        $startTime = Carbon::now()->subDays(7)->timestamp;
        $endTime = Carbon::now()->timestamp;

        $tuyaService = new TuyaApiService($lock->apartment->building);
        $result = $tuyaService->getUnlockRecords(
            $lock->device_id,
            $startTime,
            $endTime
        );

        if (empty($result['logs'])) {
            return;
        }

        foreach ($result['logs'] as $logData) {
            $unlockedAt = Carbon::createFromTimestampMs($logData['update_time']);

            // Avoid duplicates
            $exists = UnlockLog::where('lock_id', $lock->id)
                ->where('unlocked_at', $unlockedAt)
                ->exists();

            if ($exists) {
                continue;
            }

            // Try to correlate with temp password
            $tempPasswordId = null;
            if (isset($logData['status']['code']) && $logData['status']['code'] === 'unlock_temporary') {
                $sn = $logData['status']['value'] ?? null;
                if ($sn) {
                    $tempPassword = TempPassword::where('lock_id', $lock->id)
                        ->where('tuya_sn', $sn)
                        ->first();
                    $tempPasswordId = $tempPassword?->id;
                }
            }

            UnlockLog::create([
                'lock_id' => $lock->id,
                'temp_password_id' => $tempPasswordId,
                'unlock_method' => $logData['status']['code'] ?? 'unknown',
                'unlock_value' => $logData['status']['value'] ?? null,
                'nick_name' => $logData['nick_name'] ?? null,
                'unlocked_at' => $unlockedAt,
                'raw_data' => $logData,
            ]);
        }

        $lock->update(['last_sync' => now()]);
        Log::info("Synced {$lock->id} ({$lock->name}): " . count($result['logs']) . " logs");
    }
}
