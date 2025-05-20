# Fusion CRM V4 - Database Schema

This document outlines the database schema for Fusion CRM V4, including the core tables, relationships, indices, and best practices for database interaction.

## Database Design Principles

The database design for Fusion CRM V4 adheres to the following principles:

1. **Multi-Tenant Data Isolation**: All tables include a `tenant_id` column for data segregation
2. **Normalization**: Database is normalized to 3NF where appropriate
3. **Strategic Denormalization**: Some denormalization for performance where needed
4. **Soft Deletes**: Most entities support soft deletion for data recovery
5. **Consistent Naming**: Tables use plural snake_case, columns use snake_case
6. **Indexing Strategy**: Strategic indexing for frequently queried columns, including tenant_id
7. **Timestamp Tracking**: `created_at`, `updated_at` on all tables

## Core Entity Tables

### Tenant Management

#### `tenants` Table
Stores organization/tenant information.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `name` | varchar(255) | NOT NULL | Tenant name |
| `domain` | varchar(255) | NULL, UNIQUE | Custom domain |
| `slug` | varchar(255) | NOT NULL, UNIQUE | URL-friendly identifier |
| `settings` | json | NULL | Tenant-specific settings |
| `plan_id` | bigint | NULLABLE, FK | Billing plan |
| `subscription_status` | varchar(50) | NOT NULL | active, inactive, trial |
| `trial_ends_at` | timestamp | NULLABLE | Trial end date |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

### User Management

#### `users` Table
Stores user accounts.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `name` | varchar(255) | NOT NULL | User's full name |
| `email` | varchar(255) | NOT NULL, UNIQUE | Email address |
| `password` | varchar(255) | NOT NULL | Hashed password |
| `remember_token` | varchar(100) | NULLABLE | Remember token |
| `email_verified_at` | timestamp | NULLABLE | Email verification timestamp |
| `profile_photo_path` | varchar(2048) | NULLABLE | Profile photo |
| `active` | boolean | NOT NULL, DEFAULT true | Account status |
| `phone` | varchar(50) | NULLABLE | Phone number |
| `settings` | json | NULLABLE | User preferences |
| `last_login_at` | timestamp | NULLABLE | Last login timestamp |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `role_user` Table (Pivot)
Links users to roles.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `role_id` | bigint | NOT NULL, FK | Role foreign key |
| `user_id` | bigint | NOT NULL, FK | User foreign key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |

### Property Management

