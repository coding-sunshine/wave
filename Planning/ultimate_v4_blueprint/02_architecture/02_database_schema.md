# Fusion CRM V4 Database Schema

## Introduction

This document outlines the database schema for Fusion CRM V4, a comprehensive real estate CRM system optimized for the Australian market. The schema is designed to support multi-tenancy, complex relationships, and optimal performance.

## Core Design Principles

### Multi-Tenant Architecture
- Each table includes a `tenant_id` column to ensure proper data isolation between different organizations.
- Foreign key constraints include tenant scoping for security.

### Data Integrity
- Appropriate constraints and validations are implemented at the database level.
- Soft deletes using `deleted_at` are implemented for relevant tables.
- UUID columns are added for secure external references.

### Normalization & Performance
- Tables are normalized to reduce redundancy while considering performance.
- Indexed columns are used for frequently queried data.
- JSON columns are used for flexible, schema-less data storage where appropriate.

## Third-Party Package Integration

### Spatie Package Integration

The schema leverages several Spatie packages to provide standard functionality:

1. **Spatie Laravel Permission**
   - Provides role-based access control
   - Uses tables: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
   - Integrates with the User Management section

2. **Spatie Laravel MediaLibrary**
   - Handles file attachments and media management
   - Uses the `media` table with polymorphic relationships
   - Extends functionality in the Media Management and Document Management sections

3. **Spatie Laravel ActivityLog**
   - Tracks user actions throughout the system
   - Uses the `activity_log` table
   - Integrates with various models across the application

### Wave Boilerplate Integration

The schema incorporates core tables from the Wave boilerplate:

- `users`: User authentication and profiles 
- `user_preferences`: User-specific application preferences
- `settings`: System configuration
- `announcements`: System announcements
- `api_keys`: API authentication 
- `subscriptions`: Subscription management
- `plans`: Subscription plans
- `themes`: Theme configuration
- Additional tables may be available in the Wave package

## User Management

The user management system is built on top of the Wave boilerplate and integrates with Spatie Laravel Permission package.

### `users` Table (Wave Core)
Enhanced with real estate specific fields.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `name` | varchar(255) | NOT NULL | User's full name |
| `email` | varchar(255) | NOT NULL, UNIQUE | Email address |
| `username` | varchar(255) | NOT NULL, UNIQUE | Username |
| `password` | varchar(255) | NOT NULL | Encrypted password |
| `remember_token` | varchar(100) | NULLABLE | Remember me token |
| `avatar` | varchar(255) | NULLABLE | Avatar image path |
| `roles` | text | NULLABLE | Legacy roles JSON |
| `license_number` | varchar(100) | NULLABLE | Real estate license |
| `job_title` | varchar(255) | NULLABLE | Official job title |
| `phone` | varchar(50) | NULLABLE | Contact phone |
| `mobile` | varchar(50) | NULLABLE | Mobile number |
| `biography` | text | NULLABLE | User bio for website |
| `social_links` | json | NULLABLE | Social media links |
| `settings` | json | NULLABLE | User settings |
| `last_login_at` | timestamp | NULLABLE | Last login timestamp |
| `email_verified_at` | timestamp | NULLABLE | Email verification |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

### `user_preferences` Table (Wave Core)
Stores user-specific application preferences.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `user_id` | bigint | NOT NULL, FK | User foreign key |
| `key` | varchar(255) | NOT NULL | Preference key |
| `value` | text | NULLABLE | Preference value |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |

### Spatie Role & Permission Integration

The system uses the following tables from Spatie Laravel Permission:

#### `roles` Table (Spatie Permission)
Defines user roles in the system.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `name` | varchar(255) | NOT NULL | Role name |
| `guard_name` | varchar(255) | NOT NULL | Laravel guard |
| `created_at` | timestamp | NULLABLE | Creation timestamp |
| `updated_at` | timestamp | NULLABLE | Update timestamp |

#### `permissions` Table (Spatie Permission)
Defines granular permissions.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `name` | varchar(255) | NOT NULL | Permission name |
| `guard_name` | varchar(255) | NOT NULL | Laravel guard |
| `created_at` | timestamp | NULLABLE | Creation timestamp |
| `updated_at` | timestamp | NULLABLE | Update timestamp |

#### Pivot Tables (Spatie Permission)
- `model_has_permissions`: Links permissions to users
- `model_has_roles`: Links roles to users
- `role_has_permissions`: Links permissions to roles

## Multi-Tenant Management

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

## Contact Management

#### `contacts` Table
Stores client/lead information with improved support for real estate preferences.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `assigned_to` | bigint | NULLABLE, FK | Assigned user |
| `first_name` | varchar(100) | NULLABLE | First name |
| `last_name` | varchar(100) | NOT NULL | Last name |
| `email` | varchar(255) | NULLABLE | Email address |
| `phone` | varchar(50) | NULLABLE | Phone number |
| `mobile` | varchar(50) | NULLABLE | Mobile number |
| `address` | varchar(255) | NULLABLE | Address |
| `suburb` | varchar(100) | NULLABLE | Suburb/city |
| `state` | varchar(100) | NULLABLE | State/province |
| `postcode` | varchar(20) | NULLABLE | Postal code |
| `country` | varchar(100) | NULLABLE, DEFAULT 'Australia' | Country |
| `source_id` | bigint | NULLABLE, FK | Lead source |
| `source_detail` | varchar(255) | NULLABLE | Detailed source info |
| `company_id` | bigint | NULLABLE, FK | Associated company |
| `job_title` | varchar(100) | NULLABLE | Job title |
| `contact_type` | varchar(50) | NOT NULL, DEFAULT 'lead' | Contact type |
| `status` | varchar(50) | NOT NULL | Contact status |
| `stage` | varchar(50) | NULLABLE | Sales pipeline stage |
| `score` | integer | NULLABLE | Lead score |
| `min_budget` | decimal(12,2) | NULLABLE | Min budget for property |
| `max_budget` | decimal(12,2) | NULLABLE | Max budget for property |
| `min_bedrooms` | integer | NULLABLE | Min bedrooms required |
| `max_bedrooms` | integer | NULLABLE | Max bedrooms required |
| `min_bathrooms` | decimal(3,1) | NULLABLE | Min bathrooms required |
| `max_bathrooms` | decimal(3,1) | NULLABLE | Max bathrooms required |
| `preferred_suburbs` | json | NULLABLE | Preferred suburbs |
| `property_preferences` | json | NULLABLE | Property type preferences |
| `timeline` | varchar(100) | NULLABLE | Purchase timeline |
| `investment_status` | varchar(50) | NULLABLE | First-time investor, etc. |
| `finance_status` | varchar(50) | NULLABLE | Pre-approved, etc. |
| `tags` | json | NULLABLE | Tags |
| `custom_fields` | json | NULLABLE | Custom field values |
| `notes` | text | NULLABLE | General notes |
| `last_contacted_at` | timestamp | NULLABLE | Last contact timestamp |
| `next_followup_at` | timestamp | NULLABLE | Next scheduled follow-up |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Property Management

### Australian Real Estate Categories

The system supports various Australian property types and categories:

1. **Residential Properties**
   - Houses/Detached Dwellings
   - Townhouses
   - Units/Apartments
   - Duplexes
   - Villa Homes
   - Rural Residential

2. **Land**
   - Vacant Land
   - House and Land Packages
   - Rural Land

3. **Commercial Properties**
   - Retail
   - Office
   - Industrial
   - Mixed Use

4. **Development Projects**
   - Off-the-plan Apartments/Units
   - Land Subdivisions
   - House and Land Packages

