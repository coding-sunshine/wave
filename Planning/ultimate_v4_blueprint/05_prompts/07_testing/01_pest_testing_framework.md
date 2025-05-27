# Pest Testing Framework Implementation

## Context
Fusion CRM v4 requires comprehensive testing following the project rules, using Pest PHP for all tests. This prompt establishes the testing framework and conventions for the entire application.

## Task
Set up the Pest testing framework with:

1. Directory structure following project standards
2. Base test classes for different test types
3. Testing helpers and utilities
4. CI/CD integration

## Technical Requirements
- Pest PHP for all tests
- Clear test organization (Feature vs. Unit tests)
- Testing database configuration
- Factory pattern for test data
- Mock strategies for external services

## Implementation Details
- Organize tests according to project standards:
  - Feature tests in `tests/Feature/Http` and `tests/Feature/Console`
  - Unit tests in `tests/Unit` with subdirectories by component type
- Create base test classes with tenant context support
- Implement database transactions for test isolation
- Set up mock implementations for external services
- Create helper traits for common testing scenarios

## Expected Output
- Complete Pest testing framework setup
- Base test classes for different test types
- Helper traits for common testing scenarios
- Example tests for main application components
- Documentation on testing conventions
