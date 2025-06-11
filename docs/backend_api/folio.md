# Laravel Folio Guide for Wave CRM

*A complete reference for implementing and utilizing file-based routing in the Wave SaaS starter kit (Laravel 12, PHP 8.3).*

---

## 1. What is Laravel Folio?

[Laravel Folio](https://laravel.com/docs/folio) is a file-based routing system for Laravel applications that simplifies route definition and organization. It provides:

* Intuitive file-based routing similar to Next.js or Remix
* Automatic route registration based on your directory structure
* View-based routing with embedded PHP logic
* Support for dynamic route parameters
* Middleware and parameter constraints
* Layout templates and nested layouts
* SEO-friendly URL structures

Wave uses Folio to create clean, maintainable routes for various parts of the application.

---

## 2. Installation (already configured)

Laravel Folio comes pre-configured with Wave. The core dependency is:

```bash
composer require laravel/folio
```

Wave ships with `laravel/folio:^1.1` in `composer.json`.

---

## 3. Configuration

### 3.1 Directory Structure

Folio routes are defined in the `resources/views/pages` directory by default. Wave follows PSR-12 conventions for file organization:

```
resources/
  └── views/
      └── pages/
          ├── index.blade.php        # Route: /
          ├── about.blade.php        # Route: /about
          ├── contact.blade.php      # Route: /contact
          └── dashboard/
              ├── index.blade.php    # Route: /dashboard
              └── settings.blade.php # Route: /dashboard/settings
```

### 3.2 Middleware Configuration

Folio middleware is configured in the `app/Providers/FolioServiceProvider.php` file:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;

class FolioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Folio::path(resource_path('views/pages'))
            ->middleware([
                'web',
                'verified' => ['dashboard/*'],
                'subscribed' => ['dashboard/premium/*'],
            ]);
    }
}
```

---

## 4. Basic Usage

### 4.1 Creating Simple Routes

Create a new file in `resources/views/pages/`:

```php
<!-- resources/views/pages/pricing.blade.php -->

<x-app-layout>
    <h1>Pricing Plans</h1>
    
    <div class="pricing-cards">
        @foreach($plans as $plan)
            <div class="pricing-card">
                <h2>{{ $plan->name }}</h2>
                <p class="price">${{ $plan->price }}</p>
                <a href="{{ route('subscribe', $plan) }}">Subscribe</a>
            </div>
        @endforeach
    </div>
</x-app-layout>

<?php

use App\Models\Plan;

$plans = Plan::where('active', 1)->get();
?>
```

This creates a `/pricing` route automatically.

### 4.2 Route Parameters

For dynamic routes, use square brackets in the filename:

```php
<!-- resources/views/pages/blog/[slug].blade.php -->

<x-app-layout>
    <article>
        <h1>{{ $post->title }}</h1>
        <div class="metadata">
            Posted on {{ $post->created_at->format('F j, Y') }}
        </div>
        <div class="content">
            {!! $post->content !!}
        </div>
    </article>
</x-app-layout>

<?php

use App\Models\Post;

$post = Post::where('slug', $slug)->firstOrFail();
?>
```

This creates a route like `/blog/{slug}` that responds to `/blog/my-first-post`.

### 4.3 Using Layouts

Folio pages can use Blade layouts:

```php
<!-- resources/views/pages/contact.blade.php -->

<x-app-layout>
    <h1>Contact Us</h1>
    
    <form action="{{ route('contact.submit') }}" method="POST">
        @csrf
        <!-- Form fields -->
    </form>
</x-app-layout>
```

---

## 5. Advanced Features

### 5.1 Route Model Binding

```php
<!-- resources/views/pages/users/[user].blade.php -->

<x-app-layout>
    <h1>{{ $user->name }}</h1>
    
    <div class="profile">
        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
        <p>Member since {{ $user->created_at->format('F Y') }}</p>
    </div>
</x-app-layout>

<?php

use App\Models\User;

// Route binding happens automatically
?>
```

### 5.2 Route Groups and Middleware

```php
<!-- resources/views/pages/dashboard.blade.php -->

<x-app-layout>
    <h1>Dashboard</h1>
    
    <div class="dashboard-content">
        <!-- Dashboard content here -->
    </div>
