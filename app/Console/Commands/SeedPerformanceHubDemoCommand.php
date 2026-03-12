<?php

namespace App\Console\Commands;

use App\Models\DailyMetricRollup;
use App\Models\Deployment;
use App\Models\DeploymentMetricRollup;
use App\Models\Site;
use App\Models\SyntheticRun;
use App\Models\Team;
use App\Models\VitalsEvent;
use App\Services\PerformanceHub\RefreshRollupsAction;
use Database\Seeders\PerformanceHubDemoSeeder;
use Illuminate\Console\Command;

class SeedPerformanceHubDemoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance-hub:seed-demo {--fresh : Replace any existing performance hub data before seeding the demo workspace}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed a demo performance workspace with sample sites, field events, synthetic runs, and refreshed rollups.';

    /**
     * Execute the console command.
     */
    public function handle(RefreshRollupsAction $refreshRollups): int
    {
        if ($this->hasExistingPerformanceHubData() && ! $this->option('fresh')) {
            $this->components->error('Performance Hub data already exists. Re-run with --fresh to replace it.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->purgePerformanceHubData();
            $this->components->info('Cleared existing Performance Hub records.');
        }

        $seedExitCode = $this->callSilent('db:seed', [
            '--class' => PerformanceHubDemoSeeder::class,
            '--no-interaction' => true,
        ]);

        if ($seedExitCode !== self::SUCCESS) {
            $this->components->error('Demo seed failed before rollups could be refreshed.');

            return $seedExitCode;
        }

        $rollupCounts = $refreshRollups();

        $this->components->info(sprintf(
            'Seeded %d sites, %d deployments, %d vitals events, and %d synthetic runs.',
            Site::query()->count(),
            Deployment::query()->count(),
            VitalsEvent::query()->count(),
            SyntheticRun::query()->count(),
        ));

        $this->line(sprintf(
            'Refreshed %d daily rollups and %d deployment rollups.',
            $rollupCounts['daily_rollups'],
            $rollupCounts['deployment_rollups'],
        ));

        return self::SUCCESS;
    }

    private function hasExistingPerformanceHubData(): bool
    {
        return Site::query()->exists()
            || Team::query()->exists()
            || Deployment::query()->exists()
            || VitalsEvent::query()->exists()
            || SyntheticRun::query()->exists()
            || DailyMetricRollup::query()->exists()
            || DeploymentMetricRollup::query()->exists();
    }

    private function purgePerformanceHubData(): void
    {
        Site::query()->delete();
        Team::query()->delete();
    }
}
