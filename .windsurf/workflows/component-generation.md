---
description: Generate Laravel Livewire and Filament components following project standards
---

# Generate Livewire and Filament Component Workflow

This workflow helps you generate a new Livewire or Filament component that adheres to the project's coding standards and follows best practices.

## Steps

1. Determine the component name and purpose (e.g., `ClientList`, `PropertyDetail`, etc.)

2. Determine if this is a standard Livewire component or a Filament component

3. For standard Livewire components:
   ```bash
   php artisan make:livewire {ComponentName}
   ```

4. For Filament components:
   ```bash
   php artisan make:filament-component {ComponentName}
   ```

5. If creating a tenant-aware component, implement tenant scoping:
   - Add proper tenant validation
   - Use tenant-scoped models
   - Apply tenant middleware if needed

6. Refactor the generated component to follow strict typing and PSR-12 standards
   - Add `declare(strict_types=1);` at the top of the PHP file
   - Add proper PHP 8.4 return types and parameter types
   - Add PHPStan-compatible docblocks

7. Add appropriate tests using Pest
   ```bash
   php artisan pest:test {ComponentName}ComponentTest --unit
   ```
   
8. Create tenant-aware test cases
   ```bash
   php artisan pest:test Http/{ComponentName}Test --feature
   ```

9. Run code formatting
   ```bash
   composer lint
   ```

10. Run tests to verify the component
    ```bash
    composer test
    ```

## Example Livewire Component Structure

```php
<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Client;
use App\Traits\HasTenantContext;

/**
 * Client list component with filtering and pagination
 */
class ClientList extends Component
{
    use WithPagination;
    use HasTenantContext;

    /** @var string */
    public string $search = '';

    /** @var string */
    public string $sortField = 'created_at';

    /** @var string */
    public string $sortDirection = 'desc';

    /**
     * Mount the component with tenant validation
     */
    public function mount(): void
    {
        $this->validateTenantContext();
    }

    /**
     * Update search query and reset pagination
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Sort by the given field
     */
    public function sortBy(string $field): void
    {
        $this->sortDirection = $this->sortField === $field
            ? $this->sortDirection === 'asc' ? 'desc' : 'asc'
            : 'asc';

        $this->sortField = $field;
    }

    /**
     * Render the component
     */
    public function render()
    {
        $clients = Client::query()
            ->when($this->search, fn($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.client-list', [
            'clients' => $clients,
        ]);
    }
}
```

## Example Filament Component Structure

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'CRM';

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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
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
