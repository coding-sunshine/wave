# Fusion CRM V4: Comprehensive Database Schema

**Note:** This document is the comprehensive and consolidated database schema for Fusion CRM V4. It integrates and supersedes previous schema versions, including concepts from an earlier draft and the detailed design from `Planning/ultimate_v4_blueprint/database_design.md`.

## Introduction

This document outlines the database schema for Fusion CRM V4, a comprehensive real estate CRM system optimized for the Australian market. The schema is designed to support multi-tenancy (multi-subscriber, single database), complex relationships, and optimal performance, catering to a wide array of features including AI-driven tools, advanced property management, and detailed financial tracking.

## Core Design Principles

### Multi-Subscriber, Single-Database Architecture
-   The system uses a single database. Data for different subscriber organizations (referred to as "tenants") is logically separated using a `tenant_id` column on all relevant tables.
-   Application-level logic and global scopes in Eloquent models ensure that tenants can only access their own data.

### Data Integrity
-   Appropriate constraints (foreign keys, unique constraints) and validations are implemented.
-   Soft deletes using `deleted_at` are implemented for relevant tables to preserve historical data.
-   UUID columns (`uuid`) are used for secure and stable external references where appropriate.

### Normalization & Performance
-   Tables are normalized to reduce redundancy while considering performance for common query patterns.
-   Indexes are applied to foreign keys and columns frequently used in `WHERE`, `ORDER BY`, and `GROUP BY` clauses.
-   JSON columns are used for flexible, schema-less data storage where appropriate (e.g., settings, custom attributes, feature lists).

### Naming Conventions
-   Tables and columns use `snake_case`.
-   Primary keys are typically `id` (BIGINT UNSIGNED AUTO_INCREMENT).
-   Foreign keys follow the `relatedtable_singular_id` pattern (e.g., `user_id` in a `posts` table).

## Third-Party Package Integration

The schema leverages several key Laravel packages:

1.  **Wave Boilerplate Core:**
    *   Provides foundational tables for `users`, `api_keys`, `settings`, `plans`, `subscriptions`, basic CMS (`categories`, `posts`, `pages`), and `forms`. These are extended as needed.

2.  **Spatie Packages:**
    *   **`spatie/laravel-permission`**: For role-based access control. Uses `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` tables.
    *   **`spatie/laravel-medialibrary`**: For robust file and media management. Uses the `media` table with polymorphic relationships.
    *   **`spatie/laravel-activitylog`**: For tracking user actions and system events. Uses the `activity_log` table.
    *   **`spatie/laravel-tags`**: For flexible tagging across various models. Uses `tags` and `taggables` tables.
    *   **`spatie/laravel-settings`**: Can be used for managing global and tenant-specific settings, potentially extending Wave's `settings` table.

Other packages for specific functionalities (e.g., Xero integration, AI clients) will interact with this schema but might not introduce their own tables directly if they are API clients.

## Schema Details

### User & Tenant (Subscriber) Management

