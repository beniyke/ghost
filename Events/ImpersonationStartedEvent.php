<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * User impersonation and session management.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Ghost\Events;

use App\Models\User;

class ImpersonationStartedEvent
{
    public function __construct(
        public readonly User $impersonator,
        public readonly User $impersonated
    ) {
    }
}
