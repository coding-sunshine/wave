# Spatie Packages Configuration Guide for Fusion CRM V4

This guide provides detailed instructions for configuring all the Spatie packages used in Fusion CRM V4, integrated with DevDojo Wave's built-in subscription and tenant system.

## Table of Contents

1. [Wave's Tenant System](#waves-tenant-system)
2. [Laravel-Permission Configuration](#laravel-permission-configuration)
3. [Laravel-MediaLibrary Configuration](#laravel-medialibrary-configuration)
4. [Laravel-Tags Configuration](#laravel-tags-configuration)
5. [Laravel-Comments Configuration](#laravel-comments-configuration)
6. [Laravel-Model-States Configuration](#laravel-model-states-configuration)
7. [Laravel-Data Configuration](#laravel-data-configuration)
8. [Laravel-Dashboard Configuration](#laravel-dashboard-configuration)
9. [Filament Integration](#filament-integration)

## Wave's Tenant System

Fusion CRM V4 leverages DevDojo Wave's built-in tenant system, which uses a single database with tenant isolation via a `team_id` column (Wave's approach to multi-tenancy).

### Key Implementation Points

1. **Team/Tenant Scopes**: Wave already scopes queries by the current user's team. We'll adapt this for our additional models:

```php
<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTeam
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToTeam()
    {
        static::creating(function ($model) {
            if (!isset($model->team_id) && auth()->check()) {
                $model->team_id = auth()->user()->team_id;
            }
        });

        static::addGlobalScope('team', function (Builder $builder) {
            if (auth()->check() && auth()->user()->team_id) {
                $builder->where('team_id', auth()->user()->team_id);
            }
        });
    }
}
```

2. **Wave's Team Relationship**: Wave already provides a User-Team relationship which we'll leverage across the Spatie package integrations.

## Laravel-Permission Configuration

Spatie's Laravel-Permission package requires integration with Wave's team-based tenant system.

### Configuration Steps

1. Modify the `permission_tables.php` config to work with Wave's team structure:

```php
return [
    // ... existing config
    
    'team_foreign_key' => 'team_id',
    
    'teams' => true,
];
```

2. Update migration files to include `team_id`:

```php
Schema::create($tableNames['permissions'], function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('team_id')->nullable(); // For Wave's team-based isolation
    $table->string('name');
    $table->string('guard_name');
    $table->timestamps();
    
    $table->foreign('team_id')
        ->references('id')
        ->on('teams')
        ->onDelete('cascade');
    
    $table->unique(['name', 'guard_name', 'team_id']);
});
```

3. Extend the `Permission` and `Role` models with team awareness:

```php
namespace App\Models;

use App\Models\Traits\BelongsToTeam;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use BelongsToTeam;
    
    protected function getDefaultGuardName(): string
    {
        return 'web';
    }
}
```

4. Register the models in your `AuthServiceProvider`:

```php
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

public function boot(): void
{
    $this->app->bind(PermissionRegistrar::class, function ($app) {
        return new PermissionRegistrar($app);
    });
    
    $this->app->afterResolving(PermissionRegistrar::class, function ($permissionLoader) {
        $permissionLoader->setPermissionClass(\App\Models\Permission::class);
        $permissionLoader->setRoleClass(\App\Models\Role::class);
    });
}
```

## Laravel-MediaLibrary Configuration

Configure MediaLibrary to work with Wave's team system.

### Configuration Steps

1. Update `media-library.php` config:

```php
return [
    // ... existing config
    
    'custom_path_generator_class' => \App\Support\TeamPathGenerator::class,
];
```

2. Create a custom path generator for team files:

```php
namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class TeamPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->getTeamPath($media) . '/' . $media->id . '/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getTeamPath($media) . '/' . $media->id . '/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getTeamPath($media) . '/' . $media->id . '/responsive-images/';
    }
    
    protected function getTeamPath(Media $media): string
    {
        $teamId = $media->getCustomProperty('team_id') ?? 'shared';
        return 'teams/' . $teamId;
    }
}
```

3. Extend the `Media` model to work with Wave's team system:

```php
namespace App\Models;

use App\Models\Traits\BelongsToTeam;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class Media extends SpatieMedia
{
    use BelongsToTeam;
    
    protected static function booted()
    {
        static::creating(function ($media) {
            if (auth()->check() && !$media->hasCustomProperty('team_id')) {
                $media->setCustomProperty('team_id', auth()->user()->team_id);
            }
        });
    }
}
```

## Laravel-Tags Configuration

Configure the Tags package to work with Wave's team system.

### Configuration Steps

1. Update `tags.php` config:

```php
return [
    // ... existing config
    
    'team_foreign_key' => 'team_id',
];
```

2. Extend the `Tag` model:

```php
namespace App\Models;

use App\Models\Traits\BelongsToTeam;
use Spatie\Tags\Tag as SpatieTag;

class Tag extends SpatieTag
{
    use BelongsToTeam;
    
    public static function getWithType(string $type, string $locale = null): Builder
    {
        $locale = $locale ?? app()->getLocale();
        
        return static::query()
            ->where('type', $type)
            ->where(function($query) use ($locale) {
                $query->whereJsonContains('name->'.$locale, 'name')
                    ->orWhereJsonContains('name->en', 'name')
                    ->orWhereNull('name');
            });
    }
}
```

## Laravel-Comments Configuration

Configure the Comments package to work with Wave's team system.

### Configuration Steps

1. Update `comments.php` config:

```php
return [
    // ... existing config
    
    'comment_model' => \App\Models\Comment::class,
    'reaction_model' => \App\Models\Reaction::class,
];
```

2. Extend the `Comment` model:

```php
namespace App\Models;

use App\Models\Traits\BelongsToTeam;
use Spatie\Comments\Models\Comment as SpatieComment;

class Comment extends SpatieComment
{
    use BelongsToTeam;
}
```

## Laravel-Model-States Configuration

Configure Model-States to work with Wave's team system.

### Configuration Steps

1. Ensure state transitions check team permissions:

```php
namespace App\States\Deal;

use Spatie\ModelStates\Transition;

class NewToQualifiedTransition extends Transition
{
    public function handle(): DealState
    {
        // Check if the user belongs to the same team as the deal
        if ($this->deal->team_id !== auth()->user()->team_id) {
            throw new \Exception('You are not authorized to modify this deal.');
        }
        
        // Execute the transition
        $this->deal->qualified_at = now();
        $this->deal->save();
        
        return new Qualified($this->deal);
    }
}
```

## Laravel-Data Configuration

Configure Data to validate and respect Wave's team boundaries.

### Configuration Steps

1. Create a base `TeamData` class:

```php
namespace App\Data;

use Spatie\LaravelData\Data;

abstract class TeamData extends Data
{
    public ?int $team_id;
    
    public static function fromArray(array $data): static 
    {
        if (auth()->check() && !isset($data['team_id'])) {
            $data['team_id'] = auth()->user()->team_id;
        }
        
        return parent::fromArray($data);
    }
    
    protected function validateTeamAccess(?int $team_id): void
    {
        if (auth()->check() && $team_id !== auth()->user()->team_id) {
            throw new \Exception('Data validation failed: team mismatch');
        }
    }
}
```

2. Use this base class for all your Data objects:

```php
namespace App\Data;

class DealData extends TeamData
{
    public function __construct(
        public ?int $id,
        public ?int $team_id,
        public string $title,
        // ... other properties
    ) {
        $this->validateTeamAccess($team_id);
    }
}
```

## Laravel-Dashboard Configuration

Configure Dashboard to respect Wave's team boundaries.

### Configuration Steps

1. Update `dashboard.php` config:

```php
return [
    // ... existing config
    
    'team_tile_cache_prefix' => 'team_',
];
```

2. Create a base `TeamAwareTile` class:

```php
namespace App\Dashboard;

use Spatie\Dashboard\Models\Tile;

abstract class TeamAwareTile extends Tile
{
    public function getData(): array
    {
        $cacheKey = $this->getDashboardName() . '.' . $this->getName();
        
        if (auth()->check()) {
            $cacheKey = 'team_' . auth()->user()->team_id . '.' . $cacheKey;
        }
        
        return $this->cache->get($cacheKey, []);
    }
    
    public function put(array $data): self
    {
        $cacheKey = $this->getDashboardName() . '.' . $this->getName();
        
        if (auth()->check()) {
            $cacheKey = 'team_' . auth()->user()->team_id . '.' . $cacheKey;
        }
        
        $this->cache->put($cacheKey, $data, $this->refreshInSeconds);
        
        return $this;
    }
}
```

## Filament Integration

Integrate Spatie packages with Filament admin panel while respecting Wave's team system.

### Configuration Steps

1. Create Filament Resources for each model:

```php
namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    protected static function getNavigationGroup(): ?string
    {
        return __('User Management');
    }
    
    // Use Spatie Permission in Filament form
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ... other fields
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->multiple(),
                Forms\Components\Select::make('permissions')
                    ->relationship('permissions', 'name')
                    ->preload()
                    ->multiple(),
            ]);
    }
    
    // Filter by team automatically
    protected static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->where('team_id', auth()->user()->team_id);
    }
}
```

2. Create custom Filament plugins for each Spatie package:

```php
namespace App\Filament\Plugins;

use Filament\PluginServiceProvider;
use Spatie\FilamentMediaLibrary\FilamentMediaLibraryPlugin;

class SpatieMediaLibraryPlugin extends PluginServiceProvider
{
    public static string $name = 'media-library';
    
    protected array $resources = [
        // Your custom media resources
    ];
    
    public function boot()
    {
        parent::boot();
        
        // Register team-aware versions of components
    }
}
```

This guide provides a foundation for integrating all the Spatie packages with Wave's built-in team-based tenant system. Additional customization may be needed based on your specific requirements.