#### `tenants` Table
Stores subscriber organizations.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `name` | VARCHAR(255) | NOT NULL | Tenant's company/organization name |
| `slug` | VARCHAR(255) | NOT NULL, UNIQUE, INDEX | URL-friendly identifier |
| `email` | VARCHAR(255) | NOT NULL | Primary contact email for the tenant |
| `phone` | VARCHAR(50) | NULLABLE | Primary contact phone for the tenant |
| `logo_path` | VARCHAR(255) | NULLABLE | Path to tenant's logo for white-labeling |
| `primary_color` | VARCHAR(7) | NULLABLE | Hex color code for branding (e.g., #RRGGBB) |
| `secondary_color` | VARCHAR(7) | NULLABLE | Hex color code for branding |
| `current_plan_id` | BIGINT UNSIGNED | NULLABLE, FK to `plans` | Current subscription plan from Wave/SaaS billing |
| `subscription_id` | BIGINT UNSIGNED | NULLABLE, FK to `subscriptions` | Current subscription from Wave/SaaS billing |
| `subscription_status` | VARCHAR(50) | NOT NULL, DEFAULT 'trial' | e.g., active, trial, past_due, canceled |
| `trial_ends_at` | TIMESTAMP | NULLABLE | Date when trial period ends |
| `settings` | JSON | NULLABLE | Tenant-specific overrides of global settings |
| `xero_tenant_id_override` | VARCHAR(255) | NULLABLE | If tenant connects their own Xero organization |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `users` Table (Extending Wave Core)
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | The subscriber organization this user primarily belongs to |
| `name` | VARCHAR(255) | NOT NULL | User's full name |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE, INDEX | Email address (primary login identifier) |
| `username` | VARCHAR(255) | NULLABLE, UNIQUE, INDEX | Optional username |
| `password` | VARCHAR(255) | NOT NULL | Encrypted password |
| `avatar` | VARCHAR(255) | NULLABLE | Path to profile picture |
| `remember_token` | VARCHAR(100) | NULLABLE | "Remember me" token |
| `email_verified_at` | TIMESTAMP | NULLABLE | Timestamp of email verification |
| `job_title` | VARCHAR(255) | NULLABLE | User's job title or role |
| `phone` | VARCHAR(50) | NULLABLE | Direct phone number |
| `mobile` | VARCHAR(50) | NULLABLE | Mobile phone number |
| `license_number` | VARCHAR(100) | NULLABLE | Real estate license number (state-specific) |
| `biography` | TEXT | NULLABLE | User bio for profiles/website |
| `social_links` | JSON | NULLABLE | Social media profile links (e.g., `{"linkedin": "url"}`) |
| `user_settings` | JSON | NULLABLE | User-specific preferences (overrides tenant settings) |
| `onboarding_progress` | JSON | NULLABLE | Tracks completion of onboarding steps |
| `last_login_at` | TIMESTAMP | NULLABLE | Timestamp of last successful login |
| `is_platform_admin` | BOOLEAN | NOT NULL, DEFAULT false | For super administrators of the Fusion V4 platform itself |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `tenant_user` Table (Pivot: User's association and role within a tenant)
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users`, INDEX | User identifier |
| `role_id` | BIGINT UNSIGNED | NULLABLE, FK to `roles`, INDEX | User's role within this specific tenant |
| `is_primary_contact` | BOOLEAN | NOT NULL, DEFAULT false | Is this user the main admin/contact for the tenant? |
| `status` | VARCHAR(50) | NOT NULL, DEFAULT 'active' | e.g., active, invited, suspended |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| *Unique composite key on (`tenant_id`, `user_id`)* |

#### `roles` Table (Spatie/laravel-permission, potentially tenant-scoped)
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NULLABLE, FK to `tenants`, INDEX | If NULL, it's a global/system role. Otherwise, tenant-specific. |
| `name` | VARCHAR(255) | NOT NULL | Role name (e.g., Admin, Agent, Property Manager) |
| `guard_name` | VARCHAR(255) | NOT NULL | Laravel guard (usually 'web' or 'api') |
| `description` | TEXT | NULLABLE | Description of the role's purpose |
| `is_system_role` | BOOLEAN | NOT NULL, DEFAULT false | True for default roles like 'admin', 'agent' |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |
| *Unique composite key on (`name`, `guard_name`, `tenant_id`)* |

#### `permissions` Table (Spatie/laravel-permission)
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(255) | NOT NULL | Permission name (e.g., edit_property, delete_contact) |
| `guard_name` | VARCHAR(255) | NOT NULL | Laravel guard |
| `group_name` | VARCHAR(255) | NULLABLE | For grouping permissions in UI (e.g., Properties, Contacts) |
| `description` | TEXT | NULLABLE | Description of what the permission allows |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |
| *Unique composite key on (`name`, `guard_name`)* |

#### Pivot Tables (Spatie/laravel-permission)
*   `model_has_permissions` (Links permissions directly to users)
*   `model_has_roles` (Links roles to users)
*   `role_has_permissions` (Links permissions to roles)
    *   *Note: If strict tenant-scoping of role assignments is needed beyond `tenant_user.role_id`, these pivot tables might require a `tenant_id` or be managed via tenant-specific guards.*

### White-Label & Customization

#### `tenant_domains` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `domain` | VARCHAR(255) | NOT NULL, UNIQUE, INDEX | Custom domain name (e.g., crm.agency.com.au) |
| `is_primary` | BOOLEAN | NOT NULL, DEFAULT false | Is this the primary custom domain for the tenant? |
| `is_verified` | BOOLEAN | NOT NULL, DEFAULT false | DNS verification status |
| `verification_token` | VARCHAR(255) | NULLABLE | Token for DNS verification (e.g., TXT record value) |
| `ssl_status` | VARCHAR(50) | NULLABLE, DEFAULT 'pending' | e.g., pending, active, failed, needs_renewal |
| `ssl_certificate_expires_at` | TIMESTAMP | NULLABLE | SSL certificate expiry date |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `white_label_settings` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, UNIQUE, INDEX | Tenant identifier |
| `company_display_name` | VARCHAR(255) | NULLABLE | Overrides `tenants.name` for display purposes |
| `logo_media_id` | BIGINT UNSIGNED | NULLABLE, FK to `media` | Custom logo for the tenant's instance |
| `favicon_media_id` | BIGINT UNSIGNED | NULLABLE, FK to `media` | Custom favicon |
| `primary_color` | VARCHAR(20) | NULLABLE | Primary brand color (hex, rgba) |
| `secondary_color` | VARCHAR(20) | NULLABLE | Secondary brand color |
| `accent_color` | VARCHAR(20) | NULLABLE | Accent color for UI elements |
| `login_page_message` | TEXT | NULLABLE | Custom message on the login page |
| `dashboard_footer_text` | TEXT | NULLABLE | Custom text in the dashboard footer |
| `custom_css` | TEXT | NULLABLE | Tenant-specific CSS overrides |
| `custom_js` | TEXT | NULLABLE | Tenant-specific JavaScript (use with caution) |
| `email_from_name` | VARCHAR(255) | NULLABLE | Default "From" name for system emails |
| `email_from_address` | VARCHAR(255) | NULLABLE | Default "From" email address for system emails |
| `support_contact_details` | JSON | NULLABLE | Custom support contact info (`{"email": "...", "phone": "..."}`) |
| `api_documentation_url` | VARCHAR(255) | NULLABLE | URL for tenant's white-labeled API documentation |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

### CRM Core: Contacts (Leads/Clients), Deals

#### `contacts` Table
Serves as the central repository for all individuals and organizations (leads, clients, vendors, etc.).
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `assigned_to_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users`, INDEX | Assigned agent/user |
| `first_name` | VARCHAR(100) | NULLABLE | Contact's first name |
| `last_name` | VARCHAR(100) | NULLABLE, INDEX | Contact's last name |
| `email` | VARCHAR(255) | NULLABLE, INDEX | Primary email address |
| `phone` | VARCHAR(50) | NULLABLE | Primary phone number |
| `mobile` | VARCHAR(50) | NULLABLE, INDEX | Mobile phone number |
| `company_name` | VARCHAR(255) | NULLABLE, INDEX | Associated company name |
| `job_title` | VARCHAR(100) | NULLABLE | Contact's job title |
| `address_street` | VARCHAR(255) | NULLABLE | Street address |
| `address_suburb` | VARCHAR(100) | NULLABLE, INDEX | Suburb/city |
| `address_state` | VARCHAR(100) | NULLABLE, INDEX | State/province |
| `address_postcode` | VARCHAR(20) | NULLABLE, INDEX | Postal code |
| `address_country` | VARCHAR(100) | NULLABLE, DEFAULT 'Australia' | Country |
| `lead_source_id` | BIGINT UNSIGNED | NULLABLE, FK to `lead_sources`, INDEX | Origin of the lead/contact |
| `lead_source_detail` | VARCHAR(255) | NULLABLE | Specifics about the source (e.g., form name, event name) |
| `contact_type` | VARCHAR(50) | NOT NULL, INDEX | e.g., lead, client, vendor, buyer, tenant_prospect, platform_subscriber_prospect |
| `status` | VARCHAR(50) | NOT NULL, INDEX | Current status (e.g., new, contacted, qualified, active_client) |
| `stage_id` | BIGINT UNSIGNED | NULLABLE, FK to `pipeline_stages`, INDEX | If contact is in a generic pipeline |
| `score` | INTEGER | NULLABLE | Lead score (calculated or manual) |
| `min_budget` | DECIMAL(15,2) | NULLABLE | Minimum budget for property purchase/lease |
| `max_budget` | DECIMAL(15,2) | NULLABLE | Maximum budget |
| `min_bedrooms` | INTEGER | NULLABLE | Minimum bedrooms required |
| `max_bedrooms` | INTEGER | NULLABLE | Maximum bedrooms required |
| `min_bathrooms` | DECIMAL(3,1) | NULLABLE | Minimum bathrooms required (e.g., 1.5) |
| `max_bathrooms` | DECIMAL(3,1) | NULLABLE | Maximum bathrooms required |
| `preferred_suburbs` | JSON | NULLABLE | Array of preferred suburb names |
| `property_preferences` | JSON | NULLABLE | Detailed property preferences (e.g., `{"type": ["house"], "features": ["pool"]}`) |
| `timeline` | VARCHAR(100) | NULLABLE | Purchase/lease timeline (e.g., 0-3 months) |
| `investment_status` | VARCHAR(50) | NULLABLE | e.g., first_home_buyer, investor, downsizer |
| `finance_status` | VARCHAR(50) | NULLABLE | e.g., pre_approved, seeking_finance, cash_buyer |
| `notes` | TEXT | NULLABLE | General notes about the contact |
| `last_contacted_at` | TIMESTAMP | NULLABLE, INDEX | Timestamp of the last interaction |
| `next_followup_at` | TIMESTAMP | NULLABLE, INDEX | Scheduled date for next follow-up |
| `gdpr_consent_given_at` | TIMESTAMP | NULLABLE | Timestamp for GDPR/privacy consent |
| `do_not_contact_reason` | TEXT | NULLABLE | Reason if contact should not be contacted |
| `custom_fields` | JSON | NULLABLE | For ad-hoc, tenant-defined fields |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `lead_sources` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `name` | VARCHAR(255) | NOT NULL | Source name (e.g., Website Form, Facebook Ad, Referral) |
| `type` | VARCHAR(50) | NOT NULL, INDEX | Source type (e.g., online, offline, campaign, organic) |
| `is_active` | BOOLEAN | NOT NULL, DEFAULT true | Whether this source is currently active |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `deals` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `title` | VARCHAR(255) | NOT NULL | Deal title (e.g., "Sale of 123 Main St") |
| `contact_id` | BIGINT UNSIGNED | NOT NULL, FK to `contacts` (buyer/lessee), INDEX | Primary contact for the deal |
| `vendor_contact_id` | BIGINT UNSIGNED | NULLABLE, FK to `contacts` (seller/lessor), INDEX | Seller/vendor contact |
| `property_listing_id` | BIGINT UNSIGNED | NULLABLE, FK to `property_listings`, INDEX | Associated property listing |
| `property_unit_id` | BIGINT UNSIGNED | NULLABLE, FK to `property_units`, INDEX | Specific property unit involved |
| `project_id` | BIGINT UNSIGNED | NULLABLE, FK to `projects`, INDEX | Associated project (for off-the-plan sales) |
| `assigned_to_user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users`, INDEX | Primary agent/user responsible for the deal |
| `pipeline_stage_id` | BIGINT UNSIGNED | NOT NULL, FK to `pipeline_stages`, INDEX | Current stage in the sales/lease pipeline |
| `deal_type` | VARCHAR(50) | NOT NULL, INDEX | e.g., sale, lease, project_sale, referral |
| `value` | DECIMAL(15,2) | NULLABLE | Estimated or actual value of the deal |
| `currency` | VARCHAR(3) | NOT NULL, DEFAULT 'AUD' | Currency code |
| `expected_close_date` | DATE | NULLABLE, INDEX | Target date for closing the deal |
| `actual_close_date` | DATE | NULLABLE | Actual date the deal was closed (won/lost) |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., open, won, lost, abandoned |
| `lost_reason_id` | BIGINT UNSIGNED | NULLABLE, FK to `deal_lost_reasons` | Reason if the deal was lost |
| `lost_reason_notes` | TEXT | NULLABLE | Additional details if deal was lost |
| `probability` | INTEGER | NULLABLE | Win probability (0-100), can be auto-set by stage |
| `description` | TEXT | NULLABLE | Detailed description or notes about the deal |
| `commission_percentage` | DECIMAL(5,2) | NULLABLE | Agreed commission rate |
| `commission_amount` | DECIMAL(15,2) | NULLABLE | Calculated or fixed commission amount |
| `gst_inclusive` | BOOLEAN | NOT NULL, DEFAULT true | Is the commission GST inclusive? |
| `finance_due_date` | DATE | NULLABLE | Finance condition due date |
| `settlement_date` | DATE | NULLABLE | Expected or actual settlement date |
| `custom_fields` | JSON | NULLABLE | Tenant-defined custom fields for deals |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `pipelines` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `name` | VARCHAR(255) | NOT NULL | Pipeline name (e.g., Sales Pipeline, Rental Pipeline) |
| `entity_type` | VARCHAR(255) | NOT NULL | Eloquent model this pipeline applies to (e.g., `App\Models\Deal`) |
| `is_default` | BOOLEAN | NOT NULL, DEFAULT false | Is this the default pipeline for the entity type? |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `pipeline_stages` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `pipeline_id` | BIGINT UNSIGNED | NOT NULL, FK to `pipelines`, INDEX | Parent pipeline |
| `name` | VARCHAR(255) | NOT NULL | Stage name (e.g., Qualification, Negotiation, Closed Won) |
| `order` | INTEGER | NOT NULL, INDEX | Display order of the stage within the pipeline |
| `probability` | INTEGER | NULLABLE | Default win probability for deals in this stage (0-100) |
| `is_default_lost_stage` | BOOLEAN | NOT NULL, DEFAULT false | Is this a terminal "lost" stage? |
| `is_default_won_stage` | BOOLEAN | NOT NULL, DEFAULT false | Is this a terminal "won" stage? |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `deal_lost_reasons` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `name` | VARCHAR(255) | NOT NULL | Reason name (e.g., Lost to Competitor, Price Too High) |
| `is_active` | BOOLEAN | NOT NULL, DEFAULT true | Whether this reason is currently in use |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `appraisals` Table
Stores property appraisals and valuation data.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK | Tenant foreign key |
| `uuid` | UUID | NOT NULL, UNIQUE | External identifier |
| `property_unit_id` | BIGINT UNSIGNED | NULLABLE, FK to `property_units` | Property unit reference (if existing property) |
| `property_address_text` | TEXT | NULLABLE | Address text if not linked to existing unit |
| `contact_id` | BIGINT UNSIGNED | NOT NULL, FK to `contacts` | Property owner/prospect |
| `agent_user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users` | Appraising agent |
| `appraisal_date` | DATE | NOT NULL | Date of appraisal |
| `appraisal_type` | VARCHAR(50) | NOT NULL | e.g., sales_appraisal, rental_appraisal |
| `min_estimated_value` | DECIMAL(15,2) | NULLABLE | Lower end of estimated value range |
| `max_estimated_value` | DECIMAL(15,2) | NULLABLE | Upper end of estimated value range |
| `suggested_list_price` | DECIMAL(15,2) | NULLABLE | Agent's suggested list price/rent |
| `confidence_level` | VARCHAR(50) | NULLABLE | Agent's confidence in appraisal (e.g., high, medium) |
| `comparable_properties_json` | JSON | NULLABLE | Details of comparable properties used |
| `market_conditions_summary` | TEXT | NULLABLE | Summary of current market conditions |
| `suggested_improvements` | TEXT | NULLABLE | Improvements suggested to owner |
| `proposed_commission_rate` | DECIMAL(5,2) | NULLABLE | Proposed commission percentage |
| `proposed_marketing_budget` | DECIMAL(12,2) | NULLABLE | Suggested marketing budget |
| `notes` | TEXT | NULLABLE | General appraisal notes |
| `status` | VARCHAR(50) | NOT NULL | e.g., pending, presented, accepted, rejected |
| `outcome` | VARCHAR(50) | NULLABLE | e.g., listed, not_listed, future_prospect |
| `resulting_listing_id` | BIGINT UNSIGNED | NULLABLE, FK to `property_listings` | If appraisal resulted in a listing |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `deal_conditions` Table
Tracks contract conditions and contingencies for deals.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK | Tenant foreign key |
| `deal_id` | BIGINT UNSIGNED | NOT NULL, FK to `deals` | Associated deal |
| `condition_type` | VARCHAR(100) | NOT NULL | e.g., subject_to_finance, subject_to_building_pest, subject_to_sale |
| `description` | TEXT | NOT NULL | Detailed description of the condition |
| `due_date` | DATE | NOT NULL | Date by which condition must be met |
| `status` | VARCHAR(50) | NOT NULL | e.g., pending, satisfied, failed, waived |
| `satisfied_at` | TIMESTAMP | NULLABLE | Timestamp when condition was met/waived |
| `notes` | TEXT | NULLABLE | Notes related to the condition |
| `responsible_party` | VARCHAR(100) | NULLABLE | e.g., buyer, seller, bank |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

### Property & Listing Management

#### `developers` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `name` | VARCHAR(255) | NOT NULL | Developer company name |
| `abn` | VARCHAR(20) | NULLABLE | Australian Business Number |
| `description` | TEXT | NULLABLE | Developer company description |
| `address_street` | VARCHAR(255) | NULLABLE | Office street address |
| `address_suburb` | VARCHAR(100) | NULLABLE | Office suburb |
| `address_state` | VARCHAR(100) | NULLABLE | Office state |
| `address_postcode` | VARCHAR(20) | NULLABLE | Office postcode |
| `phone` | VARCHAR(50) | NULLABLE | Office phone number |
| `email` | VARCHAR(255) | NULLABLE | Office email address |
| `website` | VARCHAR(255) | NULLABLE | Company website URL |
| `logo_media_id` | BIGINT UNSIGNED | NULLABLE, FK to `media` | Company logo |
| `is_active` | BOOLEAN | NOT NULL, DEFAULT true | Active status |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `projects` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `developer_id` | BIGINT UNSIGNED | NULLABLE, FK to `developers`, INDEX | Associated developer |
| `name` | VARCHAR(255) | NOT NULL | Project name |
| `slug` | VARCHAR(255) | NOT NULL, UNIQUE, INDEX | URL-friendly identifier |
| `description` | TEXT | NULLABLE | Project description |
| `address_street` | VARCHAR(255) | NULLABLE | Project street address |
| `address_suburb` | VARCHAR(100) | NULLABLE, INDEX | Project suburb |
| `address_state` | VARCHAR(100) | NULLABLE, INDEX | Project state |
| `address_postcode` | VARCHAR(20) | NULLABLE, INDEX | Project postcode |
| `address_country` | VARCHAR(100) | NULLABLE, DEFAULT 'Australia' | Project country |
| `latitude` | DECIMAL(10,8) | NULLABLE | GPS latitude |
| `longitude` | DECIMAL(11,8) | NULLABLE | GPS longitude |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., planning, under_construction, completed, selling |
| `project_type` | VARCHAR(100) | NULLABLE | e.g., residential_apartments, land_subdivision, mixed_use |
| `total_units` | INTEGER | NULLABLE | Total number of units/lots in the project |
| `available_units` | INTEGER | NULLABLE | Number of currently available units/lots |
| `min_price` | DECIMAL(15,2) | NULLABLE | Starting price for units in the project |
| `max_price` | DECIMAL(15,2) | NULLABLE | Maximum price for units in the project |
| `min_bedrooms` | INTEGER | NULLABLE | Minimum bedrooms across units |
| `max_bedrooms` | INTEGER | NULLABLE | Maximum bedrooms across units |
| `start_date` | DATE | NULLABLE | Project commencement date |
| `estimated_completion_date` | DATE | NULLABLE, INDEX | Estimated project completion date |
| `actual_completion_date` | DATE | NULLABLE | Actual project completion date |
| `is_featured` | BOOLEAN | NOT NULL, DEFAULT false | Featured project flag |
| `ai_generated_suburb_info` | TEXT | NULLABLE | AI-generated information about the suburb/location |
| `ai_generated_price_rent_data` | JSON | NULLABLE | AI-analyzed price/rental data for the area |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `project_stages` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `project_id` | BIGINT UNSIGNED | NOT NULL, FK to `projects`, INDEX | Parent project |
| `name` | VARCHAR(255) | NOT NULL | Stage name (e.g., Stage 1, The Parkview Release) |
| `description` | TEXT | NULLABLE | Stage description |
| `release_date` | DATE | NULLABLE | Date this stage is/was released for sale |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., upcoming, selling, sold_out, completed |
| `order` | INTEGER | NOT NULL, DEFAULT 0 | Display order of stages within a project |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `property_units` Table
Represents individual saleable/leasable units (houses, apartments, land lots).
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `project_id` | BIGINT UNSIGNED | NOT NULL, FK to `projects`, INDEX | Parent project |
| `project_stage_id` | BIGINT UNSIGNED | NULLABLE, FK to `project_stages`, INDEX | Specific stage within the project |
| `developer_id` | BIGINT UNSIGNED | NULLABLE, FK to `developers`, INDEX | Builder/developer if different from project's main developer |
| `unit_identifier` | VARCHAR(50) | NULLABLE | e.g., Lot 101, Apartment G05, House 23 |
| `address_street_number` | VARCHAR(50) | NULLABLE | Street number if distinct |
| `address_street_name` | VARCHAR(255) | NULLABLE | Street name if distinct |
| `address_suburb` | VARCHAR(100) | NULLABLE, INDEX | Suburb (may inherit from project) |
| `address_state` | VARCHAR(100) | NULLABLE, INDEX | State (may inherit from project) |
| `address_postcode` | VARCHAR(20) | NULLABLE, INDEX | Postcode (may inherit from project) |
| `address_full` | TEXT | NULLABLE | Concatenated full address for display/search |
| `latitude` | DECIMAL(10,8) | NULLABLE | GPS latitude |
| `longitude` | DECIMAL(11,8) | NULLABLE | GPS longitude |
| `property_type` | VARCHAR(50) | NOT NULL, INDEX | e.g., house, apartment, townhouse, land, unit |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., available, under_contract, sold, leased, off_market |
| `list_price` | DECIMAL(15,2) | NULLABLE | Current asking price |
| `price_display_text` | VARCHAR(100) | NULLABLE | Text to display instead of price (e.g., "Contact Agent") |
| `land_size_sqm` | DECIMAL(10,2) | NULLABLE | Land area in square meters |
| `building_size_sqm` | DECIMAL(10,2) | NULLABLE | Building area in square meters (approx.) |
| `internal_area_sqm` | DECIMAL(8,2) | NULLABLE | Internal living area |
| `external_area_sqm` | DECIMAL(8,2) | NULLABLE | Balcony, yard, etc. area |
| `total_area_sqm` | DECIMAL(8,2) | NULLABLE | Total area |
| `bedrooms` | INTEGER | NULLABLE, INDEX | Number of bedrooms |
| `bathrooms` | DECIMAL(3,1) | NULLABLE, INDEX | Number of bathrooms (e.g., 2.5 for 2 bath + powder room) |
| `ensuites` | INTEGER | NULLABLE | Number of ensuite bathrooms |
| `toilets` | INTEGER | NULLABLE | Total number of toilets |
| `car_spaces` | INTEGER | NULLABLE, INDEX | Total car parking spaces |
| `garage_spaces` | INTEGER | NULLABLE | Number of garage spaces |
| `stories` | INTEGER | NULLABLE | Number of levels/stories |
| `year_built` | INTEGER | NULLABLE | Year the property was built |
| `description_public` | TEXT | NULLABLE | Marketing description for public listings |
| `description_internal` | TEXT | NULLABLE | Internal notes/description for agents |
| `features_json` | JSON | NULLABLE | Structured list of features (e.g., `{"living_areas": 2, "has_pool": true}`) |
| `energy_rating` | DECIMAL(3,1) | NULLABLE | Energy efficiency rating (e.g., 6.5 stars) |
| `nbn_connection_type` | VARCHAR(50) | NULLABLE | NBN status/type (e.g., FTTP, FTTN, Available) |
| `walkability_score` | INTEGER | NULLABLE | Walk Score (0-100) |
| `school_zones_json` | JSON | NULLABLE | List of school catchment zones |
| `sustainability_features_json` | JSON | NULLABLE | e.g., solar_panels, rainwater_tank |
| `ai_detected_features_json` | JSON | NULLABLE | Features detected by AI from images/brochures |
| `ai_generated_description` | TEXT | NULLABLE | Property description generated by AI |
| `is_member_uploaded` | BOOLEAN | NOT NULL, DEFAULT false | True if uploaded by a subscriber agent vs. admin/system |
| `uploaded_by_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users` | User who uploaded this unit (if `is_member_uploaded`) |
| `extra_attributes` | JSON | NULLABLE | Additional custom data |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `property_listings` Table
Represents an "advertisement" or "offering" of a `property_unit` for sale or lease.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `property_unit_id` | BIGINT UNSIGNED | NOT NULL, FK to `property_units`, INDEX | The specific property unit being listed |
| `primary_agent_user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users`, INDEX | Main agent responsible for this listing |
| `secondary_agent_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users`, INDEX | Second agent on the listing |
| `listing_type` | VARCHAR(50) | NOT NULL, INDEX | e.g., sale, lease, auction, project_release |
| `authority_type` | VARCHAR(50) | NULLABLE | e.g., exclusive, open, general, sole_agency |
| `authority_start_date` | DATE | NULLABLE | Start date of the agency agreement |
| `authority_end_date` | DATE | NULLABLE | End date of the agency agreement |
| `price_from` | DECIMAL(15,2) | NULLABLE | Lower end of price range or single price |
| `price_to` | DECIMAL(15,2) | NULLABLE | Upper end of price range (if applicable) |
| `price_display_text_override` | VARCHAR(100) | NULLABLE | Overrides `property_units.price_display_text` for this listing |
| `display_address_publicly` | BOOLEAN | NOT NULL, DEFAULT true | Show full address on public portals/website |
| `headline` | VARCHAR(255) | NULLABLE | Catchy headline for marketing materials |
| `description_override` | TEXT | NULLABLE | Overrides `property_units.description_public` for this listing |
| `inspection_times_json` | JSON | NULLABLE | Array of inspection times (`[{"datetime_start": "", "datetime_end": ""}]`) |
| `video_url` | VARCHAR(255) | NULLABLE | URL for property video (YouTube, Vimeo, etc.) |
| `virtual_tour_url` | VARCHAR(255) | NULLABLE | URL for 3D virtual tour |
| `online_platforms_json` | JSON | NULLABLE | Portals/websites where this listing is published (e.g., `{"REA": true, "Domain": false}`) |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., draft, active, under_offer, withdrawn, sold, leased |
| `is_featured_on_tenant_site` | BOOLEAN | NOT NULL, DEFAULT false | Is this listing featured on the tenant's own website? |
| `is_premium_on_portals` | BOOLEAN | NOT NULL, DEFAULT false | Does this listing have a premium upgrade on external portals? |
| `date_listed` | DATE | NULLABLE | Date the listing went live |
| `date_sold_leased` | DATE | NULLABLE | Date the property was sold or leased |
| `sold_leased_price` | DECIMAL(15,2) | NULLABLE | Final sale or lease price |
| `external_references_json` | JSON | NULLABLE | IDs from external portals (e.g., `{"REA_ID": "123", "Domain_ID": "456"}`) |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

### Australian Market Specifics (Auctions, Trust Accounting, Compliance)

#### `auctions` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `property_listing_id` | BIGINT UNSIGNED | NOT NULL, FK to `property_listings`, INDEX | The listing being auctioned |
| `auctioneer_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users`, INDEX | User acting as auctioneer |
| `auction_date` | DATETIME | NOT NULL, INDEX | Date and time of the auction |
| `auction_location_text` | VARCHAR(255) | NULLABLE | e.g., "On-site", "Online", "In-Room at Agency Office" |
| `reserve_price` | DECIMAL(15,2) | NULLABLE | The reserve price (confidential) |
| `bidder_registration_required` | BOOLEAN | NOT NULL, DEFAULT true | Must bidders register beforehand? |
| `result` | VARCHAR(50) | NULLABLE, INDEX | e.g., sold_at_auction, passed_in, withdrawn_prior, sold_prior, sold_after |
| `sold_price` | DECIMAL(15,2) | NULLABLE | Final price if sold at/after auction |
| `bidder_count` | INTEGER | NULLABLE | Number of registered/active bidders |
| `highest_bid` | DECIMAL(15,2) | NULLABLE | Highest bid reached if passed in |
| `notes` | TEXT | NULLABLE | General notes about the auction |
| `auction_method` | VARCHAR(50) | NULLABLE | e.g., in_room, on_site, online_timed, online_live_stream |
| `online_auction_url` | VARCHAR(255) | NULLABLE | Link to online bidding platform |
| `stream_url` | VARCHAR(255) | NULLABLE | Link to live stream (if any) |
| `auction_authority_signed_media_id` | BIGINT UNSIGNED | NULLABLE, FK to `media` | Scanned copy of the signed auction authority |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `trust_accounts` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `account_name` | VARCHAR(255) | NOT NULL | Name of the trust account (e.g., "Sales Trust Account") |
| `account_number` | VARCHAR(100) | NOT NULL | Bank account number |
| `financial_institution` | VARCHAR(255) | NOT NULL | Name of the bank/financial institution |
| `bsb` | VARCHAR(10) | NOT NULL | BSB (Bank-State-Branch) number |
| `account_type` | VARCHAR(50) | NOT NULL, INDEX | e.g., sales_trust, rental_trust, general_trust |
| `state_jurisdiction` | VARCHAR(50) | NOT NULL, INDEX | Australian state/territory governing this account |
| `is_active` | BOOLEAN | NOT NULL, DEFAULT true | Is this trust account currently active? |
| `opening_balance` | DECIMAL(15,2) | NOT NULL, DEFAULT 0.00 | Initial balance when set up in system |
| `current_balance` | DECIMAL(15,2) | NOT NULL, DEFAULT 0.00 | System-calculated current balance (denormalized for quick view) |
| `notes` | TEXT | NULLABLE | Notes about the trust account |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `trust_account_transactions` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `trust_account_id` | BIGINT UNSIGNED | NOT NULL, FK to `trust_accounts`, INDEX | The trust account this transaction belongs to |
| `transaction_date` | DATE | NOT NULL, INDEX | Date of the transaction |
| `reference_number` | VARCHAR(100) | NULLABLE, INDEX | Internal or bank reference for the transaction |
| `description` | VARCHAR(255) | NOT NULL | Description of the transaction |
| `transaction_type` | VARCHAR(50) | NOT NULL, INDEX | e.g., deposit, withdrawal, transfer, journal_adjustment, interest |
| `amount` | DECIMAL(15,2) | NOT NULL | Transaction amount (positive for deposit, negative for withdrawal) |
| `running_balance` | DECIMAL(15,2) | NOT NULL | Balance of the trust account after this transaction |
| `related_entity_type` | VARCHAR(255) | NULLABLE | Polymorphic: e.g., `App\Models\Deal`, `App\Models\Tenancy` |
| `related_entity_id` | BIGINT UNSIGNED | NULLABLE, INDEX | Polymorphic ID |
| `contact_id` | BIGINT UNSIGNED | NULLABLE, FK to `contacts`, INDEX | Payer or payee contact |
| `receipt_number` | VARCHAR(100) | NULLABLE | Official receipt number issued |
| `payment_method` | VARCHAR(50) | NULLABLE | Method of payment (e.g., EFT, Cheque, Cash) |
| `processed_by_user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users`, INDEX | User who recorded/processed this transaction |
| `is_reconciled` | BOOLEAN | NOT NULL, DEFAULT false | Has this transaction been reconciled against a bank statement? |
| `reconciliation_id` | BIGINT UNSIGNED | NULLABLE, FK to a future `trust_reconciliations` table | Link to reconciliation batch |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `agent_licenses` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users`, INDEX | The user (agent) holding the license |
| `license_number` | VARCHAR(100) | NOT NULL | Official license number |
| `license_type` | VARCHAR(100) | NOT NULL | e.g., Full License, Certificate of Registration, Corporate License |
| `state_jurisdiction` | VARCHAR(50) | NOT NULL, INDEX | Australian state/territory of issue (e.g., NSW, VIC) |
| `issuing_body` | VARCHAR(255) | NOT NULL | Regulatory body (e.g., NSW Fair Trading, Consumer Affairs Victoria) |
| `issue_date` | DATE | NOT NULL | Date the license was issued |
| `expiry_date` | DATE | NOT NULL, INDEX | Date the license expires |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., active, suspended, expired, pending_renewal |
| `conditions` | TEXT | NULLABLE | Any conditions or restrictions on the license |
| `license_document_media_id` | BIGINT UNSIGNED | NULLABLE, FK to `media` | Scanned copy of the license document |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `compliance_checklist_templates` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `name` | VARCHAR(255) | NOT NULL | Name of the checklist template (e.g., "Residential Sale Checklist NSW") |
| `checklist_type` | VARCHAR(100) | NOT NULL, INDEX | e.g., sales_residential, lease_residential, auction_residential, property_management_ingoin |
| `state_jurisdiction` | VARCHAR(50) | NOT NULL, INDEX | Applicable state/territory (or "ALL") |
| `items_json` | JSON | NOT NULL | Array of checklist items: `[{"id": "uuid", "text": "...", "is_required": true, "document_link_placeholder": "...", "help_text": "..."}]` |
| `is_active` | BOOLEAN | NOT NULL, DEFAULT true | Is this template currently in use? |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `compliance_checklists` Table
Instance of a compliance checklist applied to a specific entity.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `template_id` | BIGINT UNSIGNED | NOT NULL, FK to `compliance_checklist_templates`, INDEX | The template used for this checklist |
| `checklistable_type` | VARCHAR(255) | NOT NULL | Polymorphic: e.g., `App\Models\Deal`, `App\Models\PropertyListing`, `App\Models\Tenancy` |
| `checklistable_id` | BIGINT UNSIGNED | NOT NULL | Polymorphic ID |
| `completed_items_json` | JSON | NULLABLE | Stores status of each item: `{"item_uuid": {"completed_at": "ts", "user_id": 1, "notes": "...", "media_id": 5 (for uploaded doc)}}` |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., pending, in_progress, complete, issues_found, overdue |
| `completed_by_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users` | User who marked the checklist as complete |
| `completed_at` | TIMESTAMP | NULLABLE | Timestamp of overall completion |
| `notes` | TEXT | NULLABLE | General notes about this specific checklist instance |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

### Rental & Strata Management

#### `rental_properties` Table
Properties under rental management.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `property_unit_id` | BIGINT UNSIGNED | NOT NULL, FK to `property_units`, UNIQUE, INDEX | The property unit being managed for rental |
| `owner_contact_id` | BIGINT UNSIGNED | NOT NULL, FK to `contacts`, INDEX | The landlord/owner of the property |
| `property_manager_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users`, INDEX | Assigned property manager from the agency |
| `management_agreement_start_date` | DATE | NOT NULL | Start date of the management agreement |
| `management_agreement_end_date` | DATE | NULLABLE | End date of the management agreement |
| `management_fee_percentage` | DECIMAL(5,2) | NOT NULL | Management fee as a percentage of rent |
| `letting_fee_amount` | DECIMAL(12,2) | NULLABLE | Fee for finding a new tenant |
| `letting_fee_type` | VARCHAR(50) | NULLABLE | e.g., fixed_amount, weeks_rent |
| `target_rental_amount` | DECIMAL(12,2) | NOT NULL | Desired or current weekly/monthly rent |
| `payment_frequency` | VARCHAR(50) | NOT NULL, DEFAULT 'weekly' | How often rent is collected (weekly, fortnightly, monthly) |
| `bond_required_amount` | DECIMAL(12,2) | NOT NULL | Amount of bond/security deposit required |
| `available_from_date` | DATE | NULLABLE, INDEX | Date the property is available for lease |
| `preferred_lease_term_months` | INTEGER | NULLABLE | Preferred length of lease in months |
| `pet_policy` | VARCHAR(50) | NULLABLE | e.g., allowed, not_allowed, on_application |
| `furnishing_status` | VARCHAR(50) | NULLABLE | e.g., furnished, unfurnished, partly_furnished |
| `special_conditions_for_lease` | TEXT | NULLABLE | Standard special conditions to include in leases |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., active_management, pending_lease, inactive, notice_to_vacate |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `tenancies` Table (Lease Agreements)
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `rental_property_id` | BIGINT UNSIGNED | NOT NULL, FK to `rental_properties`, INDEX | The rental property being leased |
| `lease_type` | VARCHAR(50) | NOT NULL, INDEX | e.g., fixed_term, periodic, short_term_stay |
| `start_date` | DATE | NOT NULL, INDEX | Official start date of the lease |
| `end_date` | DATE | NULLABLE, INDEX | Official end date of the lease (for fixed term) |
| `rent_amount` | DECIMAL(12,2) | NOT NULL | Agreed rent amount per frequency period |
| `rent_payment_frequency` | VARCHAR(50) | NOT NULL | e.g., weekly, fortnightly, monthly |
| `rent_payment_day` | VARCHAR(50) | NULLABLE | e.g., Monday, 1st_of_month (day rent is due) |
| `bond_amount_lodged` | DECIMAL(12,2) | NOT NULL | Amount of bond lodged with authority |
| `bond_lodged_date` | DATE | NULLABLE | Date bond was lodged |
| `bond_authority_reference` | VARCHAR(100) | NULLABLE | Reference number from bond authority |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., current, notice_given, ended, pending_start, arrears |
| `notice_given_date` | DATE | NULLABLE | Date termination notice was given (by tenant or landlord) |
| `notice_period_days` | INTEGER | NULLABLE | Length of the notice period |
| `expected_vacate_date` | DATE | NULLABLE | Date tenant is expected to vacate |
| `actual_vacate_date` | DATE | NULLABLE | Date tenant actually vacated |
| `lease_document_media_id` | BIGINT UNSIGNED | NULLABLE, FK to `media` | Scanned copy of the signed lease agreement |
| `special_conditions` | TEXT | NULLABLE | Any special conditions specific to this lease |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `tenancy_contacts` Table (Pivot for multiple tenant individuals on a single lease)
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant (agency) identifier |
| `tenancy_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenancies`, INDEX | The specific lease agreement |
| `contact_id` | BIGINT UNSIGNED | NOT NULL, FK to `contacts`, INDEX | The individual tenant (person) |
| `is_primary_leaseholder` | BOOLEAN | NOT NULL, DEFAULT false | Is this the main contact for the lease? |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| *Unique composite key on (`tenancy_id`, `contact_id`)* |

#### `inspections` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `inspectable_type` | VARCHAR(255) | NOT NULL | Polymorphic: e.g. `App\Models\RentalProperty`, `App\Models\PropertyListing` |
| `inspectable_id` | BIGINT UNSIGNED | NOT NULL | Polymorphic ID |
| `tenancy_id` | BIGINT UNSIGNED | NULLABLE, FK to `tenancies`, INDEX | Link to tenancy if it's a tenanted property inspection |
| `inspector_user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users`, INDEX | User who conducted/is scheduled for inspection |
| `inspection_type` | VARCHAR(50) | NOT NULL, INDEX | e.g., routine, ingoing, outgoing, open_for_inspection, pre_purchase |
| `scheduled_datetime` | DATETIME | NOT NULL, INDEX | Scheduled date and time |
| `completed_datetime` | DATETIME | NULLABLE | Actual completion date and time |
| `report_document_media_id` | BIGINT UNSIGNED | NULLABLE, FK to `media` | Link to the inspection report PDF/document |
| `overall_condition` | VARCHAR(50) | NULLABLE | e.g., good, fair, poor (for condition reports) |
| `tenant_present` | BOOLEAN | NULLABLE | Was the tenant present during the inspection? |
| `notes` | TEXT | NULLABLE | General notes from the inspection |
| `action_required` | BOOLEAN | NOT NULL, DEFAULT false | Does this inspection require follow-up actions? |
| `followup_date` | DATE | NULLABLE | Date for follow-up if action required |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., scheduled, completed, cancelled, rescheduled, report_pending |
| `attendee_count` | INTEGER | NULLABLE | For open homes: number of attendees |
| `feedback_summary` | TEXT | NULLABLE | For open homes: summary of feedback received |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `inspection_attendees` Table
For tracking attendees at open for inspections or scheduled viewings.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `inspection_id` | BIGINT UNSIGNED | NOT NULL, FK to `inspections`, INDEX | The inspection event |
| `contact_id` | BIGINT UNSIGNED | NULLABLE, FK to `contacts`, INDEX | Link to existing contact if attendee is known |
| `name` | VARCHAR(255) | NULLABLE | Attendee's name (if not an existing contact or for quick entry) |
| `email` | VARCHAR(255) | NULLABLE | Attendee's email |
| `phone` | VARCHAR(50) | NULLABLE | Attendee's phone |
| `check_in_time` | DATETIME | NOT NULL | Time attendee checked in |
| `feedback_rating` | INTEGER | NULLABLE | Rating given by attendee (e.g., 1-5 stars) |
| `feedback_notes` | TEXT | NULLABLE | Specific feedback from attendee |
| `is_potential_buyer_or_tenant` | BOOLEAN | NULLABLE | Flag if attendee is a strong prospect |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `maintenance_requests` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `rental_property_id` | BIGINT UNSIGNED | NOT NULL, FK to `rental_properties`, INDEX | The property requiring maintenance |
| `tenancy_id` | BIGINT UNSIGNED | NULLABLE, FK to `tenancies`, INDEX | Current tenancy associated with the request |
| `reported_by_contact_id` | BIGINT UNSIGNED | NOT NULL, FK to `contacts`, INDEX | Person who reported the issue (e.g., tenant) |
| `reported_by_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users`, INDEX | Agent/user who logged the request if not tenant |
| `request_type` | VARCHAR(100) | NOT NULL, INDEX | e.g., plumbing, electrical, general_repair, appliance_repair |
| `title` | VARCHAR(255) | NOT NULL | Brief title of the maintenance request |
| `description` | TEXT | NOT NULL | Detailed description of the issue |
| `urgency` | VARCHAR(50) | NOT NULL, INDEX | e.g., low, medium, high, emergency |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., reported, awaiting_owner_approval, assigned_to_contractor, work_in_progress, awaiting_invoice, completed, closed, cancelled |
| `assigned_to_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users`, INDEX | Internal agent/PM managing the request |
| `assigned_to_contractor_id` | BIGINT UNSIGNED | NULLABLE, FK to `contractors`, INDEX | External contractor assigned to the job |
| `owner_notified_at` | DATETIME | NULLABLE | When the property owner was notified |
| `owner_approval_status` | VARCHAR(50) | NULLABLE | e.g., pending, approved, rejected, quote_requested |
| `owner_approved_at` | DATETIME | NULLABLE | When the owner approved the work/quote |
| `quote_amount` | DECIMAL(12,2) | NULLABLE | Quoted cost from contractor |
| `approved_quote_media_id` | BIGINT UNSIGNED | NULLABLE, FK to `media` | Document for approved quote |
| `estimated_cost` | DECIMAL(12,2) | NULLABLE | Initial estimated cost |
| `actual_cost` | DECIMAL(12,2) | NULLABLE | Final actual cost of the repair |
| `invoice_media_id` | BIGINT UNSIGNED | NULLABLE, FK to `media` | Contractor's invoice document |
| `invoice_paid_at` | DATETIME | NULLABLE | Date contractor invoice was paid |
| `access_instructions` | TEXT | NULLABLE | Instructions for accessing the property |
| `completed_at` | DATETIME | NULLABLE | Date the maintenance work was completed |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `contractors` Table (Tradespeople for maintenance)
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `company_name` | VARCHAR(255) | NOT NULL | Contractor's business name |
| `contact_name` | VARCHAR(255) | NULLABLE | Primary contact person at the contractor |
| `email` | VARCHAR(255) | NULLABLE | Contractor's email address |
| `phone` | VARCHAR(50) | NULLABLE | Contractor's phone number |
| `mobile` | VARCHAR(50) | NULLABLE | Contractor's mobile number |
| `trade_type` | VARCHAR(100) | NOT NULL, INDEX | e.g., plumber, electrician, handyman, gardener |
| `abn` | VARCHAR(20) | NULLABLE | Australian Business Number |
| `license_number` | VARCHAR(100) | NULLABLE | Trade license number |
| `insurance_policy_number` | VARCHAR(100) | NULLABLE | Public liability insurance policy number |
| `insurance_expiry_date` | DATE | NULLABLE | Insurance expiry date |
| `is_preferred` | BOOLEAN | NOT NULL, DEFAULT false | Is this a preferred contractor for the agency? |
| `notes` | TEXT | NULLABLE | Internal notes about the contractor |
| `rating` | INTEGER | NULLABLE | Internal rating (1-5) based on past work |
| `address_street` | VARCHAR(255) | NULLABLE | Contractor's address |
| `address_suburb` | VARCHAR(100) | NULLABLE |
| `address_state` | VARCHAR(100) | NULLABLE |
| `address_postcode` | VARCHAR(20) | NULLABLE |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `rental_payments` Table
Tracks rent payments received from tenants.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant (agency) identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `tenancy_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenancies`, INDEX | The lease this payment is for |
| `payment_date` | DATE | NOT NULL, INDEX | Date the payment was received/processed |
| `rent_period_start_date` | DATE | NOT NULL, INDEX | Start date of the rental period this payment covers |
| `rent_period_end_date` | DATE | NOT NULL, INDEX | End date of the rental period this payment covers |
| `amount_paid` | DECIMAL(12,2) | NOT NULL | Amount of rent paid |
| `payment_method` | VARCHAR(50) | NOT NULL | e.g., bank_transfer, direct_debit, bpay, cash |
| `reference` | VARCHAR(255) | NULLABLE | Payment reference number (e.g., bank transaction ID) |
| `received_by_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users` | User who processed/recorded the payment |
| `notes` | TEXT | NULLABLE | Notes about the payment |
| `is_reconciled_with_trust` | BOOLEAN | NOT NULL, DEFAULT false | Has this payment been reconciled with trust account records? |
| `trust_transaction_id` | BIGINT UNSIGNED | NULLABLE, FK to `trust_account_transactions` | Link to the corresponding trust transaction |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `strata_schemes` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `name` | VARCHAR(255) | NOT NULL | Name of the strata scheme (e.g., "The Grand Apartments SP12345") |
| `strata_plan_number` | VARCHAR(100) | NOT NULL, INDEX | Official strata plan number |
| `number_of_lots` | INTEGER | NOT NULL | Total number of lots in the scheme |
| `address_street` | VARCHAR(255) | NOT NULL | Street address of the strata property |
| `address_suburb` | VARCHAR(100) | NOT NULL | Suburb |
| `address_state` | VARCHAR(100) | NOT NULL | State |
| `address_postcode` | VARCHAR(20) | NOT NULL | Postcode |
| `strata_manager_name` | VARCHAR(255) | NULLABLE | Name of the strata manager or company |
| `strata_management_company` | VARCHAR(255) | NULLABLE | Name of the strata management company (if different) |
| `manager_contact_email` | VARCHAR(255) | NULLABLE | Email for strata manager |
| `manager_contact_phone` | VARCHAR(50) | NULLABLE | Phone for strata manager |
| `state_jurisdiction` | VARCHAR(50) | NOT NULL, INDEX | Australian state/territory governing this strata scheme |
| `year_registered` | INTEGER | NULLABLE | Year the strata plan was registered |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `strata_property_links` Table
Links `property_units` to `strata_schemes`.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `strata_scheme_id` | BIGINT UNSIGNED | NOT NULL, FK to `strata_schemes`, INDEX | The strata scheme |
| `property_unit_id` | BIGINT UNSIGNED | NOT NULL, FK to `property_units`, INDEX | The specific property unit within the strata |
| `lot_number_in_strata_plan` | VARCHAR(50) | NOT NULL | Lot number as per the strata plan |
| `unit_entitlement` | DECIMAL(10,2) | NULLABLE | Unit of entitlement for voting/levies |
| `levies_json` | JSON | NULLABLE | Current levy details (e.g., `{"admin_fund": 500, "capital_works_fund": 200, "frequency": "quarterly"}`) |
| `special_conditions` | TEXT | NULLABLE | Any special conditions related to this lot in the strata |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| *Unique composite key on (`strata_scheme_id`, `property_unit_id`)* |

#### `strata_meetings` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `strata_scheme_id` | BIGINT UNSIGNED | NOT NULL, FK to `strata_schemes`, INDEX | The strata scheme this meeting belongs to |
| `meeting_type` | VARCHAR(100) | NOT NULL, INDEX | e.g., Annual General Meeting, Extraordinary General Meeting, Committee Meeting |
| `meeting_datetime` | DATETIME | NOT NULL, INDEX | Date and time of the meeting |
| `location` | VARCHAR(255) | NULLABLE | Physical or virtual location of the meeting |
| `agenda_document_media_id` | BIGINT UNSIGNED | NULLABLE, FK to `media` | Link to the meeting agenda document |
| `minutes_document_media_id` | BIGINT UNSIGNED | NULLABLE, FK to `media` | Link to the meeting minutes document |
| `attendees_json` | JSON | NULLABLE | List of attendees (can be simple names or links to contacts) |
| `notes` | TEXT | NULLABLE | General notes about the meeting |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

### Marketing & AI Content

#### `marketing_campaigns` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `name` | VARCHAR(255) | NOT NULL | Campaign name |
| `description` | TEXT | NULLABLE | Detailed description of the campaign |
| `campaign_type` | VARCHAR(50) | NOT NULL, INDEX | e.g., email, social_media, property_launch, lead_nurturing, brand_awareness |
| `target_audience_description` | TEXT | NULLABLE | Description of the target audience |
| `target_contact_segment_id` | BIGINT UNSIGNED | NULLABLE, FK to a future `contact_segments` table | Link to a predefined contact segment |
| `budget` | DECIMAL(12,2) | NULLABLE | Total budget allocated for the campaign |
| `start_date` | DATE | NOT NULL, INDEX | Campaign start date |
| `end_date` | DATE | NULLABLE, INDEX | Campaign end date (if applicable) |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., draft, active, completed, archived, paused |
| `created_by_user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users` | User who created the campaign |
| `managed_by_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users` | User responsible for managing the campaign |
| `kpis_json` | JSON | NULLABLE | Key Performance Indicators (e.g., `{"target_leads": 100, "target_conversion_rate": 0.05}`) |
| `results_summary_json` | JSON | NULLABLE | Summary of campaign results (e.g., `{"actual_leads": 120, "conversion_rate": 0.06}`) |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `campaign_activities` Table
Specific activities within a broader marketing campaign.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `marketing_campaign_id` | BIGINT UNSIGNED | NOT NULL, FK to `marketing_campaigns`, INDEX | Parent campaign |
| `activity_type` | VARCHAR(50) | NOT NULL, INDEX | e.g., email_blast, facebook_post, google_ad, webinar, event |
| `name` | VARCHAR(255) | NOT NULL | Name of this specific activity |
| `description` | TEXT | NULLABLE | Description of the activity |
| `channel` | VARCHAR(100) | NOT NULL | Marketing channel used (e.g., Email, Facebook, Google Ads, LinkedIn) |
| `cost` | DECIMAL(12,2) | NULLABLE | Cost associated with this activity |
| `scheduled_datetime` | DATETIME | NULLABLE | When this activity is scheduled to run/start |
| `executed_datetime` | DATETIME | NULLABLE | When this activity actually ran/completed |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., planned, in_progress, completed, failed, cancelled |
| `assigned_to_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users` | User responsible for this activity |
| `metrics_json` | JSON | NULLABLE | Performance metrics (e.g., `{"impressions": 1000, "clicks": 100, "ctr": 0.1}`) |
| `content_template_id` | BIGINT UNSIGNED | NULLABLE, FK to `content_templates` | Link to content used for this activity |
| `target_url` | VARCHAR(512) | NULLABLE | URL associated with this activity (e.g., link in ad) |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `content_templates` Table
Templates for emails, social media posts, landing page sections, ad copy, etc.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `name` | VARCHAR(255) | NOT NULL | Internal name for the template |
| `template_type` | VARCHAR(50) | NOT NULL, INDEX | e.g., email, social_post, landing_page_section, ad_copy, sms |
| `subject_line` | VARCHAR(255) | NULLABLE | For emails or ads |
| `body_html` | TEXT | NULLABLE | HTML content (for emails, landing pages) |
| `body_text` | TEXT | NULLABLE | Plain text content (for SMS, email fallbacks, social posts) |
| `ai_prompt_instructions` | TEXT | NULLABLE | Specific instructions for AI if this template is AI-assisted |
| `placeholders_json` | JSON | NULLABLE | List of available placeholders (e.g., `["contact.first_name", "property.address"]`) |
| `is_ai_assisted` | BOOLEAN | NOT NULL, DEFAULT false | Does this template rely on AI for content generation? |
| `created_by_user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users` | User who created the template |
| `last_edited_by_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users` | User who last edited the template |
| `version` | INTEGER | NOT NULL, DEFAULT 1 | Version number of the template |
| `is_active` | BOOLEAN | NOT NULL, DEFAULT true | Is this template currently available for use? |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `landing_pages` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `name` | VARCHAR(255) | NOT NULL | Internal name for the landing page |
| `slug` | VARCHAR(255) | NOT NULL, UNIQUE (within tenant), INDEX | URL-friendly slug |
| `title` | VARCHAR(255) | NOT NULL | HTML Title tag content |
| `marketing_campaign_id` | BIGINT UNSIGNED | NULLABLE, FK to `marketing_campaigns`, INDEX | Associated marketing campaign |
| `form_id` | BIGINT UNSIGNED | NULLABLE, FK to `forms` (Wave core), INDEX | Lead capture form used on this page |
| `content_json` | JSON | NULLABLE | Structured content for the page (e.g., using a block editor format) |
| `seo_meta_description` | TEXT | NULLABLE | Meta description for SEO |
| `seo_keywords_json` | JSON | NULLABLE | Array of SEO keywords |
| `header_scripts` | TEXT | NULLABLE | Custom scripts for the `<head>` section (e.g., analytics, pixels) |
| `footer_scripts` | TEXT | NULLABLE | Custom scripts for before `</body>` |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., draft, published, archived |
| `published_at` | TIMESTAMP | NULLABLE | Timestamp when the page was last published |
| `visits_count` | INTEGER | NOT NULL, DEFAULT 0 | Total number of page views |
| `conversion_count` | INTEGER | NOT NULL, DEFAULT 0 | Total number of conversions (e.g., form submissions) |
| `created_by_user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users` | User who created the page |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `form_submissions` Table
Tracks submissions from forms on landing pages or embedded elsewhere.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `form_id` | BIGINT UNSIGNED | NOT NULL, FK to `forms` (Wave core), INDEX | The form that was submitted |
| `landing_page_id` | BIGINT UNSIGNED | NULLABLE, FK to `landing_pages`, INDEX | Landing page where the form was submitted (if applicable) |
| `contact_id` | BIGINT UNSIGNED | NULLABLE, FK to `contacts`, INDEX | Link to contact created/updated by this submission |
| `data_json` | JSON | NOT NULL | The actual data submitted in the form |
| `source_url` | VARCHAR(512) | NULLABLE | URL from which the form was submitted |
| `ip_address` | VARCHAR(45) | NULLABLE | Submitter's IP address |
| `user_agent` | TEXT | NULLABLE | Submitter's browser user agent string |
| `utm_source` | VARCHAR(255) | NULLABLE | UTM tracking parameter |
| `utm_medium` | VARCHAR(255) | NULLABLE | UTM tracking parameter |
| `utm_campaign` | VARCHAR(255) | NULLABLE | UTM tracking parameter |
| `utm_term` | VARCHAR(255) | NULLABLE | UTM tracking parameter |
| `utm_content` | VARCHAR(255) | NULLABLE | UTM tracking parameter |
| `processed_at` | TIMESTAMP | NULLABLE | Timestamp if/when this submission was processed by an automation/workflow |
| `status` | VARCHAR(50) | NOT NULL, DEFAULT 'new' | e.g., new, processed, error, duplicate |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `ai_prompts` Table
Stores reusable AI prompt templates.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier (or NULL for system-wide prompts) |
| `name` | VARCHAR(255) | NOT NULL | Internal name for the prompt template |
| `description` | TEXT | NULLABLE | Description of what the prompt does |
| `prompt_text` | TEXT | NOT NULL | The actual prompt template, potentially with placeholders like `{{variable_name}}` |
| `model_identifier` | VARCHAR(100) | NOT NULL | AI model to use (e.g., gpt-4, gpt-3.5-turbo, claude-2) |
| `usage_context` | VARCHAR(100) | NOT NULL, INDEX | Where this prompt is typically used (e.g., property_description, email_cold_outreach, lead_summary) |
| `expected_output_format` | VARCHAR(50) | NULLABLE | e.g., text, json, markdown, html_list |
| `temperature` | DECIMAL(3,2) | NULLABLE | Model temperature setting (e.g., 0.7) |
| `max_tokens` | INTEGER | NULLABLE | Maximum tokens for the response |
| `system_message` | TEXT | NULLABLE | System message/role definition for the AI model |
| `created_by_user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users` | User who created the prompt |
| `is_shared_with_tenant` | BOOLEAN | NOT NULL, DEFAULT false | If a system prompt, is it shared with all users in the tenant? |
| `version` | INTEGER | NOT NULL, DEFAULT 1 | Version of the prompt |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `ai_generated_contents` Table
Logs instances of AI content generation.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `ai_prompt_id` | BIGINT UNSIGNED | NULLABLE, FK to `ai_prompts` | Link to the prompt template used (if any) |
| `generated_by_user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users` | User who initiated the generation |
| `related_entity_type` | VARCHAR(255) | NULLABLE | Polymorphic: Entity this content is for (e.g., `App\Models\PropertyUnit`) |
| `related_entity_id` | BIGINT UNSIGNED | NULLABLE | Polymorphic ID |
| `input_params_json` | JSON | NULLABLE | Data used to fill placeholders in the prompt |
| `full_prompt_sent` | TEXT | NULLABLE | The exact prompt sent to the AI model |
| `generated_content_text` | TEXT | NULLABLE | The main text content generated by AI |
| `generated_content_json` | JSON | NULLABLE | If AI output was structured JSON |
| `model_used` | VARCHAR(100) | NOT NULL | Specific AI model version used for this generation |
| `tokens_prompt` | INTEGER | NULLABLE | Number of tokens in the input prompt |
| `tokens_completion` | INTEGER | NULLABLE | Number of tokens in the AI's completion/response |
| `cost` | DECIMAL(10,5) | NULLABLE | Estimated cost of this AI generation |
| `rating` | INTEGER | NULLABLE | User feedback on the quality of generation (e.g., 1-5 stars) |
| `feedback_notes` | TEXT | NULLABLE | User's textual feedback |
| `is_used` | BOOLEAN | NOT NULL, DEFAULT false | Has this generated content been actively used/applied? |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

### Portal & API Integrations

#### `portal_integrations` Table
Configuration for integrations with external real estate portals (REA, Domain, etc.).
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `name` | VARCHAR(255) | NOT NULL | Friendly name for this integration (e.g., "REA Office Feed") |
| `portal_type` | VARCHAR(100) | NOT NULL, INDEX | Type of portal (e.g., REA, Domain, Zenu, CustomXML, Juwai) |
| `credentials_json` | JSON | NOT NULL | Encrypted credentials (API keys, FTP details, client IDs) |
| `settings_json` | JSON | NULLABLE | Configuration settings (e.g., feed_frequency, default_listing_tier, agency_id_on_portal) |
| `is_active` | BOOLEAN | NOT NULL, DEFAULT true | Is this integration currently active? |
| `last_successful_sync_at` | TIMESTAMP | NULLABLE | Timestamp of the last successful data synchronization |
| `last_sync_status` | VARCHAR(50) | NULLABLE | Status of the last sync attempt (e.g., success, failed, partial_success) |
| `last_sync_message` | TEXT | NULLABLE | Message or error details from the last sync |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `portal_published_listings` Table
Tracks the status of property listings published to external portals.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `property_listing_id` | BIGINT UNSIGNED | NOT NULL, FK to `property_listings`, INDEX | The internal property listing |
| `portal_integration_id` | BIGINT UNSIGNED | NOT NULL, FK to `portal_integrations`, INDEX | The portal this listing is published to |
| `external_listing_id` | VARCHAR(255) | NULLABLE, INDEX | The ID of this listing on the external portal |
| `published_url` | VARCHAR(512) | NULLABLE | Direct URL to the listing on the portal |
| `listing_tier_on_portal` | VARCHAR(100) | NULLABLE | Tier of the listing on the portal (e.g., Premium, Standard, Feature) |
| `publication_status` | VARCHAR(50) | NOT NULL, INDEX | e.g., pending_upload, active, inactive, error, needs_update, withdrawn |
| `first_published_at` | TIMESTAMP | NULLABLE | When this listing was first published to this portal |
| `last_published_at` | TIMESTAMP | NULLABLE | When this listing was last updated/re-published to this portal |
| `portal_expiry_date` | DATE | NULLABLE | Expiry date of the listing on the portal (if applicable) |
| `view_statistics_json` | JSON | NULLABLE | Statistics from the portal (views, clicks, inquiries) if available |
| `inquiry_count` | INTEGER | NOT NULL, DEFAULT 0 | Number of inquiries received via this portal for this listing |
| `last_sync_error_json` | JSON | NULLABLE | Details of any errors during the last sync attempt for this listing |
| `payload_sent_to_portal_json` | JSON | NULLABLE | The data payload that was last sent to the portal for this listing |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| *Unique composite key on (`property_listing_id`, `portal_integration_id`)* |

#### `website_integrations` Table
Configuration for tenant's own websites (PIAB Fast PHP Sites, WordPress, etc.).
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `name` | VARCHAR(255) | NOT NULL | Friendly name for this website integration (e.g., "Main Agency Website") |
| `website_type` | VARCHAR(50) | NOT NULL, INDEX | e.g., wordpress, php_fast_site, custom_api_feed, shopify |
| `base_url` | VARCHAR(255) | NOT NULL | The main URL of the website |
| `api_endpoint` | VARCHAR(255) | NULLABLE | API endpoint for pushing data (if applicable) |
| `api_key_encrypted` | TEXT | NULLABLE | Encrypted API key for authentication |
| `settings_json` | JSON | NULLABLE | Specific settings for this website (e.g., theme_name, property_display_options, default_image_sizes) |
| `is_active` | BOOLEAN | NOT NULL, DEFAULT true | Is this integration currently active? |
| `last_successful_push_at` | TIMESTAMP | NULLABLE | Timestamp of the last successful data push/sync |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

### Xero & Financial Integration

#### `xero_connections` Table
Stores OAuth2 connection details for a tenant's Xero organization.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, UNIQUE, INDEX | Tenant identifier (assuming one Xero org per Fusion tenant) |
| `xero_tenant_id_guid` | UUID | NULLABLE, INDEX | Xero's unique ID for the connected organization (retrieved after auth) |
| `xero_user_name` | VARCHAR(255) | NULLABLE | Name of the Xero user who authorized the connection |
| `access_token_encrypted` | TEXT | NOT NULL | Encrypted OAuth2 access token |
| `refresh_token_encrypted` | TEXT | NOT NULL | Encrypted OAuth2 refresh token |
| `token_expires_at` | TIMESTAMP | NOT NULL | Expiry timestamp for the access token |
| `scopes` | JSON | NOT NULL | OAuth scopes granted during authorization |
| `status` | VARCHAR(50) | NOT NULL, DEFAULT 'disconnected' | e.g., connected, disconnected, needs_reauth, error |
| `connected_by_user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users` | User who initiated and authorized the connection |
| `connected_at` | TIMESTAMP | NOT NULL | Timestamp of successful connection |
| `last_sync_at` | TIMESTAMP | NULLABLE | Timestamp of the last general synchronization activity |
| `sync_settings_json` | JSON | NULLABLE | Tenant's preferences for Xero sync (e.g., `{"sync_contacts": true, "sync_invoices_from_date": "..."}`) |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `xero_sync_logs` Table
Logs synchronization activities with Xero.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `xero_connection_id` | BIGINT UNSIGNED | NOT NULL, FK to `xero_connections`, INDEX | The Xero connection used |
| `entity_type` | VARCHAR(100) | NOT NULL | Type of entity being synced (e.g., Contact, Invoice, Payment) |
| `direction` | VARCHAR(20) | NOT NULL | Sync direction (e.g., fusion_to_xero, xero_to_fusion) |
| `status` | VARCHAR(50) | NOT NULL | Sync status (e.g., success, failed, pending, partial_success) |
| `message` | TEXT | NULLABLE | Success message or error details |
| `payload_sent_json` | JSON | NULLABLE | Data payload sent to Xero (for debugging) |
| `response_received_json` | JSON | NULLABLE | Response received from Xero (for debugging) |
| `duration_ms` | INTEGER | NULLABLE | Duration of the sync operation in milliseconds |
| `records_processed` | INTEGER | NULLABLE | Number of records processed in this sync batch |
| `created_at` | TIMESTAMP | NOT NULL | Timestamp of the log entry |

#### `xero_entity_mappings` Table
Maps Fusion CRM entities to their corresponding Xero entities.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `fusion_entity_type` | VARCHAR(255) | NOT NULL | Polymorphic type in Fusion (e.g., `App\Models\Contact`) |
| `fusion_entity_id` | BIGINT UNSIGNED | NOT NULL | Polymorphic ID in Fusion |
| `xero_entity_type` | VARCHAR(100) | NOT NULL | Type of entity in Xero (e.g., Contact, Invoice, Payment) |
| `xero_entity_guid` | UUID | NOT NULL | Xero's globally unique identifier for the entity |
| `last_synced_at` | TIMESTAMP | NOT NULL | Timestamp of the last successful sync for this specific mapping |
| `sync_status` | VARCHAR(50) | NOT NULL, DEFAULT 'synced' | e.g., synced, pending_fusion_update, pending_xero_update, error |
| `extra_data_json` | JSON | NULLABLE | Additional data, like Xero entity version for optimistic locking |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| *Unique composite key on (`tenant_id`, `fusion_entity_type`, `fusion_entity_id`, `xero_entity_type`)* |
| *Unique composite key on (`tenant_id`, `xero_entity_type`, `xero_entity_guid`)* |

#### `invoices` Table
Stores invoices, potentially synced with Xero.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier for Fusion |
| `deal_id` | BIGINT UNSIGNED | NULLABLE, FK to `deals`, INDEX | Associated deal (e.g., for commission invoices) |
| `contact_id` | BIGINT UNSIGNED | NOT NULL, FK to `contacts`, INDEX | The contact (customer) this invoice is for |
| `invoice_number` | VARCHAR(100) | NOT NULL, INDEX | Invoice number (can be auto-generated by Fusion or from Xero) |
| `xero_invoice_guid` | UUID | NULLABLE, INDEX | Link to Xero invoice via `xero_entity_mappings` (convenience) |
| `invoice_date` | DATE | NOT NULL, INDEX | Date the invoice was issued |
| `due_date` | DATE | NOT NULL, INDEX | Date the invoice is due for payment |
| `total_amount` | DECIMAL(15,2) | NOT NULL | Total amount of the invoice (including tax) |
| `tax_amount` | DECIMAL(15,2) | NOT NULL | Total tax amount on the invoice |
| `amount_due` | DECIMAL(15,2) | NOT NULL | Current outstanding amount due |
| `amount_paid` | DECIMAL(15,2) | NOT NULL, DEFAULT 0.00 | Total amount paid against this invoice |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., draft, sent, paid, overdue, void, partially_paid (can sync from Xero) |
| `currency` | VARCHAR(3) | NOT NULL, DEFAULT 'AUD' | Currency code |
| `reference` | VARCHAR(255) | NULLABLE | Reference field (e.g., PO number) |
| `description` | TEXT | NULLABLE | Overall description or notes for the invoice |
| `payment_terms` | VARCHAR(100) | NULLABLE | Payment terms (e.g., "Net 30") |
| `sent_to_xero_at` | TIMESTAMP | NULLABLE | Timestamp when this invoice was last pushed to Xero |
| `last_synced_from_xero_at` | TIMESTAMP | NULLABLE | Timestamp when this invoice was last updated from Xero |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `invoice_items` Table
Line items for an invoice.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `invoice_id` | BIGINT UNSIGNED | NOT NULL, FK to `invoices`, INDEX | Parent invoice |
| `description` | TEXT | NOT NULL | Description of the line item |
| `quantity` | DECIMAL(10,2) | NOT NULL | Quantity of the item/service |
| `unit_price` | DECIMAL(15,2) | NOT NULL | Price per unit (exclusive of tax) |
| `line_amount` | DECIMAL(15,2) | NOT NULL | Total amount for this line (quantity * unit_price) |
| `tax_type_code` | VARCHAR(50) | NULLABLE | Xero tax type code (e.g., GSTONINCOME, EXEMPTGST) |
| `tax_rate_percentage` | DECIMAL(5,2) | NULLABLE | Tax rate applied to this line item |
| `tax_amount_on_line` | DECIMAL(15,2) | NULLABLE | Tax amount for this line item |
| `account_code` | VARCHAR(50) | NULLABLE | Xero general ledger account code for this line item |
| `xero_line_item_guid` | UUID | NULLABLE, INDEX | Xero's GUID for this line item (if synced) |
| `product_or_service_id` | BIGINT UNSIGNED | NULLABLE, FK to a future `products_services` table | Link to internal product/service catalog |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `payments` Table
Records payments made or received.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier for Fusion |
| `invoice_id` | BIGINT UNSIGNED | NULLABLE, FK to `invoices`, INDEX | Invoice this payment is applied to (if any) |
| `contact_id` | BIGINT UNSIGNED | NULLABLE, FK to `contacts`, INDEX | Contact making or receiving the payment |
| `payment_date` | DATE | NOT NULL, INDEX | Date the payment was made/received |
| `amount` | DECIMAL(15,2) | NOT NULL | Amount of the payment |
| `currency` | VARCHAR(3) | NOT NULL, DEFAULT 'AUD' | Currency code |
| `payment_method` | VARCHAR(50) | NOT NULL | e.g., bank_transfer, credit_card, cash, cheque |
| `reference` | VARCHAR(255) | NULLABLE | Payment reference (e.g., transaction ID, cheque number) |
| `notes` | TEXT | NULLABLE | Notes about the payment |
| `xero_payment_guid` | UUID | NULLABLE, INDEX | Link to Xero payment via `xero_entity_mappings` |
| `xero_account_code_for_payment` | VARCHAR(50) | NULLABLE | Xero bank account code where payment was made/received |
| `is_reconciled_in_xero` | BOOLEAN | NOT NULL, DEFAULT false | Has this payment been reconciled in Xero? |
| `last_synced_from_xero_at` | TIMESTAMP | NULLABLE | Timestamp of last sync from Xero |
| `processed_by_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users` | User who recorded/processed this payment in Fusion |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `commissions` Table
Tracks overall commission for a deal or transaction.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `deal_id` | BIGINT UNSIGNED | NOT NULL, FK to `deals`, INDEX | Associated deal |
| `invoice_id` | BIGINT UNSIGNED | NULLABLE, FK to `invoices`, INDEX | Associated commission invoice (if applicable) |
| `total_commission_amount` | DECIMAL(15,2) | NOT NULL | Total commission earned on the deal |
| `commission_rate_percentage` | DECIMAL(5,2) | NULLABLE | Overall commission rate applied |
| `base_amount_for_commission` | DECIMAL(15,2) | NULLABLE | The sale price or value commission was calculated on |
| `commission_type` | VARCHAR(50) | NOT NULL | e.g., percentage_of_sale, fixed_amount, tiered |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., pending_calculation, calculated, pending_payment, paid, disputed |
| `calculation_method_notes` | TEXT | NULLABLE | Notes on how the commission was calculated |
| `payment_date` | DATE | NULLABLE | Date the full commission was paid out (if tracked at this level) |
| `gst_amount_on_commission` | DECIMAL(15,2) | NULLABLE | GST component of the commission |
| `notes` | TEXT | NULLABLE | General notes about this commission record |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `commission_splits` Table
Details how a commission is split among multiple agents/parties.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `commission_id` | BIGINT UNSIGNED | NOT NULL, FK to `commissions`, INDEX | Parent commission record |
| `recipient_user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users`, INDEX | User (agent) receiving this portion of the commission |
| `split_amount` | DECIMAL(15,2) | NOT NULL | Amount of commission allocated to this recipient |
| `split_percentage` | DECIMAL(5,2) | NULLABLE | Percentage of total commission allocated (if applicable) |
| `notes` | TEXT | NULLABLE | Notes specific to this split |
| `status` | VARCHAR(50) | NOT NULL, INDEX | e.g., pending_payment, paid, on_hold |
| `payment_date` | DATE | NULLABLE | Date this split portion was paid to the recipient |
| `payment_reference` | VARCHAR(255) | NULLABLE | Reference for the payment of this split |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

### Task Management & Interactions

#### `tasks` Table
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `taskable_type` | VARCHAR(255) | NULLABLE | Polymorphic: Entity this task relates to (e.g., `App\Models\Contact`, `App\Models\Deal`) |
| `taskable_id` | BIGINT UNSIGNED | NULLABLE | Polymorphic ID |
| `assigned_by_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users` | User who assigned the task |
| `assigned_to_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users`, INDEX | User responsible for completing the task |
| `title` | VARCHAR(255) | NOT NULL | Task title |
| `description` | TEXT | NULLABLE | Detailed description of the task |
| `due_datetime` | DATETIME | NULLABLE, INDEX | When the task is due |
| `completed_at` | TIMESTAMP | NULLABLE | Timestamp when the task was completed |
| `priority` | VARCHAR(50) | NOT NULL, DEFAULT 'medium' | e.g., low, medium, high, urgent |
| `status` | VARCHAR(50) | NOT NULL, DEFAULT 'pending' | e.g., pending, in_progress, completed, deferred, cancelled |
| `recurrence_pattern` | VARCHAR(100) | NULLABLE | Recurrence schedule (e.g., daily, weekly on Monday, monthly on 1st) |
| `reminder_datetime` | TIMESTAMP | NULLABLE | When to send a reminder for this task |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

#### `interactions` Table
Logs various types of communications and activities with contacts or related to entities.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant identifier |
| `uuid` | UUID | NOT NULL, UNIQUE, INDEX | External identifier |
| `contact_id` | BIGINT UNSIGNED | NOT NULL, FK to `contacts`, INDEX | The primary contact involved in the interaction |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK to `users`, INDEX | User who logged or participated in the interaction |
| `interactionable_type` | VARCHAR(255) | NULLABLE | Polymorphic: Entity this interaction relates to (e.g., `App\Models\Deal`, `App\Models\PropertyListing`) |
| `interactionable_id` | BIGINT UNSIGNED | NULLABLE | Polymorphic ID |
| `type` | VARCHAR(50) | NOT NULL, INDEX | e.g., call, email, meeting, note, sms, site_visit, document_sent |
| `medium` | VARCHAR(50) | NULLABLE | Communication medium (e.g., phone, outlook, zoom, in_person) |
| `direction` | VARCHAR(20) | NULLABLE | e.g., inbound, outbound (for calls/emails) |
| `subject` | VARCHAR(255) | NULLABLE | Subject of the email, meeting title, call purpose |
| `content` | TEXT | NULLABLE | Details of the interaction, email body, meeting notes, call summary |
| `occurred_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | When the interaction actually happened |
| `duration_minutes` | INTEGER | NULLABLE | Duration of the call or meeting in minutes |
| `location` | VARCHAR(255) | NULLABLE | Location of the meeting or event |
| `outcome` | VARCHAR(100) | NULLABLE | Result or outcome of the interaction (e.g., appointment_booked, information_sent) |
| `next_steps` | TEXT | NULLABLE | Agreed upon next steps or follow-up actions |
| `is_important` | BOOLEAN | NOT NULL, DEFAULT false | Flag for important interactions |
| `extra_attributes` | JSON | NULLABLE | Additional attributes |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

### General Utility Tables

#### `tags` Table (Spatie/laravel-tags)
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NULLABLE, FK to `tenants`, INDEX | If NULL, global tag. Otherwise, tenant-specific. |
| `name` | JSON | NOT NULL | Tag name (translatable using Spatie's convention) |
| `slug` | JSON | NOT NULL | Tag slug (translatable) |
| `type` | VARCHAR(255) | NULLABLE, INDEX | For grouping tags (e.g., contact_status, property_feature, document_type) |
| `order_column` | INTEGER | NULLABLE | For custom ordering of tags |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

#### `taggables` Table (Spatie/laravel-tags - Pivot)
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `tag_id` | BIGINT UNSIGNED | NOT NULL, FK to `tags` | Tag identifier |
| `taggable_type` | VARCHAR(255) | NOT NULL | Polymorphic type of the tagged model |
| `taggable_id` | BIGINT UNSIGNED | NOT NULL | Polymorphic ID of the tagged model |
| *Primary key on (`tag_id`, `taggable_type`, `taggable_id`)* |

#### `activity_log` Table (Spatie/laravel-activitylog)
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NULLABLE, FK to `tenants`, INDEX | Added via custom Activity model for tenant scoping |
| `log_name` | VARCHAR(255) | NULLABLE, INDEX | Log category (e.g., default, auth, contact) |
| `description` | TEXT | NOT NULL | Description of the activity |
| `subject_type` | VARCHAR(255) | NULLABLE | Polymorphic type of the subject model |
| `subject_id` | BIGINT UNSIGNED | NULLABLE | Polymorphic ID of the subject model |
| `event` | VARCHAR(255) | NULLABLE, INDEX | Event name (e.g., created, updated, deleted) |
| `causer_type` | VARCHAR(255) | NULLABLE | Polymorphic type of the user/entity causing the activity |
| `causer_id` | BIGINT UNSIGNED | NULLABLE | Polymorphic ID of the causer |
| `properties` | JSON | NULLABLE | Additional data (e.g., old/new attributes) |
| `batch_uuid` | UUID | NULLABLE, INDEX | For grouping related activities |
| `created_at` | TIMESTAMP | NOT NULL | Timestamp of the activity |
| `updated_at` | TIMESTAMP | NOT NULL | (Usually same as created_at for logs) |

#### `media` Table (Spatie/laravel-medialibrary)
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NULLABLE, FK to `tenants`, INDEX | Added via custom Media model for tenant scoping |
| `model_type` | VARCHAR(255) | NOT NULL | Polymorphic type of the associated model |
| `model_id` | BIGINT UNSIGNED | NOT NULL | Polymorphic ID of the associated model |
| `uuid` | UUID | NULLABLE, UNIQUE, INDEX | UUID for the media item |
| `collection_name` | VARCHAR(255) | NOT NULL, INDEX | Name of the media collection (e.g., avatars, property_images, documents) |
| `name` | VARCHAR(255) | NOT NULL | Name of the media item (often derived from file name) |
| `file_name` | VARCHAR(255) | NOT NULL | Original file name |
| `mime_type` | VARCHAR(255) | NULLABLE | MIME type of the file |
| `disk` | VARCHAR(255) | NOT NULL | Filesystem disk where the file is stored |
| `conversions_disk` | VARCHAR(255) | NULLABLE | Disk for storing generated conversions (thumbnails, etc.) |
| `size` | BIGINT UNSIGNED | NOT NULL | File size in bytes |
| `manipulations` | JSON | NOT NULL | Applied image manipulations |
| `custom_properties` | JSON | NOT NULL | User-defined custom properties for the media item |
| `generated_conversions` | JSON | NOT NULL | Information about generated image conversions |
| `responsive_images` | JSON | NOT NULL | Information about responsive image variants |
| `order_column` | INTEGER | NULLABLE, INDEX | For ordering media within a collection |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

#### `notifications` Table (Laravel Core)
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | CHAR(36) | PK | UUID primary key |
| `tenant_id` | BIGINT UNSIGNED | NULLABLE, FK to `tenants`, INDEX | For querying notifications by tenant |
| `type` | VARCHAR(255) | NOT NULL | Class name of the notification |
| `notifiable_type` | VARCHAR(255) | NOT NULL | Polymorphic type of the notifiable entity (e.g., `App\Models\User`) |
| `notifiable_id` | BIGINT UNSIGNED | NOT NULL | Polymorphic ID of the notifiable entity |
| `data` | TEXT | NOT NULL | JSON-encoded notification data |
| `read_at` | TIMESTAMP | NULLABLE | Timestamp when the notification was read |
| `created_at` | TIMESTAMP | NULLABLE | Record creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Record last update timestamp |

#### `jobs`, `failed_jobs`, `job_batches` Tables (Laravel Core for Queues)
*   Standard Laravel Queue table structures. Consider adding `tenant_id` if queue jobs need to be directly queryable by tenant, though job payloads usually contain tenant context.

### Settings & Configuration (Extending Wave `settings` or using Spatie `laravel-settings`)

#### `settings` Table
This can be Wave's core `settings` table, potentially extended or used alongside `spatie/laravel-settings`. If using a single table:
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NULLABLE, FK to `tenants`, INDEX | If NULL, global setting. Otherwise, tenant-specific. |
| `group` | VARCHAR(255) | NOT NULL, INDEX | Grouping for settings (e.g., general, mail, xero, appearance) |
| `key` | VARCHAR(255) | NOT NULL | Setting key (e.g., site_name, mail_from_address) |
| `display_name` | VARCHAR(255) | NOT NULL | User-friendly name for the setting |
| `value` | TEXT | NULLABLE | Value of the setting (can be string, number, JSON array/object) |
| `type` | VARCHAR(50) | NOT NULL, DEFAULT 'text' | Input type for UI (e.g., text, textarea, boolean, select, image, color_picker) |
| `details` | TEXT | NULLABLE | Additional details, e.g., options for a select type as JSON |
| `order` | INTEGER | NOT NULL, DEFAULT 0 | Display order within the group |
| `is_encrypted` | BOOLEAN | NOT NULL, DEFAULT false | Should the value be encrypted at rest? |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| *Unique composite key on (`tenant_id`, `group`, `key`)* |

### Strategy-Based Funnel Engine

#### `funnel_templates` Table
Pre-built or custom templates for marketing/sales funnels.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NULLABLE, FK to `tenants`, INDEX | NULL for system-wide templates, tenant_id for custom ones |
| `name` | VARCHAR(255) | NOT NULL | Name of the funnel template (e.g., Co-Living Investor Funnel) |
| `description` | TEXT | NULLABLE | Description of the funnel strategy and purpose |
| `strategy_tags_json` | JSON | NULLABLE | Tags associated with this funnel (e.g., `["coliving", "investment", "first_home_buyer"]`) |
| `landing_page_content_template_id` | BIGINT UNSIGNED | NULLABLE, FK to `content_templates` | Template for the primary landing page |
| `email_sequence_template_ids_json` | JSON | NULLABLE | Array of `content_templates.id` for the email nurture sequence |
| `lead_scoring_rules_json` | JSON | NULLABLE | Configuration for lead scoring specific to this funnel |
| `property_filter_presets_json` | JSON | NULLABLE | Default property filters to apply for leads in this funnel |
| `n8n_workflow_template_id` | VARCHAR(255) | NULLABLE | ID of an N8N template workflow to associate |
| `vapi_agent_template_id` | VARCHAR(255) | NULLABLE | ID of a VAPI voice agent template to associate |
| `is_active` | BOOLEAN | NOT NULL, DEFAULT true | Is this template available for use? |
| `created_by_user_id` | BIGINT UNSIGNED | NULLABLE, FK to `users` | User who created this template (if custom) |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `active_funnels` Table
An instance of a `funnel_template` deployed and actively used by a tenant.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | BIGINT UNSIGNED | NOT NULL, FK to `tenants`, INDEX | Tenant who deployed this funnel instance |
| `funnel_template_id` | BIGINT UNSIGNED | NOT NULL, FK to `funnel_templates`, INDEX | The template this instance is based on |
| `name` | VARCHAR(255) | NOT NULL | Tenant-defined name for this active funnel instance |
| `deployed_landing_page_id` | BIGINT UNSIGNED | NULLABLE, FK to `landing_pages` | The specific landing page created for this funnel instance |
| `deployed_marketing_campaign_id` | BIGINT UNSIGNED | NULLABLE, FK to `marketing_campaigns` | The marketing campaign (e.g., for email sequence) for this funnel |
| `status` | VARCHAR(50) | NOT NULL, DEFAULT 'active' | e.g., active, paused, archived, completed |
| `n8n_instance_config_json` | JSON | NULLABLE | Configuration for the specific N8N workflow instance (if applicable) |
| `vapi_instance_config_json` | JSON | NULLABLE | Configuration for the specific VAPI agent instance (if applicable) |
| `start_date` | DATE | NULLABLE | When this funnel instance was activated |
| `end_date` | DATE | NULLABLE | When this funnel instance was deactivated/archived |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |

#### `funnel_leads` Table
Associates contacts (leads) with specific active funnel instances.
| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| `active_funnel_id` | BIGINT UNSIGNED | NOT NULL, FK to `active_funnels`, INDEX | The active funnel this lead is part of |
| `contact_id` | BIGINT UNSIGNED | NOT NULL, FK to `contacts`, INDEX | The lead/contact in the funnel |
| `entry_point_description` | VARCHAR(255) | NULLABLE | How the lead entered this funnel (e.g., "Landing Page Submission", "Manual Import") |
| `current_funnel_stage_name` | VARCHAR(100) | NULLABLE | Current stage of the lead within this specific funnel (e.g., "Initial Inquiry", "Follow-up Call Scheduled") |
| `engagement_score_in_funnel` | INTEGER | NULLABLE | Score reflecting engagement within this funnel |
| `converted_at` | TIMESTAMP | NULLABLE | Timestamp if the lead converted through this funnel |
| `conversion_deal_id` | BIGINT UNSIGNED | NULLABLE, FK to `deals` | Link to deal if conversion resulted in a deal |
| `notes` | TEXT | NULLABLE | Notes specific to this lead within this funnel |
| `created_at` | TIMESTAMP | NOT NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Record last update timestamp |
| *Unique composite key on (`active_funnel_id`, `contact_id`)* |

## Relationships Summary

*   **One-to-Many:** `tenants` to most other tables (e.g., `users`, `contacts`, `projects`, `deals`). `projects` to `project_stages`. `project_stages` to `property_units`. `pipelines` to `pipeline_stages`.
*   **Many-to-Many:** `users` <=> `roles` (via `model_has_roles`). `contacts` <=> `tags` (via `taggables`). `deals` <=> `contacts` (e.g., multiple buyers/sellers on a deal, potentially via a `deal_contacts` pivot if complex roles are needed per deal).
*   **Polymorphic:** `activity_log` (subject, causer). `media` (model). `tags` (taggable). `tasks` (taskable, interactionable). `compliance_checklists` (checklistable). `ai_generated_contents` (related_entity). `inspections` (inspectable). `trust_account_transactions` (related_entity).

## Query Optimization Guidelines

To ensure optimal database performance:

1.  **Use Eager Loading**: Always eager load relationships (e.g., `Model::with('relation')->get()`) in Laravel Eloquent to prevent N+1 query problems.
2.  **Chunk Large Queries**: Use `chunk()` or `chunkById()` for processing large result sets to manage memory usage.
3.  **Use DB Transactions**: Wrap related database operations within `DB::transaction(function () { ... });` to ensure atomicity.
4.  **Optimize Select Columns**: Only select the columns actually needed in your queries (`select('id', 'name', ...)`).
5.  **Use Query Caching**: Cache results of expensive or frequently run queries where data doesn't change often.
6.  **Analyze Query Plans**: Use `EXPLAIN` (or your database's equivalent) to analyze and optimize complex queries.
7.  **Tenant Scoping**: Implement global scopes on Eloquent models to automatically apply `WHERE tenant_id = ?` conditions, or ensure all queries manually include tenant scoping.

## Backup and Maintenance

Database backup and maintenance recommendations:

1.  **Daily Backups**: Configure automated full database dumps daily, stored off-site.
2.  **Incremental/Differential Backups**: Consider more frequent (e.g., hourly) incremental or differential backups for critical data.
3.  **Point-in-Time Recovery (PITR)**: Ensure binary logging (MySQL) or WAL archiving (PostgreSQL) is enabled and configured for PITR capabilities.
4.  **Regular Optimization**: Schedule regular database maintenance tasks (e.g., `OPTIMIZE TABLE` for MySQL if using MyISAM or for defragmentation with InnoDB; `VACUUM ANALYZE` for PostgreSQL).
5.  **Index Maintenance**: Periodically analyze index usage and fragmentation. Rebuild indexes if necessary.
6.  **Data Archiving**: Develop a strategy for archiving or purging old, inactive data (e.g., old logs, soft-deleted records beyond a retention period) to maintain performance on active tables.
7.  **Monitoring**: Implement database performance monitoring to track query times, connection counts, disk space, and other vital metrics.

---
This schema is based on the detailed feature requirements and aims to provide a robust and scalable foundation for Fusion CRM V4. It incorporates best practices and leverages existing Laravel ecosystem tools.
Original comprehensive design was drafted in `Planning/ultimate_v4_blueprint/database_design.md`.
