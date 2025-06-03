# Core CRM Models Implementation

## Context
Fusion CRM v4 needs robust data models for its core CRM functionality. These models will build upon the multi-tenant architecture we've established and follow strict coding standards.

## Task
Implement the core CRM models with proper relationships, validation, and tenant awareness:

1. Client model (individuals and organizations that are property buyers/sellers)
2. Property model (real estate properties with detailed attributes)
3. Lead model (potential clients with lead scoring and attribution)
4. Deal model (sales pipeline tracking from lead to close)
5. Activity model (interactions with clients, properties, and leads)

## Technical Requirements
- PHP 8.4 with strict types (`declare(strict_types=1);`)
- PSR-12 coding standards
- Laravel Eloquent relationships
- Apply tenant scoping to all models
- Type hinting for all properties and methods
- Factory classes for each model for testing

## Implementation Details
- Use camelCase for methods and variables
- Use PascalCase for classes
- Use snake_case for database columns
- Implement tenant scoping via trait
- Create appropriate migrations with indexes
- Add PHPDoc blocks to all methods
- Avoid mass assignment with `$fillable`
- Include model events where appropriate
- Create Pest tests for all models

## Expected Output
- Migration files for all models
- Eloquent model classes with relationships
- Factory classes for testing
- Complete test coverage using Pest
