---
trigger: manual
glob: ["resources/js/**/*.{js,jsx,ts,tsx}", "app/Http/Controllers/**/*Controller.php", "routes/**/*.php"]
description: Inertia.js best practices with Laravel and React
---

# Inertia.js Best Practices

## 1. Controller Structure

### 1.1 Basic Controller Pattern
```php
class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Users/Index', [
            'users' => UserResource::collection(
                User::query()
                    ->latest()
                    ->paginate()
            ),
            'filters' => Request::only(['search', 'role', 'trashed']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'roles' => Role::all(),
        ]);
    }
}
```

### 1.2 Shared Data
```php
// HandleInertiaRequests middleware
class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? UserResource::make($request->user()) : null,
                'permissions' => $request->user()?->permissions ?? [],
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'app' => [
                'name' => config('app.name'),
                'environment' => config('app.environment'),
            ],
        ]);
    }
}
```

## 2. React Components

### 2.1 Page Component Structure
```tsx
import { Head, useForm } from '@inertiajs/react';
import { PageProps } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
}

interface UsersIndexProps extends PageProps {
    users: {
        data: User[];
        meta: {
            current_page: number;
            last_page: number;
        };
    };
    filters: {
        search?: string;
        role?: string;
        trashed?: string;
    };
}

export default function UsersIndex({ users, filters }: UsersIndexProps) {
    const { data, setData, get, processing } = useForm(filters);

    function handleFiltersChange(e: React.ChangeEvent<HTMLInputElement>) {
        setData(e.target.name, e.target.value);
        get(route('users.index'), {
            preserveState: true,
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Users" />
            
            <div className="max-w-7xl mx-auto py-6">
                {/* Content */}
            </div>
        </>
    );
}
```

### 2.2 Form Handling
```tsx
import { useForm } from '@inertiajs/react';

interface FormData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export default function UserCreate() {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('users.store'), {
            onSuccess: () => {
                // Handle success
            },
        });
    }

    return (
        <form onSubmit={handleSubmit}>
            <Input
                label="Name"
                value={data.name}
                error={errors.name}
                onChange={e => setData('name', e.target.value)}
            />
            {/* Other fields */}
        </form>
    );
}
```

## 3. Best Practices

### 3.1 Route Organization
```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('users', [UserController::class, 'index'])
        ->name('users.index')
        ->middleware('can:view-users');
        
    Route::post('users', [UserController::class, 'store'])
        ->name('users.store')
        ->middleware('can:create-users');
});
```

### 3.2 TypeScript Integration
```tsx
// resources/js/types/index.d.ts
declare namespace App {
    interface User {
        id: number;
        name: string;
        email: string;
        permissions: string[];
    }
}

declare module '@inertiajs/core' {
    interface PageProps {
        auth: {
            user: App.User;
            permissions: string[];
        };
        flash: {
            message?: string;
            success?: string;
            error?: string;
        };
        errors: Record<string, string>;
    }
}
```

### 3.3 Layout Handling
```tsx
// resources/js/Layouts/AppLayout.tsx
import { PropsWithChildren } from 'react';
import Navigation from '@/Components/Navigation';

export default function AppLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen bg-gray-100">
            <Navigation />
            <main>{children}</main>
        </div>
    );
}

// Usage in page component
UsersIndex.layout = page => <AppLayout>{page}</AppLayout>;
```

### 3.4 Error Handling
```tsx
// resources/js/Components/ErrorBoundary.tsx
import { Component, PropsWithChildren } from 'react';

interface State {
    hasError: boolean;
}

export default class ErrorBoundary extends Component<PropsWithChildren, State> {
    constructor(props: PropsWithChildren) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError(): State {
        return { hasError: true };
    }

    render() {
        if (this.state.hasError) {
            return <div>Something went wrong</div>;
        }

        return this.props.children;
    }
}
```

## 4. Performance Optimization

### 4.1 Lazy Loading
```tsx
// resources/js/Pages/index.ts
import { lazy } from 'react';

export const UsersIndex = lazy(() => import('./Users/Index'));
export const UserCreate = lazy(() => import('./Users/Create'));
```

### 4.2 Partial Reloads
```php
// Controller
public function update(UpdateUserRequest $request, User $user): JsonResponse
{
    $user->update($request->validated());

    return response()->json([
        'user' => UserResource::make($user),
    ]);
}

// React Component
function handleUpdate() {
    patch(route('users.update', user.id), data, {
        preserveScroll: true,
        preserveState: true,
        only: ['user'],
    });
}
```

### 4.3 Asset Management
```php
// config/inertia.php
return [
    'ssr' => [
        'enabled' => true,
        'url' => 'http://127.0.0.1:13714',
    ],
    'version' => [
        'current' => fn () => md5_file(public_path('mix-manifest.json')),
    ],
];
```

## 5. Security

### 5.1 Authorization
```php
// Controller
public function update(User $user): Response
{
    $this->authorize('update', $user);
    
    return Inertia::render('Users/Edit', [
        'user' => UserResource::make($user),
        'roles' => Role::all(),
    ]);
}

// React Component
import { usePage } from '@inertiajs/react';

function UserActions({ user }) {
    const { auth } = usePage<PageProps>().props;
    
    if (!auth.permissions.includes('update-users')) {
        return null;
    }
    
    return (
        <button onClick={() => handleEdit(user)}>
            Edit User
        </button>
    );
}
```

### 5.2 CSRF Protection
```php
// Already handled by Laravel, but ensure proper setup
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    // Add any routes that should be excluded
];
```

## 6. Testing

### 6.1 Controller Tests
```php
it('displays users page with correct props', function () {
    $users = User::factory()->count(3)->create();
    
    $response = $this->actingAs($this->user)
        ->get(route('users.index'));
        
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Users/Index')
        ->has('users.data', 3)
        ->has('filters')
    );
});
```

### 6.2 React Component Tests
```tsx
import { render, screen } from '@testing-library/react';
import { createInertiaApp } from '@inertiajs/react';

it('renders user form correctly', async () => {
    const app = await createInertiaApp({
        page: {
            component: UserCreate,
            props: {
                errors: {},
                auth: { user: null },
            },
        },
        render: render,
    });
    
    expect(screen.getByLabelText('Name')).toBeInTheDocument();
    expect(screen.getByLabelText('Email')).toBeInTheDocument();
});
```
