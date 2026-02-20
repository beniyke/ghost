<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * User impersonation and session management.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Ghost\Services;

use App\Models\User;
use App\Services\Auth\Interfaces\AuthServiceInterface;
use App\Services\SessionService;
use Core\Event;
use Core\Services\ConfigServiceInterface;
use Ghost\Events\ImpersonationStartedEvent;
use Ghost\Events\ImpersonationStoppedEvent;
use Helpers\Http\Session;
use RuntimeException;

class GhostManagerService
{
    public function __construct(
        private readonly AuthServiceInterface $auth,
        private readonly SessionService $session_service,
        private readonly ConfigServiceInterface $config,
        private readonly Session $session
    ) {
    }

    public function impersonate(User $user): bool
    {
        if ($this->isGhosting()) {
            return false;
        }

        $impersonator = $this->auth->user();

        if (!$impersonator || !($impersonator instanceof User)) {
            return false;
        }

        if ($impersonator->isSelf($user)) {
            return false;
        }

        if (!$this->canImpersonate($impersonator, $user)) {
            return false;
        }

        $authKey = $this->auth->getSessionKey();
        $originalToken = $this->session->get($authKey);
        $token = $this->session_service->create($user);

        if (!$token) {
            throw new RuntimeException("Failed to create session for impersonated user.");
        }

        $expiresAt = time() + (int) $this->config->get('ghost.ttl');
        $ghostData = [
            'impersonator_id' => $impersonator->id,
            'impersonated_id' => $user->id,
            'original_token' => $originalToken,
            'expires_at' => $expiresAt,
        ];

        $ghostData['signature'] = $this->generateSignature($ghostData);

        $this->session->set($this->config->get('ghost.session_key'), $ghostData);
        $this->session->set($authKey, $token);

        Event::dispatch(new ImpersonationStartedEvent($impersonator, $user));

        return true;
    }

    /**
     * Stop the current impersonation and restore the original user session.
     */
    public function stop(): bool
    {
        $ghostKey = $this->config->get('ghost.session_key');
        $ghostData = $this->session->get($ghostKey);

        if (!$ghostData) {
            return false;
        }

        if (!$this->verifySignature($ghostData)) {
            $this->session->delete($ghostKey);

            return false;
        }

        $impersonator = User::find($ghostData['impersonator_id']);
        $impersonated = User::find($ghostData['impersonated_id']);

        $authKey = $this->auth->getSessionKey();
        $this->session->set($authKey, $ghostData['original_token']);
        $this->session->delete($ghostKey);

        if ($impersonator && $impersonated) {
            Event::dispatch(new ImpersonationStoppedEvent($impersonator, $impersonated));
        }

        return true;
    }

    public function isGhosting(): bool
    {
        $ghostData = $this->session->get($this->config->get('ghost.session_key'));

        if (!$ghostData || !isset($ghostData['signature'])) {
            return false;
        }

        return $this->verifySignature($ghostData);
    }

    public function getImpersonator(): ?User
    {
        if (!$this->isGhosting()) {
            return null;
        }

        $ghostData = $this->session->get($this->config->get('ghost.session_key'));

        return User::find($ghostData['impersonator_id']);
    }

    public function getImpersonated(): ?User
    {
        if (!$this->isGhosting()) {
            return null;
        }

        $ghostData = $this->session->get($this->config->get('ghost.session_key'));

        return User::find($ghostData['impersonated_id']);
    }

    public function isExpired(): bool
    {
        if (!$this->isGhosting()) {
            return false;
        }

        $ghostData = $this->session->get($this->config->get('ghost.session_key'));

        return time() > $ghostData['expires_at'];
    }

    private function generateSignature(array $data): string
    {
        unset($data['signature']);
        ksort($data);
        $payload = json_encode($data);
        $key = $this->config->get('encryption_key', 'ghost-secret-key-fallback');

        return hash_hmac('sha256', $payload, $key);
    }

    private function verifySignature(array $data): bool
    {
        if (!isset($data['signature'])) {
            return false;
        }

        $signature = $data['signature'];

        return hash_equals($signature, $this->generateSignature($data));
    }

    private function canImpersonate(User $impersonator, User $target): bool
    {
        $protectedRoles = $this->config->get('ghost.protected_roles', ['super-admin']);
        foreach ($protectedRoles as $role) {
            if ($target->hasRole($role)) {
                return false;
            }
        }

        $allowedRoles = $this->config->get('ghost.allowed_roles', ['admin', 'super-admin']);
        foreach ($allowedRoles as $role) {
            if ($impersonator->hasRole($role)) {
                return true;
            }
        }

        return false;
    }
}