#### `developers` Table
Stores property developers information.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `name` | varchar(255) | NOT NULL | Developer name |
| `abn` | varchar(20) | NULLABLE | Australian Business Number |
| `description` | text | NULLABLE | Developer description |
| `address` | varchar(255) | NULLABLE | Address |
| `suburb` | varchar(100) | NULLABLE | Suburb/city |
| `state` | varchar(100) | NULLABLE | State/province |
| `postcode` | varchar(20) | NULLABLE | Postal code |
| `phone` | varchar(50) | NULLABLE | Phone number |
| `email` | varchar(255) | NULLABLE | Email address |
| `website` | varchar(255) | NULLABLE | Website URL |
| `logo_path` | varchar(255) | NULLABLE | Logo file path |
| `is_active` | boolean | NOT NULL, DEFAULT true | Active status |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `projects` Table
Stores property development projects.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `developer_id` | bigint | NULLABLE, FK | Developer foreign key |
| `name` | varchar(255) | NOT NULL | Project name |
| `slug` | varchar(255) | NOT NULL | URL-friendly identifier |
| `description` | text | NULLABLE | Project description |
| `address` | varchar(255) | NULLABLE | Street address |
| `suburb` | varchar(100) | NULLABLE | Suburb/city |
| `state` | varchar(100) | NULLABLE | State/province |
| `postcode` | varchar(20) | NULLABLE | Postal code |
| `country` | varchar(100) | NULLABLE, DEFAULT 'Australia' | Country |
| `latitude` | decimal(10,8) | NULLABLE | Latitude coordinates |
| `longitude` | decimal(11,8) | NULLABLE | Longitude coordinates |
| `status` | varchar(50) | NOT NULL | Project status |
| `project_type_id` | bigint | NULLABLE, FK | Project type reference |
| `total_units` | integer | NULLABLE | Total number of units |
| `available_units` | integer | NULLABLE | Available units count |
| `min_price` | decimal(12,2) | NULLABLE | Minimum price |
| `max_price` | decimal(12,2) | NULLABLE | Maximum price |
| `min_bedrooms` | integer | NULLABLE | Min bedrooms across units |
| `max_bedrooms` | integer | NULLABLE | Max bedrooms across units |
| `has_house_land` | boolean | NULLABLE | Has house and land packages |
| `has_townhouses` | boolean | NULLABLE | Has townhouses |
| `has_apartments` | boolean | NULLABLE | Has apartments |
| `start_date` | date | NULLABLE | Project start date |
| `estimated_completion` | date | NULLABLE | Est. completion date |
| `featured` | boolean | NOT NULL, DEFAULT false | Featured flag |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `stages` Table
Stores stages within a project.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `project_id` | bigint | NOT NULL, FK | Project foreign key |
| `name` | varchar(255) | NOT NULL | Stage name |
| `description` | text | NULLABLE | Stage description |
| `release_date` | date | NULLABLE | Release date |
| `status` | varchar(50) | NOT NULL | Stage status |
| `order` | integer | NOT NULL, DEFAULT 0 | Display order |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `property_units` Table
Stores individual property units.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `project_id` | bigint | NOT NULL, FK | Project foreign key |
| `stage_id` | bigint | NULLABLE, FK | Stage foreign key |
| `unit_number` | varchar(50) | NULLABLE | Unit identifier |
| `lot_number` | varchar(50) | NULLABLE | Lot identifier |
| `street_number` | varchar(50) | NULLABLE | Street number |
| `street_name` | varchar(255) | NULLABLE | Street name |
| `unit_type` | varchar(50) | NOT NULL | Type (house, apartment, etc.) |
| `list_price` | decimal(12,2) | NULLABLE | Total price |
| `land_size` | decimal(10,2) | NULLABLE | Land size (m²) |
| `land_price` | decimal(12,2) | NULLABLE | Land price |
| `build_price` | decimal(12,2) | NULLABLE | Build price |
| `internal_area` | decimal(8,2) | NULLABLE | Internal area (m²) |
| `external_area` | decimal(8,2) | NULLABLE | External area (m²) |
| `total_area` | decimal(8,2) | NULLABLE | Total area (m²) |
| `bedrooms` | integer | NULLABLE | Number of bedrooms |
| `bathrooms` | decimal(3,1) | NULLABLE | Number of bathrooms |
| `car_spaces` | integer | NULLABLE | Number of car spaces |
| `description` | text | NULLABLE | Unit description |
| `features` | json | NULLABLE | Unit features |
| `status` | varchar(50) | NOT NULL | Unit status |
| `sale_status` | varchar(50) | NULLABLE | Sale status |
| `display_status` | varchar(50) | NULLABLE | Display status |
| `estimated_completion` | date | NULLABLE | Estimated completion date |
| `energy_rating` | decimal(3,1) | NULLABLE | Energy efficiency rating |
| `nbn_connection_type` | varchar(50) | NULLABLE | NBN connection type |
| `walkability_score` | integer | NULLABLE | Walkability score (0-100) |
| `school_zones` | json | NULLABLE | School catchment zones |
| `sustainability_features` | json | NULLABLE | Solar, batteries, etc. |
| `ai_detected_features` | json | NULLABLE | AI-detected features |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `property_listings` Table
Stores property listings for sale, rent, or auction.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `property_unit_id` | bigint | NOT NULL, FK | Property unit reference |
| `agent_id` | bigint | NOT NULL, FK | Primary agent |
| `listing_type` | varchar(50) | NOT NULL | Sale/Lease/Auction |
| `authority_type` | varchar(50) | NOT NULL | Exclusive/Open/Auction |
| `authority_start` | date | NOT NULL | Authority start date |
| `authority_end` | date | NOT NULL | Authority end date |
| `price_from` | decimal(12,2) | NULLABLE | Listing price from |
| `price_to` | decimal(12,2) | NULLABLE | Listing price to |
| `price_display` | varchar(50) | NOT NULL | How price is displayed |
| `display_address` | boolean | NOT NULL, DEFAULT true | Whether to show address |
| `description` | text | NULLABLE | Listing description |
| `features` | json | NULLABLE | Property features |
| `ai_detected_features` | json | NULLABLE | AI-detected features |
| `inspection_times` | json | NULLABLE | Open inspection times |
| `online_platforms` | json | NULLABLE | Marketing platforms |
| `marketing_description` | text | NULLABLE | Marketing copy |
| `video_url` | varchar(255) | NULLABLE | Property video URL |
| `virtual_tour_url` | varchar(255) | NULLABLE | Virtual tour URL |
| `status` | varchar(50) | NOT NULL | Listing status |
| `featured` | boolean | NOT NULL, DEFAULT false | Featured listing flag |
| `highlight_listing` | boolean | NOT NULL, DEFAULT false | Highlighted listing |
| `premium_listing` | boolean | NOT NULL, DEFAULT false | Premium listing tier |
| `external_references` | json | NULLABLE | External platform IDs |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `auctions` Table
Stores auction-specific information.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `property_listing_id` | bigint | NOT NULL, FK | Property listing reference |
| `auctioneer_id` | bigint | NULLABLE, FK | Auctioneer reference |
| `auction_date` | datetime | NOT NULL | Auction date/time |
| `auction_location` | varchar(255) | NULLABLE | Auction venue |
| `reserve_price` | decimal(12,2) | NULLABLE | Reserve price |
| `bidder_registration_required` | boolean | NOT NULL, DEFAULT true | Registration required |
| `result` | varchar(50) | NULLABLE | Sold/Passed in/Withdrawn |
| `sold_price` | decimal(12,2) | NULLABLE | Final sold price |
| `bidder_count` | integer | NULLABLE | Number of bidders |
| `highest_bid` | decimal(12,2) | NULLABLE | Highest bid amount |
| `notes` | text | NULLABLE | Auction notes |
| `auction_method` | varchar(50) | NULLABLE | In-room/On-site/Online |
| `online_auction_url` | varchar(255) | NULLABLE | Online auction link |
| `stream_url` | varchar(255) | NULLABLE | Livestream URL |
| `auction_authority_signed` | boolean | NOT NULL, DEFAULT false | Authority signed flag |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `trust_account_transactions` Table
Tracks transactions in the agent's trust account (required by Australian regulations).

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `transaction_date` | date | NOT NULL | Transaction date |
| `reference_no` | varchar(100) | NOT NULL | Reference number |
| `description` | varchar(255) | NOT NULL | Transaction description |
| `transaction_type` | varchar(50) | NOT NULL | Deposit/Withdrawal/Transfer |
| `amount` | decimal(12,2) | NOT NULL | Transaction amount |
| `property_listing_id` | bigint | NULLABLE, FK | Property reference |
| `contact_id` | bigint | NULLABLE, FK | Contact reference |
| `deal_id` | bigint | NULLABLE, FK | Deal reference |
| `receipt_number` | varchar(100) | NULLABLE | Receipt number |
| `trust_account_id` | varchar(100) | NOT NULL | Trust account identifier |
| `processed_by` | bigint | NOT NULL, FK | User who processed |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Deal Management

