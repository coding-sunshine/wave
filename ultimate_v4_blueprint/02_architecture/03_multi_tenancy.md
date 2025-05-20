# Fusion CRM V4 - Multi-Tenancy Implementation

This document outlines the multi-tenancy architecture for Fusion CRM V4, implementing a Single Database multi-tenancy model to provide isolated environments for each tenant within a shared infrastructure.

## Overview

Fusion CRM V4 employs a Single Database Multi-Tenancy approach, where:

1. **All tenants share a single database instance**
2. **Data is segregated by tenant_id columns on relevant tables**
3. **Application logic enforces tenant isolation**
4. **Infrastructure is shared for efficiency**

This approach offers the optimal balance between resource efficiency, maintenance simplicity, and tenant isolation for the Fusion CRM use case.

## Architectural Components

### 1. Tenant Management System

The central component that manages tenant creation, configuration, and access:

```php
namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class TenantManager
{
    /**
     * The current active tenant.
     */
    protected ?Tenant $tenant = null;
    
    /**
     * Get the current tenant.
     */
    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }
    
    /**
     * Set the current tenant.
     */
    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        
        if ($tenant) {
            // Store in cache for quick access
            Cache::put('current_tenant_' . request()->session()->getId(), $tenant->id, now()->addHours(2));
            
            // Make tenant ID available globally
            app()->instance('tenant_id', $tenant->id);
            
            // Set tenant configuration
            $this->setTenantConfiguration($tenant);
        } else {
            // Clear tenant from cache
            Cache::forget('current_tenant_' . request()->session()->getId());
            
            // Reset to default configuration
            $this->resetConfiguration();
        }
    }
    
    /**
     * Initialize tenant from session or request.
     */
    public function initializeTenant(): ?Tenant
    {
        // Check for cached tenant
        $cachedTenantId = Cache::get('current_tenant_' . request()->session()->getId());
        
        if ($cachedTenantId) {
            $tenant = Tenant::find($cachedTenantId);
            
            if ($tenant) {
                $this->setTenant($tenant);
                return $tenant;
            }
        }
        
        // Check for tenant in domain
        $tenant = $this->resolveTenantFromDomain();
        
        if ($tenant) {
            $this->setTenant($tenant);
            return $tenant;
        }
        
        return null;
    }
    
    /**
     * Resolve tenant from the current domain.
     */
    protected function resolveTenantFromDomain(): ?Tenant
    {
        $domain = request()->getHost();
        
        // Check for exact domain match
        $tenant = Tenant::where('domain', $domain)->first();
        
        if (!$tenant) {
            // Check for subdomain match
            $parts = explode('.', $domain);
            $subdomain = array_shift($parts);
            $baseDomain = implode('.', $parts);
            
            $tenant = Tenant::where('subdomain', $subdomain)
                ->where('domain', $baseDomain)
                ->first();
        }
        
        return $tenant;
    }
    
    /**
     * Set tenant-specific configuration.
     */
    protected function setTenantConfiguration(Tenant $tenant): void
    {
        // Set tenant-specific storage paths
        config([
            'filesystems.disks.tenant.root' => storage_path('app/tenants/' . $tenant->id),
            'filesystems.disks.tenant.url' => env('APP_URL') . '/storage/tenants/' . $tenant->id,
        ]);
        
        // Other tenant-specific configurations
        if ($tenant->settings) {
            // Apply tenant-specific settings
            foreach ($tenant->settings as $key => $value) {
                config(["tenant.settings.{$key}" => $value]);
            }
        }
    }
    
    /**
     * Reset configuration to defaults.
     */
    protected function resetConfiguration(): void
    {
        // Reset to default storage configuration
        config([
            'filesystems.disks.tenant.root' => storage_path('app/tenants/default'),
            'filesystems.disks.tenant.url' => env('APP_URL') . '/storage/tenants/default',
        ]);
    }
}
```

### 2. Tenant Database Schema

The foundation of the multi-tenant database architecture:

