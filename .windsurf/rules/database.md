---
trigger: manual
---

# Database Rules
@globs: ["database/migrations/*.php", "app/Models/*.php"]

You are a database expert focusing on Laravel's Eloquent ORM. Follow these rules:

## Migrations
- Use appropriate column types
- Add proper indexes
- Include foreign key constraints
- Use consistent naming conventions
- Include docblocks for complex migrations
- Avoid using raw SQL
- Always include `tenant_id` foreign key where appropriate
- Use soft deletes for important data
- Maintain data integrity with constraints

## Models
- Define proper relationships
- Use model observers when needed
- Implement proper scopes
- Use proper attribute casting
- Define fillable/guarded properties
- Use model factories for testing
- Implement the `BelongsToTenant` trait for tenant-scoped models
- Apply `TenantScope` global scope to maintain data isolation
- Use validation traits for complex validation rules

## Query Optimization
- Use eager loading to avoid N+1
- Use query builder methods properly
- Implement proper caching strategies
- Use chunking for large datasets
- Monitor query performance
- Use database transactions appropriately
- Apply tenant scopes consistently
- Add appropriate indexes for tenant-specific queries
- Use query caching with tenant awareness

## Multi-Tenancy
- Use Wave Kit's team model as a foundation for tenants
- Apply tenant scoping consistently across related models
- Validate tenant context in controllers and middleware
- Ensure database queries include tenant scoping
- Implement proper tenant separation for shared resources
- Use tenant-aware factories for testing
- Design migrations with multi-tenancy in mind
- Implement proper tenant provisioning workflows
- Handle tenant-specific settings properly

## Wave Kit Database Integration
- Extend Wave models with tenant-specific functionality
- Use model observers for tenant-related events
- Implement proper cascading deletes for tenant resources
- Use tenant-aware seeders for development
- Implement proper tenant data import/export functions
- Use proper database connection configuration
- Handle tenant database migrations properly
- Follow Wave Kit naming conventions where applicable

## Filament Integration
- Use form schemas consistently
- Implement proper resource relationships
- Use tenant-aware resource queries
- Implement global tenant scoping for Filament resources
- Use proper validation rules in Filament forms
- Implement proper error handling
- Design proper table relationships
- Use resource policies for authorization

## Data Integrity
- Implement proper validation rules
- Use database constraints
- Handle soft deletes properly
- Implement proper audit trails
- Use proper data types
- Handle unique constraints properly
- Validate tenant context before data mutations
- Implement proper data archiving strategies
- Use proper data encryption for sensitive information
