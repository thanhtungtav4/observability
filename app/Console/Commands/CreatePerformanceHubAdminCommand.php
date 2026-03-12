<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreatePerformanceHubAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance-hub:create-admin
        {email : Admin email address}
        {password : Admin password}
        {--name=Performance Hub Admin : Display name for the admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or promote a session-based admin user for the web dashboard.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::query()->updateOrCreate(
            ['email' => $this->argument('email')],
            [
                'name' => (string) $this->option('name'),
                'password' => $this->argument('password'),
                'email_verified_at' => now(),
                'is_admin' => true,
            ],
        );

        $this->components->info(sprintf(
            '%s admin user %s.',
            $user->wasRecentlyCreated ? 'Created' : 'Updated',
            $user->email,
        ));

        return self::SUCCESS;
    }
}