```sql
-- Tenants table
CREATE TABLE tenants (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    subdomain VARCHAR(255) NULL,
    domain VARCHAR(255) NULL,
    logo VARCHAR(255) NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(50) NULL,
    address TEXT NULL,
    status ENUM('active', 'inactive', 'suspended', 'trial') NOT NULL DEFAULT 'trial',
    trial_ends_at TIMESTAMP NULL,
    settings JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE INDEX tenants_subdomain_domain_unique (subdomain, domain)
);

-- Sample tenant-aware entities

-- Users table (tenant-aware)
CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    -- Other user fields
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE INDEX users_tenant_id_email_unique (tenant_id, email)
);

-- Contacts table (tenant-aware)
CREATE TABLE contacts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    -- Other contact fields
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX contacts_tenant_id_index (tenant_id)
);

-- Properties table (tenant-aware)
CREATE TABLE lots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    lot_number VARCHAR(50) NOT NULL,
    street_number VARCHAR(50) NULL,
    street_name VARCHAR(255) NULL,
    suburb VARCHAR(255) NULL,
    state VARCHAR(50) NULL,
    postcode VARCHAR(20) NULL,
    -- Other property fields
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    INDEX lots_tenant_id_index (tenant_id)
);
```

### 3. Tenant Scoping Middleware

Middleware for automatic tenant resolution and scoping:

```php
namespace App\Http\Middleware;

use App\Services\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;

class TenantScope
{
    /**
     * The tenant manager instance.
     */
    protected $tenantManager;
    
    /**
     * Create a new middleware instance.
     */
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Initialize tenant from request/session
        $tenant = $this->tenantManager->initializeTenant();
        
        // If no tenant was resolved and we're not on a public route,
        // redirect to the tenant selection page
        if (!$tenant && !$this->isPublicRoute($request)) {
            return redirect()->route('tenant.select');
        }
        
        return $next($request);
    }
    
    /**
     * Determine if the request is for a public route.
     */
    protected function isPublicRoute(Request $request): bool
    {
        $publicRoutes = [
            'login',
            'register',
            'password.request',
            'password.reset',
            'tenant.select',
            'tenant.landing',
            'api.documentation',
            // Add other public routes here
        ];
        
        return $request->routeIs($publicRoutes) || 
               $request->is('api/public/*') ||
               $request->is('images/*', 'css/*', 'js/*', 'favicon.ico');
    }
}
```

### 4. Global Tenant Scope for Models

Automatic tenant scoping for Eloquent models:

```php
namespace App\Models\Traits;

use App\Services\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

trait BelongsToTenant
{
    /**
     * Boot the trait.
     */
    public static function bootBelongsToTenant()
    {
        // Apply tenant scope to all queries
        static::addGlobalScope('tenant', function (Builder $builder) {
            $model = $builder->getModel();
            
            // Only apply scope if model has tenant_id column
            if (Schema::hasColumn($model->getTable(), 'tenant_id')) {
                $tenantId = app('tenant_id') ?? app(TenantManager::class)->getTenant()?->id;
                
                if ($tenantId) {
                    $builder->where($model->getTable() . '.tenant_id', $tenantId);
                }
            }
        });
        
        // Auto-set tenant_id on creation
        static::creating(function (Model $model) {
            // Only set tenant_id if column exists and not already set
            if (Schema::hasColumn($model->getTable(), 'tenant_id') && is_null($model->tenant_id)) {
                $tenantId = app('tenant_id') ?? app(TenantManager::class)->getTenant()?->id;
                
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }
    
    /**
     * Get the tenant that the model belongs to.
     */
    public function tenant()
    {
        return $this->belongsTo('App\Models\Tenant');
    }
}
```

Example usage in a model:

```php
namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use BelongsToTenant;
    
    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        // Other fillable fields
    ];
    
    // Model relationships and methods
}
```

### 5. Tenant Isolation for Jobs & Queue Processing

Ensuring tenant context is preserved in background jobs:

