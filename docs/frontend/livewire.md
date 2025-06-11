# Livewire Guide for Wave CRM

*A complete reference for building interactive UI components with Livewire in the Wave SaaS starter kit (Laravel 12, PHP 8.3).*

---

## 1. What is Livewire?

[Livewire](https://livewire.laravel.com) is a full-stack framework for Laravel that makes building dynamic interfaces simple without leaving the comfort of PHP. It provides:

* Real-time UI updates without writing JavaScript
* Two-way data binding between PHP and the frontend
* Form validation with real-time feedback
* Event handling and component communication
* File uploads with progress indicators
* Pagination, sorting, and filtering capabilities
* State preservation during page refreshes

Wave leverages Livewire 3.x to create interactive components that enhance the user experience while maintaining developer productivity.

---

## 2. Installation (already configured)

Livewire comes pre-configured with Wave. The core dependency is:

```bash
composer require livewire/livewire
```

Wave ships with `livewire/livewire:^3.5` in `composer.json`.

Frontend assets are automatically published during deployment with:

```bash
php artisan livewire:publish --assets
```

---

## 3. Configuration

### 3.1 Configuration Files

Livewire configuration is defined in `config/livewire.php`. Key settings include:

| Setting | Purpose |
|---------|---------|
| `app_url` | The application URL for route generation |
| `asset_url` | URL for static assets |
| `middleware_group` | Middleware to apply to Livewire requests |
| `temporary_file_upload` | Configuration for file uploads |

### 3.2 CSRF Protection

Livewire automatically handles CSRF protection for all requests. The CSRF token is included in the page's meta tags:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

---

## 4. Basic Usage

### 4.1 Creating a Livewire Component

To create a new component:

```bash
php artisan make:livewire TodoList
```

This generates:
- `app/Livewire/TodoList.php` - Component class
- `resources/views/livewire/todo-list.blade.php` - Component view

Basic component structure:

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Todo;

class TodoList extends Component
{
    public $todos;
    public string $newTodo = '';
    
    public function mount()
    {
        $this->todos = auth()->user()->todos;
    }
    
    public function addTodo()
    {
        $this->validate([
            'newTodo' => 'required|min:3|max:255',
        ]);
        
        Todo::create([
            'user_id' => auth()->id(),
            'name' => $this->newTodo,
        ]);
        
        $this->reset('newTodo');
        $this->todos = auth()->user()->todos->fresh();
    }
    
    public function render()
    {
        return view('livewire.todo-list');
    }
}
```

### 4.2 Rendering the Component

In a Blade view:

```blade
<div>
    @livewire('todo-list')
</div>
```

Or using the tag syntax:

```blade
<livewire:todo-list />
```

Or with parameters:

```blade
<livewire:todo-list :list-id="$listId" />
```

---

## 5. Advanced Features

### 5.1 Form Validation

```php
public function save()
{
    $validated = $this->validate([
        'title' => 'required|min:3|max:255',
        'description' => 'nullable|max:1000',
        'dueDate' => 'required|date|after:today',
        'priority' => 'required|in:low,medium,high',
    ]);
    
    $todo = Todo::create($validated);
    
    session()->flash('message', 'Todo created successfully!');
    
    return redirect()->route('todos.show', $todo);
}
```

### 5.2 Real-time Validation

```php
// In component class
public function updated($propertyName)
{
    $this->validateOnly($propertyName, [
        'title' => 'required|min:3|max:255',
        'dueDate' => 'required|date|after:today',
    ]);
}
```

### 5.3 File Uploads

```php
use Livewire\WithFileUploads;

class UploadDocument extends Component
{
    use WithFileUploads;
    
    public $document;
    
    public function save()
    {
        $this->validate([
            'document' => 'required|file|max:10240|mimes:pdf,docx',
        ]);
        
        $path = $this->document->store('documents', 'public');
        
        auth()->user()->documents()->create([
            'path' => $path,
            'name' => $this->document->getClientOriginalName(),
            'size' => $this->document->getSize(),
        ]);
        
        session()->flash('message', 'Document uploaded successfully!');
        $this->reset('document');
    }
}
```

### 5.4 Pagination

```php
use Livewire\WithPagination;

class TodoList extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public function render()
    {
        return view('livewire.todo-list', [
            'todos' => auth()->user()->todos()->paginate(10),
        ]);
    }
}
```

---

## 6. Wave Integration Patterns

### 6.1 Component Naming Conventions

Following PSR-12 and project standards:

```
app/Livewire/
  ├── Dashboard/
  │   ├── ActivityFeed.php
  │   ├── Metrics/
  │   │   ├── ActiveUsers.php
  │   │   └── RevenueChart.php
  │   └── RecentMessages.php
  ├── Settings/
  │   ├── Profile.php
  │   └── Billing.php
  └── Todos/
      ├── TodoList.php
      └── TodoCreator.php