#### `projects` Table
Stores property development projects.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `developer_id` | bigint | NULLABLE, FK | Developer foreign key |
| `name` | varchar(255) | NOT NULL | Project name |
| `slug` | varchar(255) | NOT NULL | URL-friendly identifier |
| `description` | text | NULLABLE | Project description |
| `address` | varchar(255) | NULLABLE | Street address |
| `suburb` | varchar(100) | NULLABLE | Suburb/city |
| `state` | varchar(100) | NULLABLE | State/province |
| `postcode` | varchar(20) | NULLABLE | Postal code |
| `country` | varchar(100) | NULLABLE | Country |
| `latitude` | decimal(10,8) | NULLABLE | Latitude coordinates |
| `longitude` | decimal(11,8) | NULLABLE | Longitude coordinates |
| `status` | varchar(50) | NOT NULL | Project status |
| `total_lots` | integer | NULLABLE | Total number of lots |
| `available_lots` | integer | NULLABLE | Available lots count |
| `min_price` | decimal(12,2) | NULLABLE | Minimum price |
| `max_price` | decimal(12,2) | NULLABLE | Maximum price |
| `featured` | boolean | NOT NULL, DEFAULT false | Featured flag |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `stages` Table
Stores stages within a project.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `project_id` | bigint | NOT NULL, FK | Project foreign key |
| `name` | varchar(255) | NOT NULL | Stage name |
| `description` | text | NULLABLE | Stage description |
| `release_date` | date | NULLABLE | Release date |
| `status` | varchar(50) | NOT NULL | Stage status |
| `order` | integer | NOT NULL, DEFAULT 0 | Display order |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `lots` Table
Stores individual property lots.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `stage_id` | bigint | NOT NULL, FK | Stage foreign key |
| `lot_number` | varchar(50) | NOT NULL | Lot identifier |
| `street_number` | varchar(50) | NULLABLE | Street number |
| `street_name` | varchar(255) | NULLABLE | Street name |
| `price` | decimal(12,2) | NULLABLE | Price |
| `land_size` | decimal(10,2) | NULLABLE | Land size (m²) |
| `land_price` | decimal(12,2) | NULLABLE | Land price |
| `house_price` | decimal(12,2) | NULLABLE | House price |
| `bedrooms` | integer | NULLABLE | Number of bedrooms |
| `bathrooms` | decimal(3,1) | NULLABLE | Number of bathrooms |
| `garage` | integer | NULLABLE | Number of garage spaces |
| `description` | text | NULLABLE | Lot description |
| `features` | json | NULLABLE | Property features |
| `status` | varchar(50) | NOT NULL | Lot status |
| `sale_status` | varchar(50) | NULLABLE | Sale status |
| `estimated_completion` | date | NULLABLE | Estimated completion date |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `property_media` Table
Stores media associated with properties.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `mediable_id` | bigint | NOT NULL | Polymorphic ID |
| `mediable_type` | varchar(255) | NOT NULL | Polymorphic type |
| `file_name` | varchar(255) | NOT NULL | Original filename |
| `file_path` | varchar(255) | NOT NULL | Storage path |
| `file_size` | integer | NOT NULL | File size in bytes |
| `file_type` | varchar(50) | NOT NULL | MIME type |
| `media_type` | varchar(50) | NOT NULL | Media classification |
| `title` | varchar(255) | NULLABLE | Media title |
| `description` | text | NULLABLE | Media description |
| `order` | integer | NOT NULL, DEFAULT 0 | Display order |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

### CRM Management