```php
namespace App\Jobs\Traits;

use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;

trait TenantAwareJob
{
    /**
     * The ID of the tenant this job belongs to.
     */
    protected $tenantId;
    
    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->tenantId = app('tenant_id') ?? app(TenantManager::class)->getTenant()?->id;
    }
    
    /**
     * Execute the job.
     */
    public function handle()
    {
        // Set tenant context for job execution
        if ($this->tenantId) {
            $tenant = Tenant::find($this->tenantId);
            
            if ($tenant) {
                app(TenantManager::class)->setTenant($tenant);
                $this->handleForTenant();
            } else {
                // Log that tenant doesn't exist anymore
                \Log::warning("Tenant {$this->tenantId} no longer exists. Job skipped.");
            }
        } else {
            // If no tenant ID, run without tenant context
            $this->handleForTenant();
        }
    }
    
    /**
     * Execute the job for the tenant.
     * This method should be implemented by the job class.
     */
    abstract public function handleForTenant();
}
```

Example usage in a job:

```php
namespace App\Jobs;

use App\Jobs\Traits\TenantAwareJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPropertyMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    protected $propertyId;
    
    /**
     * Create a new job instance.
     */
    public function __construct(int $propertyId)
    {
        parent::__construct(); // Important: Call parent constructor to set tenant ID
        $this->propertyId = $propertyId;
    }
    
    /**
     * Execute the job for the tenant.
     */
    public function handleForTenant()
    {
        // Job logic here, tenant context is already set
        $property = \App\Models\Lot::findOrFail($this->propertyId);
        
        // Process property media
        // ...
    }
}
```

### 6. Tenant-Aware Storage

Isolating file storage between tenants:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;

class TenantStorageServiceProvider extends ServiceProvider
{
    /**
     * Register the tenant storage disk.
     */
    public function register()
    {
        //
    }
    
