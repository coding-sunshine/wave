# Billing Guide for Wave CRM

*A complete reference for implementing and customizing the billing system in the Wave SaaS starter kit (Laravel 12, PHP 8.3).*

---

## 1. What is Wave Billing?

Wave's billing system is a comprehensive solution for managing subscriptions, processing payments, and handling invoicing for your SaaS application. Built on top of Stripe, it provides:

* Multiple subscription plans and pricing tiers
* Support for recurring and one-time payments
* Customer management and billing portals
* Invoice generation and email delivery
* Payment method management
* Subscription status tracking
* Handling of common billing events (renewals, cancellations, upgrades)

Wave's billing system is designed to handle the complexity of SaaS monetization while providing a clean, user-friendly interface.

---

## 2. Installation (already configured)

The billing system comes pre-configured with Wave. The core dependencies are:

```bash
composer require stripe/stripe-php
```

Wave ships with `stripe/stripe-php:^17.3` in `composer.json`.

---

## 3. Configuration

### 3.1 Environment Variables

Add your Stripe credentials to `.env`:

```
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
CASHIER_CURRENCY=usd
```

### 3.2 Webhook Configuration

For handling Stripe events, set up a webhook endpoint:

1. In your Stripe dashboard, go to Developers > Webhooks
2. Add an endpoint with URL: `https://your-app.com/stripe/webhook`
3. Add the Stripe webhook secret to your `.env`:

```
STRIPE_WEBHOOK_SECRET=whsec_...
```

---

## 4. Basic Usage

### 4.1 Creating a Subscription

```php
public function subscribe(Request $request)
{
    $user = $request->user();
    $paymentMethod = $request->paymentMethod;
    $planId = $request->planId;
    
    return $user->newSubscription('default', $planId)
        ->create($paymentMethod);
}
```

### 4.2 Checking Subscription Status

```php
if ($user->subscribed('default')) {
    // User has an active subscription
}

if ($user->subscription('default')->onGracePeriod()) {
    // User is on their grace period after cancellation
}

if ($user->subscription('default')->cancelled()) {
    // User has cancelled their subscription
}
```

---

## 5. Advanced Features

### 5.1 Handling Plan Changes

```php
// Upgrade or downgrade
$user->subscription('default')->swap($newPlanId);

// Proration handling
$user->subscription('default')->swap($newPlanId, [
    'proration_behavior' => 'always_invoice'
]);
```

### 5.2 Adding Billing Portal

Wave includes a pre-built customer portal for managing subscriptions:

```php
Route::get('/billing', function (Request $request) {
    return $request->user()->redirectToBillingPortal(
        route('home')
    );
})->middleware(['auth'])->name('billing');
```

### 5.3 Handling Webhooks

Stripe webhooks are automatically processed through the Laravel Cashier package. You can add custom handling in `app/Providers/EventServiceProvider.php`:

```php
/**
 * Register any events for your application.
 */
public function boot(): void
{
    parent::boot();
    
    // Handle payment failure
    Event::listen(function (PaymentFailed $event) {
        // Notify user about failed payment
    });
}
```

---

## 6. Wave Integration Patterns

### 6.1 Subscription Plans

Wave's admin panel allows you to create and manage subscription plans through an intuitive interface. These plans are synced with your Stripe account automatically.

To display plans to users:

```php
use Wave\Models\Plan;

$plans = Plan::where('active', 1)->get();

return view('billing.plans', compact('plans'));
```

### 6.2 Middleware for Protected Routes

For features that require a subscription:

```php
// Only allow subscribed users
Route::middleware(['auth', 'subscribed'])->group(function () {
    Route::get('/premium-feature', 'FeatureController@index');
});

// Only allow users subscribed to specific plans
Route::middleware(['auth', 'subscribed:pro,enterprise'])->group(function () {
    Route::get('/advanced-feature', 'FeatureController@advanced');
});
```

### 6.3 Displaying User Subscription Info

In Blade templates:

```blade
@if (auth()->user()->subscribed('default'))
    <p>You are subscribed to the {{ auth()->user()->subscription('default')->plan }} plan.</p>
    
    @if (auth()->user()->subscription('default')->onGracePeriod())
        <p>Your subscription will end on {{ auth()->user()->subscription('default')->ends_at->format('F j, Y') }}</p>
    @endif
    
    <a href="{{ route('billing') }}">Manage Subscription</a>
@else
    <p>You are not currently subscribed.</p>
    <a href="{{ route('plans') }}">View Plans</a>
@endif
```

---

## 7. Testing Billing

### 7.1 Test Mode

During development, use Stripe's test mode and test cards:

- Successful payment: `4242 4242 4242 4242`
- Failed payment: `4000 0000 0000 0002`

### 7.2 Mocking Cashier in Tests

```php
use Laravel\Cashier\Cashier;
use Illuminate\Support\Facades\Event;

// In your test setup
public function setUp(): void
{
    parent::setUp();
    
    $this->mock = $this->mock(Cashier::class);
    $this->mock->shouldReceive('createOrGetStripeCustomer')->andReturn('cus_test123');
}

// Example test
public function test_user_can_subscribe()
{
    Event::fake();
    
    $this->mock->shouldReceive('createSubscription')
        ->once()
        ->andReturn((object)[
            'id' => 'sub_test123',
            'status' => 'active'
        ]);
    
    $response = $this->post('/subscribe', [
        'plan' => 'monthly',
        'payment_method' => 'pm_card_visa'
    ]);
    
    $response->assertRedirect('/dashboard');
    $this->assertTrue($this->user->fresh()->subscribed('default'));
}
```

---

## 8. Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| Webhook failures | Check webhook endpoint URL and secret |
| Payment failures | Verify test card or customer payment method |
| Currency mismatch | Ensure CASHIER_CURRENCY matches your Stripe configuration |
| Subscription not activating | Check Stripe dashboard for successful payment |

For further assistance, see [Laravel Cashier documentation](https://laravel.com/docs/billing) or [Stripe documentation](https://stripe.com/docs).
