<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class EnsurePerformanceHubTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('services.performance_hub.internal_token');
        $providedToken = $request->bearerToken();

        if (! is_string($configuredToken) || $configuredToken === '' || ! is_string($providedToken) || ! hash_equals($configuredToken, $providedToken)) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication failed.');
        }

        return $next($request);
    }
}
