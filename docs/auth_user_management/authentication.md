# Authentication Guide for Wave CRM

*A complete reference for implementing and customizing the authentication system in the Wave SaaS starter kit (Laravel 12, PHP 8.3).*

---

## 1. What is Wave Authentication?

Wave's authentication system is built on top of the `devdojo/auth` package, providing a robust, customizable authentication solution that handles:

* User login and registration
* Email verification
* Password reset and confirmation
* Two-factor authentication
* Remember me functionality
* Social authentication
* Profile management

The system follows Laravel's authentication best practices while providing Wave-specific enhancements for SaaS applications.

---

## 2. Installation (already configured)

The authentication system comes pre-configured with Wave. The core dependencies are:

```bash
composer require devdojo/auth
```

Wave ships with `devdojo/auth:^1.0` in `composer.json`.

---

## 3. Configuration

### 3.1 Configuration Files

The authentication configuration is spread across multiple files:

* `config/auth.php` - Core Laravel authentication settings
* `config/wave.php` - Wave-specific authentication settings

### 3.2 Customization

Wave Auth includes a setup route that allows visual customization:

```
/auth/setup
```

Here you can:
* Upload a custom logo
* Change the color scheme
* Configure authentication options
* Customize email templates

---

## 4. Authentication Pages

### 4.1 Login

The login page (`/login`) provides:

* Email/username and password fields
* Remember me option
* Forgot password link
* Registration link
* Social authentication buttons (if configured)

Example login controller method:

```php
/**
 * Handle user authentication
 */
public function login(LoginRequest $request): RedirectResponse
{
    // Validation is handled in the FormRequest
    $credentials = $request->validated();
    
    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        
        return redirect()->intended(RouteServiceProvider::HOME);
    }
    
    return back()->withErrors([
        'email' => __('auth.failed'),
    ])->onlyInput('email');
}
```

### 4.2 Registration

The registration page (`/register`) includes:

* Name, email, and password fields
* Password confirmation
* Terms and conditions checkbox
* Login link for existing users

Example registration controller method:

```php
/**
 * Handle new user registration
 */
public function register(RegisterRequest $request): RedirectResponse
{
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);
    
    event(new Registered($user));
    
    Auth::login($user);
    
    return redirect(RouteServiceProvider::HOME);
}
```

### 4.3 Password Reset

The password reset flow includes:

* Forgot password page (`/forgot-password`)
* Email with reset link
* Password reset page (`/reset-password/{token}`)
* Password confirmation

### 4.4 Email Verification

Email verification uses Laravel's built-in verification system:

* Verification notice view (`/verify-email`)
* Email with verification link
* Verification success page

Example middleware usage:

```php
// Routes that require verified emails
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
```

### 4.5 Two-Factor Authentication

The two-factor authentication flow includes:

* 2FA setup in user settings
* QR code for authenticator apps
* Recovery codes generation
* Challenge page during login

---

## 5. Advanced Features

### 5.1 Social Authentication

Wave supports authentication with popular providers including:

* Google
* Facebook
* Twitter
* GitHub
* LinkedIn

To configure a provider, add its credentials to `.env`:

```
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT=http://your-app.com/auth/google/callback
```

### 5.2 Custom Guards

For specialized authentication needs:

```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
    'admin' => [
        'driver' => 'session',
        'provider' => 'admins',
    ],
],
```

### 5.3 Authentication Events

Wave leverages Laravel's authentication events:

```php
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;

// In EventServiceProvider
protected $listen = [
    Login::class => [
        RecordSuccessfulLogin::class,
    ],
    Failed::class => [
        LockoutAfterFailedAttempts::class,
    ],
    Registered::class => [
        SendWelcomeEmail::class,
    ],
];
```

---

## 6. Wave Integration Patterns

### 6.1 Authentication Customization

Wave makes it easy to customize authentication views:

```php
// Publish authentication views
php artisan vendor:publish --tag=wave-auth-views
```

The views will be published to `resources/views/vendor/wave/auth/`.

### 6.2 Custom Fields

To add custom fields to registration:

1. Publish the registration view
2. Add new fields to the form
3. Update the RegisterRequest validation rules
4. Extend the register method to save the additional fields

```php
// app/Http/Requests/Auth/RegisterRequest.php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
        'company' => ['required', 'string', 'max:255'],
    ];
}
```

### 6.3 Tenant-Aware Authentication

For multi-tenant applications:

```php
// In AuthServiceProvider::boot
Auth::resolved(function ($auth) {
    $auth->viaRequest('tenant', function ($request) {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }
        
        $tenantId = $request->header('X-Tenant-ID');
        
        if ($user->tenants()->where('id', $tenantId)->exists()) {
            $user->setActiveTenant($tenantId);
            return $user;
        }
        
        return null;
    });
});
```

---

## 7. Testing Authentication

### 7.1 Feature Tests

Using Pest PHP for testing authentication:

```php
use App\Models\User;

test('user can login with correct credentials', function () {
    $user = User::factory()->create();
    
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    
    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
});

test('user cannot login with incorrect password', function () {
    $user = User::factory()->create();
    
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);
    
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});
```

### 7.2 Unit Tests

Testing authorization policies:

```php
test('only admin can access admin panel', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);
    
    expect($admin->can('access-admin-panel'))->toBeTrue();
    expect($user->can('access-admin-panel'))->toBeFalse();
});
```

---

## 8. Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| Email verification not working | Check mail configuration in `.env` |
| Social login errors | Verify client IDs and secrets |
| Password reset emails not sent | Check queue worker status |
| Session issues | Clear the session cache |

For further assistance, see [Laravel Authentication documentation](https://laravel.com/docs/authentication) or [DevDojo Auth documentation](https://devdojo.com/auth).