    /**
     * Bootstrap the tenant storage services.
     */
    public function boot()
    {
        // Register tenant disk
        Storage::extend('tenant', function ($app, $config) {
            // Default configuration
            $config['root'] = storage_path('app/tenants/default');
            $config['url'] = env('APP_URL') . '/storage/tenants/default';
            
            // For S3 or other cloud storage with tenant-specific paths
            if (isset($config['driver']) && $config['driver'] === 's3') {
                $config['root'] = 'tenants/default';
                $tenantId = app('tenant_id') ?? null;
                
                if ($tenantId) {
                    $config['root'] = 'tenants/' . $tenantId;
                }
            }
            
            // Create the driver
            return Storage::createLocalDriver($config);
        });
    }
}
```

Configuration in `config/filesystems.php`:

```php
'disks' => [
    // ...
    
    'tenant' => [
        'driver' => 'tenant',
        'throw' => false,
    ],
],
```

## Tenant Lifecycle Management

### 1. Tenant Creation Process

Creating new tenants with proper isolation:

```php
namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenantCreationService
{
    /**
     * Create a new tenant with admin user.
     */
    public function createTenant(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            // Create tenant record
            $tenant = Tenant::create([
                'name' => $data['name'],
                'display_name' => $data['display_name'] ?? $data['name'],
                'subdomain' => $data['subdomain'] ?? Str::slug($data['name']),
                'domain' => $data['domain'] ?? null,
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? 'trial',
                'trial_ends_at' => $data['trial_ends_at'] ?? now()->addDays(30),
                'settings' => $data['settings'] ?? null,
            ]);
            
            // Create admin user for tenant
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'email_verified_at' => now(),
            ]);
            
            // Assign admin role to user
            $user->assignRole('admin');
            
            // Set up tenant storage
            $this->setupTenantStorage($tenant);
            
            // Initialize default settings
            $this->initializeDefaultSettings($tenant);
            
            return $tenant;
        });
    }
    
    /**
     * Set up storage for the tenant.
     */
    protected function setupTenantStorage(Tenant $tenant): void
    {
        // Create tenant storage directory
        $tenantStoragePath = storage_path('app/tenants/' . $tenant->id);
        
        // Ensure directory exists
        if (!Storage::exists($tenantStoragePath)) {
            Storage::makeDirectory($tenantStoragePath);
            
            // Create subdirectories for different asset types
            $directories = ['images', 'documents', 'media', 'exports', 'imports', 'temp'];
            
            foreach ($directories as $directory) {
                Storage::makeDirectory($tenantStoragePath . '/' . $directory);
            }
        }
    }
    
    /**
     * Initialize default settings for the tenant.
     */
    protected function initializeDefaultSettings(Tenant $tenant): void
    {
        // Set default tenant settings
        $defaultSettings = [
            'branding' => [
                'primary_color' => '#4f46e5',
                'secondary_color' => '#818cf8',
                'text_color' => '#1f2937',
                'background_color' => '#ffffff',
            ],
            'notifications' => [
                'email' => true,
                'sms' => false,
                'in_app' => true,
            ],
            'integrations' => [
                'xero' => false,
                'wordpress' => false,
                'mailchimp' => false,
            ],
            'features' => [
                'ai_enabled' => true,
                'media_management' => true,
                'advanced_reporting' => false,
            ],
        ];
        
        // Update tenant with default settings
        $tenant->update([
            'settings' => $defaultSettings,
        ]);
    }
}
```

### 2. Tenant Suspension and Deletion

Managing tenant lifecycle events:

```php
namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantManagementService
{
    /**
     * Suspend a tenant.
     */
    public function suspendTenant(Tenant $tenant, string $reason = null): bool
    {
        // Update tenant status
        $tenant->update([
            'status' => 'suspended',
            'settings' => array_merge($tenant->settings ?? [], [
                'suspension' => [
                    'date' => now()->toDateTimeString(),
                    'reason' => $reason,
                ],
            ]),
        ]);
        
        // Log suspension
        activity()
            ->causedBy(auth()->user())
            ->performedOn($tenant)
            ->withProperties(['reason' => $reason])
            ->log('Tenant suspended');
        
        return true;
    }
    
    /**
     * Reactivate a suspended tenant.
     */
    public function reactivateTenant(Tenant $tenant): bool
    {
        // Update tenant status
        $tenant->update([
            'status' => 'active',
            'settings' => array_merge($tenant->settings ?? [], [
                'reactivation' => [
                    'date' => now()->toDateTimeString(),
                ],
            ]),
        ]);
        
        // Log reactivation
        activity()
            ->causedBy(auth()->user())
            ->performedOn($tenant)
            ->log('Tenant reactivated');
        
        return true;
    }
    
    /**
     * Soft-delete a tenant.
     */
    public function deleteTenant(Tenant $tenant): bool
    {
        return DB::transaction(function () use ($tenant) {
            // Soft delete tenant (will cascade to related records)
            $tenant->update([
                'status' => 'deleted',
                'domain' => null, // Free up domain
                'subdomain' => 'deleted_' . $tenant->id . '_' . $tenant->subdomain, // Free up subdomain
            ]);
            
            $tenant->delete();
            
            // Log deletion
            activity()
                ->causedBy(auth()->user())
                ->performedOn($tenant)
                ->log('Tenant deleted');
            
            return true;
        });
    }
    
    /**
     * Permanently delete a tenant and all its data.
     */
    public function permanentlyDeleteTenant(Tenant $tenant): bool
    {
        return DB::transaction(function () use ($tenant) {
            // Get tenant ID for storage cleanup after deletion
            $tenantId = $tenant->id;
            
            // Permanently delete all tenant data
            $tenant->forceDelete();
            
            // Delete tenant storage
            $tenantStoragePath = 'tenants/' . $tenantId;
            Storage::deleteDirectory($tenantStoragePath);
            
            // Log permanent deletion
            activity()
                ->causedBy(auth()->user())
                ->withProperties(['tenant_id' => $tenantId])
                ->log('Tenant permanently deleted');
            
            return true;
        });
    }
}
```

## Tenant Database Migrations

Implementing tenant-aware schema migrations:

```php
namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateTenants extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenants:migrate 
                            {--tenant= : The ID of a specific tenant to migrate}
                            {--fresh : Whether to wipe the database and start from scratch}
                            {--seed : Whether to seed the database}';
    
    /**
     * The console command description.
     */
    protected $description = 'Run migrations for tenant-specific tables';
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get all active tenants (or specific tenant if given)
        if ($tenantId = $this->option('tenant')) {
            $tenants = Tenant::where('id', $tenantId)->get();
            
            if ($tenants->isEmpty()) {
                $this->error("Tenant with ID {$tenantId} not found");
                return Command::FAILURE;
            }
        } else {
            $tenants = Tenant::whereIn('status', ['active', 'trial'])->get();
        }
        
        // Prepare migration options
        $migrateOptions = [
            '--path' => 'database/migrations/tenant',
        ];
        
        if ($this->option('fresh')) {
            $migrateOptions['--fresh'] = true;
        }
        
        if ($this->option('seed')) {
            $migrateOptions['--seed'] = true;
        }
        
        // Migrate each tenant
        $migrateOptions['--force'] = true;
        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();
        
        foreach ($tenants as $tenant) {
            $this->line("\nMigrating tenant: {$tenant->name} (ID: {$tenant->id})");
            
            // Set current tenant ID for migration
            app()->instance('tenant_id', $tenant->id);
            
            // Run migrations
            $migrateOptions['--database'] = 'tenant';
            Artisan::call('migrate', $migrateOptions);
            
            // Clear tenant ID
            app()->forgetInstance('tenant_id');
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->info("\nTenant migrations completed successfully!");
        
        return Command::SUCCESS;
    }
}
```

## Testing Multi-Tenancy

Utilities for testing multi-tenant features:

```php
namespace Tests\Traits;

