# Filament PHP Integration with Spatie Packages

This guide demonstrates how to effectively integrate Filament PHP admin panel with Spatie packages in the Fusion CRM V4 project, ensuring proper team isolation within a single-database architecture.

## Table of Contents

1. [Introduction](#introduction)
2. [General Setup](#general-setup)
3. [Laravel-Permission Integration](#laravel-permission-integration)
4. [Laravel-MediaLibrary Integration](#laravel-medialibrary-integration)
5. [Laravel-Tags Integration](#laravel-tags-integration)
6. [Laravel-Comments Integration](#laravel-comments-integration)
7. [Laravel-Model-States Integration](#laravel-model-states-integration)
8. [Laravel-Data Integration](#laravel-data-integration)
9. [Laravel-Dashboard Integration](#laravel-dashboard-integration)
10. [Best Practices](#best-practices)

## Introduction

Filament PHP provides an elegant admin panel solution that pairs exceptionally well with Spatie packages. This guide demonstrates how to integrate both while maintaining team isolation in a single-database environment.

## General Setup

### Filament Configuration

First, ensure that your Filament resources are properly integrated with Wave's team system:

```php
namespace App\Providers;

use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Fusion CRM')
            // Add team-aware middleware to ensure proper isolation
            ->middleware([
                \App\Http\Middleware\TeamContext::class,
            ])
            ->resources([
                // Your resources here
            ]);
    }
}
```

### Team Context Middleware

Create a middleware to ensure all Filament operations occur within the user's team context:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamContext
{
    public function handle(Request $request, Closure $next)
    {
        // Ensure the user is authenticated
        if (!Auth::check()) {
            return $next($request);
        }

        // Apply team context to the session if not already set
        if (!session()->has('team_id') && Auth::user()->team_id) {
            session(['team_id' => Auth::user()->team_id]);
        }

        return $next($request);
    }
}
```

### Team-Aware Model Base Class

Create a base model class that automatically applies team scoping:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;

abstract class Model extends EloquentModel
{
    protected static function booted()
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

## Laravel-Permission Integration

### Permission Resource

Create a Filament resource for managing permissions:

```php
namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Models\Permission;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    
    protected static function getNavigationGroup(): ?string
    {
        return __('User Management');
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('guard_name')
                    ->required()
                    ->default('web')
                    ->maxLength(255),
                // Do not expose team_id in the form - it's handled automatically
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                // Team filtering is handled by global scope
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            RelationsManagers\RolesRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
```

### Role Resource

Create a Filament resource for managing roles:

```php
namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static function getNavigationGroup(): ?string
    {
        return __('User Management');
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('guard_name')
                    ->required()
                    ->default('web')
                    ->maxLength(255),
                Forms\Components\Select::make('permissions')
                    ->relationship('permissions', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                // Team ID is handled automatically
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                // Team filtering is handled by global scope
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            RelationsManagers\PermissionsRelationManager::class,
            RelationsManagers\UsersRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
```

### User Resource with Roles and Permissions

Enhance the User resource to include role and permission management:

```php
namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static function getNavigationGroup(): ?string
    {
        return __('User Management');
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => 
                        !empty($state) ? Hash::make($state) : null)
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255)
                    ->dehydrated(fn ($state) => filled($state))
                    ->label($context === 'create' ? 'Password' : 'New Password'),
                
                // Spatie Roles and Permissions Integration
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                
                Forms\Components\Select::make('direct_permissions')
                    ->relationship('permissions', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->label('Direct Permissions'),
                
                // Team ID is not editable directly - it's managed by Wave
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->listWithLineBreaks()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                // Team filtering is handled by global scope in applicable models
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            RelationsManagers\RolesRelationManager::class,
            RelationsManagers\PermissionsRelationManager::class,
        ];
    }
}
```

## Creating Team-Aware Resources

### Team-Aware Relation Manager

Create a base relation manager that respects team boundaries:

```php
namespace App\Filament\Resources\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager as BaseRelationManager;
use Illuminate\Database\Eloquent\Builder;

abstract class RelationManager extends BaseRelationManager
{
    protected function getResourceQuery(): Builder
    {
        $query = parent::getResourceQuery();
        
        // Add team scope if the model has a team_id field
        if (auth()->check() && auth()->user()->team_id && 
            $this->getRelatedModel()->hasColumn('team_id')) {
            $query->where('team_id', auth()->user()->team_id);
        }
        
        return $query;
    }
}
```

### Team Filters for Resource Tables

Create a reusable team filter for your resources:

```php
namespace App\Filament\Tables\Filters;

use App\Models\Team;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class TeamFilter extends SelectFilter
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->visible(fn() => auth()->user()->can('see_all_teams'));
        
        $this->options(
            Team::all()->pluck('name', 'id')->toArray()
        );
        
        $this->query(function (Builder $query, array $data): Builder {
            if (!isset($data['value']) || empty($data['value'])) {
                return $query;
            }
            
            return $query->where('team_id', $data['value']);
        });
    }
}
```

## Next Steps

In [Part 2 of this guide](filament-spatie-integration-guide-part2.md), we'll cover:

1. Integration of Laravel-MediaLibrary with Filament
2. Creating custom file upload components
3. Managing media collections and conversions

In [Part 3 of this guide](filament-spatie-integration-guide-part3.md), we'll explore:

1. Integration of Laravel-Model-States with Filament
2. Using Laravel-Data with Filament forms
3. Creating dashboard widgets using Laravel-Dashboard
