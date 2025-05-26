---
trigger: manual
---

# PHP Coding Standards Rules
@globs: ["**/*.php"]

You are a PHP expert focusing on Laravel development. Follow these rules for PHP files:

## Code Style
- Always use PHP 8.4 features where beneficial
- Strictly follow PSR-12 coding standards
- Add strict type declarations (`declare(strict_types=1);`)
- Use type hints for parameters and return types
- Use constructor property promotion where applicable
- Use named arguments for better readability
- Use match expressions instead of switch where appropriate
- Use array shapes and generics in PHPDoc
- Use union types and intersection types where applicable
- Format code with Laravel Pint before committing

## Laravel Best Practices
- Use dependency injection over facades where possible
- Use form requests for validation
- Use actions for business logic
- Use Laravel's built-in security features
- Follow Laravel naming conventions
- Use Laravel's query builder methods over raw SQL
- Use Laravel resources for API responses
- Follow Laravel's folder structure
- Use Laravel's built-in helper methods
- Follow Laravel's routing conventions

## Wave Kit Implementation
- Follow Wave Kit's folder structure and organization
- Extend Wave Kit models properly for tenant awareness
- Use Wave Kit's authentication system with tenant context
- Follow Wave Kit's billing and subscription patterns
- Implement Wave Kit's team functionality for multi-tenancy
- Use Wave Kit's theme system with proper customization
- Leverage Wave Kit's existing controllers with proper extension
- Follow Wave Kit's service provider patterns
- Implement Wave Kit's middleware with tenant awareness
- Use Wave Kit's API structure with proper extensions

## Filament Admin
- Follow Filament's resource pattern
- Create consistent form schemas
- Implement tenant-aware resource queries
- Use proper resource relationships
- Follow Filament's widget implementation pattern
- Create consistent table column definitions
- Use proper authorization policies with resources
- Follow Filament's action implementation patterns
- Implement consistent resource navigation
- Use proper form validation with helpful messages

## Multi-Tenancy
- Implement tenant awareness in all relevant models
- Apply tenant scoping to queries consistently
- Validate tenant context in controllers
- Use tenant middleware for proper isolation
- Follow tenant data access patterns
- Implement tenant provisioning and management properly
- Use proper tenant context in background jobs
- Follow tenant event handling patterns
- Implement tenant-aware caching strategies
- Handle tenant configuration properly

## Error Handling
- Create domain-specific exceptions
- Use try-catch blocks for expected exceptions
- Log errors appropriately using Laravel's logging
- Return consistent error responses
- Implement proper validation error handling
- Use proper exception hierarchy
- Follow error recovery patterns
- Use proper exception rendering
- Implement proper API error responses
- Follow consistency in error messages

## Testing
- Write Pest tests for all new code
- Use data providers for comprehensive test cases
- Mock external services
- Test edge cases and error scenarios
- Test tenant isolation properly
- Implement proper authentication in tests
- Use proper factories with tenant awareness
- Follow consistent test patterns
- Test authorization rules thoroughly
- Implement proper database transactions in tests
