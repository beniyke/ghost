<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * User impersonation and session management.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Ghost\Listeners;

use App\Models\Activity;
use Ghost\Events\ImpersonationStartedEvent;

class LogImpersonationStartedListener
{
    public function handle(ImpersonationStartedEvent $event): void
    {
        Activity::log(
            $event->impersonator,
            sprintf(
                'Started impersonating user #%d (%s)',
                $event->impersonated->id,
                $event->impersonated->email ?? $event->impersonated->name ?? 'unknown'
            )
        );
    }
}