use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;

trait TenantTestHelpers
{
    /**
     * The current tenant for testing.
     */
    protected $currentTenant;
    
    /**
     * Set the current tenant for testing.
     */
    protected function setTenant(Tenant $tenant = null): self
    {
        if (!$tenant) {
            // Create a test tenant if none provided
            $tenant = Tenant::factory()->create([
                'name' => 'Test Tenant',
                'display_name' => 'Test Tenant',
                'subdomain' => 'test' . rand(1000, 9999),
                'contact_email' => 'test@example.com',
                'status' => 'active',
            ]);
        }
        
        $this->currentTenant = $tenant;
        
        // Set as current tenant
        app(TenantManager::class)->setTenant($tenant);
        app()->instance('tenant_id', $tenant->id);
        
        return $this;
    }
    
    /**
     * Reset the tenant after tests.
     */
    protected function resetTenant(): void
    {
        app(TenantManager::class)->setTenant(null);
        app()->forgetInstance('tenant_id');
        $this->currentTenant = null;
    }
    
    /**
     * Override base tearDown method.
     */
    protected function tearDown(): void
    {
        $this->resetTenant();
        parent::tearDown();
    }
}
```

## Security Considerations

1. **Data Isolation**
   - Tenant ID is added to all database queries automatically
   - Global scopes ensure data is only accessible to appropriate tenants
   - File storage is isolated per tenant

2. **Cross-Tenant Protection**
   - Authentication system validates tenant context
   - API tokens include tenant scoping
   - Jobs and queues maintain tenant context

3. **Resource Limits**
   - Implement rate limiting per tenant
   - Monitor resource usage by tenant
   - Set storage limits per tenant tier

4. **Access Controls**
   - Role-based permissions within each tenant
   - Super-admin access is strictly limited
   - Audit logging tracks all tenant management actions

## Performance Optimizations

1. **Database Optimizations**
   - Properly indexed tenant_id columns
   - Partitioning for large tenants
   - Query caching for tenant-specific queries

2. **Caching Strategies**
   - Tenant-aware cache keys
   - Cache tenant settings and configuration
   - Cache invalidation on tenant updates

3. **Scaling Considerations**
   - Horizontal scaling of web servers
   - Vertical scaling of database for multi-tenant model
   - CDN usage for tenant assets

## Conclusion

The Single Database Multi-Tenancy approach in Fusion CRM V4 provides:

1. **Cost Efficiency**: Shared infrastructure with lower operational overhead
2. **Simplified Maintenance**: One database to maintain and upgrade
3. **Robust Isolation**: Tenant data separation through application logic
4. **Scaling Flexibility**: Easier to scale for many smaller tenants
5. **Development Simplicity**: Single codebase without complex routing

This architecture is ideal for Fusion CRM's use case, where there are many tenants with similar requirements and data models. The tenant-aware components ensure proper isolation while maintaining efficient resource usage. 