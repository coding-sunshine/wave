# Data Tables and Filters Implementation

## Context
Fusion CRM v4 requires powerful, interactive data tables with advanced filtering capabilities for managing leads, properties, deals, and clients. These components need to be reusable, efficient, and tenant-aware.

## Task
Implement a comprehensive data table and filtering system that:

1. Creates reusable, configurable data table components
2. Implements powerful filtering and sorting capabilities
3. Supports pagination and bulk actions
4. Maintains performance with large datasets

## Technical Requirements
- PHP 8.4 with strict typing (`declare(strict_types=1);`)
- PSR-12 coding standards
- Livewire for server-side rendering and interactivity
- Alpine.js for client-side interactions
- Tailwind CSS for styling
- Tenant-aware data scoping

## Implementation Details
- Create a base DataTable Livewire component
- Implement column configuration system
- Design filter components with different types (text, select, date, etc.)
- Create sorting functionality with proper database queries
- Implement pagination with state persistence
- Add bulk action capabilities with confirmation
- Ensure proper performance with eager loading and pagination

## Expected Output
- Base DataTable Livewire component
- Column configuration system
- Filter components for different data types
- Sorting implementation
- Pagination with state persistence
- Bulk action handling
- Usage examples for leads, properties, deals, and clients
- Complete test coverage for all components