#### `deals` Table
Stores sales opportunities and property transactions.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `property_listing_id` | bigint | NULLABLE, FK | Property listing reference |
| `property_unit_id` | bigint | NULLABLE, FK | Property unit reference |
| `project_id` | bigint | NULLABLE, FK | Project reference |
| `agent_id` | bigint | NULLABLE, FK | Primary agent |
| `assigned_to` | bigint | NULLABLE, FK | Assigned user |
| `vendor_id` | bigint | NULLABLE, FK | Vendor/seller contact |
| `title` | varchar(255) | NOT NULL | Deal title |
| `description` | text | NULLABLE | Deal description |
| `purchase_price` | decimal(12,2) | NULLABLE | Purchase price |
| `deposit_amount` | decimal(12,2) | NULLABLE | Deposit amount |
| `deposit_paid_date` | date | NULLABLE | Deposit payment date |
| `deposit_held_by` | varchar(100) | NULLABLE | Deposit holder (agent/solicitor) |
| `contract_date` | date | NULLABLE | Contract date |
| `contract_type` | varchar(50) | NULLABLE | Contract type |
| `exchange_date` | date | NULLABLE | Contract exchange date |
| `finance_due_date` | date | NULLABLE | Finance due date |
| `finance_approved_date` | date | NULLABLE | Finance approval date |
| `building_due_date` | date | NULLABLE | Building/pest due date |
| `settlement_date` | date | NULLABLE | Settlement date |
| `cooling_off_period_end` | date | NULLABLE | End of cooling off |
| `status` | varchar(50) | NOT NULL | Deal status |
| `stage` | varchar(50) | NOT NULL | Pipeline stage |
| `seller_legal_representative` | varchar(255) | NULLABLE | Vendor's solicitor |
| `buyer_legal_representative` | varchar(255) | NULLABLE | Buyer's solicitor |
| `conveyancer` | varchar(255) | NULLABLE | Conveyancer |
| `finance_provider` | varchar(255) | NULLABLE | Finance provider |
| `commission_percentage` | decimal(5,2) | NULLABLE | Commission percentage |
| `commission_amount` | decimal(12,2) | NULLABLE | Commission amount |
| `gst_inclusive` | boolean | NOT NULL, DEFAULT true | GST inclusive flag |
| `custom_fields` | json | NULLABLE | Custom field values |
| `tags` | json | NULLABLE | Tags |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `appraisals` Table
Stores property appraisals and valuation data.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `property_unit_id` | bigint | NULLABLE, FK | Property unit reference |
| `contact_id` | bigint | NOT NULL, FK | Property owner |
| `agent_id` | bigint | NOT NULL, FK | Appraising agent |
| `appraisal_date` | date | NOT NULL | Appraisal date |
| `appraisal_type` | varchar(50) | NOT NULL | Sale/Rental appraisal |
| `min_value` | decimal(12,2) | NULLABLE | Minimum value |
| `max_value` | decimal(12,2) | NULLABLE | Maximum value |
| `suggested_list_price` | decimal(12,2) | NULLABLE | Suggested list price |
| `confidence_level` | varchar(50) | NULLABLE | Agent confidence |
| `comparable_properties` | json | NULLABLE | Comparable properties |
| `market_conditions` | text | NULLABLE | Market assessment |
| `improvements_suggested` | text | NULLABLE | Suggested improvements |
| `commission_proposal` | decimal(5,2) | NULLABLE | Proposed commission rate |
| `marketing_budget` | decimal(12,2) | NULLABLE | Suggested marketing budget |
| `notes` | text | NULLABLE | Appraisal notes |
| `status` | varchar(50) | NOT NULL | Status |
| `result` | varchar(50) | NULLABLE | Won/Lost/Pending |
| `listing_id` | bigint | NULLABLE, FK | Resulting listing |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `deal_conditions` Table
Tracks contract conditions and contingencies.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `deal_id` | bigint | NOT NULL, FK | Deal reference |
| `condition_type` | varchar(50) | NOT NULL | Finance/Building/Pest/Other |
| `description` | text | NOT NULL | Condition description |
| `due_date` | date | NOT NULL | Condition due date |
| `status` | varchar(50) | NOT NULL | Pending/Satisfied/Failed |
| `satisfied_date` | date | NULLABLE | Date condition met |
| `notes` | text | NULLABLE | Condition notes |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Task Management

#### `tasks` Table
Stores tasks related to deals, contacts, etc.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
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
| `recurrence_pattern` | varchar(100) | NULLABLE | Recurrence schedule |
| `reminder_at` | timestamp | NULLABLE | Reminder time |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Communication Management

#### `interactions` Table
Stores client/prospect communications and activities.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `contact_id` | bigint | NOT NULL, FK | Contact reference |
| `user_id` | bigint | NOT NULL, FK | User who logged interaction |
| `agent_id` | bigint | NULLABLE, FK | Agent reference |
| `project_id` | bigint | NULLABLE, FK | Project reference |
| `property_unit_id` | bigint | NULLABLE, FK | Property reference |
| `deal_id` | bigint | NULLABLE, FK | Deal reference |
| `type` | varchar(50) | NOT NULL | Interaction type |
| `medium` | varchar(50) | NULLABLE | Communication medium |
| `direction` | varchar(20) | NULLABLE | Inbound/outbound |
| `subject` | varchar(255) | NULLABLE | Interaction subject |
| `content` | text | NULLABLE | Interaction content |
| `occurred_at` | timestamp | NOT NULL | When interaction occurred |
| `duration_minutes` | integer | NULLABLE | Duration in minutes |
| `location` | varchar(255) | NULLABLE | Location |
| `result` | varchar(100) | NULLABLE | Result/outcome |
| `next_steps` | text | NULLABLE | Follow-up actions |
| `is_important` | boolean | NOT NULL, DEFAULT false | Important flag |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Financial Management

