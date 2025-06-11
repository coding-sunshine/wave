# Spatie Laravel Permission Guide for Wave CRM

*A complete reference for implementing role-based access control in the Wave SaaS starter kit (Laravel 12, PHP 8.3).*

---

## 1. What is Spatie Laravel Permission?

[Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) is a package that provides a flexible and elegant way to implement role-based access control in Laravel applications. It provides:

* Simple API for working with permissions and roles
* Database-backed permission storage
* Role and permission assignment to users
* Permission inheritance through roles
* Permission caching for optimal performance
* Blade directives for convenient authorization checks
* Support for multiple user models (guards)
* Super-admin role capability

Wave uses Spatie Laravel Permission to manage access control throughout the application, ensuring users can only access features appropriate to their role and permissions.

---

## 2. Installation (already configured)

Spatie Laravel Permission comes pre-configured with Wave. The core dependency is:

```bash
composer require spatie/laravel-permission
```

Wave ships with `spatie/laravel-permission:^6.0` in `composer.json`.

---

## 3. Configuration

### 3.1 Database Structure

The package uses the following tables (created by its migrations):

* `permissions`: Stores individual permissions
* `roles`: Stores available roles
* `model_has_permissions`: Maps permissions directly to users
* `model_has_roles`: Maps roles to users
* `role_has_permissions`: Maps permissions to roles

### 3.2 Main Configuration

The configuration file is published at `config/permission.php`:

```php
<?php

declare(strict_types=1);

return [
    'models' => [
        'permission' => Spatie\Permission\Models\Permission::class,
        'role' => Spatie\Permission\Models\Role::class,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'model_morph_key' => 'model_id',
    ],

    'display_permission_in_exception' => true,
    'display_role_in_exception' => true,

    'enable_wildcard_permission' => false,

    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key' => 'spatie.permission.cache',
        'model_key' => 'name',
        'store' => 'default',
    ],
];
```

### 3.3 User Model Setup

The `User` model is configured to use the `HasRoles` trait:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    // Rest of the model...
}
```

---

## 4. Basic Usage

### 4.1 Managing Roles and Permissions

Wave provides the following predefined roles and permissions:

```php
// Roles
const ROLE_ADMIN = 'admin';
const ROLE_USER = 'user';

// Permissions
const PERMISSION_ACCESS_ADMIN = 'access_admin';
const PERMISSION_MANAGE_USERS = 'manage_users';
const PERMISSION_MANAGE_ROLES = 'manage_roles';
const PERMISSION_MANAGE_SETTINGS = 'manage_settings';
const PERMISSION_MANAGE_BILLING = 'manage_billing';
```

These are initialized in a seeder:

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        Permission::create(['name' => 'access_admin']);
        Permission::create(['name' => 'manage_users']);
        Permission::create(['name' => 'manage_roles']);
        Permission::create(['name' => 'manage_settings']);
        Permission::create(['name' => 'manage_billing']);

        // Create roles and assign permissions
        $userRole = Role::create(['name' => 'user']);
        
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
        
        // Assign admin role to first user
        User::first()->assignRole('admin');
    }
}
```

### 4.2 Assigning Roles to Users

```php
// Assign a role to a user
$user->assignRole('admin');

// Assign multiple roles
$user->assignRole(['writer', 'editor']);

// Alternative syntax
$user->assignRole('writer', 'editor');
```

### 4.3 Checking User Roles

```php
// Check if user has a role
if ($user->hasRole('admin')) {
    // User has admin role
}

// Check if user has any of the given roles
if ($user->hasAnyRole(['admin', 'editor'])) {
    // User has at least one of these roles
}

// Check if user has all of the given roles
if ($user->hasAllRoles(['admin', 'super-admin'])) {
    // User has all these roles
}
```

### 4.4 Working with Permissions

```php
// Give permission to a user
$user->givePermissionTo('manage_users');

// Give multiple permissions
$user->givePermissionTo(['manage_users', 'manage_roles']);

// Check if user has a permission
if ($user->hasPermissionTo('manage_users')) {
    // User has this permission
}

// Check permission through roles
if ($user->hasPermissionViaRole('manage_users')) {
    // User has this permission via their role
}
```

---

## 5. Advanced Features

### 5.1 Creating Custom Permissions and Roles

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateTeamManagerRoleAction
{
    public function handle(): Role
    {
        // Create team management permissions
        Permission::firstOrCreate(['name' => 'manage_team']);
        Permission::firstOrCreate(['name' => 'invite_team_members']);
        Permission::firstOrCreate(['name' => 'remove_team_members']);
        
        // Create team manager role
        $role = Role::firstOrCreate(['name' => 'team_manager']);
        
        // Assign permissions to role
        $role->givePermissionTo([
            'manage_team',
            'invite_team_members',
            'remove_team_members',
        ]);
        
        return $role;
    }
}
```

### 5.2 Using Middleware for Route Protection

Wave configures middleware in `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // Other middleware...
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

