---
trigger: manual
glob: ["**/*.{php,js,jsx,ts,tsx,css,blade.php}", "!vendor/**/*", "!node_modules/**/*"]
description: Comprehensive code style guide for PHP, Laravel, Wave Kit, Filament, Livewire, AlpineJS, and Tailwind
---

# Code Style Guide

## PHP & Laravel

### 1. File Structure
```php
<?php

declare(strict_types=1);

namespace App\Whatever;

use Statements;

class ClassName
{
    // Code
}
```

### 2. Naming Conventions
- **Classes:** PascalCase
  ```php
  class UserController
  class CreateUserAction
  ```
- **Methods/Variables:** camelCase
  ```php
  public function getUserById()
  private string $firstName
  ```
- **Constants:** UPPER_SNAKE_CASE
  ```php
  const MAX_ATTEMPTS = 3
  ```
- **Database:** snake_case
  ```php
  Schema::create('user_profiles', function (Blueprint $table) {
      $table->id();
      $table->string('first_name');
  });
  ```

### 3. Type Declarations
```php
public function processUser(User $user): array
{
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
}
```

### 4. Laravel Conventions
```php
// Controllers
class UserController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->users->create($request->validated());
        return response()->json($user, Response::HTTP_CREATED);
    }
}

// Actions
class CreateUserAction
{
    public function handle(array $data): User
    {
        return DB::transaction(fn () => User::create($data));
    }
}
```

### 5. Multi-Tenancy Patterns
```php
// Models with tenant awareness
class Property extends Model
{
    use BelongsToTenant;
    
    protected $fillable = [
        'name',
        'address',
        'status',
        // other fields
    ];
    
    // Relationships and other methods
}

// Tenant-aware Livewire components
class ListProperties extends Component
{
    use WithTenantAwareness;
    
    public function render(): View
    {
        return view('livewire.properties.list', [
            'properties' => Property::query()
                ->forCurrentTenant()
                ->latest()
                ->paginate(),
        ]);
    }
}
```

## Wave Kit Implementation

### 1. Extending Wave Models
```php
// Tenant model extending Wave's team concept
class Tenant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        'settings',
        'api_key',
        'subscription_status',
    ];
    
    protected $casts = [
        'settings' => 'array',
    ];

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

// User model extending Wave's user model
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

## Filament Admin

### 1. Resource Structure
```php
class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->options([
                        'available' => 'Available',
                        'pending' => 'Pending',
                        'sold' => 'Sold',
                    ])
                    ->required(),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'available',
                        'warning' => 'pending',
                        'danger' => 'sold',
                    ]),
            ])
            ->filters([
                // Filters
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
```

### 2. Custom Widgets
```php
class StatsOverview extends Widget
{
    protected static string $view = 'filament.widgets.stats-overview';
    
    protected function getStats(): array
    {
        return [
            Stat::make('Total Properties', Property::count())
                ->description('Total properties in the system')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Active Listings', Property::where('status', 'available')->count())
                ->description('Properties currently listed')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
            Stat::make('Sold This Month', Property::whereMonth('sold_at', now()->month)->count())
                ->description('Properties sold this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
}
```

## Livewire Components

### 1. Component Structure
```php
class CreateUser extends Component
{
    public string $name = '';
    public string $email = '';
    
    protected $rules = [
        'name' => 'required|min:2',
        'email' => 'required|email|unique:users',
    ];
    
    public function save(): void
    {
        $this->validate();
        // Save user
    }
}
```

### 2. View Structure
```blade
<div>
    <form wire:submit.prevent="save">
        <x-input wire:model.defer="name" />
        <x-input wire:model.defer="email" type="email" />
        <x-button>Submit</x-button>
    </form>
</div>
```

## AlpineJS

### 1. Component Structure
```html
<div x-data="{ open: false }">
    <button @click="open = !open">
        Toggle
    </button>
    
    <div x-show="open" x-transition>
        Content
    </div>
</div>
```

### 2. Data Management
```js
Alpine.data('dropdown', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
    },
}));
```

## Tailwind CSS

### 1. Class Organization
```html
<button class="
    inline-flex items-center px-4 py-2  /* Layout */
    bg-blue-500 text-white      /* Colors */
    rounded-lg shadow-sm        /* Visual */
    hover:bg-blue-600          /* States */
    transition-colors          /* Animation */
    disabled:opacity-50        /* Conditions */
">
    Button Text
</button>
```

### 2. Theme Extension
```js
// tailwind.config.js
module.exports = {
    theme: {
        extend: {
            colors: {
                'primary': {
                    50: '#f8f5ff',
                    // Other shades
                    500: '#6941c6',
                    // Other shades
                    900: '#42307d',
                },
                'secondary': {
                    // Secondary color palette
                },
            },
        },
    },
};
```

## Documentation

### 1. PHPDoc Standards
```php
/**
 * Process a property listing.
 *
 * @param \App\Models\Property $property The property to process
 * @param array<string, mixed> $data Additional data for processing
 * @return array<string, mixed> Processed property data
 * @throws \App\Exceptions\PropertyProcessingException When processing fails
 */
public function processProperty(Property $property, array $data): array
{
    // Implementation
}
```

### 2. Git Standards
1. **Branch Naming:**
   ```
   feature/property-management
   bugfix/user-registration
   hotfix/security-vulnerability
   ```

2. **PR Template:**
   ```markdown
   ## Description
   Brief description of changes

   ## Changes
   - Added X feature
   - Fixed Y bug

   ## Tests
   - [ ] Unit tests
   - [ ] Feature tests
   ```

3. **Issues:**
   ```markdown
   ## Bug Report
   **Description:**
   Clear description of the issue

   **Steps to Reproduce:**
   1. Step 1
   2. Step 2

   **Expected vs Actual:**
   - Expected: X
   - Actual: Y
   ```

4. **Git Commits:**
   ```
   feat: add user registration
   fix: resolve email validation issue
   refactor: improve error handling
   ```

## Inertia.js Integration
Refer to `.windsurf/rules/inertia.rules.md` for comprehensive Inertia.js best practices and patterns.

## React & TypeScript

### 1. Component Structure
```tsx
import { FC, useState } from 'react';

interface UserFormProps {
    onSubmit: (data: UserData) => void;
    initialData?: UserData;
}

export const UserForm: FC<UserFormProps> = ({ onSubmit, initialData }) => {
    const [data, setData] = useState(initialData);
    
    return (
        <form className="space-y-4">
            {/* Component content */}
        </form>
    );
};
```

### 2. Hooks & State Management
```tsx
const useUser = (userId: string) => {
    const [user, setUser] = useState<User | null>(null);
    
    useEffect(() => {
        // Fetch user
    }, [userId]);
    
    return { user };
};
```

### 3. Event Handlers
```tsx
const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Handle submission
};
```

## General Guidelines

1. **Comments:**
   - Use PHPDoc for classes and methods
   - Add inline comments for complex logic
   - Keep comments up to date

2. **Error Handling:**
   ```php
   try {
       $result = $this->service->process();
   } catch (ServiceException $e) {
       Log::error('Service failed', ['error' => $e->getMessage()]);
       throw new ProcessingException($e->getMessage());
   }
   ```

3. **Testing:**
   ```php
   it('creates a new user', function () {
       $response = post('/users', [
           'name' => 'John Doe',
           'email' => 'john@example.com',
       ]);
       
       $response->assertCreated();
       expect(User::count())->toBe(1);
   });
   ```

4. **Git Commits:**
   ```
   feat: add user registration
   fix: resolve email validation issue
   refactor: improve error handling
   ```
