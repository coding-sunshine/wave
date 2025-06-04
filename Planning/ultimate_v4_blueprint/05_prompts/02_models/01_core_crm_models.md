# Core CRM Models Implementation

## Context
Fusion CRM v4 needs robust data models for its core CRM functionality. These models will build upon the multi-tenant architecture we've established and follow strict coding standards. **The definitive source for all database table structures, fields, and relationships is the comprehensive database schema document located at `Planning/ultimate_v4_blueprint/02_architecture/02_database_schema.md`.**

## Task
Implement the core CRM models with proper relationships, validation, and tenant awareness, ensuring strict adherence to the schema defined in `Planning/ultimate_v4_blueprint/02_architecture/02_database_schema.md`. The primary models to create are:

1.  **Contact Model** (representing leads, clients, vendors, etc., based on the `contacts` table in the schema)
2.  **Property Models**:
    *   `Project` Model (based on the `projects` table)
    *   `PropertyUnit` Model (based on the `property_units` table)
    *   `PropertyListing` Model (based on the `property_listings` table)
3.  **Deal Model** (based on the `deals` table for sales pipeline tracking)
4.  **Interaction Model** (representing activities like calls, emails, notes, based on the `interactions` table)
5.  **Task Model** (based on the `tasks` table)

All model properties, fillable attributes, casts, and relationships (Eloquent relationships like `belongsTo`, `hasMany`, `morphTo`, `morphMany`, etc.) must directly correspond to the columns and relationships defined in the referenced database schema document.

## Technical Requirements
- PHP 8.4 with strict types (`declare(strict_types=1);`)
- PSR-12 coding standards
- Laravel Eloquent relationships
- **Strict adherence to the database schema defined in `Planning/ultimate_v4_blueprint/02_architecture/02_database_schema.md` for all table structures and model attributes.**
- Apply tenant scoping to all relevant models.
- Type hinting for all properties and methods.
- Factory classes for each model for testing.

## Implementation Details
- Use camelCase for methods and variables.
- Use PascalCase for classes.
- Use snake_case for database columns (as defined in the schema).
- **Tenant Scoping**: Implement tenant scoping for all models that have a `tenant_id` column (as specified in the schema). This should typically be achieved by:
    - Adding a `tenant_id` foreign key to the model's table.
    - Creating a global scope (e.g., `TenantScope`) that automatically applies a `WHERE tenant_id = ?` condition to queries.
    - Alternatively, use a dedicated trait (e.g., `BelongsToTenant`) that applies this scope and provides a `tenant()` relationship.
    - Ensure that creating new records automatically associates them with the current tenant.
- Create appropriate migrations with all columns, data types, indexes, and foreign key constraints as defined in the `02_database_schema.md`.
- Add PHPDoc blocks to all methods and properties.
- Define `$fillable` or `$guarded` properties appropriately for mass assignment protection.
- Implement model events (observers or boot methods) where appropriate for lifecycle hooks (e.g., generating UUIDs, setting default values).
- Create Pest tests for all models, covering:
    - Model creation and attribute assignment.
    - Relationship definitions and functionality.
    - Tenant scoping behavior.
    - Any custom model methods or accessors/mutators.

## Expected Output
- Migration files for all core CRM models, accurately reflecting the schema in `Planning/ultimate_v4_blueprint/02_architecture/02_database_schema.md`.
- Eloquent model classes (`Contact`, `Project`, `PropertyUnit`, `PropertyListing`, `Deal`, `Interaction`, `Task`) with correct properties, casts, fillable attributes, and Eloquent relationships as per the schema.
- Tenant scoping implemented correctly on all relevant models.
- Factory classes for each model to facilitate testing.
- Complete Pest test coverage for all models and their core functionalities.