#### `commissions` Table
Enhanced commission tracking for real estate deals.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `deal_id` | bigint | NOT NULL, FK | Associated deal |
| `agent_id` | bigint | NULLABLE, FK | Primary agent |
| `invoice_id` | bigint | NULLABLE, FK | Associated invoice |
| `amount` | decimal(12,2) | NOT NULL | Commission amount |
| `rate` | decimal(5,2) | NULLABLE | Commission rate percentage |
| `base_amount` | decimal(12,2) | NULLABLE | Amount commission is based on |
| `commission_type` | varchar(50) | NOT NULL | Percentage/fixed amount |
| `status` | varchar(50) | NOT NULL | Commission status |
| `calculation_method` | varchar(50) | NULLABLE | How commission is calculated |
| `payment_date` | date | NULLABLE | Payment date |
| `gst_amount` | decimal(12,2) | NULLABLE | GST amount |
| `notes` | text | NULLABLE | Notes |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `commission_splits` Table
Stores commission distribution among multiple parties.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `commission_id` | bigint | NOT NULL, FK | Parent commission |
| `recipient_id` | bigint | NOT NULL, FK | Recipient (user/agent) |
| `recipient_type` | varchar(50) | NOT NULL | Recipient entity type |
| `amount` | decimal(12,2) | NOT NULL | Split amount |
| `percentage` | decimal(5,2) | NULLABLE | Split percentage |
| `notes` | text | NULLABLE | Notes |
| `status` | varchar(50) | NOT NULL | Payment status |
| `payment_date` | date | NULLABLE | Payment date |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `invoices` Table
Stores invoice data for Xero integration.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `deal_id` | bigint | NULLABLE, FK | Associated deal |
| `contact_id` | bigint | NULLABLE, FK | Associated contact |
| `invoice_number` | varchar(100) | NOT NULL | Invoice number |
| `xero_invoice_id` | varchar(255) | NULLABLE | Xero invoice ID |
| `xero_contact_id` | varchar(255) | NULLABLE | Xero contact ID |
| `invoice_date` | date | NOT NULL | Issue date |
| `due_date` | date | NOT NULL | Due date |
| `amount` | decimal(12,2) | NOT NULL | Total amount |
| `tax_amount` | decimal(12,2) | NOT NULL | GST amount |
| `status` | varchar(50) | NOT NULL | Invoice status |
| `currency` | varchar(3) | NOT NULL, DEFAULT 'AUD' | Currency code |
| `reference` | varchar(255) | NULLABLE | Reference number |
| `description` | text | NULLABLE | Invoice description |
| `payment_terms` | varchar(100) | NULLABLE | Payment terms |
| `payment_method` | varchar(50) | NULLABLE | Payment method |
| `paid_amount` | decimal(12,2) | NULLABLE | Amount paid to date |
| `paid_date` | date | NULLABLE | Date fully paid |
| `last_synced_at` | timestamp | NULLABLE | Last Xero sync |
| `extra_attributes` | json | NULLABLE | Additional attributes |
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
| `tax_type` | varchar(50) | NOT NULL, DEFAULT 'GST' | Tax type |
| `tax_rate` | decimal(5,2) | NOT NULL | Tax rate |
| `tax_amount` | decimal(12,2) | NOT NULL | Tax amount |
| `xero_line_item_id` | varchar(255) | NULLABLE | Xero line ID |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |

#### `payments` Table
Tracks deposit and commission payments.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `payable_id` | bigint | NOT NULL | Polymorphic ID |
| `payable_type` | varchar(255) | NOT NULL | Polymorphic type |
| `payment_method` | varchar(50) | NOT NULL | Payment method |
| `amount` | decimal(12,2) | NOT NULL | Payment amount |
| `currency` | varchar(3) | NOT NULL, DEFAULT 'AUD' | Currency code |
| `payment_date` | date | NOT NULL | Payment date |
| `reference` | varchar(255) | NULLABLE | Payment reference |
| `notes` | text | NULLABLE | Payment notes |
| `status` | varchar(50) | NOT NULL | Payment status |
| `processed_by` | bigint | NULLABLE, FK | User who processed |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Media & Document Management

### Spatie MediaLibrary Integration

The `media` table (Spatie MediaLibrary) serves as the foundation for all media and document management:

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `model_type` | varchar(255) | NOT NULL | Polymorphic type |
| `model_id` | bigint | NOT NULL | Polymorphic ID |
| `uuid` | uuid | NULLABLE, UNIQUE | External identifier |
| `collection_name` | varchar(255) | NOT NULL | Collection category |
| `name` | varchar(255) | NOT NULL | Media name |
| `file_name` | varchar(255) | NOT NULL | Original file name |
| `mime_type` | varchar(255) | NULLABLE | MIME type |
| `disk` | varchar(255) | NOT NULL | Storage disk |
| `conversions_disk` | varchar(255) | NULLABLE | Conversions disk |
| `size` | bigint | NOT NULL | File size in bytes |
| `manipulations` | json | NOT NULL | Image manipulations |
| `custom_properties` | json | NOT NULL | Custom properties |
| `generated_conversions` | json | NOT NULL | Generated conversions |
| `responsive_images` | json | NOT NULL | Responsive variants |
| `order_column` | integer | NULLABLE | Sort order |
| `created_at` | timestamp | NULLABLE | Creation timestamp |
| `updated_at` | timestamp | NULLABLE | Update timestamp |

### Media Collections & Extended Properties

The system defines several media collections for different entity types:

1. **Property Images**: `property_images`
   - Main property photos, floor plans, site plans
   - Custom properties: `is_featured`, `sort_order`, `caption`

2. **Developer Assets**: `developer_assets`
   - Developer logos, marketing material
   - Custom properties: `asset_type`, `dimensions`

3. **Project Media**: `project_media`
   - Project photos, 3D renders, video tours
   - Custom properties: `media_type`, `is_hero`, `sort_order`

4. **User Avatars**: `avatars`
   - User profile photos
   - Custom properties: `crop_coordinates`

5. **Document Files**: `documents`
   - Contract PDFs, brochures, disclosure documents
   - Custom properties: `document_type`, `version`, `expiry_date`

#### `property_media_metadata` Table
Stores additional metadata for property media beyond what Spatie MediaLibrary provides.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `media_id` | bigint | NOT NULL, FK | Media foreign key |
| `title` | varchar(255) | NULLABLE | Media title |
| `description` | text | NULLABLE | Detailed description |
| `taken_at` | timestamp | NULLABLE | Photo capture date |
| `location` | point | NULLABLE | Geolocation data |
| `photographer` | varchar(255) | NULLABLE | Photographer name |
| `copyright` | varchar(255) | NULLABLE | Copyright info |
| `is_approved` | boolean | NOT NULL, DEFAULT false | Approval status |
| `approved_by` | bigint | NULLABLE, FK | Approving user |
| `approved_at` | timestamp | NULLABLE | Approval date |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

### Document Management Extensions

The system extends the Spatie MediaLibrary with additional document-specific functionality:

#### `document_references` Table
Extends the functionality of Spatie MediaLibrary with real estate specific document metadata.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `media_id` | bigint | NOT NULL, FK | Media table FK |
| `document_type` | varchar(100) | NOT NULL | Document type |
| `reference_no` | varchar(100) | NULLABLE | Document reference number |
| `version` | varchar(50) | NULLABLE | Document version |
| `status` | varchar(50) | NOT NULL | Document status |
| `is_template` | boolean | NOT NULL, DEFAULT false | Template flag |
| `is_signed` | boolean | NOT NULL, DEFAULT false | Signed status |
| `expires_at` | date | NULLABLE | Expiry date |
| `signed_at` | timestamp | NULLABLE | Signing timestamp |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `document_shares` Table
Tracks document sharing with clients/external users.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `media_id` | bigint | NOT NULL, FK | Media ID |
| `shared_by` | bigint | NOT NULL, FK | User who shared |
| `shared_with` | bigint | NULLABLE, FK | User shared with |
| `email` | varchar(255) | NULLABLE | External email |
| `access_token` | varchar(100) | NULLABLE | Access token |
| `permissions` | varchar(50) | NOT NULL | View/download/edit |
| `expires_at` | timestamp | NULLABLE | Access expiry |
| `last_accessed_at` | timestamp | NULLABLE | Last access time |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `document_templates` Table
Predefined document templates for sales contracts, etc.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `name` | varchar(255) | NOT NULL | Template name |
| `description` | text | NULLABLE | Template description |
| `media_id` | bigint | NULLABLE, FK | Associated file if any |
| `document_type` | varchar(100) | NOT NULL | Document type |
| `content` | text | NULLABLE | Template content |
| `placeholders` | json | NULLABLE | Template placeholders |
| `is_active` | boolean | NOT NULL, DEFAULT true | Active status |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Portal Integrations

