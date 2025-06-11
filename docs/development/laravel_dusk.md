# Laravel Dusk Guide for Wave CRM

*A complete reference for browser testing with Laravel Dusk in the Wave SaaS starter kit (Laravel 12, PHP 8.3).*

---

## 1. What is Laravel Dusk?

[Laravel Dusk](https://laravel.com/docs/dusk) is a browser automation and testing API that provides an expressive, easy-to-use browser automation and testing API. It provides:

* Browser automation for end-to-end testing
* Simple interaction with web elements
* Testing of JavaScript-enabled applications
* Integration with Chrome and Chromedriver
* Screenshot capturing functionality
* Console logging for debugging

Wave uses Laravel Dusk to perform comprehensive end-to-end testing of the application, ensuring all features work correctly from a user's perspective.

---

## 2. Installation (already configured)

Laravel Dusk comes pre-configured with Wave. The core dependency is:

```bash
composer require laravel/dusk --dev
```

Wave ships with `laravel/dusk:^7.0` in the `require-dev` section of `composer.json`.

---

## 3. Basic Usage

### 3.1 Running Tests

To run all Dusk tests:

```bash
php artisan dusk
```

To run specific tests:

```bash
php artisan dusk --filter=AuthenticationTest
```

### 3.2 Basic Example Test

Here's a basic example of a Dusk test in Wave:

```php
<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class LoginTest extends DuskTestCase
{
    public function test_user_can_login_with_correct_credentials(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);

            $browser->visit('/login')
                    ->type('email', 'test@example.com')
                    ->type('password', 'password')
                    ->press('Login')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Welcome back');
        });
    }
}
```

---

## 4. Advanced Features

### 4.1 Page Objects

Wave uses Dusk Page Objects to represent different pages in the application:

```php
<?php

declare(strict_types=1);

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page as BasePage;

class Dashboard extends BasePage
{
    public function url(): string
    {
        return '/dashboard';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@navbar' => '.navbar',
            '@sidebar' => '.sidebar',
            '@settings-button' => '#settings-button',
        ];
    }
    
    public function navigateToSettings(Browser $browser): self
    {
        $browser->click('@settings-button');
        
        return $this;
    }
}
```

Usage:

```php
$browser->visit(new Pages\Dashboard)
        ->navigateToSettings()
        ->assertSee('Settings');
```

### 4.2 Component Testing

Test reusable UI components:

```php
<?php

declare(strict_types=1);

namespace Tests\Browser\Components;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Component as BaseComponent;

class Datepicker extends BaseComponent
{
    public function selector(): string
    {
        return '.datepicker';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertVisible($this->selector());
    }

    public function elements(): array
    {
        return [
            '@input' => 'input',
            '@calendar' => '.calendar',
            '@next-month' => '.next-month',
            '@prev-month' => '.prev-month',
        ];
    }
    
    public function selectDate(Browser $browser, string $date): self
    {
        $browser->click('@input')
                ->click('@calendar')
                ->click("[data-date='{$date}']");
                
        return $this;
    }
}
```

Usage:

```php
$browser->within(new Components\Datepicker, function (Browser $browser) {
    $browser->selectDate('2023-01-15');
});
```

---

## 5. Wave Integration Patterns

### 5.1 Testing Authentication Flow

```php
<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class AuthFlowTest extends DuskTestCase
{
    public function test_complete_auth_flow(): void
    {
        $this->browse(function (Browser $browser) {
            // Registration
            $browser->visit('/register')
                    ->type('name', 'Test User')
                    ->type('email', 'newuser@example.com')
                    ->type('password', 'password')
                    ->type('password_confirmation', 'password')
                    ->press('Register')
                    ->assertPathIs('/dashboard');
            
            // Logout
            $browser->click('#user-menu-button')
                    ->click('#logout-button')
                    ->assertPathIs('/');
                    
            // Login
            $browser->visit('/login')
                    ->type('email', 'newuser@example.com')
                    ->type('password', 'password')
                    ->press('Login')
                    ->assertPathIs('/dashboard');
        });
    }
}
```

### 5.2 Testing Subscription Process

```php
<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class SubscriptionTest extends DuskTestCase
{
    public function test_user_can_subscribe_to_plan(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();
            
            $browser->loginAs($user)
                    ->visit('/pricing')
                    ->click('#subscribe-basic')
                    ->waitFor('#payment-form')
                    ->within('#payment-form', function (Browser $browser) {
                        // Fill in Stripe test card details
                        $browser->type('cardnumber', '4242424242424242')
                                ->type('exp-date', '12/30')
                                ->type('cvc', '123')
                                ->type('postal', '12345')
                                ->press('Subscribe');
                    })
                    ->waitFor('#subscription-success')
                    ->assertSee('Thank you for subscribing!')
                    ->assertPathIs('/dashboard');
        });
    }
}
```

---

## 6. Testing Best Practices

### 6.1 Test Structure

Follow these best practices for Dusk tests:

1. Use descriptive test method names prefixed with `test_`
2. Group related tests in the same test class
3. Use Page Objects for reusable page interactions
4. Use Components for reusable UI elements
5. Create helper methods for common actions

### 6.2 Test Data Management

```php
<?php

declare(strict_types=1);

// In DuskTestCase.php
protected function setUp(): void
{
    parent::setUp();
    
    // Create common test data
    $this->setupTestData();
}

protected function setupTestData(): void
{
    // Create test data that multiple tests will use
    $this->admin = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');
    
    $this->user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
    ]);
}
```

---

## 7. Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| Tests failing randomly | Increase wait time for elements to load |
| Element not found | Use waitFor() method before interacting with elements |
| Browser not starting | Check chromedriver version matches Chrome version |
| Screenshots needed for diagnosis | Use screenshot() method to capture current state |
| JavaScript errors | Check browser console logs with dumpConsole() |

For further assistance, see [Laravel Dusk documentation](https://laravel.com/docs/dusk) or the [GitHub repository](https://github.com/laravel/dusk).
