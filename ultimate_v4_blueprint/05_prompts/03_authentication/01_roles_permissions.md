# Roles and Permissions System Implementation

## Context
Fusion CRM v4 requires a sophisticated roles and permissions system that works within our multi-tenant architecture. Each tenant will have their own set of roles and user assignments.

## Task
Implement a comprehensive roles and permissions system that:

1. Extends Wave's existing user authentication
2. Creates a tenant-aware role and permission structure
3. Implements policy-based authorization
4. Provides a management interface for tenant administrators

## Technical Requirements
- PHP 8.4 with strict typing (`declare(strict_types=1);`)
- PSR-12 coding standards
- Laravel Gates and Policies
- Tenant-scoped roles and permissions
- Role-based middleware
- Test coverage with Pest

## Implementation Details
- Extend Laravel's built-in authorization features
- Create role and permission models with tenant scoping
- Implement caching for performance optimization
- Provide helper traits for controllers and models
- Use policy-based authorization for all resources
- Include seed data for default roles

## Expected Output
- Role and Permission models with migrations
- Authorization policies for all resources
- Middleware for role-based route protection
- Helper traits for authorization checks
- Complete test coverage using Pest
- Seeder for default roles and permissions
