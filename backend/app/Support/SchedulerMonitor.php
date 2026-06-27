<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Log;

class SchedulerMonitor
{
    public static function track(Event $event, string $commandName, string $slug): Event
    {
        $logPath = storage_path('logs/scheduler.log');

        return $event
            ->appendOutputTo($logPath)
            ->before(function () use ($slug, $commandName): void {
                $at = now()->toDateTimeString();
                AppSetting::setSchedulerCommandValue($slug, 'started_at', $at);
                AppSetting::setSchedulerCommandValue($slug, 'status', 'running');
                Log::channel('scheduler')->info("Scheduled command started: {$commandName}");
            })
            ->onSuccess(function () use ($slug, $commandName): void {
                $at = now()->toDateTimeString();
                AppSetting::setSchedulerCommandValue($slug, 'success_at', $at);
                AppSetting::setSchedulerCommandValue($slug, 'status', 'success');
                Log::channel('scheduler')->info("Scheduled command succeeded: {$commandName}");
            })
            ->onFailure(function () use ($slug, $commandName): void {
                $at = now()->toDateTimeString();
                AppSetting::setSchedulerCommandValue($slug, 'failure_at', $at);
                AppSetting::setSchedulerCommandValue($slug, 'status', 'failure');
                Log::channel('scheduler')->error("Scheduled command failed: {$commandName}");
            });
    }
}
