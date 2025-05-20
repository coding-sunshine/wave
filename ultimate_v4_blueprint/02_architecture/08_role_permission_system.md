# Fusion CRM V4 - Role & Permission System Architecture

This document outlines the architecture for the Role & Permission System in Fusion CRM V4, focusing on the implementation of a flexible, secure, and granular access control system.

## Overview

Fusion CRM V4 implements a comprehensive role and permission system that provides:

1. **Single-Tenant Architecture**: Complete isolation of data and permissions between tenants
2. **Custom Roles Matrix**: Flexible role definitions with inheritance and customization
3. **Granular Permissions**: Fine-grained access control at feature and record levels
4. **Dynamic UI Adaptation**: Interface elements that respond to user permissions
5. **Audit Logging**: Comprehensive tracking of permission changes and access attempts

## Core Components

### 1. Role & Permission Models

The fundamental data models that support the permission system:

```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT NULL,
    is_system BOOLEAN NOT NULL DEFAULT false,
    parent_role_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_role_id) REFERENCES roles(id) ON DELETE SET NULL,
    UNIQUE INDEX tenant_role_slug (tenant_id, slug)
);

CREATE TABLE permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT NULL,
    module VARCHAR(50) NOT NULL,
    is_system BOOLEAN NOT NULL DEFAULT false,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE INDEX permission_slug (slug)
);

CREATE TABLE role_permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE INDEX role_permission (role_id, permission_id)
);

CREATE TABLE user_roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE INDEX user_role (user_id, role_id)
);

CREATE TABLE user_permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    granted BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE INDEX user_permission (user_id, permission_id)
);

CREATE TABLE permission_scopes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    scope_type VARCHAR(50) NOT NULL,
    scope_value JSON NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);
```

### 2. Permission Service

Central service to handle permission checks and management:

