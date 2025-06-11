# Laravel Pail Guide for Wave CRM

*A complete reference for implementing and utilizing Laravel Pail log viewing in the Wave SaaS starter kit (Laravel 12, PHP 8.3).*

---

## 1. What is Laravel Pail?

[Laravel Pail](https://github.com/laravel/pail) is an elegant log viewer tailored specifically for Laravel applications. It provides:

* Real-time log viewing in the terminal
* Intuitive filtering and search capabilities
* Color-coded log levels for better visibility
* Support for multiple log files
* The ability to format and parse log entries for readability
* Seamless integration with Laravel's logging system

Wave includes Laravel Pail to streamline the development and debugging process by providing immediate access to application logs.

---

## 2. Installation (already configured)

Laravel Pail comes pre-configured with Wave. The core dependency is:

```bash
composer require laravel/pail --dev
```

Wave ships with `laravel/pail:^1.1` in the `require-dev` section of `composer.json`.

---

## 3. Basic Usage

### 3.1 Viewing Logs in Real-Time

To start the Pail log viewer and watch logs in real-time:

```bash
php artisan pail
```

This command will display log entries as they occur, making it ideal for debugging during development.

### 3.2 Viewing Recent Logs

To view the most recent log entries:

```bash
php artisan pail:list
```

By default, this shows the most recent entries from the current day's log file.

### 3.3 Filtering by Log Level

View only specific log levels:

```bash
# View only error and critical logs
php artisan pail --level=error --level=critical

# Or using the shorter syntax
php artisan pail -l error -l critical
```

---

## 4. Advanced Features

### 4.1 Advanced Filtering

Filter logs by various criteria:

```bash
# Filter by search term
php artisan pail --grep="Payment failed"

# Filter by date range
php artisan pail --since="2023-01-01" --until="2023-01-31"

# Combine filters
php artisan pail --level=error --grep="database" --since="24 hours ago"
```

### 4.2 Formatting Options

Control the display format:

```bash
# Show logs in JSON format
php artisan pail --format=json

# Include stacktraces in output
php artisan pail --trace
```

### 4.3 Working with Multiple Log Files

Specify which log file(s) to view:

```bash
# View a specific log file
php artisan pail --file=laravel-2025-06-10.log

# View logs from multiple files
php artisan pail --file=laravel-2025-06-10.log --file=laravel-2025-06-09.log
```

---

## 5. Wave Integration Patterns

### 5.1 Debugging Application Flow

When debugging Wave's application flow, use specific context in logs:

```php
// In a Wave service class
public function processSubscription(User $user, Plan $plan): Subscription
{
    try {
        Log::info('Starting subscription process', [
            'user_id' => $user->id,
            'plan' => $plan->name,
            'amount' => $plan->price,
        ]);
        
        // Subscription logic
        
        Log::info('Subscription processed successfully', [
            'subscription_id' => $subscription->id,
        ]);
        
        return $subscription;
    } catch (Exception $e) {
        Log::error('Subscription processing failed', [
            'user_id' => $user->id,
            'plan' => $plan->name,
            'error' => $e->getMessage(),
        ]);
        
        throw $e;
    }
}
```

Then view these specific log entries:

```bash
php artisan pail --grep="subscription"
```

### 5.2 Custom Log Channels

Wave has several custom log channels configured in `config/logging.php`:

```php
'channels' => [
    // Default Laravel channels...
    
    'subscription' => [
        'driver' => 'daily',
        'path' => storage_path('logs/subscription.log'),
        'level' => 'debug',
        'days' => 14,
    ],
    
    'payment' => [
        'driver' => 'daily',
        'path' => storage_path('logs/payment.log'),
        'level' => 'debug',
        'days' => 30,
    ],
],
```

To use these channels in code:

```php
// Log to a specific channel
Log::channel('subscription')->info('Subscription renewed');
```

To view logs from a specific channel:

```bash
# View logs from a specific channel's file
php artisan pail --file=subscription-2025-06-11.log
```

---

## 6. Testing and Debugging

### 6.1 Debugging Tests with Pail

When running tests that generate logs, use Pail to view those logs:

```bash
php artisan pail --file=laravel-testing.log
```

### 6.2 Debugging Production Issues Locally

When debugging production issues locally:

1. Download production logs to your local environment
2. Use Pail to analyze them:

```bash
php artisan pail --file=/path/to/downloaded/production.log
```

---

## 7. Best Practices

### 7.1 Structured Logging

Use structured logging with context arrays for better filtering:

```php
// Good - structured with context
Log::info('User registered', [
    'user_id' => $user->id,
    'email' => $user->email,
    'plan' => $plan->name,
]);

// Avoid - unstructured string concatenation
Log::info("User {$user->id} registered with email {$user->email} on plan {$plan->name}");
```

### 7.2 Log Levels

Use appropriate log levels:

| Level | When to Use |
|-------|-------------|
| emergency | System is unusable |
| alert | Action must be taken immediately |
| critical | Critical conditions |
| error | Error conditions |
| warning | Warning conditions |
| notice | Normal but significant events |
| info | Informational messages |
| debug | Detailed debug information |

### 7.3 Context-Rich Logging

Include relevant context in logs:

```php
Log::error('Payment processing failed', [
    'exception' => $e->getMessage(),
    'payment_id' => $payment->id,
    'amount' => $payment->amount,
    'provider' => $payment->provider,
    'user_id' => $user->id,
    'subscription_id' => $subscription->id,
]);
```

---

## 8. Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| No logs appearing | Verify log file path and permissions |
| Too many logs to parse | Use more specific filters |
| Slow performance with large logs | Limit date range or use more specific filters |
| Missing context data | Ensure structured logging in code |

For further assistance, see [Laravel Pail documentation](https://github.com/laravel/pail) or the [Laravel Logging documentation](https://laravel.com/docs/logging).
