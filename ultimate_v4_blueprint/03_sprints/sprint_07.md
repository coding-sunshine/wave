# Sprint 7: Deal & Pipeline Management

## 📅 Timeline
- **Duration**: 2 weeks
- **Sprint Goal**: Implement core pipeline functionality
- **Priority Adjustment**: Advanced forecasting moved to phase 2

## 🏆 Epics
### Epic 1: Pipeline Core Infrastructure
**Description**: Implement essential deal tracking system (V3 architecture)

### Epic 2: Pipeline Management Services
**Description**: Build service layer for pipeline and deal operations

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 2.1 Create pipeline repository | High | 6 | 1.1, 1.2 | Implement repository pattern for pipeline data access |
| 2.2 Develop deal repository | High | 6 | 1.3 | Implement repository pattern for deal data access |
| 2.3 Implement pipeline service | High | 8 | 2.1 | Create service layer for pipeline business logic |
| 2.4 Create deal service | High | 10 | 2.2, 2.3 | Create service layer for deal business logic |
| 2.5 Develop forecasting service | Medium | 8 | 1.5, 2.4 | Implement deal forecasting and analytics |

**Suggested Packages**:
- `league/period ^5.1` - [Period](https://github.com/thephpleague/period) - Time period management
- `nesbot/carbon ^2.72` - [Carbon](https://github.com/briannesbitt/Carbon) - Date and time handling

### Epic 3: Pipeline & Deal UI
**Description**: Create user interface components for pipeline management

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 3.1 Create pipeline configuration UI | High | 8 | 2.1, 2.3 | Implement interface for creating and configuring pipelines |
| 3.2 Develop pipeline Kanban board | High | 12 | 1.1, 1.2, 1.3, 2.4 | Create drag-and-drop Kanban board for deal management |
| 3.3 Implement deal details view | High | 10 | 3.2, 2.4 | Create detailed deal view with edit capabilities |
| 3.4 Create deal creation wizard | Medium | 8 | 3.3, 2.4 | Implement step-by-step wizard for deal creation |
| 3.5 Develop pipeline reporting dashboard | Medium | 10 | 2.5, 3.2 | Create dashboard with pipeline metrics and forecasting |

**Suggested Packages**:
- `livewire/sortable ^1.0` - [Livewire Sortable](https://github.com/livewire/sortable) - Drag-and-drop sorting
- `apexcharts/apexcharts ^3.44` - [ApexCharts](https://github.com/apexcharts/apexcharts.js) - Interactive charts

## 🧩 Cursor IDE-Ready Prompts (MCPs)

### MCP 1.3: Implement Deal Model and Migration
```
Create a Deal model and migration for Fusion CRM V4 with the following specifications:
1. Generate migration with these fields:
   - id (bigIncrements)
   - tenant_id (foreignId with constraint)
   - title (string)
   - description (text, nullable)
   - pipeline_id (foreignId with constraint)
   - stage_id (foreignId with constraint)
   - client_id (foreignId with constraint)
   - property_id (foreignId, nullable, with constraint)
   - amount (decimal, 15, 2)
   - currency (string, default='USD')
   - expected_close_date (date, nullable)
   - actual_close_date (date, nullable)
   - probability (integer, default=0) - percentage 0-100
   - status (string) - open, won, lost, cancelled
   - source (string, nullable)
   - assigned_to (foreignId, nullable, references users)
   - reason_lost (text, nullable)
   - is_recurring (boolean, default=false)
   - recurring_details (json, nullable)
   - next_follow_up (date, nullable)
   - created_by (foreignId with constraint)
   - timestamps
   - softDeletes

2. Create Deal model with:
   - Tenant scope implementation
   - Relationships to:
     - Tenant (BelongsTo)
     - Pipeline (BelongsTo)
     - Stage (BelongsTo)
     - Client (BelongsTo)
     - Property (BelongsTo, nullable)
     - AssignedTo (BelongsTo User)
     - Creator (BelongsTo User)
     - Activities (MorphMany)
     - Notes (MorphMany)
     - Documents (MorphMany)
     - Products (BelongsToMany with pivot table including quantity, price)
   - Accessor methods for:
     - formatted_amount
     - weighted_amount (amount * probability/100)
     - days_in_pipeline
     - days_in_stage
     - days_to_close (from now to expected_close_date)
     - status_label
   - Scope methods for:
     - byStatus
     - byPipeline
     - byStage
     - byOwner
     - byProbabilityRange
     - byCloseDate
     - openDeals
     - wonDeals
     - lostDeals

3. Configure for activity logging with detailed tracking of stage changes

4. Set up factories and seeders for testing

Implement using Laravel 12's best practices for model definition with proper
type hints, docblocks, and tenant isolation logic.
```

### MCP 2.4: Create Deal Service
```
Create a comprehensive DealService for Fusion CRM V4 that implements the following:
1. Define DealServiceInterface with methods:
   - getAllDeals($filters = [], $perPage = 15): LengthAwarePaginator
   - getDealById($id): ?Deal
   - createDeal(array $data): Deal
   - updateDeal($id, array $data): bool
   - deleteDeal($id): bool
   - restoreDeal($id): bool
   - getDealsByPipeline($pipelineId, $filters = [], $perPage = 15): LengthAwarePaginator
   - getDealsByStage($stageId, $filters = [], $perPage = 15): LengthAwarePaginator
   - getDealsByClient($clientId, $filters = [], $perPage = 15): LengthAwarePaginator
   - getDealsByUser($userId, $filters = [], $perPage = 15): LengthAwarePaginator
   - moveDealToStage($dealId, $stageId, $notes = null): bool
   - updateDealStatus($dealId, $status, $notes = null): bool
   - markDealAsWon($dealId, $closeDate = null, $notes = null): bool
   - markDealAsLost($dealId, $reason = null): bool
   - calculateDealForecast($dealId): array
   - getDealTimeline($dealId): Collection
   - addDealNote($dealId, $note, $userId): bool
   - attachDocument($dealId, $documentId): bool
   - detachDocument($dealId, $documentId): bool
   - assignDealTo($dealId, $userId): bool
   - getTotalDealsValue($filters = []): float
   - getWeightedDealsValue($filters = []): float
   - getDealConversionRate($filters = []): float
   - getAverageDealCycle($filters = []): int
   - getRecentlyModifiedDeals($limit = 5): Collection
   - getDealsSummaryByStage($pipelineId): Collection
   - searchDeals(string $term, $filters = []): Collection

2. Implement DealService class that:
   - Injects DealRepository, PipelineRepository, StageRepository
   - Implements all interface methods with proper business logic
   - Handles pipeline stage transitions with validation
   - Tracks deal history and activity
   - Creates events for deal lifecycle: created, stage_changed, status_changed, won, lost
   - Implements proper tenant isolation
   - Uses transactions where appropriate
   - Implements caching strategy for frequently accessed data
   - Integrates with notification system for deal updates

3. Create ServiceProvider for binding the interface to implementation

Follow Laravel 12 best practices for service implementation with clean
separation of concerns, proper validation, and error handling.
```

### MCP 3.2: Develop Pipeline Kanban Board
```
Create a sophisticated Kanban board component for deal pipeline management in Fusion CRM V4:
1. Implement a Livewire component PipelineKanbanComponent that:
   - Renders pipeline stages as columns
   - Displays deals as draggable cards within their respective stages
   - Handles real-time updates when deals change stages
   - Supports filtering by various criteria
   - Implements drag-and-drop functionality between stages
   - Maintains proper state during page refreshes

2. Create deal card component (DealCardComponent) with:
   - Clean visual representation of key deal information
   - Color coding based on deal value or probability
   - Quick action buttons for common operations
   - Visual indicators for overdue deals, new deals, etc.
   - Progressive loading of additional deal details
   - Progress indicators for deal completion

3. Implement stage column component (StageColumnComponent) with:
   - Header showing stage name and deal count
   - Total value and weighted value of deals in stage
   - Deal cards sorted by priority or other criteria
   - Drag-and-drop target zones
   - Ability to collapse/expand columns
   - Add deal button for quick creation in specific stage

4. Add pipeline control features:
   - Pipeline selector for switching between pipelines
   - Filter controls for deal owner, client, date range, value, etc.
   - Search functionality for finding specific deals
   - View options (compact/detailed cards)
   - Stage visibility toggles
   - Quick deal creation button

5. Implement with:
   - Smooth animations for transitions
   - Optimistic UI updates
   - Loading states and skeleton UI
   - Responsive design that adapts to different screen sizes
   - Performance optimizations for large numbers of deals

6. Create interactive features:
   - Deal detail modal on click
   - Inline stage transition reasons
   - Inline deal value editing
   - Stage capacity visualization
   - Deal aging indicators
   - Probability visualization

Ensure the component maintains tenant isolation and respects user permissions.
Implement using Livewire with Alpine.js for interactions and Tailwind for styling.
Focus on performance with smart loading strategies and minimal DOM updates.
```