```

### 6.2 Component Best Practices

1. Keep components focused on a single responsibility
2. Extract complex logic to dedicated Actions
3. Use validation rules consistent with project standards
4. Leverage type hinting and strict types
5. Follow naming conventions:
   - Properties: `camelCase`
   - Methods: `camelCase`
   - Database columns: `snake_case`

Example of proper component structure:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\User;
use App\Actions\UpdateProfileAction;
use Illuminate\Validation\Rule;

class Profile extends Component
{
    public User $user;
    public string $name = '';
    public string $email = '';
    
    public function mount(): void
    {
        $this->user = auth()->user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
    }
    
    public function save(UpdateProfileAction $action): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user->id),
            ],
        ]);
        
        $action->handle($this->user, $validated);
        
        $this->dispatch('profile-updated');
    }
    
    public function render()
    {
        return view('livewire.settings.profile');
    }
}
```

### 6.3 Integrating with AlpineJS

For more complex client-side interactions:

```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle Menu</button>
    
    <div x-show="open" x-transition>
        <livewire:navigation-menu />
    </div>
</div>
```

---

## 7. Testing Livewire Components

### 7.1 Component Testing

Using Pest PHP for testing components:

```php
use App\Livewire\TodoList;
use App\Models\Todo;
use App\Models\User;

test('can create a new todo', function () {
    $user = User::factory()->create();
    
    $this->actingAs($user)
        ->livewire(TodoList::class)
        ->set('newTodo', 'Test Todo Item')
        ->call('addTodo')
        ->assertHasNoErrors()
        ->assertSet('newTodo', '');
    
    $this->assertDatabaseHas('todos', [
        'user_id' => $user->id,
        'name' => 'Test Todo Item',
    ]);
});

test('validates required fields', function () {
    $this->actingAs(User::factory()->create())
        ->livewire(TodoList::class)
        ->set('newTodo', '')
        ->call('addTodo')
        ->assertHasErrors(['newTodo' => 'required']);
});

test('renders todos for the authenticated user', function () {
    $user = User::factory()->create();
    
    $todos = Todo::factory()->count(3)->create([
        'user_id' => $user->id,
    ]);
    
    $this->actingAs($user)
        ->livewire(TodoList::class)
        ->assertSee($todos[0]->name)
        ->assertSee($todos[1]->name)
        ->assertSee($todos[2]->name);
});
```

### 7.2 Mocking Dependencies

```php
use App\Livewire\Metrics\RevenueChart;
use App\Services\RevenueService;
use Mockery;

test('shows correct revenue data', function () {
    $user = User::factory()->create();
    
    $revenueMock = Mockery::mock(RevenueService::class);
    $revenueMock->shouldReceive('getMonthlyData')
        ->once()
        ->andReturn([
            'labels' => ['Jan', 'Feb', 'Mar'],
            'data' => [1000, 1500, 2000],
        ]);
    
    $this->instance(RevenueService::class, $revenueMock);
    
    $this->actingAs($user)
        ->livewire(RevenueChart::class)
        ->assertSet('labels', ['Jan', 'Feb', 'Mar'])
        ->assertSet('data', [1000, 1500, 2000]);
});
```

---

## 8. Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| Component not updating | Check browser console for errors |
| Validation errors not showing | Verify `$this->validate()` is called |
| JavaScript errors | Ensure proper Livewire asset publication |
| Property updates not persisting | Check property naming and case sensitivity |
| Performance issues | Consider lazy loading or optimization techniques |

For further assistance, see [Livewire documentation](https://livewire.laravel.com/docs) or the official [Livewire GitHub repository](https://github.com/livewire/livewire).
