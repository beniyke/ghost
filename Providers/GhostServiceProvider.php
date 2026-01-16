<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * User impersonation and session management.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Ghost\Providers;

use Core\Event;
use Core\Services\ServiceProvider;
use Ghost\Events\ImpersonationStartedEvent;
use Ghost\Events\ImpersonationStoppedEvent;
use Ghost\Listeners\LogImpersonationStartedListener;
use Ghost\Listeners\LogImpersonationStoppedListener;
use Ghost\Services\GhostManagerService;

class GhostServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(GhostManagerService::class);
    }

    public function boot(): void
    {
        Event::listen(ImpersonationStartedEvent::class, LogImpersonationStartedListener::class);
        Event::listen(ImpersonationStoppedEvent::class, LogImpersonationStoppedListener::class);
    }
}
