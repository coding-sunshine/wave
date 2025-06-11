# Pest PHP Testing Guide for Wave CRM

*A complete reference for implementing and utilizing Pest PHP tests in the Wave SaaS starter kit (Laravel 12, PHP 8.3).*

---

## 1. What is Pest PHP?

[Pest PHP](https://pestphp.com) is a modern testing framework with a focus on simplicity and developer experience. It provides:

* An elegant, expressive syntax for writing tests
* Improved readability with natural language descriptions
* Higher-order assertions for compact testing code
* Powerful dataset-driven testing capabilities
* Plugin ecosystem for extending functionality
* Full compatibility with PHPUnit tests

Wave leverages Pest PHP as its primary testing framework for all unit, feature, and integration tests.

---

## 2. Installation (already configured)

Pest PHP comes pre-configured with Wave. The core dependencies are:

```bash
composer require --dev pestphp/pest pestphp/pest-plugin-laravel
```

Wave ships with `pestphp/pest:^3.4` and `pestphp/pest-plugin-laravel:^3.1` in `composer.json`.

---

## 3. Configuration

### 3.1 Configuration Files

Pest configuration is defined in:

* `phpunit.xml` - Core test configuration
* `Pest.php` - Pest-specific configuration

### 3.2 Directory Structure

Following the project rules, Wave organizes tests as follows:

* `tests/Feature/Http/` - For HTTP controller/route tests
* `tests/Feature/Console/` - For console command tests
* `tests/Unit/Actions/` - For action class tests
* `tests/Unit/Models/` - For model tests
* `tests/Unit/Jobs/` - For job tests
* `tests/Unit/Services/` - For service tests
* `tests/Unit/Components/` - For component tests

---

## 4. Basic Usage

### 4.1 Creating a Test

To create a new test:

```bash
php artisan pest:test TodoTest
```

This generates:
- `tests/Feature/TodoTest.php`

For a unit test:

```bash
php artisan pest:test TodoTest --unit
```

This generates:
- `tests/Unit/TodoTest.php`

### 4.2 Writing Tests

Basic test structure:

```php
<?php

use App\Models\Todo;
use App\Models\User;

test('user can create a todo', function () {
    // Arrange
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // Act
    $response = $this->post('/todos', [
        'title' => 'Test Todo',
        'description' => 'This is a test todo',
        'due_date' => now()->addDays(3)->format('Y-m-d'),
    ]);
    
    // Assert
    $response->assertRedirect('/todos');
    $this->assertDatabaseHas('todos', [
        'title' => 'Test Todo',
        'user_id' => $user->id,
    ]);
});

test('todo requires a title', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    
    $response = $this->post('/todos', [
        'description' => 'This is a test todo',
        'due_date' => now()->addDays(3)->format('Y-m-d'),
    ]);
    
    $response->assertSessionHasErrors('title');
});
```

### 4.3 Running Tests

To run all tests:

```bash
php artisan test
# Or using the composer script
composer test
```

To run a specific test file:

```bash
php artisan test tests/Feature/TodoTest.php
```

To run tests with a specific name pattern:

```bash
php artisan test --filter=todo
```

---

## 5. Advanced Features

### 5.1 Expectations API

Using higher-order expectations for cleaner assertions:

```php
test('todo has the correct attributes', function () {
    $todo = Todo::factory()->create([
        'title' => 'Test Todo',
        'completed' => false,
    ]);
    
    expect($todo->title)->toBe('Test Todo');
    expect($todo->completed)->toBeFalse();
    expect($todo->user)->toBeInstanceOf(User::class);
    expect($todo->created_at)->toBeInstanceOf(Carbon\Carbon::class);
});
```

### 5.2 Datasets

For testing multiple scenarios efficiently:

```php
test('validates todo priority', function (string $priority, bool $valid) {
    $user = User::factory()->create();
    $this->actingAs($user);
    
    $response = $this->post('/todos', [
        'title' => 'Test Todo',
        'description' => 'Description',
        'priority' => $priority,
    ]);
    
    if ($valid) {
        $response->assertSessionDoesntHaveErrors('priority');
    } else {
        $response->assertSessionHasErrors('priority');
    }
})->with([
    ['low', true],
    ['medium', true],
    ['high', true],
    ['critical', true],
    ['invalid', false],
    ['', false],
]);
```

### 5.3 Test Hooks

For shared setup and teardown logic:

```php
// In Pest.php
uses()->beforeEach(function () {
    // Setup code runs before each test
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
})->afterEach(function () {
    // Teardown code runs after each test
})->in('Feature/Http/Todo');
```

### 5.4 Groups and Skipping

For organizing and controlling test execution:

```php
test('expensive calculation test', function () {
    // Long-running test
})->group('slow');

test('feature not ready yet', function () {
    // Test for unfinished feature
})->skip('Feature not implemented yet');
```

Run specific groups:

```bash
php artisan test --group=slow
```

---

## 6. Wave Integration Patterns

### 6.1 Testing Convention

Following the project rules, Wave enforces:

* All new code must be accompanied by relevant tests
* Both feature tests (end-to-end) and unit tests (isolated components)
* Clear test descriptions using the `it(...)` function
* Model factories for all Eloquent models
* Strict types and type hinting in test files

Example of proper test structure:

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Todo;

it('validates user input on creation and returns validation errors', function () {
    // Arrange
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // Act
    $response = $this->post('/todos', [
        'title' => '',
        'due_date' => 'not-a-date',
    ]);
    
    // Assert
    $response->assertSessionHasErrors(['title', 'due_date']);
});
```

### 6.2 Testing Actions

For testing isolated business logic:

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Todo;
use App\Actions\CreateTodoAction;

it('creates a todo with proper attributes', function () {
    // Arrange
    $user = User::factory()->create();
    $action = app(CreateTodoAction::class);
    $data = [
        'title' => 'New Todo',
        'description' => 'Todo description',
        'due_date' => now()->addDay()->format('Y-m-d'),
    ];
    
    // Act
    $todo = $action->handle($user, $data);
    
    // Assert
    expect($todo)->toBeInstanceOf(Todo::class);
    expect($todo->title)->toBe('New Todo');
    expect($todo->user_id)->toBe($user->id);
    expect($todo->completed)->toBeFalse();
});
```

### 6.3 Testing Models

Testing model relationships and methods:

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Todo;

it('belongs to a user', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $user->id]);
    
    expect($todo->user)->toBeInstanceOf(User::class);
    expect($todo->user->id)->toBe($user->id);
});

