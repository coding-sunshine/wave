# Google Analytics Guide for Wave CRM

*A complete reference for implementing and leveraging Google Analytics in the Wave SaaS starter kit (Laravel 12, PHP 8.3).*

---

## 1. What is Google Analytics Integration?

Wave integrates with Google Analytics through the `bezhansalleh/filament-google-analytics` package, which provides:

* Seamless Google Analytics data display within your Filament admin panel
* Real-time visitor tracking and reporting
* Custom dashboard widgets for key metrics
* User behavior analytics
* Goal conversion tracking
* Campaign performance monitoring
* Content engagement statistics

This integration helps you make data-driven decisions about your SaaS product's performance and user engagement.

---

## 2. Installation (already configured)

Google Analytics integration comes pre-configured with Wave. The core dependency is:

```bash
composer require bezhansalleh/filament-google-analytics
```

Wave ships with `bezhansalleh/filament-google-analytics:^2.0` in `composer.json`.

---

## 3. Configuration

### 3.1 Google Analytics Account Setup

1. Create a Google Analytics 4 property at [analytics.google.com](https://analytics.google.com)
2. Set up a data stream for your website
3. Note your Measurement ID (G-XXXXXXXXXX)

### 3.2 Service Account Creation

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google Analytics API
4. Create a service account with appropriate permissions
5. Generate and download the JSON credentials file

### 3.3 Environment Variables

Add the following to your `.env` file:

```
ANALYTICS_PROPERTY_ID=your-property-id
ANALYTICS_SERVICE_ACCOUNT_CREDENTIALS_JSON="{...your-credentials-json...}"
```

Alternatively, for more security:

```
ANALYTICS_PROPERTY_ID=your-property-id
ANALYTICS_SERVICE_ACCOUNT_CREDENTIALS_PATH=/absolute/path/to/credentials.json
```

### 3.4 Config File

The configuration file is published at `config/filament-google-analytics.php`:

```php
<?php

declare(strict_types=1);

return [
    'date_range' => 'last_7_days',
    'metrics' => [
        'visitors',
        'sessions',
        'sessions_duration',
        'page_views',
        'bounce_rate',
    ],
    'tracking' => [
        'id' => env('ANALYTICS_PROPERTY_ID'),
    ],
    'service_account_credentials_json' => env('ANALYTICS_SERVICE_ACCOUNT_CREDENTIALS_JSON'),
    // or
    'service_account_credentials_path' => env('ANALYTICS_SERVICE_ACCOUNT_CREDENTIALS_PATH'),
];
```

---

## 4. Basic Usage

### 4.1 Tracking Code Integration

The tracking code is automatically added to your layout templates in production:

```php
@if (app()->environment('production'))
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('filament-google-analytics.tracking.id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ config('filament-google-analytics.tracking.id') }}');
    </script>
@endif
```

### 4.2 Viewing Analytics Data

Analytics data is available in the Filament admin panel:

1. Log in to your admin panel
2. Navigate to the Dashboard
3. View the Google Analytics widgets showing key metrics

---

## 5. Advanced Features

### 5.1 Custom Events Tracking

Track specific user actions in your application:

```php
// In a controller or service
public function completeOnboarding(User $user): void
{
    // Handle onboarding completion logic
    
    // Track the event in Google Analytics
    if (app()->environment('production')) {
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->trackEvent('user_onboarding', [
            'category' => 'user',
            'action' => 'completed_onboarding',
            'label' => "User #{$user->id}",
        ]);
    }
}
```

### 5.2 Enhanced E-commerce Tracking

For tracking subscription purchases:

```php
// In a subscription service
public function createSubscription(User $user, Plan $plan): Subscription
{
    // Create the subscription
    $subscription = $user->subscriptions()->create([
        'plan_id' => $plan->id,
        // Other fields
    ]);
    
    // Track in Google Analytics
    if (app()->environment('production')) {
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->trackPurchase([
            'transaction_id' => $subscription->id,
            'value' => $plan->price,
            'currency' => 'USD',
            'items' => [
                [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => $plan->price,
                ]
            ]
        ]);
    }
    
    return $subscription;
}
```

### 5.3 Custom Dimensions and Metrics

For tracking SaaS-specific metrics:

```php
// In AnalyticsServiceProvider
public function boot(): void
{
    $this->app->singleton(AnalyticsService::class, function ($app) {
        $service = new AnalyticsService();
        
        // Register custom dimensions
        $service->setCustomDimensions([
            1 => 'subscription_plan',
            2 => 'user_role',
            3 => 'account_tier',
        ]);
        
        // Register custom metrics
        $service->setCustomMetrics([
            1 => 'projects_created',
            2 => 'api_calls',
            3 => 'storage_used_mb',
        ]);
        
        return $service;
    });
}
```

---

## 6. Wave Integration Patterns

### 6.1 Admin Dashboard Widgets

Custom Filament widgets for displaying analytics data:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use BezhanSalleh\FilamentGoogleAnalytics\Widgets\PageViewsWidget;

class CustomPageViewsWidget extends PageViewsWidget
{
    protected static ?int $sort = 2;
    
    protected function getOptions(): array
    {
        return [
            'title' => 'Page Views by Path',
            'height' => 300,
            'options' => [
                'filter_dimension' => 'page_path', // Filter by specific dimension
            ],
        ];
    }
}
```

Register in `app/Providers/FilamentServiceProvider.php`:

```php
protected function getWidgets(): array
{
    return [
        // Other widgets
        \App\Filament\Widgets\CustomPageViewsWidget::class,
    ];
}
```

### 6.2 User Segmentation

Track different user segments:

```php
// In middleware
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    if ($request->user() && app()->environment('production')) {
        $user = $request->user();
        $plan = $user->subscription?->plan?->name ?? 'free';
        
        // Set user properties for analytics
        $script = <<<JS
            gtag('set', 'user_properties', {
                plan_tier: '{$plan}',
                user_role: '{$user->role}',
                signup_date: '{$user->created_at->format('Y-m-d')}'
            });
        JS;
        
        // Inject the script
        $content = $response->getContent();
        $content = str_replace('</head>', "<script>{$script}</script></head>", $content);
        $response->setContent($content);
    }
    
    return $response;
}
```

### 6.3 Feature Usage Tracking

Track which features users are engaging with:

```php
// In a feature controller
public function executeFeature(Request $request)
{
    // Feature execution logic
    
    // Track feature usage
    if (app()->environment('production')) {
        $user = $request->user();
        $plan = $user->subscription?->plan?->name ?? 'free';
        
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->trackEvent('feature_usage', [
            'category' => 'feature',
            'action' => 'execute',
            'label' => 'export_pdf',
            'value' => 1,
            'custom_dimensions' => [
                'dimension1' => $plan, // subscription_plan
                'dimension2' => $user->role, // user_role
            ],
        ]);
    }
    
    return response()->json(['success' => true]);
}
```

---

## 7. Testing Analytics Implementation

### 7.1 Development Environment Testing

For testing in development without affecting production data:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DevAnalyticsService implements AnalyticsServiceInterface
{
    public function trackEvent(string $eventName, array $parameters = []): void
    {
        Log::channel('analytics')->info("Analytics Event: {$eventName}", $parameters);
    }
    
    public function trackPurchase(array $purchaseData): void
    {
        Log::channel('analytics')->info('Analytics Purchase', $purchaseData);
    }
    
    // Other methods...
}
```

