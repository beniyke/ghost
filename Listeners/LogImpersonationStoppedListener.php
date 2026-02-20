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

use Activity\Activity;
use Ghost\Events\ImpersonationStoppedEvent;

class LogImpersonationStoppedListener
{
    public function handle(ImpersonationStoppedEvent $event): void
    {
        if (!class_exists(Activity::class)) {
            return;
        }

        Activity::user((int) $event->impersonator->id)
            ->subject($event->impersonated)
            ->description(sprintf(
                'Stopped impersonating user #%d (%s)',
                $event->impersonated->id,
                $event->impersonated->email ?? $event->impersonated->name ?? 'unknown'
            ))
            ->log();
    }
}
