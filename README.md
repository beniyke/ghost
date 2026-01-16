<!-- This file is auto-generated from docs/ghost.md -->

# Ghost

Ghost is a user impersonation package that allows administrators to seamlessly log in as other users to troubleshoot issues, provide support, or test user experiences without knowing their passwords.

## Features

- **HMAC-SHA256 Session Integrity**: Prevents session tampering while impersonating.
- **Configurable TTL**: Automatically ends impersonation sessions after a set duration.
- **Role-Based Authorization**: Only users with allowed roles can impersonate, and protected roles cannot be impersonated.
- **Activity Logging**: Built-in listeners log all impersonation events to the activity table.
- **Nested Impersonation Prevention**: Cannot impersonate while already impersonating.
- **Lifecycle Events**: Dispatches `ImpersonationStarted` and `ImpersonationStopped` events.
- **Fluent API**: Simple `Ghost` facade for common operations.

## Requirements

Your `User` model must implement a `hasRole(string $role): bool` method:

```php
// App\Models\User
public function hasRole(string $role): bool
{
    return $this->role === $role;
}
```

## Installation

Ghost can be installed as a framework package:

```bash
php dock package:install Ghost --packages
```

## Basic Usage

### Starting Impersonation

```php
use App\Models\User;
use Ghost\Ghost;

$user = User::find(123);

if (Ghost::impersonate($user)) {
    // Success: Now $this->auth->user() returns user 123
} else {
    // Failed: No permission, protected user, or already ghosting
}
```

### Stopping Impersonation

```php
Ghost::stop();
// Now $this->auth->user() returns your original account
```

### Checking Status

```php
if (Ghost::isGhosting()) {
    $impersonator = Ghost::getImpersonator(); // Original admin
    $impersonated = Ghost::getImpersonated(); // User being viewed

    if (Ghost::isExpired()) {
        Ghost::stop(); // Session TTL exceeded
    }
}
```

## Configuration

Located at `App/Config/ghost.php`:

```php
return [
    // Session duration in seconds (default: 1 hour)
    'ttl' => env('GHOST_TTL', 3600),

    // Session key for storing ghost state
    'session_key' => 'anchor_ghost_impersonation',

    // Roles that cannot be impersonated (e.g., super-admins)
    'protected_roles' => ['super-admin'],

    // Roles allowed to impersonate (if User has hasRole() method)
    'allowed_roles' => ['admin', 'super-admin'],
];
```

## Authorization

Ghost implements a layered authorization system:

### Nested Impersonation Prevention

You cannot start a new impersonation while already impersonating someone.

### Role-Based Access

If no permission system exists but `hasRole()` is available, Ghost checks against `allowed_roles`:

```php
// In your User model
public function hasRole(string $role): bool
{
    return $this->role === $role;
}
```

### Protected Roles

Users with roles in the `protected_roles` config array cannot be impersonated by anyone.

## Security

Ghost uses a signed session payload with HMAC-SHA256 to prevent tampering:

| Field             | Purpose                                 |
| ----------------- | --------------------------------------- |
| `impersonator_id` | Original admin's user ID                |
| `impersonated_id` | Target user's ID                        |
| `original_token`  | Original session token for restoration  |
| `expires_at`      | Unix timestamp when session expires     |
| `signature`       | HMAC-SHA256 hash using `encryption_key` |

The `ImpersonateMiddleware` automatically terminates impersonation if the signature is invalid or TTL has expired.

## Activity Logging

Ghost automatically logs all impersonation events to the activity table via built-in listeners:

- `LogImpersonationStarted`: Records when admin starts impersonating
- `LogImpersonationStopped`: Records when impersonation ends

Example activity log entry:

```
User: Admin (ID: 1)
Description: Started impersonating user #123 (john@example.com)
```

## Events

Ghost dispatches events for custom integrations:

```php
use Ghost\Events\ImpersonationStarted;
use Ghost\Events\ImpersonationStopped;

// Both events contain:
$event->impersonator; // User who initiated impersonation
$event->impersonated; // User being impersonated
```

## Implementation

#### Admin Panel Controller

```php
namespace App\Admin\Controllers;

use App\Core\BaseController;
use App\Services\UserService;
use Ghost\Ghost;
use Helpers\Http\Response;

class ImpersonateController extends BaseController
{
    public function impersonate(UserService $userService, ?string $refid = null): Response
    {
        $user = $userService->getUser($refid);

        if (!$user) {
            $this->flash->error('User not found.');

            return $this->response->redirect($this->request->callback());
        }

        if (!Ghost::impersonate($user)) {
            $this->flash->error('Cannot impersonate this user.');

            return $this->response->redirect($this->request->callback());
        }

        $this->flash->success("Now viewing as {$user->name}");

        return $this->response->redirect('dashboard');
    }

    public function stop(): Response
    {
        Ghost::stop();

        $this->flash->success('Returned to your account.');

        return $this->response->redirect('admin/users');
    }
}
```

#### Protecting Sensitive Actions

Prevent impersonators from performing destructive actions:

```php
// In a controller that handles account deletion
public function destroy(): Response
{
    if (Ghost::isGhosting()) {
        $this->flash->error('Cannot delete account while impersonating.');

        return $this->response->redirect($this->request->callback());
    }

    // Proceed with deletion...
}
```

#### API Protection Middleware

```php
namespace App\Middleware;

use Closure;
use Core\Middleware\MiddlewareInterface;
use Ghost\Ghost;
use Helpers\Http\Request;
use Helpers\Http\Response;

class PreventGhostActionsMiddleware implements MiddlewareInterface
{
    private array $blockedRoutes = [
        'account/delete',
        'billing/cancel'
    ];

    public function handle(Request $request, Response $response, Closure $next): mixed
    {
        if (Ghost::isGhosting() && in_array($request->route(), $this->blockedRoutes)) {
            return $response->json([
                'error' => 'This action is not allowed while impersonating'
            ], 403);
        }

        return $next($request, $response);
    }
}
```
