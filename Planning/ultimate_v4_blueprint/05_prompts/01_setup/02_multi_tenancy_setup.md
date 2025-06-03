# Multi-Tenancy Implementation for Fusion CRM v4

## Context
Fusion CRM v4 requires robust multi-tenancy to support multiple property agencies (tenants) with isolated data. Wave provides a team-based foundation that we'll extend into a full multi-tenant architecture.

## Task
Implement a comprehensive multi-tenancy solution that:

1. Extends Wave's team structure to function as tenants
2. Creates a global tenant scope to automatically filter queries by tenant
3. Implements middleware for tenant context awareness
4. Sets up tenant database migrations and models

## Technical Requirements
- Use PHP 8.4 features where applicable
- Follow PSR-12 coding standards strictly
- Implement strict types (`declare(strict_types=1);`)
- Design models according to Laravel and Eloquent best practices
- Create thorough Pest tests for all tenant-related functionality

## Expected Output
- Tenant model extending Wave's team model
- Tenant scope trait to apply to tenant-aware models
- Middleware for tenant context in requests
- Base tenant-aware model abstract class
- Migration for extending team table with tenant-specific fields
- Complete test coverage for tenant functionality
