# JWT Authentication Guide for Wave CRM

*A complete reference for implementing and utilizing JWT-based API authentication in the Wave SaaS starter kit (Laravel 12, PHP 8.3).*

---

## 1. What is JWT Authentication?

JWT (JSON Web Token) authentication is a token-based stateless authentication mechanism used primarily for APIs. Wave implements JWT authentication using the `tymon/jwt-auth` package, providing:

* Secure, stateless API authentication
* Token-based user identification
* Configurable token expiration and refresh
* Role-based API access control
* Cross-domain authentication capabilities
* Support for multiple authentication guards

JWT authentication is ideal for SaaS applications that need to provide API access to third-party integrations or mobile applications.

---

## 2. Installation (already configured)

JWT authentication comes pre-configured with Wave. The core dependency is:

```bash
composer require tymon/jwt-auth
```

Wave ships with `tymon/jwt-auth:^2.2` in `composer.json`.

---

## 3. Configuration

### 3.1 JWT Secret Key

A JWT secret key is required for token signing. If not already generated, run:

```bash
php artisan jwt:secret
```

This will add the `JWT_SECRET` variable to your `.env` file.

### 3.2 Configuration File

The JWT configuration is defined in `config/jwt.php`. Key settings include:

| Setting | Purpose | Default |
|---------|---------|---------|
| `ttl` | Token time-to-live in minutes | 60 |
| `refresh_ttl` | Token refresh time in minutes | 20160 (2 weeks) |
| `blacklist_enabled` | Whether token blacklisting is enabled | true |
| `algorithm` | Signing algorithm | HS256 |

---

## 4. Basic Usage

### 4.1 User Model Setup

Ensure your User model implements the JWT interface:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    // ...
    
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
```

### 4.2 API Authentication Controller

Wave includes a pre-built API authentication controller:

```php
<?php

namespace Wave\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT token via given credentials.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }
}
```

### 4.3 API Routes

Define your API routes with JWT authentication middleware:

```php
Route::group([
    'middleware' => 'api',
    'prefix' => 'api/v1',
    'namespace' => 'App\Http\Controllers\API',
], function () {
    // Auth routes
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::get('me', 'AuthController@me');
    
    // Protected API routes
    Route::middleware('auth:api')->group(function () {
        Route::apiResource('users', 'UserController');
        Route::apiResource('projects', 'ProjectController');
        // ...
    });
});
```

---

## 5. Advanced Features

### 5.1 Custom Claims

Add custom claims to your JWT tokens:

```php
public function getJWTCustomClaims()
{
    return [
        'tenant_id' => $this->tenant_id,
        'role' => $this->role,
        'permissions' => $this->permissions->pluck('name')->toArray()
    ];
}
```

### 5.2 Token Blacklisting

Blacklisting revokes tokens before their expiration. To enable this feature:

```php
// config/jwt.php
'blacklist_enabled' => true,
```

This ensures security for scenarios like user logout or permission changes.

### 5.3 Multiple Authentication Guards

Configure multiple authentication guards for different API versions or user types:

```php
// config/auth.php
'guards' => [
    // ...
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
    'admin_api' => [
        'driver' => 'jwt',
        'provider' => 'admins',
    ],
],
```

Usage:

```php
auth('admin_api')->attempt($credentials);
```

---

## 6. Wave Integration Patterns

### 6.1 API Resources

Use Laravel API Resources for consistent response formatting:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toIso8601String(),
            'subscription' => new SubscriptionResource($this->subscription),
        ];
    }
}
```

Usage in controllers:

```php
public function show(User $user)
{
    return new UserResource($user);
}

public function index()
{
    return UserResource::collection(User::paginate(15));
}
```

### 6.2 API Versioning

Wave recommends URL-based API versioning:

```php
Route::prefix('api/v1')->group(function () {
    // V1 API endpoints
});

Route::prefix('api/v2')->group(function () {
    // V2 API endpoints
});
```

### 6.3 Rate Limiting

Protect your API from abuse with rate limiting:

```php
Route::middleware(['api', 'auth:api', 'throttle:60,1'])->group(function () {
    // Routes limited to 60 requests per minute
});

Route::middleware(['api', 'auth:api', 'throttle:subscription_rate_limit'])->group(function () {
    // Routes with dynamic rate limits based on subscription
});
```

Implementation of dynamic rate limiting:

```php
// In RouteServiceProvider.php
RateLimiter::for('subscription_rate_limit', function (Request $request) {
    $user = $request->user();
    
    // Default rate limit if no subscription
    $limit = 30;
    
    if ($user->subscription) {
        $limit = match ($user->subscription->plan->name) {
            'basic' => 60,
            'pro' => 120,
            'enterprise' => 500,
            default => 30,
        };
    }
    
    return Limit::perMinute($limit)->by($user->id);
});
```

---

## 7. Testing JWT Authentication

### 7.1 Feature Tests

Using Pest PHP for testing JWT authentication:

```php
use App\Models\User;

test('user can get JWT token with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
    
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in'
        ]);
});

test('protected routes require authentication', function () {
    // Attempt without token
    $response = $this->getJson('/api/me');
    $response->assertStatus(401);
    
    // Authenticate and try again
    $token = auth('api')->login(User::factory()->create());
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/me');
    
    $response->assertStatus(200);
});
```

### 7.2 Unit Tests

```php
test('JWT token contains expected custom claims', function () {
    $user = User::factory()->create(['role' => 'admin']);
    
    // Manually authenticate the user
    $token = auth('api')->login($user);
    
    // Get the payload from the token
    $payload = auth('api')->payload();
    
    // Verify custom claims
    expect($payload->get('role'))->toBe('admin');
    expect($payload->get('sub'))->toBe($user->id);
});
```

---

## 8. Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| Token not working | Verify JWT_SECRET in .env |
| Token expiring too quickly | Check `ttl` in config/jwt.php |
| "Token invalid" error | Ensure token is being sent correctly in Authorization header |
| Cross-domain issues | Configure CORS headers properly |
| Performance issues | Consider caching user retrieval |

For further assistance, see [tymon/jwt-auth documentation](https://jwt-auth.readthedocs.io/) or [JWT.io](https://jwt.io/).