```php
namespace App\Services\Access;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class PermissionService
{
    protected $tenantManager;
    
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Check if a user has a specific permission
     */
    public function hasPermission(User $user, string $permission, ?string $scope = null): bool
    {
        // If user is a superadmin, they have all permissions
        if ($this->isSuperAdmin($user)) {
            return true;
        }
        
        // Get cached user permissions
        $permissions = $this->getUserPermissions($user);
        
        // Check direct permission
        if (!isset($permissions[$permission])) {
            return false;
        }
        
        // If no scope is required, basic permission is enough
        if ($scope === null) {
            return true;
        }
        
        // Check permission scope
        $permissionScopes = $this->getUserPermissionScopes($user);
        
        return $this->checkPermissionScope($permissionScopes, $permission, $scope);
    }
    
    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        // If user is a superadmin, they have all permissions
        if ($this->isSuperAdmin($user)) {
            return true;
        }
        
        $userPermissions = $this->getUserPermissions($user);
        
        foreach ($permissions as $permission) {
            if (isset($userPermissions[$permission])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(User $user, array $permissions): bool
    {
        // If user is a superadmin, they have all permissions
        if ($this->isSuperAdmin($user)) {
            return true;
        }
        
        $userPermissions = $this->getUserPermissions($user);
        
        foreach ($permissions as $permission) {
            if (!isset($userPermissions[$permission])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if user is a superadmin
     */
    public function isSuperAdmin(User $user): bool
    {
        $tenant = $this->tenantManager->getTenant();
        
        // Tenant owner is considered a superadmin
        if ($tenant && $tenant->owner_id === $user->id) {
            return true;
        }
        
        return $user->hasRole('superadmin');
    }
    
    /**
     * Get all permissions for a user
     */
    public function getUserPermissions(User $user): array
    {
        $cacheKey = "user_permissions_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($user) {
            // Get permissions from roles
            $rolePermissions = $this->getRolePermissions($user);
            
            // Get direct user permissions
            $userPermissions = $this->getDirectUserPermissions($user);
            
            // Merge permissions (user permissions override role permissions)
            $permissions = array_merge($rolePermissions, $userPermissions);
            
            // Filter out denied permissions
            $permissions = array_filter($permissions, function ($granted) {
                return $granted;
            });
            
            return $permissions;
        });
    }
    
    /**
     * Get permission scopes for a user
     */
    protected function getUserPermissionScopes(User $user): array
    {
        $cacheKey = "user_permission_scopes_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($user) {
            // Get scopes from roles
            $roleScopes = $this->getRolePermissionScopes($user);
            
            // Get user-specific scopes
            $userScopes = $user->permissionScopes()
                ->get()
                ->groupBy('permission.slug')
                ->map(function ($scopes) {
                    return $scopes->pluck('scope_value', 'scope_type')->toArray();
                })
                ->toArray();
            
            // Merge scopes (user scopes override role scopes)
            $scopes = array_merge_recursive($roleScopes, $userScopes);
            
            return $scopes;
        });
    }
    
    /**
     * Get permissions granted through roles
     */
    protected function getRolePermissions(User $user): array
    {
        $roleIds = $user->roles()->pluck('roles.id')->toArray();
        
        if (empty($roleIds)) {
            return [];
        }
        
        return \DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->whereIn('role_permissions.role_id', $roleIds)
            ->pluck('permissions.id', 'permissions.slug')
            ->map(function () {
                return true;
            })
            ->toArray();
    }
    
    /**
     * Get direct user permissions
     */
    protected function getDirectUserPermissions(User $user): array
    {
        return $user->permissions()
            ->get()
            ->pluck('pivot.granted', 'slug')
            ->toArray();
    }
    
    /**
     * Get permission scopes from roles
     */
    protected function getRolePermissionScopes(User $user): array
    {
        $roles = $user->roles()->with('permissionScopes.permission')->get();
        
        $scopes = [];
        
        foreach ($roles as $role) {
            foreach ($role->permissionScopes as $scope) {
                $permissionSlug = $scope->permission->slug;
                $scopeType = $scope->scope_type;
                
                if (!isset($scopes[$permissionSlug])) {
                    $scopes[$permissionSlug] = [];
                }
                
                if (!isset($scopes[$permissionSlug][$scopeType])) {
                    $scopes[$permissionSlug][$scopeType] = $scope->scope_value;
                } else {
                    // Merge scope values
                    $scopes[$permissionSlug][$scopeType] = array_merge(
                        $scopes[$permissionSlug][$scopeType],
                        $scope->scope_value
                    );
                }
            }
        }
        
        return $scopes;
    }
    
    /**
     * Check if a scope allows an action
     */
    protected function checkPermissionScope(array $permissionScopes, string $permission, string $scope): bool
    {
        if (!isset($permissionScopes[$permission])) {
            return false;
        }
        
        // Determine scope type and value
        list($scopeType, $scopeValue) = $this->parseScopeString($scope);
        
        if (!isset($permissionScopes[$permission][$scopeType])) {
            return false;
        }
        
        $allowedValues = $permissionScopes[$permission][$scopeType];
        
        // Check for wildcard
        if (in_array('*', $allowedValues)) {
            return true;
        }
        
        // Check for specific value
        return in_array($scopeValue, $allowedValues);
    }
    
    /**
     * Parse scope string into type and value
     */
    protected function parseScopeString(string $scope): array
    {
        $parts = explode(':', $scope, 2);
        
        if (count($parts) !== 2) {
            return ['global', $scope];
        }
        
        return [$parts[0], $parts[1]];
    }
    
    /**
     * Clear permission cache for a user
     */
    public function clearUserPermissionCache(User $user): void
    {
        Cache::forget("user_permissions_{$user->id}");
        Cache::forget("user_permission_scopes_{$user->id}");
    }
    
    /**
     * Grant a permission to a role
     */
    public function grantPermissionToRole(Role $role, string $permissionSlug): bool
    {
        $permission = Permission::where('slug', $permissionSlug)->first();
        
        if (!$permission) {
            return false;
        }
        
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        
        // Clear cache for all users with this role
        $userIds = $role->users()->pluck('users.id')->toArray();
        
        foreach ($userIds as $userId) {
            Cache::forget("user_permissions_{$userId}");
            Cache::forget("user_permission_scopes_{$userId}");
        }
        
        return true;
    }
    
    /**
     * Revoke a permission from a role
     */
    public function revokePermissionFromRole(Role $role, string $permissionSlug): bool
    {
        $permission = Permission::where('slug', $permissionSlug)->first();
        
        if (!$permission) {
            return false;
        }
        
        $role->permissions()->detach($permission->id);
        
        // Clear cache for all users with this role
        $userIds = $role->users()->pluck('users.id')->toArray();
        
        foreach ($userIds as $userId) {
            Cache::forget("user_permissions_{$userId}");
            Cache::forget("user_permission_scopes_{$userId}");
        }
        
        return true;
    }
    
    /**
     * Set permission scope for a role
     */
    public function setRolePermissionScope(
        Role $role, 
        string $permissionSlug, 
        string $scopeType, 
        array $scopeValue
    ): bool {
        $permission = Permission::where('slug', $permissionSlug)->first();
        
        if (!$permission) {
            return false;
        }
        
        // Check if the role has this permission
        if (!$role->permissions()->where('permissions.id', $permission->id)->exists()) {
            $this->grantPermissionToRole($role, $permissionSlug);
        }
        
        // Update or create scope
        $role->permissionScopes()->updateOrCreate(
            [
                'permission_id' => $permission->id,
                'scope_type' => $scopeType
            ],
            [
                'scope_value' => $scopeValue
            ]
        );
        
        // Clear cache for all users with this role
        $userIds = $role->users()->pluck('users.id')->toArray();
        
        foreach ($userIds as $userId) {
            Cache::forget("user_permission_scopes_{$userId}");
        }
        
        return true;
    }
}
```