#### `leads` Table
Stores potential clients/leads.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `assigned_to` | bigint | NULLABLE, FK | Assigned user |
| `first_name` | varchar(100) | NULLABLE | First name |
| `last_name` | varchar(100) | NOT NULL | Last name |
| `email` | varchar(255) | NULLABLE | Email address |
| `phone` | varchar(50) | NULLABLE | Phone number |
| `address` | varchar(255) | NULLABLE | Address |
| `suburb` | varchar(100) | NULLABLE | Suburb/city |
| `state` | varchar(100) | NULLABLE | State/province |
| `postcode` | varchar(20) | NULLABLE | Postal code |
| `country` | varchar(100) | NULLABLE | Country |
| `source` | varchar(100) | NULLABLE | Lead source |
| `source_detail` | varchar(255) | NULLABLE | Detailed source info |
| `status` | varchar(50) | NOT NULL | Lead status |
| `stage` | varchar(50) | NULLABLE | Sales pipeline stage |
| `score` | integer | NULLABLE | Lead score |
| `tags` | json | NULLABLE | Tags |
| `custom_fields` | json | NULLABLE | Custom field values |
| `notes` | text | NULLABLE | General notes |
| `last_contacted_at` | timestamp | NULLABLE | Last contact timestamp |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `contacts` Table
Stores client/contact information.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `lead_id` | bigint | NULLABLE, FK | Associated lead |
| `first_name` | varchar(100) | NULLABLE | First name |
| `last_name` | varchar(100) | NOT NULL | Last name |
| `email` | varchar(255) | NULLABLE | Email address |
| `phone` | varchar(50) | NULLABLE | Phone number |
| `company` | varchar(255) | NULLABLE | Company name |
| `position` | varchar(255) | NULLABLE | Job position |
| `address` | varchar(255) | NULLABLE | Address |
| `suburb` | varchar(100) | NULLABLE | Suburb/city |
| `state` | varchar(100) | NULLABLE | State/province |
| `postcode` | varchar(20) | NULLABLE | Postal code |
| `country` | varchar(100) | NULLABLE | Country |
| `contact_type` | varchar(50) | NULLABLE | Contact type |
| `tags` | json | NULLABLE | Tags |
| `custom_fields` | json | NULLABLE | Custom field values |
| `notes` | text | NULLABLE | General notes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `deals` Table
Stores sales opportunities and deals.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `lead_id` | bigint | NULLABLE, FK | Associated lead |
| `contact_id` | bigint | NULLABLE, FK | Associated contact |
| `lot_id` | bigint | NULLABLE, FK | Associated property lot |
| `assigned_to` | bigint | NULLABLE, FK | Assigned user |
| `title` | varchar(255) | NOT NULL | Deal title |
| `description` | text | NULLABLE | Deal description |
| `value` | decimal(12,2) | NULLABLE | Deal value |
| `status` | varchar(50) | NOT NULL | Deal status |
| `stage` | varchar(50) | NOT NULL | Pipeline stage |
| `probability` | integer | NULLABLE | Closure probability |
| `expected_close_date` | date | NULLABLE | Expected close date |
| `actual_close_date` | date | NULLABLE | Actual close date |
| `custom_fields` | json | NULLABLE | Custom field values |
| `tags` | json | NULLABLE | Tags |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `tasks` Table
Stores tasks related to deals, leads, etc.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `taskable_id` | bigint | NULLABLE | Polymorphic ID |
| `taskable_type` | varchar(255) | NULLABLE | Polymorphic type |
| `assigned_by` | bigint | NULLABLE, FK | Assigning user |
| `assigned_to` | bigint | NULLABLE, FK | Assigned user |
| `title` | varchar(255) | NOT NULL | Task title |
| `description` | text | NULLABLE | Task description |
| `due_date` | datetime | NULLABLE | Due date/time |
| `completed_at` | timestamp | NULLABLE | Completion timestamp |
| `priority` | varchar(50) | NOT NULL | Task priority |
| `status` | varchar(50) | NOT NULL | Task status |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `notes` Table
Stores notes related to various entities.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `notable_id` | bigint | NOT NULL | Polymorphic ID |
| `notable_type` | varchar(255) | NOT NULL | Polymorphic type |
| `user_id` | bigint | NOT NULL, FK | Author |
| `title` | varchar(255) | NULLABLE | Note title |
| `content` | text | NOT NULL | Note content |
| `pinned` | boolean | NOT NULL, DEFAULT false | Pinned status |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

### Financial Management

#### `invoices` Table
Stores invoice data for Xero integration.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `deal_id` | bigint | NULLABLE, FK | Associated deal |
| `contact_id` | bigint | NULLABLE, FK | Associated contact |
| `invoice_number` | varchar(100) | NOT NULL | Invoice number |
| `xero_invoice_id` | varchar(255) | NULLABLE | Xero invoice ID |
| `xero_contact_id` | varchar(255) | NULLABLE | Xero contact ID |
| `invoice_date` | date | NOT NULL | Issue date |
| `due_date` | date | NOT NULL | Due date |
| `amount` | decimal(12,2) | NOT NULL | Total amount |
| `tax_amount` | decimal(12,2) | NOT NULL | Tax amount |
| `status` | varchar(50) | NOT NULL | Invoice status |
| `currency` | varchar(3) | NOT NULL | Currency code |
| `reference` | varchar(255) | NULLABLE | Reference number |
| `description` | text | NULLABLE | Invoice description |
| `last_synced_at` | timestamp | NULLABLE | Last Xero sync |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `invoice_items` Table
Stores line items for invoices.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `invoice_id` | bigint | NOT NULL, FK | Invoice foreign key |
| `description` | text | NOT NULL | Item description |
| `quantity` | decimal(10,2) | NOT NULL | Quantity |
| `unit_price` | decimal(12,2) | NOT NULL | Unit price |
| `amount` | decimal(12,2) | NOT NULL | Line total |
| `tax_rate` | decimal(5,2) | NOT NULL | Tax rate |
| `tax_amount` | decimal(12,2) | NOT NULL | Tax amount |
| `xero_line_item_id` | varchar(255) | NULLABLE | Xero line ID |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |

