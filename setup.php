<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * User impersonation and session management.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

return [
    'providers' => [
        Ghost\Providers\GhostServiceProvider::class,
    ],
    'middleware' => [
        'web' => [
            Ghost\Middleware\ImpersonateMiddleware::class,
        ],
    ],
];
