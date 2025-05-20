---
trigger: manual
glob: ["**/*.{php,js,jsx,ts,tsx,css,blade.php}", "!vendor/**/*", "!node_modules/**/*"]
description: Comprehensive code style guide for PHP, Laravel, React, Livewire, AlpineJS, and Tailwind
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
    Submit
</button>
```

### 2. Component Patterns
```blade
<x-card class="divide-y divide-gray-200">
    <x-card.header class="p-4 bg-gray-50">
        {{ $title }}
    </x-card.header>
    
    <x-card.body class="p-4">
        {{ $slot }}
    </x-card.body>
</x-card>
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
