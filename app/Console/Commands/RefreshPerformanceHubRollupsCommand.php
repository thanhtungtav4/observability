<?php

namespace App\Console\Commands;

use App\Services\PerformanceHub\RefreshRollupsAction;
use Illuminate\Console\Command;

class RefreshPerformanceHubRollupsCommand extends Command
{
    protected $signature = 'performance-hub:refresh-rollups';

    protected $description = 'Refresh daily and per-deployment performance rollups.';

    public function handle(RefreshRollupsAction $refreshRollups): int
    {
        $result = $refreshRollups();

        $this->info(sprintf(
            'Refreshed %d daily rollups and %d deployment rollups.',
            $result['daily_rollups'],
            $result['deployment_rollups'],
        ));

        return self::SUCCESS;
    }
}