### 3. Role Service

Service to manage roles and their assignments:

```php
namespace App\Services\Access;

use App\Models\Role;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RoleService
{
    protected $permissionService;
    protected $tenantManager;
    
    public function __construct(
        PermissionService $permissionService,
        TenantManager $tenantManager
    ) {
        $this->permissionService = $permissionService;
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Create a new role
     */
    public function createRole(array $data): Role
    {
        $tenant = $this->tenantManager->getTenant();
        
        $role = new Role();
        $role->tenant_id = $tenant->id;
        $role->name = $data['name'];
        $role->slug = $data['slug'] ?? Str::slug($data['name']);
        $role->description = $data['description'] ?? null;
        $role->is_system = $data['is_system'] ?? false;
        $role->parent_role_id = $data['parent_role_id'] ?? null;
        $role->save();
        
        // Assign permissions if provided
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            foreach ($data['permissions'] as $permission) {
                $this->permissionService->grantPermissionToRole($role, $permission);
            }
        }
        
        return $role;
    }
    
    /**
     * Update a role
     */
    public function updateRole(Role $role, array $data): Role
    {
        if (isset($data['name'])) {
            $role->name = $data['name'];
        }
        
        if (isset($data['slug'])) {
            $role->slug = $data['slug'];
        }
        
        if (isset($data['description'])) {
            $role->description = $data['description'];
        }
        
        if (isset($data['parent_role_id'])) {
            $role->parent_role_id = $data['parent_role_id'];
        }
        
        $role->save();
        
        return $role;
    }
    
    /**
     * Delete a role
     */
    public function deleteRole(Role $role): bool
    {
        // Check if it's a system role
        if ($role->is_system) {
            throw new \Exception('System roles cannot be deleted');
        }
        
        // Get all users assigned to this role
        $users = $role->users;
        
        // Delete the role
        $result = $role->delete();
        
        // Clear permission cache for affected users
        foreach ($users as $user) {
            $this->permissionService->clearUserPermissionCache($user);
        }
        
        return $result;
    }
    
    /**
     * Assign role to user
     */
    public function assignRoleToUser(User $user, string $roleSlug): bool
    {
        $tenant = $this->tenantManager->getTenant();
        
        $role = Role::where('tenant_id', $tenant->id)
            ->where('slug', $roleSlug)
            ->first();
        
        if (!$role) {
            return false;
        }
        
        $user->roles()->syncWithoutDetaching([$role->id]);
        
        // Clear permission cache
        $this->permissionService->clearUserPermissionCache($user);
        
        return true;
    }
    
    /**
     * Remove role from user
     */
    public function removeRoleFromUser(User $user, string $roleSlug): bool
    {
        $tenant = $this->tenantManager->getTenant();
        
        $role = Role::where('tenant_id', $tenant->id)
            ->where('slug', $roleSlug)
            ->first();
        
        if (!$role) {
            return false;
        }
        
        $user->roles()->detach($role->id);
        
        // Clear permission cache
        $this->permissionService->clearUserPermissionCache($user);
        
        return true;
    }
    
    /**
     * Get all available roles for current tenant
     */
    public function getAllRoles(): Collection
    {
        $tenant = $this->tenantManager->getTenant();
        
        return Role::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();
    }
    
    /**
     * Get roles for a specific user
     */
    public function getUserRoles(User $user): Collection
    {
        return $user->roles;
    }
    
    /**
     * Set up default roles for a new tenant
     */
    public function setupDefaultRoles(int $tenantId): void
    {
        $defaultRoles = [
            [
                'name' => 'Administrator',
                'slug' => 'administrator',
                'description' => 'Full access to all features',
                'is_system' => true
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Manage team members and most settings',
                'is_system' => true
            ],
            [
                'name' => 'Agent',
                'slug' => 'agent',
                'description' => 'Regular agent with client and deal access',
                'is_system' => true
            ],
            [
                'name' => 'Builder',
                'slug' => 'builder',
                'description' => 'Home builder with limited access',
                'is_system' => true
            ],
            [
                'name' => 'Client',
                'slug' => 'client',
                'description' => 'External client with very limited access',
                'is_system' => true
            ]
        ];
        
        foreach ($defaultRoles as $roleData) {
            $role = new Role();
            $role->tenant_id = $tenantId;
            $role->name = $roleData['name'];
            $role->slug = $roleData['slug'];
            $role->description = $roleData['description'];
            $role->is_system = $roleData['is_system'];
            $role->save();
        }
    }
}
```

