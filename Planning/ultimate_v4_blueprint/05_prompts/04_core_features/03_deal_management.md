# Deal Management System Implementation

## Context
The Deal Management System is a critical component of Fusion CRM v4, tracking sales pipelines from lead to close. This system needs to support various property strategies and deal types as outlined in the blueprint.

## Task
Implement a comprehensive deal management system that:

1. Creates a flexible deal model with stages and status tracking
2. Supports multiple deal types and property strategies
3. Implements pipeline visualization and management
4. Integrates with leads, properties, and clients

## Technical Requirements
- PHP 8.4 with strict typing (`declare(strict_types=1);`)
- PSR-12 coding standards
- Follow SOLID principles
- Ensure tenant isolation for deal data
- Implement proper validation with Form Requests
- Create comprehensive Pest tests

## Implementation Details
- Create Action classes for all deal operations
- Implement proper validation using Form Requests
- Design a flexible deal stage system with configurable pipelines
- Create a robust deal tracking mechanism with status history
- Implement proper database indexing for performance
- Add event-driven architecture for deal lifecycle events

## Expected Output
- Deal model with migrations and relationships
- DealStage and DealPipeline models
- DealController with proper route definitions
- Action classes for all deal operations
- Form Requests for validation
- Livewire components for pipeline visualization
- Complete test coverage with Pest tests
