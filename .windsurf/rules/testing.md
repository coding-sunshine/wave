---
trigger: manual
---

# Testing Rules
@globs: ["tests/**/*.php"]

You are a testing expert focusing on Pest PHP. Follow these rules:

## Test Structure
- Use descriptive test names
- Follow Arrange-Act-Assert pattern
- Group related tests together
- Use proper test isolation
- Create proper test datasets
- Use appropriate test doubles
- Test tenant-specific functionality
- Implement proper tenant context in tests
- Test multi-tenant scenarios
- Follow consistent naming patterns

## Pest Specific
- Use expectations properly
- Utilize higher order testing
- Use datasets for multiple scenarios
- Use proper test hooks
- Follow Pest naming conventions
- Use proper test organization
- Use expectations with custom messages
- Implement proper Pest plugins
- Use Pest's architectural testing features
- Follow descriptive testing style

## Testing Wave Kit Extensions
- Test Wave Kit model extensions
- Test tenant-aware authentication
- Test custom Wave Kit middleware
- Test Wave Kit theme customizations
- Test Wave Kit service extensions
- Use proper Wave Kit testing utilities
- Test subscription and billing integrations
- Test user impersonation with tenant context
- Test Wave Kit API extensions
- Follow Wave Kit testing patterns

## Filament Admin Testing
- Test Filament resource forms
- Test Filament resource tables
- Test Filament resource actions
- Test Filament widgets
- Test Filament authorization policies
- Use proper Filament testing utilities
- Test custom Filament fields
- Test tenant-aware resources
- Test Filament page navigation
- Follow Filament testing best practices

## Multi-Tenancy Testing
- Test tenant isolation properly
- Test cross-tenant functionality
- Test tenant-specific features
- Test tenant provisioning process
- Use proper tenant factories
- Test tenant middleware
- Implement proper tenant context in tests
- Test tenant authorization
- Test tenant data migrations
- Follow multi-tenant testing patterns

## Test Coverage
- Test happy path scenarios
- Test edge cases
- Test error conditions
- Test validation rules
- Test authorization rules
- Test business logic thoroughly
- Test tenant-specific validation
- Test cross-tenant interactions
- Test tenant middleware behavior
- Test tenant lifecycle hooks

## Best Practices
- Keep tests focused and small
- Use proper test data factories
- Clean up after tests
- Mock external services
- Use proper database transactions
- Follow testing pyramid principles
- Use proper tenant context setup
- Test with proper tenant isolation
- Follow consistent assert patterns
- Implement proper test documentation