### 4. Permission Middleware

Middleware to enforce permissions at the route level:

```php
namespace App\Http\Middleware;

use App\Services\Access\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    protected $permissionService;
    
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission, ?string $scope = null): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        if (!$this->permissionService->hasPermission($user, $permission, $scope)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return $next($request);
    }
}
```

### 5. Role-Based UI Manager

Service to customize UI based on user roles and permissions:

```php
namespace App\Services\UI;

use App\Models\User;
use App\Services\Access\PermissionService;
use Illuminate\Support\Collection;

class UIManager
{
    protected $permissionService;
    
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    
    /**
     * Get navigation menu for user
     */
    public function getNavigation(User $user): array
    {
        $menu = config('navigation.menu');
        
        // Filter menu items based on permissions
        return $this->filterNavigationItems($menu, $user);
    }
    
    /**
     * Filter navigation items based on user permissions
     */
    protected function filterNavigationItems(array $items, User $user): array
    {
        $result = [];
        
        foreach ($items as $item) {
            // Check if item has permission requirement
            if (isset($item['permission'])) {
                // Skip item if user doesn't have required permission
                if (!$this->permissionService->hasPermission($user, $item['permission'])) {
                    continue;
                }
            }
            
            // Process subitems if they exist
            if (isset($item['children']) && is_array($item['children'])) {
                $children = $this->filterNavigationItems($item['children'], $user);
                
                // Skip parent item if all children are filtered out
                if (empty($children) && !isset($item['route'])) {
                    continue;
                }
                
                $item['children'] = $children;
            }
            
            $result[] = $item;
        }
        
        return $result;
    }
    
    /**
     * Get dashboard widgets for user
     */
    public function getDashboardWidgets(User $user): array
    {
        $widgets = config('dashboard.widgets');
        
        // Filter widgets based on permissions
        return array_filter($widgets, function ($widget) use ($user) {
            if (!isset($widget['permission'])) {
                return true;
            }
            
            return $this->permissionService->hasPermission($user, $widget['permission']);
        });
    }
    
    /**
     * Get form fields configuration based on user permissions
     */
    public function getFormConfig(string $formKey, User $user): array
    {
        $formConfig = config("forms.{$formKey}");
        
        if (!$formConfig) {
            return [];
        }
        
        // Filter fields based on permissions
        $fields = array_filter($formConfig['fields'] ?? [], function ($field) use ($user) {
            if (!isset($field['permission'])) {
                return true;
            }
            
            return $this->permissionService->hasPermission($user, $field['permission']);
        });
        
        $formConfig['fields'] = array_values($fields);
        
        return $formConfig;
    }
    
    /**
     * Get allowed actions for a specific module
     */
    public function getAllowedActions(string $module, User $user): array
    {
        $actions = config("actions.{$module}");
        
        if (!$actions) {
            return [];
        }
        
        // Filter actions based on permissions
        return array_filter($actions, function ($action) use ($user) {
            if (!isset($action['permission'])) {
                return true;
            }
            
            return $this->permissionService->hasPermission($user, $action['permission']);
        });
    }
}
```

