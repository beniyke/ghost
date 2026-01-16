<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * User impersonation and session management.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Ghost\Middleware;

use Closure;
use Ghost\Services\GhostManagerService;
use Helpers\Http\Request;
use Helpers\Http\Response;

class ImpersonateMiddleware
{
    public function __construct(
        private readonly GhostManagerService $ghost
    ) {
    }

    public function handle(Request $request, Response $response, Closure $next): mixed
    {
        if ($this->ghost->isGhosting()) {
            if ($this->ghost->isExpired()) {
                $this->ghost->stop();

                return $next($request, $response);
            }
        }

        return $next($request, $response);
    }
}