#### `portal_integrations` Table
Stores configuration for Australian real estate portal integrations.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `name` | varchar(255) | NOT NULL | Integration name |
| `portal_type` | varchar(100) | NOT NULL | Domain/REA/Other |
| `credentials` | json | NULLABLE | Encrypted credentials |
| `settings` | json | NULLABLE | Configuration settings |
| `is_active` | boolean | NOT NULL, DEFAULT true | Active status |
| `last_sync` | timestamp | NULLABLE | Last sync timestamp |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `portal_listings` Table
Tracks listings published to external portals like Domain and REA.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `property_listing_id` | bigint | NOT NULL, FK | Listing reference |
| `portal_integration_id` | bigint | NOT NULL, FK | Integration reference |
| `external_id` | varchar(255) | NULLABLE | External listing ID |
| `listing_url` | varchar(512) | NULLABLE | Published URL |
| `listing_tier` | varchar(100) | NULLABLE | Premium/Highlight/Standard |
| `publication_status` | varchar(50) | NOT NULL | Publication status |
| `first_published_at` | timestamp | NULLABLE | First publish date |
| `last_published_at` | timestamp | NULLABLE | Last publish date |
| `expiry_date` | date | NULLABLE | Listing expiry date |
| `view_statistics` | json | NULLABLE | View/click statistics |
| `inquiry_count` | integer | NULLABLE, DEFAULT 0 | Inquiry count |
| `sync_issues` | json | NULLABLE | Synchronization issues |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Activity Logging

The system leverages Spatie's Laravel ActivityLog for tracking user activities and system events.

### Spatie ActivityLog Integration

The `activity_log` table (Spatie ActivityLog) is used with the following structure:

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `log_name` | varchar(255) | NULLABLE, INDEX | Log category |
| `description` | text | NOT NULL | Activity description |
| `subject_type` | varchar(255) | NULLABLE | Polymorphic subject type |
| `subject_id` | bigint | NULLABLE | Polymorphic subject ID |
| `causer_type` | varchar(255) | NULLABLE | Polymorphic causer type |
| `causer_id` | bigint | NULLABLE | Polymorphic causer ID |
| `properties` | json | NULLABLE | Additional data |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |

### Activity Log Categories

The system defines several activity log categories for tracking different types of activities:

1. **Authentication**: `auth` - User login, logout, failed attempts
2. **User Management**: `user` - User creation, updates, permissions
3. **Contacts**: `contact` - Lead/client interactions, updates
4. **Properties**: `property` - Property listing changes, unit updates
5. **Deals**: `deal` - Deal progress, stage changes, document signing
6. **System**: `system` - System configuration, maintenance
7. **Tenants**: `tenant` - Tenant creation, plan changes
8. **Financial**: `financial` - Invoices, payments, commissions

### Implementation

Eloquent models implement the `LogsActivity` trait from the Spatie package to automatically log changes:

