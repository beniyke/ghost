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
    /**
     * Default time-to-live for impersonation sessions (in seconds).
     * After this duration, the impersonation will be automatically stopped.
     */
    'ttl' => env('GHOST_TTL', 3600),

    /**
     * The session key used to store ghost state.
     */
    'session_key' => 'anchor_ghost_impersonation',

    /**
     * Roles that cannot be impersonated (e.g., super-admins).
     * Only used if User model has hasRole() method.
     */
    'protected_roles' => ['super-admin'],

    /**
     * Roles allowed to impersonate other users.
     * Only used if User model has hasRole() method.
     */
    'allowed_roles' => ['admin', 'super-admin'],
];
