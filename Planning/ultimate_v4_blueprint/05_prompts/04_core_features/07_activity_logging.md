# Activity Logging System Implementation

## Context
A comprehensive activity logging system is crucial for Fusion CRM v4 to track user actions, system events, and changes to important entities. This system needs to be tenant-aware and provide insights into user behavior and data changes.

## Task
Implement a robust activity logging system that:

1. Tracks user actions across the application
2. Records changes to important entities (leads, properties, deals, clients)
3. Provides a searchable activity timeline
4. Integrates with the audit and compliance requirements

## Technical Requirements
- PHP 8.4 with strict typing (`declare(strict_types=1);`)
- PSR-12 coding standards
- Follow SOLID principles
- Tenant isolation for all activity data
- Proper database indexing for performance
- Complete test coverage with Pest

## Implementation Details
- Create a dedicated ActivityService for logging actions
- Implement model observers for automatic change tracking
- Design a flexible activity type system
- Build a robust search and filtering system for activities
- Create proper database indexes for performance
- Implement a cleanup/archiving strategy for old activities

## Expected Output
- Activity model with migrations and relationships
- ActivityType enum or model
- ActivityService for logging actions
- Model observers for automatic tracking
- Controllers and routes for activity management
- Livewire components for activity timelines
- Complete test coverage with Pest tests
