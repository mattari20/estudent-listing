<?php

namespace App\Services;

use App\Models\MonitoringAlert;
use App\Models\Source;
use App\Models\SourceMonitoringPolicy;
use Illuminate\Support\Facades\DB;

class MonitoringAlertService
{
    public function raise(string $type, string $severity, string $message, ?Source $source = null, ?SourceMonitoringPolicy $policy = null, array $details = []): MonitoringAlert
    {
        $key = sha1($type.'|'.($source?->id ?? 0).'|'.($policy?->id ?? 0));

        return DB::transaction(function () use ($type, $severity, $message, $source, $policy, $details, $key): MonitoringAlert {
            DB::table('monitoring_alert_identities')->insertOrIgnore([
                'dedupe_key' => $key,
                'active_alert_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $identity = DB::table('monitoring_alert_identities')
                ->where('dedupe_key', $key)
                ->lockForUpdate()
                ->first();

            $active = $identity?->active_alert_id
                ? MonitoringAlert::query()->whereKey($identity->active_alert_id)->lockForUpdate()->first()
                : null;

            if ($active !== null && $active->status === 'open') {
                $active->update([
                    'last_detected_at' => now(),
                    'severity' => $severity,
                    'message' => $message,
                    'details' => $details,
                ]);

                return $active;
            }

            $alert = MonitoringAlert::create([
                'source_monitoring_policy_id' => $policy?->id,
                'source_id' => $source?->id,
                'alert_type' => $type,
                'severity' => $severity,
                'status' => 'open',
                'dedupe_key' => $key,
                'message' => $message,
                'details' => $details,
                'first_detected_at' => now(),
                'last_detected_at' => now(),
            ]);

            DB::table('monitoring_alert_identities')
                ->where('dedupe_key', $key)
                ->update(['active_alert_id' => $alert->id, 'updated_at' => now()]);

            return $alert;
        }, 3);
    }
}