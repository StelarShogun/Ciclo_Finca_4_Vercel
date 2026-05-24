<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use Illuminate\Console\Command;

class SchedulerHeartbeatCommand extends Command
{
    protected $signature = 'scheduler:heartbeat';

    protected $description = 'Actualiza scheduler_last_heartbeat_at en app_settings para verificar que el scheduler está activo.';

    public function handle(): int
    {
        AppSetting::setSchedulerLastHeartbeatAt(now()->toDateTimeString());

        return self::SUCCESS;
    }
}
