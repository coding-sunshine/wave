# Authentication & User Management Documentation

*A comprehensive guide to authentication and user management features in Fusion CRM v4*

---

## Overview

This documentation covers the authentication and user management features of Fusion CRM, built on the Wave SaaS framework. The system combines Laravel's authentication, DevDojo Auth package, Spatie Permissions, and custom CRM enhancements.

## Current Implementation Status

### ✅ Implemented Features

| Feature | Description | Status | Documentation |
|---------|-------------|--------|---------------|
| **Core Authentication** | Username/email login, registration, password reset | ✅ IMPLEMENTED | [Authentication](./authentication.md) |
| **JWT Authentication** | Token-based API authentication with refresh | ✅ IMPLEMENTED | [JWT Authentication](./jwt_authentication.md) |
| **Spatie Permissions** | Advanced role and permission management | ✅ IMPLEMENTED | [Spatie Permissions](./spatie_permissions.md) |
| **User Impersonation** | Admin ability to login as other users | ✅ IMPLEMENTED | Built into Wave |
| **Two-Factor Auth** | Database columns ready, implementation pending | ⚠️ PARTIAL | Columns exist, UI pending |
| **Social Authentication** | OAuth provider support structure | ⚠️ PARTIAL | Tables exist, providers pending |

### 🔴 Not Yet Implemented

| Feature | Description | Priority | Notes |
|---------|-------------|----------|-------|
| **CRM-Specific Roles** | Agent, Broker, Client, Developer roles | HIGH | Required for Phase 1 |
| **Team Permissions** | Team-based data isolation and access | HIGH | Critical for multi-tenancy |
| **Advanced Security** | IP whitelisting, device tracking, audit logs | MEDIUM | Phase 2 enhancement |
| **SSO Integration** | SAML/OAuth for enterprise clients | LOW | Future enhancement |

## System Architecture

### Authentication Stack
```
┌─────────────────────────────────────┐
│         Fusion CRM UI               │
├─────────────────────────────────────┤
│     DevDojo Auth Package            │ ← Custom views & flows
├─────────────────────────────────────┤
│     Laravel Authentication          │ ← Core auth system
├─────────────────────────────────────┤
│     Spatie Permissions              │ ← Roles & permissions
├─────────────────────────────────────┤
│     JWT Auth (API)                  │ ← API authentication
└─────────────────────────────────────┘
```

### Key Components

1. **User Model** (`app/Models/User.php`)
   - Extends `Wave\User`
   - Implements `FilamentUser`, `JWTSubject`
   - Uses traits: `HasRoles`, `Impersonate`, `HasProfileKeyValues`
   - Auto-generates username from name
   - Assigns default role on creation

2. **Authentication Controllers**
   - `app/Http/Controllers/Auth/` - Web authentication
   - `wave/src/Http/Controllers/API/AuthController.php` - API authentication

3. **Middleware**
   - `auth` - Standard web authentication
   - `auth:api` - JWT API authentication
   - `role:name` - Spatie role checking
   - `permission:name` - Spatie permission checking

4. **Database Tables**
   - `users` - Core user data with 2FA columns
   - `user_social_provider` - OAuth provider links
   - `roles` & `permissions` - Spatie tables
   - `model_has_roles` & `model_has_permissions` - Assignments

## Configuration Files

- **Auth Config**: `config/auth.php`
- **JWT Config**: `config/jwt.php`
- **Permission Config**: `config/permission.php`
- **DevDojo Auth**: `config/devdojo/auth/`

## API Endpoints

### Authentication
- `POST /api/v1/auth/login` - Login with credentials
- `POST /api/v1/auth/logout` - Logout and invalidate token
- `POST /api/v1/auth/refresh` - Refresh JWT token
- `POST /api/v1/auth/register` - Register new user
- `GET /api/v1/auth/user` - Get authenticated user

## Development Guidelines

### Adding New Roles
```php
// In a seeder or migration
use Spatie\Permission\Models\Role;

$agentRole = Role::create([
    'name' => 'agent',
    'guard_name' => 'web',
    'description' => 'Real estate agent with lead and property access'
]);
```

### Checking Permissions
```php
// In controllers
if ($user->can('manage-leads')) {
    // User has permission
}

// In Blade views
@can('manage-leads')
    <!-- Show content -->
@endcan

// In Filament resources
public static function canViewAny(): bool
{
    return auth()->user()->can('view-leads');
}
```

### Protecting API Routes
```php
Route::middleware(['auth:api'])->group(function () {
    Route::get('/leads', [LeadController::class, 'index']);
});
```

## Next Steps for CRM Implementation

1. **Create CRM-Specific Roles**
   - Define Agent, Broker, Client, Developer roles
   - Set up permission matrix for each role
   - Implement role-based dashboards

2. **Implement Team-Based Permissions**
   - Add team association to users
   - Create team-based data scoping
   - Implement cross-team collaboration rules

3. **Complete 2FA Implementation**
   - Add 2FA setup UI in user profile
   - Implement 2FA verification flow
   - Add backup codes generation

4. **Add Security Enhancements**
   - Implement login attempt tracking
   - Add IP-based restrictions
   - Create security audit logs

---

*For Wave-specific features, refer to the [Wave documentation](https://devdojo.com/wave/docs). For Laravel authentication details, see the [Laravel docs](https://laravel.com/docs/authentication).*