Usage in routes:

```php
// Routes requiring a specific role
Route::middleware('role:admin')->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
});

// Routes requiring specific permissions
Route::middleware('permission:manage_users')->group(function () {
    Route::resource('users', UserController::class);
});

// Routes requiring either a role OR a permission
Route::middleware('role_or_permission:admin|manage_settings')->group(function () {
    Route::get('/settings', [SettingController::class, 'index']);
});
```

### 5.3 Super Admin Role

To create a super-admin that bypasses all permission checks:

```php
// In a seeder or service
$superAdminRole = Role::create(['name' => 'super-admin']);

// In AuthServiceProvider
public function boot(): void
{
    // Default Laravel Policies...

    // Super admin bypass
    Gate::before(function ($user, $ability) {
        return $user->hasRole('super-admin') ? true : null;
    });
}
```

---

## 6. Wave Integration Patterns

### 6.1 Role-Based UI Components

Wave uses roles to dynamically display UI components:

```php
// In a Blade template
@can('access_admin')
    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
        Admin Dashboard
    </a>
@endcan

@role('admin')
    <div class="admin-controls">
        <!-- Admin-specific controls -->
    </div>
@endrole
```

### 6.2 Permission-Based Feature Access

```php
// In a controller
public function updateSettings(Request $request): RedirectResponse
{
    if (!$request->user()->can('manage_settings')) {
        abort(403);
    }
    
    // Or using the authorize method
    $this->authorize('manage_settings');
    
    // Update settings logic
    
    return redirect()->back()->with('success', 'Settings updated!');
}
```

### 6.3 Filament Admin Integration

Wave integrates Spatie permissions with Filament admin panel:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Resources\Resource;
use App\Filament\Resources\UserResource\Pages;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    public static function canAccess(): bool
    {
        return auth()->user()->can('manage_users');
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('roles.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'user' => 'primary',
                        default => 'secondary',
                    }),
            ])
            ->actions([
                // Only show delete action if user has permission
                DeleteAction::make()
                    ->visible(fn (User $record) => auth()->user()->can('manage_users')),
            ]);
    }
    
    // Other resource configuration...
}
```

---

## 7. Testing Permissions

### 7.1 Unit Testing with Permissions

Using Pest PHP for testing permissions:

```php
<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

it('allows admins to access admin dashboard', function () {
    // Create permission and role
    $permission = Permission::create(['name' => 'access_admin']);
    $role = Role::create(['name' => 'admin']);
    $role->givePermissionTo($permission);
    
    // Create user with admin role
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    // Create regular user
    $user = User::factory()->create();
    
    // Test access
    $this->actingAs($admin)->get('/admin/dashboard')->assertSuccessful();
    $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
});

it('allows users with specific permission to access protected routes', function () {
    // Create permission
    $permission = Permission::create(['name' => 'view_reports']);
    
    // Create user with direct permission
    $userWithPermission = User::factory()->create();
    $userWithPermission->givePermissionTo($permission);
    
    // Create user without permission
    $userWithoutPermission = User::factory()->create();
    
    // Test access
    $this->actingAs($userWithPermission)->get('/reports')->assertSuccessful();
    $this->actingAs($userWithoutPermission)->get('/reports')->assertForbidden();
});
```

### 7.2 Testing Policy Integration

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Post;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::create(['name' => 'edit_posts']);
});

it('allows post authors to edit their posts', function () {
    // Create author user
    $author = User::factory()->create();
    
    // Create post owned by author
    $post = Post::factory()->create(['user_id' => $author->id]);
    
    // Test policy
    $this->actingAs($author);
    expect($author->can('update', $post))->toBeTrue();
});

it('allows users with edit_posts permission to edit any post', function () {
    // Create editor user with permission
    $editor = User::factory()->create();
    $editor->givePermissionTo('edit_posts');
    
    // Create post owned by someone else
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);
    
    // Test policy
    $this->actingAs($editor);
    expect($editor->can('update', $post))->toBeTrue();
});
```

---

## 8. Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| Permission denied unexpectedly | Clear permission cache with `php artisan permission:cache-reset` |
| Newly created permissions not working | Reset the permission cache after creating new permissions |
| Role/permission not found | Check for typos in role/permission names |
| Middleware not applying | Verify route middleware registration in `Kernel.php` |
| Permission checks not working with custom guards | Verify the guard is configured correctly in `config/auth.php` |

For further assistance, see [Spatie Laravel Permission documentation](https://spatie.be/docs/laravel-permission) or the [GitHub repository](https://github.com/spatie/laravel-permission).
