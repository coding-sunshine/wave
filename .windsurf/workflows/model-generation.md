---
description: Generate Eloquent model with tenant awareness and proper validation
---

# Generate Tenant-Aware Eloquent Model Workflow

This workflow helps you generate a new Eloquent model that follows the project's multi-tenant architecture, adheres to PSR-12 coding standards, and implements proper validation.

## Steps

1. Determine the model name and purpose (e.g., `Client`, `Property`, `Deal`, etc.)

2. Determine if this is a standard model or one that extends a Wave Kit model

3. For standard tenant-aware models, generate the model, migration, factory, and seeder using Artisan:
   ```bash
   php artisan make:model {ModelName} -mfs
   ```

4. For Wave Kit extended models:
   ```bash
   php artisan wave:make-model {ModelName}
   ```

5. Update the migration file to include tenant_id and appropriate columns:
   ```bash
   php artisan migrate:make create_{model_name_plural}_table
   ```

6. If extending a Wave Kit model, make sure to follow proper extension patterns:
   - Extend the appropriate Wave base model
   - Apply tenant scoping properly
   - Implement any required interface contracts

7. Refactor the generated model to follow strict typing, tenant awareness, and PSR-12 standards:
   - Add `declare(strict_types=1);` to the top of the file
   - Use the `BelongsToTenant` trait
   - Implement proper type hints for properties and methods
   - Add comprehensive PHPDoc comments

8. Create a Filament Resource for the model if needed:
   ```bash
   php artisan make:filament-resource {ModelName}
   ```

9. Create a Pest test for the model:
   ```bash
   php artisan pest:test Models/{ModelName}Test --unit
   ```

10. Create tenant-aware test cases:
    ```bash
    php artisan pest:test Models/{ModelName}TenantTest --unit
    ```

11. Run code formatting:
    ```bash
    composer lint
    ```

12. Run tests to verify the model:
    ```bash
    composer test
    ```

## Example Standard Model Structure

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Client model representing a customer in the CRM
 * 
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Models\Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Property[] $properties
 */
class Client extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the properties for the client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
```

## Example Wave Kit Extended Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wave\Models\User as WaveUser;

/**
 * Extended User model with tenant awareness
 * 
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property array|null $settings
 * @property array|null $permissions
 * @property string|null $avatar
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Property[] $properties
 */
class User extends WaveUser
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Additional fillable attributes beyond the Wave User attributes
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'settings',
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
        'permissions' => 'array',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the tenant that owns the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the properties for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
```

## Example Migration Structure

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Composite unique for tenant+email
            $table->unique(['tenant_id', 'email']);
            
            // Indexes for performance
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'name']);
        });
    }
};
```

## Example Filament Resource for Model

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

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
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->maxLength(65535),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535),
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
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Apply tenant scoping
        return parent::getEloquentQuery()
            ->whereBelongsTo(auth()->user()->tenant)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
