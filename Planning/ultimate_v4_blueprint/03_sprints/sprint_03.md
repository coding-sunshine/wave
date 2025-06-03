# Sprint 3: Client Management System

## 📅 Timeline
- **Duration**: 2 weeks
- **Sprint Goal**: Implement core client management capabilities

## 🏆 Epics

### Epic 1: Client Data Models
**Description**: Create comprehensive data models for client management

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 1.1 Create client model and migration | High | 6 | Sprint 1: 2.2 | Implement client data structure with migrations |
| 1.2.1 Create contact model and migration | High | 4 | 1.1.1 | Implement contact data structure for client relationships |
| 1.2.2 Implement client notes and activity tracking | Medium | 6 | 1.1.1 | Create models for client notes and activity logs |
| 1.2.3 Create client tagging system | Medium | 4 | 1.1.1 | Implement tagging functionality for clients |
| 1.2.4 Develop client categorization system | Medium | 4 | 1.1.1 | Implement client categories and classifications |

**Suggested Packages**:
- `spatie/laravel-tags ^4.5` - [Spatie Laravel Tags](https://github.com/spatie/laravel-tags) - Tagging functionality
- `spatie/laravel-activitylog ^4.7` - [Spatie Laravel Activitylog](https://github.com/spatie/laravel-activitylog) - Activity logging

### Epic 2: Client Management Services
**Description**: Build service layer for client data manipulation

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 2.1 Develop client service | High | 12 | 1.1, 1.2 | Create service layer with direct model interaction for client operations |
| 2.2 Implement contact relationship service | Medium | 6 | 1.2, 2.1 | Create service for managing client contacts |
| 2.3 Create client import/export service | Medium | 8 | 2.1 | Implement CSV import/export functionality |
| 2.4 Develop client search service | Medium | 6 | 1.1, 2.1 | Create advanced search capabilities for clients |
| 2.5 Implement data validation layer | Medium | 6 | 2.1, 2.2 | Create comprehensive validation for client data operations |

**Suggested Packages**:
- `maatwebsite/excel ^3.1` - [Laravel Excel](https://github.com/SpartnerNL/Laravel-Excel) - Excel/CSV imports and exports
- `laravel/scout ^10.5` - [Laravel Scout](https://github.com/laravel/scout) - Full-text search functionality

### Epic 3: Client Management UI
**Description**: Build interactive UI components for client management

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 3.1 Create client list component | High | 8 | 2.1 | Implement Livewire component for client listing and filtering |
| 3.2 Develop client details view | High | 10 | 3.1 | Create detailed client profile view with tabs |
| 3.3 Implement client creation/edit forms | High | 8 | 3.1 | Create forms for adding and editing clients |
| 3.4 Create contact management components | Medium | 6 | 3.2, 2.2 | Implement UI for managing client contacts |
| 3.5 Develop client activity timeline | Medium | 6 | 1.3, 3.2 | Create visual timeline of client activities |

**Suggested Packages**:
- `wire-elements/modal ^1.0` - [Livewire Modal](https://github.com/wire-elements/modal) - Modal dialogs for Livewire
- `rappasoft/laravel-livewire-tables ^3.1` - [Laravel Livewire Tables](https://github.com/rappasoft/laravel-livewire-tables) - Interactive data tables

## 🧩 Cursor IDE-Ready Prompts (MCPs)

### MCP 1.1: Create Client Model and Migration
```
Create a Client model and migration for Fusion CRM V4 with the following specifications:
1. Generate migration with these fields:
   - id (bigIncrements)
   - tenant_id (foreignId with constraint)
   - company_name (string, nullable)
   - first_name (string)
   - last_name (string)
   - email (string, unique within tenant)
   - phone (string, nullable)
   - mobile (string, nullable)
   - address_line_1 (string, nullable)
   - address_line_2 (string, nullable)
   - city (string, nullable)
   - state (string, nullable)
   - postal_code (string, nullable)
   - country (string, nullable)
   - lead_source (string, nullable)
   - status (string, default='active')
   - client_type (string, default='general')
   - notes (text, nullable)
   - last_contact_date (timestamp, nullable)
   - created_by (foreignId with constraint)
   - timestamps
   - softDeletes

2. Create Client model with:
   - Tenant scope implementation
   - Relationships to:
     - Tenant (BelongsTo)
     - Contacts (HasMany)
     - Properties (HasMany)
     - Notes (MorphMany)
     - Activities (MorphMany)
     - User [creator] (BelongsTo)
   - Appropriate accessor methods for full_name, formatted_address
   - Mutator methods for automatic formatting
   - Scope methods for filtering clients by status, type

3. Configure for activity logging and tagging

Implement using Laravel 12's best practices for model definition, with proper
type hints, docblocks, and tenant isolation logic.
```

### MCP 2.1: Develop Client Service
```
Create a comprehensive ClientService for Fusion CRM V4 that implements the following:
1. Define ClientServiceInterface with methods:
   - getAllClients($filters = [], $perPage = 15): LengthAwarePaginator
   - getClientById($id): ?Client
   - createClient(array $data): Client
   - updateClient($id, array $data): bool
   - deleteClient($id): bool
   - restoreClient($id): bool
   - searchClients(string $term, $filters = []): Collection
   - importClients(UploadedFile $file): array
   - exportClients($filters = []): string (file path)
   - getClientActivities($clientId, $limit = 10): Collection
   - addClientNote($clientId, $note, $userId): bool
   - getClientsByStatus(string $status): Collection
   - getClientsByType(string $type): Collection
   - getRecentlyCreatedClients($limit = 5): Collection
   - getRecentlyUpdatedClients($limit = 5): Collection

2. Implement ClientService class that:
   - Uses Eloquent models directly for data access
   - Implements all interface methods with proper business logic
   - Includes robust validation and error handling
   - Maintains proper tenant isolation through model scopes
   - Fires appropriate events for client lifecycle actions
   - Uses transactions where appropriate
   - Implements caching strategy for frequently accessed data

3. Create ServiceProvider for binding the interface to implementation

Example implementation for key methods:
```php
public function getAllClients($filters = [], $perPage = 15): LengthAwarePaginator
{
    $query = Client::query();
    
    // Apply filters
    if (isset($filters['status'])) {
        $query->where('status', $filters['status']);
    }
    
    if (isset($filters['type'])) {
        $query->where('client_type', $filters['type']);
    }
    
    if (isset($filters['search'])) {
        $search = $filters['search'];
        $query->where(function($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('company_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
    
    return $query->latest()->paginate($perPage);
}
```

Focus on clean code, separation of concerns, and maintaining tenant boundaries
throughout all operations. Follow Laravel 12 best practices for service implementation.
```

### MCP 3.2: Develop Client Details View
```
Create a comprehensive client details view component for Fusion CRM V4:
1. Implement a Livewire component ClientDetailsComponent that:
   - Receives client ID as input
   - Loads client data via ClientService
   - Supports tab-based navigation for different sections
   - Implements real-time updates when client data changes

2. Create Blade views for:
   - Main client information panel with edit capabilities
   - Client contact list and management
   - Properties associated with client
   - Notes and activity timeline
   - Documents tab for client-related files
   - Transactions/financial history

3. Implement child components:
   - ClientContactsComponent for contact management
   - ClientNotesComponent for notes CRUD
   - ClientPropertiesComponent for property listing
   - ClientActivityComponent for activity timeline
   - ClientDocumentsComponent for document management

4. Add features:
   - Inline editing of key fields
   - Quick action buttons for common tasks
   - Responsive design for all screen sizes
   - Permission-based UI adaptation

Include proper loading states, error handling, and optimistic UI updates.
Focus on UX with clean, modern design following Tailwind best practices.
Ensure all components maintain tenant isolation and respect user permissions.
```
