# Sprint 4: Property Management System

## 📅 Timeline
- **Duration**: 2 weeks
- **Sprint Goal**: Implement core property management and listings functionality

## 🏆 Epics

### Epic 1: Property Data Architecture
**Description**: Build comprehensive data models for property management

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 1.1 Create property model and migration | High | 8 | Sprint 1: 2.2, Sprint 3: 1.1 | Implement property data structure with migrations |
| 1.2.1 Implement property features system | High | 6 | 1.1.1 | Create models for property features and amenities |
| 1.2.2 Develop property status tracking | Medium | 4 | 1.1.1 | Implement property status lifecycle and history |
| 1.2.3 Create property media management | Medium | 6 | 1.1.1 | Set up media library integration for property images |
| 1.2.4 Implement property categorization | Medium | 4 | 1.1.1 | Create property types, categories, and classifications |

**Suggested Packages**:
- `spatie/laravel-medialibrary ^11.0` - [Spatie Laravel MediaLibrary](https://github.com/spatie/laravel-medialibrary) - Media management
- `spatie/laravel-enum ^3.0` - [Spatie Laravel Enum](https://github.com/spatie/laravel-enum) - Enum support for property types

### Epic 2: Property Management Services
**Description**: Create service layer for property data manipulation

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 2.1 Create property repository | High | 6 | 1.1 | Implement repository pattern for property data access |
| 2.2 Develop property service | High | 8 | 2.1 | Create service layer for property business logic |
| 2.3 Implement property search service | Medium | 8 | 2.1, 2.2 | Create advanced search and filtering for properties |
| 2.4 Create property media service | Medium | 6 | 1.4, 2.2 | Implement service for managing property images |
| 2.5 Develop property import/export service | Medium | 6 | 2.1, 2.2 | Create functionality for bulk property operations |

**Suggested Packages**:
- `intervention/image ^3.2` - [Intervention Image](https://github.com/Intervention/image) - Image manipulation
- `league/csv ^9.11` - [League CSV](https://github.com/thephpleague/csv) - CSV processing for imports/exports

### Epic 3: Property Management UI
**Description**: Build UI components for property management

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 3.1 Create property list component | High | 8 | 2.1, 2.2 | Implement Livewire component for property listing |
| 3.2 Develop property details view | High | 10 | 3.1 | Create detailed property profile view |
| 3.3 Implement property creation/edit forms | High | 12 | 3.1 | Create multi-step forms for property management |
| 3.4 Create property media upload component | Medium | 8 | 1.4, 2.4, 3.2 | Implement UI for uploading and managing property images |
| 3.5 Develop property search filters UI | Medium | 6 | 2.3, 3.1 | Create advanced search interface for properties |

**Suggested Packages**:
- `livewire-ui/modal ^1.0` - [Livewire UI Modal](https://github.com/wire-elements/modal) - Modal dialogs
- `alpinejs/alpine ^3.13` - [Alpine.js](https://github.com/alpinejs/alpine) - JavaScript framework for interactions

## 🧩 Cursor IDE-Ready Prompts (MCPs)

### MCP 1.1: Create Property Model and Migration
```
Create a Property model and migration for Fusion CRM V4 with the following specifications:
1. Generate migration with these fields:
   - id (bigIncrements)
   - tenant_id (foreignId with constraint)
   - client_id (foreignId, nullable, with constraint)
   - property_title (string)
   - slug (string, unique within tenant)
   - description (text, nullable)
   - address_line_1 (string)
   - address_line_2 (string, nullable)
   - city (string)
   - state (string)
   - postal_code (string)
   - country (string)
   - latitude (decimal, nullable, 10, 8)
   - longitude (decimal, nullable, 11, 8)
   - property_type (string) - residential, commercial, land, etc.
   - property_status (string) - for sale, for rent, sold, leased, etc.
   - bedrooms (integer, nullable)
   - bathrooms (decimal, nullable, 3, 1)
   - parking_spaces (integer, nullable)
   - land_size (decimal, nullable, 10, 2)
   - land_size_unit (string, nullable) - sqm, sqft, acre, etc.
   - building_size (decimal, nullable, 10, 2)
   - building_size_unit (string, nullable) - sqm, sqft
   - price (decimal, 15, 2)
   - price_display (string, nullable) - "On Application", etc.
   - is_featured (boolean, default false)
   - visibility (string, default 'public') - public, private, draft
   - property_identifier (string, nullable) - MLS number, etc.
   - created_by (foreignId with constraint)
   - published_at (timestamp, nullable)
   - timestamps
   - softDeletes

2. Create Property model with:
   - Tenant scope implementation
   - Relationships to:
     - Tenant (BelongsTo)
     - Client (BelongsTo)
     - Features (BelongsToMany)
     - PropertyHistory (HasMany)
     - Media (MorphMany via Spatie Media Library)
     - User [creator] (BelongsTo)
   - Appropriate accessor methods for full_address, formatted_price
   - Scope methods for filtering by type, status, visibility
   - Media library configuration for different image collections
   - Slug generation from property_title

3. Set up factories and seeders for testing

Implement using Laravel 12's best practices for model definition, with proper
type hints, docblocks, and tenant isolation logic.
```

### MCP 2.2: Develop Property Service
```
Create a PropertyService class for Fusion CRM V4 that implements the following:
1. Define PropertyServiceInterface with methods:
   - getAllProperties($filters = [], $perPage = 15): LengthAwarePaginator
   - getPropertyById($id): ?Property
   - getPropertyBySlug($slug): ?Property
   - createProperty(array $data): Property
   - updateProperty($id, array $data): bool
   - deleteProperty($id): bool
   - restoreProperty($id): bool
   - searchProperties(array $criteria, $perPage = 15): LengthAwarePaginator
   - getFeaturedProperties($limit = 5): Collection
   - getPropertiesByType(string $type, $perPage = 15): LengthAwarePaginator
   - getPropertiesByStatus(string $status, $perPage = 15): LengthAwarePaginator
   - getPropertiesByClient($clientId, $perPage = 15): LengthAwarePaginator
   - attachFeatures($propertyId, array $featureIds): bool
   - detachFeatures($propertyId, array $featureIds): bool
   - updatePropertyStatus($propertyId, string $status, ?string $notes = null): bool
   - publishProperty($propertyId): bool
   - unpublishProperty($propertyId): bool
   - getPropertyHistory($propertyId): Collection
   - getRecentProperties($limit = 5): Collection

2. Implement PropertyService class that:
   - Injects PropertyRepository
   - Implements all interface methods with proper business logic
   - Includes robust validation and error handling
   - Maintains proper tenant isolation
   - Fires appropriate events for property lifecycle
   - Uses transactions where appropriate
   - Implements caching strategy for frequently accessed data

3. Create ServiceProvider for binding the interface to implementation

Follow Laravel 12 best practices for service implementation with proper
error handling, validation, and clean separation of concerns.
```

### MCP 3.3: Implement Property Creation/Edit Forms
```
Create a comprehensive property creation and editing form system for Fusion CRM V4:
1. Implement a multi-step Livewire component PropertyFormComponent that:
   - Handles both creation and editing modes
   - Supports step-by-step form progression with validation at each step
   - Maintains form state between steps
   - Implements draft saving functionality

2. Create steps for the following sections:
   - Basic Information (title, type, status, price)
   - Location (address, coordinates with map integration)
   - Details (bedrooms, bathrooms, sizes, features)
   - Media Upload (photos, floor plans, documents)
   - Visibility & Publication (visibility settings, scheduling)

3. Implement features:
   - Client lookup/selection integration
   - Address autocomplete with Google Places API
   - Dynamic feature selection with categories
   - Drag-and-drop media upload with preview
   - Media reordering and annotation
   - Property status change with history tracking
   - Publication scheduling

4. Add validation:
   - Real-time field validation
   - Cross-field validations (e.g., price ranges)
   - Required fields by property type
   - Image dimensions and file size validation

5. Create supporting Blade views with:
   - Responsive design for all device sizes
   - Clear progress indicators
   - Proper error states and success messages
   - Loading states and optimistic UI

Ensure all components maintain tenant isolation and respect user permissions.
Follow Tailwind and Alpine.js best practices for interactive elements.
```