## Permission Types & Structure

Fusion CRM V4 implements a hierarchical permission structure:

### Module-Level Permissions

These control access to entire modules:

```php
[
    'contacts.view',       // View contacts module
    'contacts.create',     // Create new contacts
    'contacts.edit',       // Edit existing contacts
    'contacts.delete',     // Delete contacts
    
    'deals.view',          // View deals module
    'deals.create',        // Create new deals
    'deals.edit',          // Edit existing deals
    'deals.delete',        // Delete deals
    
    'properties.view',     // View properties module
    'properties.create',   // Create new properties
    'properties.edit',     // Edit existing properties
    'properties.delete',   // Delete properties
    'properties.publish',  // Publish properties to channels
    
    'users.view',          // View users
    'users.create',        // Create new users
    'users.edit',          // Edit existing users
    'users.delete',        // Delete users
    
    'settings.view',       // View settings
    'settings.edit',       // Edit settings
    
    'reports.view',        // View reports
    'reports.create',      // Create new reports
]
```

### Feature-Level Permissions

These control access to specific features within modules:

```php
[
    'contacts.export',             // Export contact data
    'contacts.import',             // Import contact data
    'contacts.merge',              // Merge duplicate contacts
    
    'deals.change_owner',          // Change deal owner
    'deals.move_stage',            // Move deal between pipeline stages
    'deals.add_notes',             // Add notes to deals
    
    'properties.upload_media',     // Upload property media
    'properties.edit_prices',      // Edit property prices
    'properties.reserve',          // Reserve properties
    
    'reports.financial',           // Access financial reports
    'reports.performance',         // Access performance reports
    'reports.export',              // Export reports
    
    'settings.roles',              // Manage roles and permissions
    'settings.templates',          // Manage email templates
    'settings.integrations',       // Manage third-party integrations
    'settings.billing',            // Access billing settings
]
```

### Record-Level Permissions via Scopes

Scopes limit permissions to specific records:

