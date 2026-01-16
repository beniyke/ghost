<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * User impersonation and session management.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Ghost;

use App\Models\User;
use Ghost\Services\GhostManagerService;

class Ghost
{
    public static function impersonate(User $user): bool
    {
        return resolve(GhostManagerService::class)->impersonate($user);
    }

    public static function stop(): bool
    {
        return resolve(GhostManagerService::class)->stop();
    }

    public static function isGhosting(): bool
    {
        return resolve(GhostManagerService::class)->isGhosting();
    }

    public static function getImpersonator(): ?User
    {
        return resolve(GhostManagerService::class)->getImpersonator();
    }

    public static function getImpersonated(): ?User
    {
        return resolve(GhostManagerService::class)->getImpersonated();
    }

    public static function isExpired(): bool
    {
        return resolve(GhostManagerService::class)->isExpired();
    }
}