it('can be marked as complete', function () {
    $todo = Todo::factory()->create(['completed' => false]);
    
    $todo->markComplete();
    
    expect($todo->completed)->toBeTrue();
    expect($todo->completed_at)->not->toBeNull();
});

it('can be filtered by completion status', function () {
    // Create 3 complete and 2 incomplete todos
    Todo::factory()->count(3)->create(['completed' => true]);
    Todo::factory()->count(2)->create(['completed' => false]);
    
    $completedCount = Todo::completed()->count();
    $incompleteCount = Todo::incomplete()->count();
    
    expect($completedCount)->toBe(3);
    expect($incompleteCount)->toBe(2);
});
```

---

## 7. Best Practices

### 7.1 Arrange-Act-Assert Pattern

Structure tests using the AAA pattern:

```php
it('allows users to update their own todos', function () {
    // Arrange
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $user->id]);
    $this->actingAs($user);
    
    // Act
    $response = $this->put("/todos/{$todo->id}", [
        'title' => 'Updated Title',
        'description' => 'Updated description',
    ]);
    
    // Assert
    $response->assertRedirect();
    $this->assertDatabaseHas('todos', [
        'id' => $todo->id,
        'title' => 'Updated Title',
        'description' => 'Updated description',
    ]);
});
```

### 7.2 Test Isolation

Ensure tests don't depend on each other:

```php
// Bad practice - relying on state from other tests
$globalUser = null;

test('creates a user', function () use (&$globalUser) {
    $globalUser = User::factory()->create();
    // ...
});

test('user can login', function () use ($globalUser) {
    // This test will fail if run individually
    $this->post('/login', [
        'email' => $globalUser->email,
        // ...
    ]);
});

// Good practice - each test creates its own state
test('user can login', function () {
    $user = User::factory()->create();
    
    $this->post('/login', [
        'email' => $user->email,
        // ...
    ]);
});
```

### 7.3 Use Factories Effectively

Leverage factories for efficient test data creation:

```php
// Create with specific attributes
$admin = User::factory()->create([
    'role' => 'admin',
]);

// Create related models
$user = User::factory()
    ->has(Todo::factory()->count(3))
    ->create();

// Create based on states
$completedTodo = Todo::factory()
    ->completed()
    ->create();
```

---

## 8. Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| Test database contamination | Use `RefreshDatabase` trait |
| Slow tests | Use database transactions or SQLite in-memory |
| Authentication issues in tests | Check for proper `actingAs($user)` calls |
| Inconsistent test results | Ensure test isolation, check for order dependencies |
| Missing assertions | Use higher-order expectations for clearer errors |

For further assistance, see [Pest PHP documentation](https://pestphp.com/docs) or the [Pest PHP GitHub repository](https://github.com/pestphp/pest).
