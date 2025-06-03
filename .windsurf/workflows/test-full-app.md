---
description: Test the application fully
---

# Comprehensive Testing Workflow for Fusion CRM v4

This workflow guides you through the complete testing process for the Fusion CRM v4 application, including multi-tenancy testing, Wave Kit integration testing, and Filament admin panel testing.

## General Testing

1. Run all tests with Pest PHP:
   ```bash
   php artisan test
   ```

2. Run tests with coverage report:
   ```bash
   php artisan test --coverage
   ```

3. Run specific test suites:
   ```bash
   php artisan test --testsuite=Feature
   php artisan test --testsuite=Unit
   ```

## Multi-Tenancy Testing

1. Test tenant isolation:
   ```bash
   php artisan test --filter=TenantIsolationTest
   ```

2. Test tenant middleware:
   ```bash
   php artisan test --filter=TenantMiddlewareTest
   ```

3. Test tenant data access:
   ```bash
   php artisan test --filter=TenantDataAccessTest
   ```

## Wave Kit Integration Testing

1. Test Wave Kit model extensions:
   ```bash
   php artisan test --filter=WaveKitModelTest
   ```

2. Test Wave Kit authentication with tenant context:
   ```bash
   php artisan test --filter=WaveKitAuthTest
   ```

3. Test Wave Kit API extensions:
   ```bash
   php artisan test --filter=WaveKitApiTest
   ```

## Filament Admin Testing

1. Test Filament resource forms:
   ```bash
   php artisan test --filter=FilamentResourceFormTest
   ```

2. Test Filament resource policies:
   ```bash
   php artisan test --filter=FilamentResourcePolicyTest
   ```

3. Test Filament widgets:
   ```bash
   php artisan test --filter=FilamentWidgetTest
   ```

## Performance Testing

1. Test database query performance:
   ```bash
   php artisan test --filter=QueryPerformanceTest
   ```

2. Test application response time:
   ```bash
   php artisan test --filter=ResponseTimeTest
   ```

## Code Quality Checks

1. Run PHP linting with Pint:
   ```bash
   composer lint
   ```

2. Run PHPStan static analysis:
   ```bash
   composer analyse
   ```

3. Run PHP Insights:
   ```bash
   php artisan insights
   ```

## End-to-End Testing

1. Run Dusk browser tests:
   ```bash
   php artisan dusk
   ```

2. Run specific Dusk tests for tenant features:
   ```bash
   php artisan dusk --filter=TenantFeatureTest
   ```

3. Run Dusk tests for Filament admin:
   ```bash
   php artisan dusk --filter=FilamentAdminTest
   ```

## Continuous Integration Flow

1. Install dependencies:
   ```bash
   composer install --no-interaction --prefer-dist
   ```

2. Generate application key:
   ```bash
   php artisan key:generate
   ```

3. Run migrations with seeders:
   ```bash
   php artisan migrate:fresh --seed
   ```

4. Run code quality checks:
   ```bash
   composer lint && composer analyse
   ```

5. Run all tests:
   ```bash
   php artisan test
   ```

## Post-Deployment Verification

1. Check application health:
   ```bash
   php artisan health:check
   ```

2. Verify tenant provisioning:
   ```bash
   php artisan tenant:verify
   ```

3. Check database integrity:
   ```bash
   php artisan db:check
   ```