</x-app-layout>

<?php

// This page will automatically get the 'verified' middleware
// as configured in the FolioServiceProvider
?>
```

### 5.3 Multiple Parameters

```php
<!-- resources/views/pages/projects/[project]/tasks/[task].blade.php -->

<x-app-layout>
    <h1>{{ $task->name }}</h1>
    
    <p>Project: {{ $project->name }}</p>
    
    <div class="task-details">
        <!-- Task details here -->
    </div>
</x-app-layout>

<?php

use App\Models\Project;
use App\Models\Task;

// Automatically gets both models and verifies the relationship
$task = $project->tasks()->findOrFail($task);
?>
```

---

## 6. Wave Integration Patterns

### 6.1 SEO-Friendly Pages

For content pages with SEO requirements:

```php
<!-- resources/views/pages/blog/[slug].blade.php -->

<x-app-layout>
    <x-slot name="meta">
        <meta name="description" content="{{ $post->excerpt }}">
        <meta property="og:title" content="{{ $post->title }}">
        <meta property="og:description" content="{{ $post->excerpt }}">
        <meta property="og:image" content="{{ $post->featured_image }}">
    </x-slot>
    
    <article>
        <h1>{{ $post->title }}</h1>
        <!-- Content -->
    </article>
</x-app-layout>

<?php

use App\Models\Post;

$post = Post::where('slug', $slug)
    ->where('published', true)
    ->firstOrFail();
?>
```

### 6.2 Authentication and Authorization

Using middleware for protected routes:

```php
<!-- resources/views/pages/dashboard/settings.blade.php -->

<x-app-layout>
    <h1>Account Settings</h1>
    
    <!-- Settings form -->
</x-app-layout>

<?php

// Policy check
$this->authorize('view-settings', auth()->user());
?>
```

### 6.3 Combining with Livewire

For complex interactive pages:

```php
<!-- resources/views/pages/dashboard/index.blade.php -->

<x-app-layout>
    <h1>Dashboard</h1>
    
    <div class="dashboard-widgets">
        <livewire:dashboard.stats />
        <livewire:dashboard.recent-activity />
    </div>
</x-app-layout>

<?php

// The page loads but Livewire handles the interactivity
?>
```

---

## 7. Testing Folio Routes

### 7.1 Feature Tests

Using Pest PHP for testing Folio routes:

```php
<?php

use App\Models\User;
use App\Models\Post;

it('shows the blog post page', function () {
    // Arrange
    $post = Post::factory()->create([
        'title' => 'Test Post',
        'slug' => 'test-post',
        'content' => 'Test content',
    ]);
    
    // Act & Assert
    $this->get("/blog/{$post->slug}")
        ->assertOk()
        ->assertSee('Test Post')
        ->assertSee('Test content');
});

it('shows 404 for non-existent blog posts', function () {
    $this->get('/blog/non-existent-post')
        ->assertNotFound();
});

it('requires authentication for dashboard pages', function () {
    // Anonymous user
    $this->get('/dashboard')
        ->assertRedirect('/login');
    
    // Authenticated user
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});
```

### 7.2 Advanced Testing Scenarios

Testing middleware restrictions:

```php
<?php

use App\Models\User;
use App\Models\Plan;

it('restricts premium content to subscribed users', function () {
    // Create user without subscription
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/dashboard/premium/reports')
        ->assertRedirect('/billing');
    
    // Create user with subscription
    $user = User::factory()->create();
    $plan = Plan::where('name', 'Premium')->first();
    $user->newSubscription('default', $plan->stripe_id)
        ->create('pm_card_visa');
    
    $this->actingAs($user)
        ->get('/dashboard/premium/reports')
        ->assertOk();
});
```

---

## 8. Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| Route not working | Check filename and directory structure |
| 404 errors | Verify model binding and existence of records |
| Layout issues | Check for correct Blade component/layout names |
| Middleware not applying | Verify FolioServiceProvider configuration |
| Performance concerns | Use caching for expensive database queries |

For further assistance, see [Laravel Folio documentation](https://laravel.com/docs/folio) or the [Laravel Folio GitHub repository](https://github.com/laravel/folio).