Register in service container conditionally:

```php
// In AppServiceProvider
public function register(): void
{
    $this->app->bind(AnalyticsServiceInterface::class, function ($app) {
        return app()->environment('production')
            ? new ProductionAnalyticsService()
            : new DevAnalyticsService();
    });
}
```

### 7.2 Mocking in Tests

For unit testing code that interacts with analytics:

```php
<?php

declare(strict_types=1);

use App\Services\AnalyticsServiceInterface;
use App\Http\Controllers\FeatureController;
use Mockery;

it('tracks feature usage when feature is executed', function () {
    // Arrange
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);
    
    $analyticsService = Mockery::mock(AnalyticsServiceInterface::class);
    $analyticsService->shouldReceive('trackEvent')
        ->once()
        ->with('feature_usage', Mockery::on(function ($params) {
            return $params['action'] === 'execute' && 
                   $params['label'] === 'export_pdf';
        }));
    
    $this->instance(AnalyticsServiceInterface::class, $analyticsService);
    
    // Act
    $response = $this->post('/features/export-pdf');
    
    // Assert
    $response->assertStatus(200);
});
```

---

## 8. Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| No data in reports | Check service account permissions |
| Missing events | Verify tracking code implementation |
| API quota limits | Implement caching for analytics queries |
| Incorrect tracking | Use Google Analytics Debugger browser extension |
| CORS issues | Ensure proper domain configuration |

For further assistance, see [Google Analytics Documentation](https://developers.google.com/analytics/devguides/collection/ga4) or [Filament Google Analytics package documentation](https://github.com/bezhanSalleh/filament-google-analytics).