#### `commissions` Table
Stores commission data for deals.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `deal_id` | bigint | NOT NULL, FK | Associated deal |
| `user_id` | bigint | NOT NULL, FK | Commission recipient |
| `invoice_id` | bigint | NULLABLE, FK | Associated invoice |
| `amount` | decimal(12,2) | NOT NULL | Commission amount |
| `rate` | decimal(5,2) | NULLABLE | Commission rate |
| `status` | varchar(50) | NOT NULL | Commission status |
| `payment_date` | date | NULLABLE | Payment date |
| `description` | text | NULLABLE | Description |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

### AI & Marketing

#### `ai_prompts` Table
Stores AI prompt templates.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `category` | varchar(100) | NOT NULL | Prompt category |
| `name` | varchar(255) | NOT NULL | Prompt name |
| `description` | text | NULLABLE | Prompt description |
| `content` | text | NOT NULL | Prompt template |
| `model` | varchar(100) | NOT NULL | AI model |
| `temperature` | decimal(3,2) | NOT NULL | Temperature setting |
| `max_tokens` | integer | NOT NULL | Max token count |
| `tags` | json | NULLABLE | Categorization tags |
| `is_system` | boolean | NOT NULL, DEFAULT false | System prompt flag |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `ai_conversations` Table
Stores conversation histories with AI.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `user_id` | bigint | NULLABLE, FK | User foreign key |
| `lead_id` | bigint | NULLABLE, FK | Lead foreign key |
| `title` | varchar(255) | NULLABLE | Conversation title |
| `context` | text | NULLABLE | Conversation context |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `ai_messages` Table
Stores individual AI messages.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `conversation_id` | bigint | NOT NULL, FK | Conversation foreign key |
| `role` | varchar(50) | NOT NULL | Message role |
| `content` | text | NOT NULL | Message content |
| `tokens` | integer | NULLABLE | Token count |
| `model` | varchar(100) | NULLABLE | AI model used |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |

#### `campaigns` Table
Stores marketing campaigns.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `user_id` | bigint | NOT NULL, FK | Campaign creator |
| `name` | varchar(255) | NOT NULL | Campaign name |
| `description` | text | NULLABLE | Campaign description |
| `type` | varchar(100) | NOT NULL | Campaign type |
| `status` | varchar(50) | NOT NULL | Campaign status |
| `start_date` | date | NULLABLE | Start date |
| `end_date` | date | NULLABLE | End date |
| `budget` | decimal(12,2) | NULLABLE | Budget amount |
| `metrics` | json | NULLABLE | Performance metrics |
| `tags` | json | NULLABLE | Categorization tags |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Database Relationships

The database design includes the following key relationships:

### One-to-Many Relationships
- Tenant → Users
- Tenant → Projects
- Project → Stages
- Stage → Lots
- User → Leads (assigned to)
- User → Deals (assigned to)
- User → Tasks (assigned to)
- User → Notes (author)
- Tenant → AI Prompts
- Tenant → Campaigns

### Many-to-Many Relationships
- Users ↔ Roles
- Properties ↔ Features
- Campaigns ↔ Leads

### Polymorphic Relationships
- Notes → Many entity types (notable)
- Tasks → Many entity types (taskable)
- Media → Many entity types (mediable)

## Indexing Strategy

To optimize database performance, the following columns should be indexed:

### Primary Indices
- All primary keys (`id` columns)
- All foreign keys (`*_id` columns)
- `tenant_id` on all tenant-scoped tables

### Secondary Indices
- `email` on users table
- `status` columns for frequently filtered tables
- `created_at` for date-range queries
- Compound indices for common query patterns

### Example Index Declarations

