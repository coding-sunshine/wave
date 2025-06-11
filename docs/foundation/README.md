# Wave Foundation Documentation

*Understanding the Wave SaaS starter kit features that power Fusion CRM v4*

---

## Overview

Fusion CRM v4 is built on top of the Wave SaaS starter kit, leveraging its robust foundation while adding specialized CRM functionality. This documentation covers how Wave features are implemented and customized for Fusion CRM.

## Wave Features Status

### ✅ Core Features Being Used

| Feature | Description | Fusion CRM Usage | Location |
|---------|-------------|------------------|----------|
| **User Management** | Complete user system with profiles | Extended with CRM fields and relationships | `app/Models/User.php` |
| **Billing & Subscriptions** | Stripe integration for SaaS billing | Ready for CRM subscription tiers | `wave/src/Subscription.php` |
| **Admin Panel** | Filament-based administration | Extended with CRM resources | `app/Filament/Resources/` |
| **API Framework** | JWT authentication and endpoints | Foundation for CRM API | `wave/src/Http/Controllers/API/` |
| **Blog System** | Content management with categories | Marketing and updates | `wave/src/Post.php` |
| **Static Pages** | Dynamic page creation | Terms, privacy, landing pages | `wave/src/Page.php` |
| **Theme System** | Multiple theme support | Using drift theme with custom branding | `resources/themes/drift/` |
| **Forms Builder** | Dynamic form creation | Lead capture and custom forms | `wave/src/Form.php` |
| **Changelog** | Version and feature tracking | Customer communication | `wave/src/Changelog.php` |

### 🔧 Wave Components

1. **Models** (`wave/src/`)
   - `User` - Base user model with SaaS features
   - `Plan` - Subscription plan management  
   - `Subscription` - User subscription tracking
   - `ApiKey` - API authentication keys
   - `Setting` - Application settings
   - `Form` & `FormEntry` - Dynamic forms
   - `Page` - Static page management
   - `Post` & `Category` - Blog system
   - `Theme` & `ThemeOptions` - Theming

2. **Service Provider** (`wave/src/WaveServiceProvider.php`)
   - Registers Wave components
   - Loads Wave routes and views
   - Configures Wave middleware

3. **Configuration** 
   - `config/wave.php` - Main Wave settings
   - `config/themes.php` - Theme configurations
   - `config/devdojo/` - DevDojo package configs

## Key Wave Dependencies

### PHP Packages (from composer.json)
- `devdojo/app: 0.11.0` - Core Wave application
- `devdojo/auth: ^1.0` - Authentication system
- `devdojo/themes: 0.0.11` - Theme system
- `filament/filament: ^3.3` - Admin panel
- `livewire/livewire: ^3.5` - Frontend framework
- `spatie/laravel-permission: ^6.12` - Permissions
- `tymon/jwt-auth: ^2.2` - JWT authentication
- `stripe/stripe-php: ^17.3` - Payment processing

### JavaScript Packages (from package.json)
- `alpinejs: ^3.4.2` - JavaScript framework
- `tailwindcss: ^3.4.17` - CSS framework
- `vite: ^6.2` - Build tool
- `axios: ^1.8.2` - HTTP client

## Customizations for Fusion CRM

### 1. User Model Extension
Located: `app/Models/User.php`
```php
class User extends \Wave\User
{
    protected static function boot()
    {
        parent::boot();
        
        // Auto-generate username from name
        static::creating(function ($user) {
            if (empty($user->username)) {
                $user->username = Str::slug($user->name . '-' . Str::random(5));
            }
        });
        
        // Assign default role on creation
        static::created(function ($user) {
            if (!$user->hasAnyRole()) {
                $user->assignRole(config('permission.default_role', 'subscriber'));
            }
        });
    }
}
```

### 2. Filament Admin Extensions
Located: `app/Filament/`
- Custom navigation groups for CRM
- Extended UserResource with CRM fields
- CRM-specific widgets and dashboard
- Custom branding and theming

### 3. Theme Customization
- Using drift theme as base (`resources/themes/drift/`)
- Custom Fusion CRM branding
- Orange/red color scheme
- Modified layouts for CRM UI

### 4. API Structure
Located: `routes/api.php`, `wave/routes/api.php`
- Extended JWT authentication
- CRM-specific endpoint structure ready
- Rate limiting configurations
- API versioning prepared

## Database Schema

### Wave Core Tables (Existing)
- `users` - User accounts with 2FA columns
- `plans` - Subscription plans
- `subscriptions` - User subscriptions  
- `api_keys` - API authentication keys
- `settings` - Application settings
- `pages` - Static page content
- `posts` & `categories` - Blog system
- `forms` & `form_entries` - Dynamic forms
- `changelogs` - Feature releases
- `themes` & `theme_options` - Theme system
- `roles` & `permissions` - Spatie RBAC tables

## Development Patterns

### 1. Extending Wave Models
```php
// Create app model extending Wave model
namespace App\Models;

class Post extends \Wave\Post
{
    // Add CRM-specific functionality
    public function relatedProperties()
    {
        return $this->belongsToMany(Property::class);
    }
}
```

### 2. Wave Service Integration
```php
// Use Wave services in CRM features
use Wave\Setting;

$appName = Setting::get('app_name', 'Fusion CRM');
```

### 3. Maintaining Wave Compatibility
- Don't modify Wave core files
- Extend models instead of changing them
- Use events and listeners for customization
- Follow Wave naming conventions

## Build and Development

### Development Command
```bash
composer run dev  # Runs server, queue, logs, and vite concurrently
```

This executes:
- `php artisan serve` - Laravel development server
- `php artisan queue:listen --tries=1` - Queue worker  
- `php artisan pail --timeout=0` - Log viewer
- `npm run dev` - Vite development server with HMR

### Wave Auto-Updates
Post-update commands automatically run:
- `php artisan filament:upgrade`
- `php artisan livewire:publish --assets`
- `php artisan optimize`
- `php artisan filament:optimize`

## Available Documentation

1. **[Wave Customizations](wave_customizations.md)** - Detailed customizations made for Fusion CRM
2. **Admin Panel** - Filament customizations and CRM resources
3. **Theme System** - Drift theme modifications and branding
4. **API Extensions** - CRM API endpoints built on Wave foundation

## Best Practices

1. **Preserve Wave Foundation**
   - Don't modify Wave core files
   - Extend rather than replace
   - Document all customizations

2. **Follow Wave Patterns**  
   - Use similar code structure
   - Maintain naming conventions
   - Leverage existing traits and services

3. **Maintain Upgradeability**
   - Test Wave updates regularly
   - Keep customizations isolated
   - Use version control effectively

## Next Steps for CRM Development

1. **Understand Current Foundation** 
   - Review existing Wave models and features
   - Understand authentication and permission system
   - Familiarize with Filament admin patterns

2. **Plan CRM Extensions**
   - Design CRM models to extend Wave patterns
   - Plan database schema additions
   - Design API endpoints following Wave structure

3. **Implement Phase 1 Features**
   - Create CRM models extending Wave base
   - Build Filament resources for CRM entities
   - Implement CRM-specific permissions and roles

---

*For detailed Wave documentation, visit [Wave Docs](https://devdojo.com/wave/docs). For implementation details, see [Wave Customizations](wave_customizations.md).*