```php
use Spatie\Activitylog\Traits\LogsActivity;

class Deal extends Model
{
    use LogsActivity;
    
    protected static $logAttributes = [
        'contact_id', 'property_unit_id', 'agent_id', 'status',
        'stage', 'purchase_price', 'settlement_date'
    ];
    
    protected static $logName = 'deal';
    
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Deal was {$eventName}";
    }
}

## Australian Regulatory & Compliance

#### `agent_licences` Table
Tracks real estate agent licenses and registrations required by Australian state/territory regulations.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `user_id` | bigint | NOT NULL, FK | User reference |
| `licence_number` | varchar(100) | NOT NULL | License number |
| `licence_type` | varchar(100) | NOT NULL | Type of license |
| `state_jurisdiction` | varchar(50) | NOT NULL | State/Territory |
| `issuing_body` | varchar(255) | NOT NULL | Regulatory authority |
| `issue_date` | date | NOT NULL | Date of issue |
| `expiry_date` | date | NOT NULL | Expiry date |
| `status` | varchar(50) | NOT NULL | Active/Suspended/Expired |
| `conditions` | text | NULLABLE | License conditions |
| `documents_media_id` | bigint | NULLABLE, FK | License documents |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `compliance_checklist_templates` Table
Defines compliance checklists for different transaction types.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `name` | varchar(255) | NOT NULL | Template name |
| `checklist_type` | varchar(100) | NOT NULL | Sale/Lease/Auction/PM |
| `state_jurisdiction` | varchar(50) | NOT NULL | State/Territory |
| `items` | json | NOT NULL | Checklist items |
| `is_active` | boolean | NOT NULL, DEFAULT true | Active status |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `compliance_checklists` Table
Instance of compliance checklist for a specific transaction.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `template_id` | bigint | NOT NULL, FK | Template reference |
| `checklistable_id` | bigint | NOT NULL | Polymorphic ID |
| `checklistable_type` | varchar(255) | NOT NULL | Polymorphic type |
| `completed_items` | json | NULLABLE | Completed checkpoints |
| `status` | varchar(50) | NOT NULL | Compliance status |
| `completed_by` | bigint | NULLABLE, FK | User who completed |
| `completed_at` | timestamp | NULLABLE | Completion date |
| `notes` | text | NULLABLE | Compliance notes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `trust_accounts` Table
Tracks agency trust accounts required by Australian regulations.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `account_name` | varchar(255) | NOT NULL | Account name |
| `account_number` | varchar(100) | NOT NULL | Account number |
| `financial_institution` | varchar(255) | NOT NULL | Bank name |
| `bsb` | varchar(10) | NOT NULL | BSB number |
| `account_type` | varchar(50) | NOT NULL | Sales/Rental/General |
| `state_jurisdiction` | varchar(50) | NOT NULL | State/Territory |
| `is_active` | boolean | NOT NULL, DEFAULT true | Active status |
| `notes` | text | NULLABLE | Account notes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Commission & Financial

#### `commission_structures` Table
Defines commission structures for sales and property management.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `name` | varchar(255) | NOT NULL | Structure name |
| `structure_type` | varchar(50) | NOT NULL | Sales/PM/Referral |
| `commission_type` | varchar(50) | NOT NULL | Flat/Percentage/Tiered |
| `base_rate` | decimal(5,2) | NULLABLE | Base percentage |
| `base_amount` | decimal(12,2) | NULLABLE | Base flat amount |
| `tiers` | json | NULLABLE | Commission tiers |
| `is_active` | boolean | NOT NULL, DEFAULT true | Active status |
| `effective_from` | date | NOT NULL | Start date |
| `effective_to` | date | NULLABLE | End date |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `agent_commissions` Table
Tracks individual agent commission allocations.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `commissionable_id` | bigint | NOT NULL | Polymorphic ID |
| `commissionable_type` | varchar(255) | NOT NULL | Polymorphic type |
| `user_id` | bigint | NOT NULL, FK | Agent reference |
| `structure_id` | bigint | NOT NULL, FK | Commission structure |
| `commission_percentage` | decimal(5,2) | NULLABLE | Commission percentage |
| `commission_amount` | decimal(12,2) | NULLABLE | Commission amount |
| `override_reason` | text | NULLABLE | Reason for override |
| `status` | varchar(50) | NOT NULL | Status |
| `paid_date` | date | NULLABLE | Payment date |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Strata Management

#### `strata_schemes` Table
Tracks body corporate/owners corporation information for strata properties.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `name` | varchar(255) | NOT NULL | Scheme name |
| `plan_number` | varchar(100) | NOT NULL | Strata plan number |
| `number_of_lots` | integer | NOT NULL | Total lots |
| `address` | json | NOT NULL | Scheme address |
| `strata_manager` | varchar(255) | NULLABLE | Manager name |
| `management_company` | varchar(255) | NULLABLE | Company name |
| `contact_details` | json | NULLABLE | Contact information |
| `state_jurisdiction` | varchar(50) | NOT NULL | State/Territory |
| `year_registered` | integer | NULLABLE | Registration year |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `strata_property_units` Table
Links property units to strata schemes.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `strata_scheme_id` | bigint | NOT NULL, FK | Strata scheme reference |
| `property_unit_id` | bigint | NOT NULL, FK | Property unit reference |
| `lot_number` | varchar(50) | NOT NULL | Lot number |
| `unit_entitlement` | decimal(10,2) | NULLABLE | Unit entitlement |
| `current_levies` | json | NULLABLE | Current levy amounts |
| `special_conditions` | text | NULLABLE | Special conditions |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `strata_meetings` Table
Tracks strata meetings and minutes.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `strata_scheme_id` | bigint | NOT NULL, FK | Strata scheme reference |
| `meeting_type` | varchar(100) | NOT NULL | AGM/EGM/Committee |
| `meeting_date` | datetime | NOT NULL | Meeting date/time |
| `location` | varchar(255) | NULLABLE | Meeting location |
| `agenda` | text | NULLABLE | Meeting agenda |
| `minutes_media_id` | bigint | NULLABLE, FK | Minutes document |
| `attendees` | json | NULLABLE | Attendees |
| `notes` | text | NULLABLE | Meeting notes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Rental Property Management

Australian real estate agencies typically manage rental properties on behalf of property investors. This section covers the rental property management functionality.

#### `rental_properties` Table
Stores properties under management for rental.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `property_unit_id` | bigint | NOT NULL, FK | Property unit reference |
| `owner_id` | bigint | NOT NULL, FK | Property owner contact |
| `property_manager_id` | bigint | NULLABLE, FK | Property manager user |
| `management_agreement_start` | date | NOT NULL | Agreement start date |
| `management_agreement_end` | date | NULLABLE | Agreement end date |
| `management_fee_percentage` | decimal(5,2) | NOT NULL | Management fee % |
| `letting_fee_amount` | decimal(12,2) | NULLABLE | Letting fee |
| `letting_fee_type` | varchar(50) | NULLABLE | Fixed/Percentage |
| `rental_amount` | decimal(12,2) | NOT NULL | Weekly rent amount |
| `payment_frequency` | varchar(50) | NOT NULL, DEFAULT 'weekly' | Rent payment frequency |
| `bond_amount` | decimal(12,2) | NOT NULL | Bond amount |
| `bond_lodgement_number` | varchar(100) | NULLABLE | Bond authority reference |
| `available_from` | date | NULLABLE | Availability date |
| `lease_term_months` | integer | NULLABLE | Lease term length |
| `pet_policy` | varchar(50) | NULLABLE | Pet policy |
| `furnishing_status` | varchar(50) | NULLABLE | Furnished/Unfurnished |
| `special_conditions` | text | NULLABLE | Special conditions |
| `status` | varchar(50) | NOT NULL | Active/Inactive/Terminated |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `tenancies` Table
Stores tenant leases and agreements.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `rental_property_id` | bigint | NOT NULL, FK | Rental property reference |
| `primary_tenant_id` | bigint | NOT NULL, FK | Primary tenant contact |
| `lease_type` | varchar(50) | NOT NULL | Fixed/Periodic/Short-term |
| `start_date` | date | NOT NULL | Lease start date |
| `end_date` | date | NULLABLE | Lease end date |
| `rent_amount` | decimal(12,2) | NOT NULL | Rent amount |
| `payment_frequency` | varchar(50) | NOT NULL | Weekly/Fortnightly/Monthly |
| `payment_day` | varchar(50) | NOT NULL | Payment due day |
| `bond_amount` | decimal(12,2) | NOT NULL | Bond amount |
| `bond_lodged_date` | date | NULLABLE | Bond lodgement date |
| `bond_number` | varchar(100) | NULLABLE | Bond reference number |
| `status` | varchar(50) | NOT NULL | Active/Notice/Ended |
| `notice_given_date` | date | NULLABLE | Termination notice date |
| `notice_period_days` | integer | NULLABLE | Notice period length |
| `vacate_date` | date | NULLABLE | Expected vacate date |
| `special_conditions` | text | NULLABLE | Special conditions |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `tenancy_tenants` Table
Stores multiple tenants for a single tenancy.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `tenancy_id` | bigint | NOT NULL, FK | Tenancy reference |
| `contact_id` | bigint | NOT NULL, FK | Tenant contact |
| `is_primary` | boolean | NOT NULL, DEFAULT false | Primary tenant flag |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |

#### `inspections` Table
Stores property inspections (routine, ingoing, outgoing).

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `rental_property_id` | bigint | NOT NULL, FK | Rental property reference |
| `tenancy_id` | bigint | NULLABLE, FK | Tenancy reference |
| `inspector_id` | bigint | NOT NULL, FK | User who inspected |
| `inspection_type` | varchar(50) | NOT NULL | Routine/Ingoing/Outgoing |
| `scheduled_date` | datetime | NOT NULL | Scheduled date/time |
| `completed_date` | datetime | NULLABLE | Completion date/time |
| `report_media_id` | bigint | NULLABLE, FK | Report document |
| `overall_condition` | varchar(50) | NULLABLE | Property condition |
| `tenant_present` | boolean | NULLABLE | Tenant present flag |
| `notes` | text | NULLABLE | Inspection notes |
| `action_required` | boolean | NOT NULL, DEFAULT false | Action needed flag |
| `followup_date` | date | NULLABLE | Follow-up date |
| `status` | varchar(50) | NOT NULL | Scheduled/Completed/Cancelled |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `maintenance_requests` Table
Stores property maintenance and repair requests.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `rental_property_id` | bigint | NOT NULL, FK | Rental property reference |
| `tenancy_id` | bigint | NULLABLE, FK | Tenancy reference |
| `requested_by_id` | bigint | NOT NULL, FK | Contact who requested |
| `request_type` | varchar(50) | NOT NULL | Type of request |
| `title` | varchar(255) | NOT NULL | Request title |
| `description` | text | NOT NULL | Issue description |
| `urgency` | varchar(50) | NOT NULL | Urgency level |
| `status` | varchar(50) | NOT NULL | Request status |
| `assigned_to` | bigint | NULLABLE, FK | User assigned to |
| `owner_notified_date` | datetime | NULLABLE | Owner notification date |
| `owner_approval_date` | datetime | NULLABLE | Owner approval date |
| `owner_approved` | boolean | NULLABLE | Owner approval status |
| `estimated_cost` | decimal(12,2) | NULLABLE | Estimated cost |
| `actual_cost` | decimal(12,2) | NULLABLE | Actual cost |
| `completed_date` | datetime | NULLABLE | Completion date |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `rental_payments` Table
Tracks rent payments from tenants.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `tenancy_id` | bigint | NOT NULL, FK | Tenancy reference |
| `payment_date` | date | NOT NULL | Payment date |
| `period_start` | date | NOT NULL | Rent period start |
| `period_end` | date | NOT NULL | Rent period end |
| `amount` | decimal(12,2) | NOT NULL | Payment amount |
| `payment_method` | varchar(50) | NOT NULL | Payment method |
| `reference` | varchar(255) | NULLABLE | Payment reference |
| `received_by` | bigint | NULLABLE, FK | User who received |
| `notes` | text | NULLABLE | Payment notes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |

## Marketing Management

#### `marketing_campaigns` Table
Tracks marketing campaigns for properties and developments.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `name` | varchar(255) | NOT NULL | Campaign name |
| `description` | text | NULLABLE | Campaign description |
| `campaign_type` | varchar(50) | NOT NULL | Campaign type |
| `target_audience` | json | NULLABLE | Target audience |
| `budget` | decimal(12,2) | NULLABLE | Campaign budget |
| `start_date` | date | NOT NULL | Start date |
| `end_date` | date | NULLABLE | End date |
| `status` | varchar(50) | NOT NULL | Campaign status |
| `created_by` | bigint | NOT NULL, FK | Creator user |
| `manager_id` | bigint | NULLABLE, FK | Campaign manager |
| `kpis` | json | NULLABLE | Success metrics |
| `results` | json | NULLABLE | Campaign results |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `campaign_items` Table
Links campaigns to properties, projects, or listings.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `campaign_id` | bigint | NOT NULL, FK | Campaign reference |
| `itemable_id` | bigint | NOT NULL | Polymorphic ID |
| `itemable_type` | varchar(255) | NOT NULL | Polymorphic type |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |

#### `marketing_activities` Table
Tracks specific marketing activities within a campaign.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `campaign_id` | bigint | NULLABLE, FK | Campaign reference |
| `activity_type` | varchar(50) | NOT NULL | Activity type |
| `name` | varchar(255) | NOT NULL | Activity name |
| `description` | text | NULLABLE | Activity description |
| `channel` | varchar(100) | NOT NULL | Marketing channel |
| `cost` | decimal(12,2) | NULLABLE | Activity cost |
| `start_date` | datetime | NOT NULL | Start date/time |
| `end_date` | datetime | NULLABLE | End date/time |
| `status` | varchar(50) | NOT NULL | Activity status |
| `assigned_to` | bigint | NULLABLE, FK | Assigned user |
| `metrics` | json | NULLABLE | Performance metrics |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Tenant Management

#### `tenant_settings` Table
Stores tenant-specific settings and configuration.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `category` | varchar(100) | NOT NULL | Setting category |
| `key` | varchar(255) | NOT NULL | Setting key |
| `value` | text | NULLABLE | Setting value |
| `encrypted` | boolean | NOT NULL, DEFAULT false | Encryption flag |
| `is_private` | boolean | NOT NULL, DEFAULT false | Private setting flag |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `tenant_subscription_plans` Table
Defines available subscription plans for tenants.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `name` | varchar(255) | NOT NULL | Plan name |
| `description` | text | NULLABLE | Plan description |
| `price` | decimal(10,2) | NOT NULL | Plan price |
| `billing_cycle` | varchar(50) | NOT NULL | Monthly/Annual/etc. |
| `features` | json | NOT NULL | Plan features |
| `is_white_label` | boolean | NOT NULL, DEFAULT false | White-label plan flag |
| `max_users` | integer | NULLABLE | Max users allowed |
| `max_properties` | integer | NULLABLE | Max properties allowed |
| `api_access` | boolean | NOT NULL, DEFAULT false | API access flag |
| `is_active` | boolean | NOT NULL, DEFAULT true | Active plan flag |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## Property Management

### Property Customization & White-Labeling

#### `property_customizations` Table
Allows tenants to customize specific details of master properties.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `property_id` | bigint | NOT NULL, FK | Property reference |
| `display_name` | varchar(255) | NULLABLE | Custom display name |
| `custom_description` | text | NULLABLE | Custom description |
| `custom_features` | json | NULLABLE | Custom property features |
| `hide_developer` | boolean | NOT NULL, DEFAULT false | Hide developer flag |
| `custom_tags` | json | NULLABLE | Custom property tags |
| `is_featured` | boolean | NOT NULL, DEFAULT false | Featured flag |
| `is_visible` | boolean | NOT NULL, DEFAULT true | Visibility flag |
| `custom_meta_title` | varchar(255) | NULLABLE | Custom SEO title |
| `custom_meta_description` | varchar(512) | NULLABLE | Custom SEO description |
| `custom_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `property_unit_customizations` Table
Allows tenants to customize specific details of property units.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `property_unit_id` | bigint | NOT NULL, FK | Property unit reference |
| `display_name` | varchar(255) | NULLABLE | Custom display name |
| `custom_description` | text | NULLABLE | Custom description |
| `custom_features` | json | NULLABLE | Custom features |
| `custom_price` | decimal(12,2) | NULLABLE | Custom price |
| `price_display_text` | varchar(100) | NULLABLE | Custom price display |
| `is_featured` | boolean | NOT NULL, DEFAULT false | Featured flag |
| `is_visible` | boolean | NOT NULL, DEFAULT true | Visibility flag |
| `custom_sorting_order` | integer | NULLABLE | Custom sorting order |
| `custom_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `tenant_properties` Table
Stores tenant-specific properties not shared with other tenants.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `name` | varchar(255) | NOT NULL | Property name |
| `type` | varchar(100) | NOT NULL | Property type |
| `status` | varchar(50) | NOT NULL | Property status |
| `address` | json | NOT NULL | Property address |
| `location` | point | NULLABLE | Geo coordinates |
| `description` | text | NULLABLE | Property description |
| `features` | json | NULLABLE | Property features |
| `developer_id` | bigint | NULLABLE, FK | Developer reference |
| `is_published` | boolean | NOT NULL, DEFAULT false | Publication status |
| `custom_fields` | json | NULLABLE | Custom field values |
| `tags` | json | NULLABLE | Property tags |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `tenant_property_units` Table
Stores tenant-specific property units not shared with other tenants.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `uuid` | uuid | NOT NULL, UNIQUE | External identifier |
| `tenant_property_id` | bigint | NOT NULL, FK | Tenant property reference |
| `unit_number` | varchar(50) | NULLABLE | Unit/apartment number |
| `title_reference` | varchar(100) | NULLABLE | Title reference |
| `land_area` | decimal(12,2) | NULLABLE | Land area (sqm) |
| `building_area` | decimal(12,2) | NULLABLE | Building area (sqm) |
| `bedrooms` | integer | NULLABLE | Number of bedrooms |
| `bathrooms` | decimal(5,1) | NULLABLE | Number of bathrooms |
| `parking_spaces` | integer | NULLABLE | Parking spaces |
| `zoning` | varchar(100) | NULLABLE | Property zoning |
| `year_built` | integer | NULLABLE | Year built |
| `energy_rating` | decimal(3,1) | NULLABLE | Energy efficiency rating |
| `nbn_connection_type` | varchar(50) | NULLABLE | NBN connection type |
| `walkability_score` | integer | NULLABLE | Walkability score (0-100) |
| `school_zones` | json | NULLABLE | School catchment zones |
| `sustainability_features` | json | NULLABLE | Solar, batteries, etc. |
| `floor_plan_media_id` | bigint | NULLABLE, FK | Floor plan reference |
| `unit_status` | varchar(50) | NOT NULL | Status |
| `price` | decimal(12,2) | NULLABLE | Price or value |
| `custom_fields` | json | NULLABLE | Custom field values |
| `extra_attributes` | json | NULLABLE | Additional attributes |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `property_notes` Table
Stores tenant-specific notes for properties and property units.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `user_id` | bigint | NOT NULL, FK | Author user reference |
| `noteable_id` | bigint | NOT NULL | Polymorphic ID |
| `noteable_type` | varchar(255) | NOT NULL | Polymorphic type |
| `note` | text | NOT NULL | Note content |
| `is_private` | boolean | NOT NULL, DEFAULT true | Private note flag |
| `is_published` | boolean | NOT NULL, DEFAULT false | Publication status |
| `tags` | json | NULLABLE | Note tags |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## White-Label Configuration

#### `white_label_settings` Table
Stores white-label configuration for tenants with white-label plans.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK, UNIQUE | Tenant foreign key |
| `company_name` | varchar(255) | NOT NULL | Company name |
| `logo_media_id` | bigint | NULLABLE, FK | Logo reference |
| `favicon_media_id` | bigint | NULLABLE, FK | Favicon reference |
| `primary_color` | varchar(20) | NULLABLE | Primary brand color |
| `secondary_color` | varchar(20) | NULLABLE | Secondary brand color |
| `custom_domain` | varchar(255) | NULLABLE, UNIQUE | Custom domain |
| `domain_verified` | boolean | NOT NULL, DEFAULT false | Domain verification |
| `email_from_name` | varchar(255) | NULLABLE | Email sender name |
| `email_from_address` | varchar(255) | NULLABLE | Email sender address |
| `custom_email_templates` | json | NULLABLE | Email template overrides |
| `custom_css` | text | NULLABLE | Custom CSS |
| `custom_js` | text | NULLABLE | Custom JavaScript |
| `api_documentation_url` | varchar(255) | NULLABLE | API docs URL |
| `support_email` | varchar(255) | NULLABLE | Support email |
| `support_phone` | varchar(100) | NULLABLE | Support phone |
| `is_active` | boolean | NOT NULL, DEFAULT true | Active status |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

#### `api_credentials` Table
Stores tenant-specific API credentials for white-label and other integrations.

| Column | Type | Constraints | Description |
|--------|------|------------|-------------|
| `id` | bigint | PK, AUTO_INCREMENT | Primary key |
| `tenant_id` | bigint | NOT NULL, FK | Tenant foreign key |
| `service_name` | varchar(255) | NOT NULL | Service name |
| `api_key` | text | NULLABLE | Encrypted API key |
| `api_secret` | text | NULLABLE | Encrypted API secret |
| `other_credentials` | json | NULLABLE | Additional credentials |
| `is_active` | boolean | NOT NULL, DEFAULT true | Active status |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Update timestamp |
| `deleted_at` | timestamp | NULLABLE | Soft delete timestamp |

## White-Label and Tenant Customization Tables

### `white_label_settings`
- `id` - bigint unsigned (PK)
- `tenant_id` - bigint unsigned (FK)
- `custom_domain` - varchar(255)
- `logo` - varchar(255)
- `primary_color` - varchar(255)
- `secondary_color` - varchar(255)
- `accent_color` - varchar(255)
- `email_sender_name` - varchar(255)
- `email_sender_address` - varchar(255)
- `footer_text` - text
- `custom_css` - longtext
- `custom_js` - longtext
- `favicon` - varchar(255)
- `created_at` - timestamp
- `updated_at` - timestamp

### `tenant_subscription_plans`
- `id` - bigint unsigned (PK)
- `name` - varchar(255)
- `description` - text
- `monthly_price` - decimal(10,2)
- `annual_price` - decimal(10,2)
- `features` - json
- `is_white_label` - boolean
- `max_users` - integer
- `max_properties` - integer
- `created_at` - timestamp
- `updated_at` - timestamp

### `api_credentials`
- `id` - bigint unsigned (PK)
- `tenant_id` - bigint unsigned (FK)
- `api_key` - varchar(255)
- `api_secret` - varchar(255)
- `allowed_ips` - json
- `scopes` - json
- `created_at` - timestamp
- `updated_at` - timestamp
- `last_used_at` - timestamp
- `expires_at` - timestamp

### `property_customizations`
- `id` - bigint unsigned (PK)
- `tenant_id` - bigint unsigned (FK)
- `property_id` - bigint unsigned (FK)
- `custom_name` - varchar(255)
- `custom_description` - text
- `custom_features` - json
- `hide_developer` - boolean
- `custom_tags` - json
- `created_at` - timestamp
- `updated_at` - timestamp

### `property_unit_customizations`
- `id` - bigint unsigned (PK)
- `tenant_id` - bigint unsigned (FK)
- `property_unit_id` - bigint unsigned (FK)
- `custom_price` - decimal(15,2)
- `custom_display_price` - varchar(255)
- `custom_features` - json
- `is_hidden` - boolean
- `custom_availability` - varchar(255)
- `created_at` - timestamp
- `updated_at` - timestamp

### `tenant_properties`
- `id` - bigint unsigned (PK)
- `tenant_id` - bigint unsigned (FK)
- `name` - varchar(255)
- `description` - text
- `features` - json
- `location` - point
- `address` - text
- `status` - varchar(255)
- `created_at` - timestamp
- `updated_at` - timestamp

### `tenant_property_units`
- `id` - bigint unsigned (PK)
- `tenant_property_id` - bigint unsigned (FK)
- `name` - varchar(255)
- `description` - text
- `price` - decimal(15,2)
- `features` - json
- `status` - varchar(255)
- `created_at` - timestamp
- `updated_at` - timestamp

### `property_notes`
- `id` - bigint unsigned (PK)
- `tenant_id` - bigint unsigned (FK)
- `notable_type` - varchar(255)
- `notable_id` - bigint unsigned
- `user_id` - bigint unsigned (FK)
- `content` - text
- `is_public` - boolean
- `created_at` - timestamp
- `updated_at` - timestamp

### `white_label_analytics_settings`
- `id` - bigint unsigned (PK)
- `tenant_id` - bigint unsigned (FK)
- `dashboard_layout` - json
- `custom_metrics` - json
- `visible_reports` - json
- `custom_report_logo` - varchar(255)
- `report_footer` - text
- `auto_email_reports` - boolean
- `email_schedule` - json
- `report_recipients` - json
- `created_at` - timestamp
- `updated_at` - timestamp

### `property_customization_templates`
- `id` - bigint unsigned (PK)
- `tenant_id` - bigint unsigned (FK)
- `name` - varchar(255)
- `description` - text
- `template_data` - json
- `is_default` - boolean
- `created_at` - timestamp
- `updated_at` - timestamp

### `property_customization_workflows`
- `id` - bigint unsigned (PK)
- `tenant_id` - bigint unsigned (FK)
- `name` - varchar(255)
- `steps` - json
- `approvers` - json
- `status` - varchar(255)
- `created_at` - timestamp
- `updated_at` - timestamp

### `property_customization_versions`
- `id` - bigint unsigned (PK)
- `property_customization_id` - bigint unsigned (FK)
- `user_id` - bigint unsigned (FK)
- `version_number` - integer
- `changes` - json
- `created_at` - timestamp

### `tenant_currency_settings`
- `id` - bigint unsigned (PK)
- `tenant_id` - bigint unsigned (FK)
- `primary_currency` - varchar(3) DEFAULT 'AUD'
- `display_currencies` - json
- `date_format` - varchar(255)
- `measurement_unit` - varchar(255)
- `thousand_separator` - varchar(1)
- `decimal_separator` - varchar(1)
- `timezone` - varchar(255)
- `language_preferences` - json
- `created_at` - timestamp
- `updated_at` - timestamp

### `currency_conversion_rates`
- `id` - bigint unsigned (PK)
- `from_currency` - varchar(3)
- `to_currency` - varchar(3)
- `rate` - decimal(10,6)
- `source` - varchar(255)
- `fetched_at` - timestamp
- `created_at` - timestamp
- `updated_at` - timestamp

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