```php
// In a Laravel migration
$table->index('tenant_id');
$table->index(['tenant_id', 'status']);
$table->index(['tenant_id', 'created_at']);
$table->unique(['tenant_id', 'email']);
```

## Soft Delete Strategy

Most entities in the system implement soft deletes:

- Records are never physically deleted from the database
- `deleted_at` timestamp is set when a record is "deleted"
- Queries automatically exclude soft-deleted records
- Records can be restored by clearing the `deleted_at` timestamp
- Periodic cleanup jobs can physically remove old soft-deleted records

## JSON Column Strategy

JSON columns are used for:

1. **Settings**: Configuration data that varies in structure
2. **Custom Fields**: User-defined fields for various entities
3. **Metrics**: Performance metrics and statistics
4. **Tags**: Flexible categorization
5. **Features**: Property features and attributes

Example JSON column schema:

```php
// Custom fields JSON schema
{
  "fields": [
    {
      "name": "budget",
      "label": "Budget Range",
      "type": "select",
      "options": ["100k-200k", "200k-300k", "300k+"],
      "value": "200k-300k"
    },
    {
      "name": "preferred_location",
      "label": "Preferred Location",
      "type": "text",
      "value": "Brisbane CBD"
    }
  ]
}
```

## Multi-Tenant Design Pattern

The multi-tenant pattern is implemented through:

1. **Global Tenant Scope**: Applied to all tenant-scoped models
2. **Foreign Key Constraints**: All tenant-related tables include a `tenant_id` foreign key
3. **Index Optimization**: Compound indices include `tenant_id` for optimal filtering
4. **Query Safety**: Automatic tenant scoping to prevent data leakage

## Database Migrations Strategy

Database migrations follow these principles:

1. **Incremental Changes**: Small, focused migrations for each schema change
2. **Forward Compatibility**: Migrations designed to be backward compatible where possible
3. **Rollback Support**: All migrations include down() methods for rollbacks
4. **Data Migration**: Separate data migration tasks for moving/transforming data

## Eloquent Model Implementation

Eloquent models should implement:

1. **Tenant Scoping**: Via a `TenantScope` global scope or trait
2. **Relationships**: Clear relationship definitions
3. **Attribute Casting**: Proper casting of JSON columns and dates
4. **Fillable/Guarded**: Security for mass assignment
5. **Accessors/Mutators**: For data transformation
6. **Scopes**: For common query patterns

Example Eloquent model implementation:

```php
class Lead extends Model
{
    use HasFactory, SoftDeletes, HasTenant;

    protected $fillable = [
        'tenant_id', 'first_name', 'last_name', 'email', 'phone',
        'status', 'source', 'notes', 'assigned_to'
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'tags' => 'array',
        'score' => 'integer',
        'last_contacted_at' => 'datetime',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function notes()
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function tasks()
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'closed');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('score', '>=', 80);
    }
}
```

## Query Optimization Guidelines

To ensure optimal database performance:

1. **Use Eager Loading**: Always eager load relationships to prevent N+1 queries
2. **Chunk Large Queries**: Use chunk() for processing large result sets
3. **Use DB Transactions**: Wrap related operations in database transactions
4. **Optimize Select Columns**: Only select needed columns in queries
5. **Use Query Caching**: Cache expensive queries where appropriate
6. **Consider Query Plans**: Use EXPLAIN to analyze and optimize complex queries

## Backup and Maintenance

Database backup and maintenance recommendations:

1. **Daily Backups**: Full database dumps daily
2. **Incremental Backups**: Hourly incremental backups
3. **Point-in-Time Recovery**: Maintain binary logs for PIT recovery
4. **Regular Optimization**: Schedule regular table optimization tasks
5. **Index Maintenance**: Periodic index analysis and rebuilding
6. **Data Archiving**: Archive old data to maintain performance

By following this database schema and the associated best practices, Fusion CRM V4 will have a solid foundation for data management that supports multi-tenancy, complex relationships, and optimal performance. 