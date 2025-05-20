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

## Laravel Best Practices
- Use dependency injection over facades where possible
- Use form requests for validation
- Use actions for business logic
- Use Laravel's built-in security features
- Follow Laravel naming conventions
- Use Laravel's query builder methods over raw SQL

## Error Handling
- Create domain-specific exceptions
- Use try-catch blocks for expected exceptions
- Log errors appropriately using Laravel's logging
- Return consistent error responses

## Testing
- Write Pest tests for all new code
- Use data providers for comprehensive test cases
- Mock external services
- Test edge cases and error scenarios
