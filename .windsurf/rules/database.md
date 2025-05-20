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

## Models
- Define proper relationships
- Use model observers when needed
- Implement proper scopes
- Use proper attribute casting
- Define fillable/guarded properties
- Use model factories for testing

## Query Optimization
- Use eager loading to avoid N+1
- Use query builder methods properly
- Implement proper caching strategies
- Use chunking for large datasets
- Monitor query performance
- Use database transactions appropriately

## Data Integrity
- Implement proper validation rules
- Use database constraints
- Handle soft deletes properly
- Implement proper audit trails
- Use proper data types
- Handle unique constraints properly