```php
// Example scopes for deal permissions
[
    'team:{team_id}',              // Scope to specific team
    'owner:{user_id}',             // Scope to records owned by user
    'status:{status}',             // Scope to specific status
    'value_min:{amount}',          // Scope to deals above amount
    'value_max:{amount}',          // Scope to deals below amount
]

// Example scopes for property permissions
[
    'project:{project_id}',        // Scope to specific project
    'property_type:{type}',        // Scope to property type
    'status:{status}',             // Scope to property status
]
```

## Pre-defined Roles

Fusion CRM V4 includes several pre-defined roles:

### Administrator

```php
[
    'permissions' => [
        // All system permissions with wildcard scopes
        '*'
    ]
]
```

### Manager

```php
[
    'permissions' => [
        // Module-level permissions
        'contacts.*',
        'deals.*',
        'properties.*',
        'users.view',
        'users.edit',
        'settings.view',
        'reports.*',
        
        // Feature-level restrictions
        '!settings.billing',
        '!users.delete',
    ],
    'scopes' => [
        'team:*',         // All teams
        'owner:*',        // All owners
        'project:*'       // All projects
    ]
]
```

### Agent

```php
[
    'permissions' => [
        // Module-level permissions
        'contacts.view',
        'contacts.create',
        'contacts.edit',
        'deals.view',
        'deals.create',
        'deals.edit',
        'deals.move_stage',
        'deals.add_notes',
        'properties.view',
        'properties.reserve',
        'reports.view',
    ],
    'scopes' => [
        'owner:{user_id}',            // Only their own records
        'team:{assigned_team_id}'     // Their team's records
    ]
]
```

### Builder

```php
[
    'permissions' => [
        'properties.view',
        'properties.edit',
        'properties.upload_media',
    ],
    'scopes' => [
        'project:{assigned_projects}'  // Only their assigned projects
    ]
]
```

### Client

```php
[
    'permissions' => [
        'properties.view',
    ],
    'scopes' => [
        'property_type:*',             // All property types
        'status:available'             // Only available properties
    ]
]
```

## Implementation Strategy

### Phase 1: Core Permission Framework

1. **Base Permission System**
   - Implement database schema
   - Create permission and role services
   - Add middleware for route protection

2. **UI Integration**
   - Build dynamic menu system
   - Implement permission-based UI components
   - Create permission-based form field visibility

### Phase 2: Role Management

3. **Role Management**
   - Develop role admin interface
   - Implement role inheritance
   - Role assignment to users

4. **Permission Scopes**
   - Implement record-level filtering
   - Add scope validation
   - Dynamic query modification based on scopes

### Phase 3: Advanced Features

5. **Permission Presets**
   - Create role templates
   - Implement bulk permission assignment
   - Role cloning functionality

6. **Auditing & Security**
   - Add permission change logging
   - Implement access attempt tracking
   - Develop security reports

## Security Considerations

1. **Privilege Escalation Prevention**
   - Users cannot grant permissions they don't have
   - Role hierarchy enforced through validation
   - System roles protected from modification

2. **Tenant Isolation**
   - Strict tenant boundaries for all role operations
   - Cross-tenant permission checks
   - Tenant context enforcement in middleware

3. **Permission Caching**
   - Efficient caching of user permissions
   - Cache invalidation on permission changes
   - Fallback mechanism for cache failures

4. **Audit Logging**
   - All permission changes logged
   - Access attempts tracked
   - Regular security audits

## Conclusion

The Role & Permission System architecture in Fusion CRM V4 provides:

1. **Granular Access Control**: Precise control over feature access
2. **Flexible Role Management**: Customizable roles with inheritance
3. **Record-Level Security**: Scope-based filtering of visible records
4. **Performance-Optimized**: Efficient permission checking with caching
5. **Security-Focused**: Comprehensive logging and audit trails

This system ensures that organizations can precisely control access to sensitive data and features, while maintaining flexibility to define custom roles that match their organizational structure. The tenant isolation ensures complete data security in a multi-tenant environment. 