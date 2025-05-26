# Wave Kit Integration Strategy

This document outlines the strategy for integrating Wave Kit as the foundation for Fusion CRM V4, with a focus on adapting its existing features for our multi-tenant architecture.

## Wave Kit Overview

Wave Kit provides several core features that serve as an excellent foundation for Fusion CRM V4:

- User authentication and management
- Team/organization structure
- Subscription and billing system
- API scaffolding and token management
- Starter UI components and layouts

## Integration Approach

### 1. Multi-Tenant Adaptation

Wave Kit's existing team model will be adapted to serve as the foundation for our tenant architecture:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tenant model extending Wave's team concept for multi-tenancy
 */
class Tenant extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'settings',
        'api_key',
        'subscription_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Get the users for the tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
    
    /**
     * Get the clients for the tenant.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }
    
    // Additional tenant-specific relationships and methods
}
```

### 2. User Model Extension

Wave Kit's existing user model will be extended to incorporate tenant relationships:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * User model extending Wave's user with tenant awareness
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'settings',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'settings' => 'array',
    ];

    /**
     * Get the tenant that the user belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    // Additional user-specific relationships and methods
}
```

### 3. Global Tenant Scope

To ensure tenant data isolation, we'll implement a global scope:

```php
<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Services\TenantManager;

/**
 * Global scope for tenant data isolation
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }
        
        $tenantId = app(TenantManager::class)->getTenantId();
        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}
```

### 4. Tenant Middleware

Middleware for automatically setting the tenant context:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to set tenant context based on authenticated user
 */
class SetTenantContext
{
    /**
     * @var TenantManager
     */
    protected TenantManager $tenantManager;

    /**
     * Constructor
     */
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $this->tenantManager->setTenantId($request->user()->tenant_id);
        }

        return $next($request);
    }
}
```

## Integrating Wave Components

### 1. Authentication Flow

Wave Kit's authentication will be extended to include tenant context:

- Login will set tenant context based on user's tenant
- Registration will create both user and assign to tenant
- Password reset flows remain unchanged
- Email verification flows remain unchanged

### 2. Team Management

Wave's team management will be adapted for tenant administration:

- Team management becomes tenant management
- Team roles become tenant-specific roles
- Team invitations become tenant invitations

### 3. Billing System

Wave's billing system will be adapted for tenant billing:

- Subscriptions will be tenant-based rather than user-based
- Billing events will be associated with tenants
- Payment methods will be stored at the tenant level
- Invoices will be generated for tenants

### 4. API Access

Wave's API functionality will be extended for tenant-specific API access:

- API tokens will include tenant context
- API endpoints will respect tenant isolation
- API rate limiting will be tenant-specific

## UI Component Adaptation

Wave Kit's UI components will serve as the foundation but will be customized for Fusion CRM:

1. **Navigation**: Adapt navigation for CRM-specific sections
2. **Dashboards**: Create tenant-specific dashboards
3. **Settings**: Extend settings for tenant management
4. **User Management**: Enhance for tenant-specific user roles
5. **Layouts**: Use as foundation but customize for CRM interface

## Implementation Strategy

1. Install Wave Kit and verify core functionality
2. Extend database schema for tenant architecture
3. Implement tenant scoping and middleware
4. Adapt authentication and billing for tenant context
5. Customize UI components for CRM functionality
6. Implement CRM-specific features and modules

## Testing Strategy

1. Unit test tenant isolation with Pest
2. Feature test tenant-aware authentication
3. Integration test billing with tenant context
4. E2E test complete user flows
