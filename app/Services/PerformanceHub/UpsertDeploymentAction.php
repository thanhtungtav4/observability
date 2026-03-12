<?php

namespace App\Services\PerformanceHub;

use App\Models\Deployment;
use App\Models\Site;
use Illuminate\Validation\ValidationException;

class UpsertDeploymentAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __invoke(array $payload): Deployment
    {
        $site = $this->resolveSite($payload['siteKey'] ?? null);

        return Deployment::query()->updateOrCreate(
            [
                'site_id' => $site->id,
                'environment' => $payload['environment'],
                'build_id' => $payload['buildId'],
            ],
            [
                'release_version' => $payload['releaseVersion'] ?? null,
                'git_ref' => $payload['gitRef'] ?? null,
                'git_commit_sha' => $payload['gitCommitSha'] ?? null,
                'deployed_at' => $payload['deployedAt'],
                'actor_name' => $payload['actorName'] ?? null,
                'ci_source' => $payload['ciSource'] ?? null,
                'metadata' => $payload['metadata'] ?? [],
            ],
        );
    }

    private function resolveSite(mixed $siteKey): Site
    {
        $site = Site::query()
            ->where('slug', $siteKey)
            ->first();

        if (! $site instanceof Site) {
            throw ValidationException::withMessages([
                'siteKey' => 'The selected site key is invalid.',
            ]);
        }

        return $site;
    }
}
