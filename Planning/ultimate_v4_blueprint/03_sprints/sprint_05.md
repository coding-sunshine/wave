# Sprint 5: Task & Activity Management with Analytics

## 📅 Timeline
- **Duration**: 2 weeks
- **Sprint Goal**: Implement core task management system with analytics dashboard
- **Priority Adjustment**: Advanced calendar features moved to later phase

## 🏆 Epics

### Epic 1: Task Core Infrastructure
**Description**: Implement essential task management system with V3 architecture

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 1.1 Create task model and migration | High | 6 | Sprint 1: 2.2 | Implement task data structure with migrations |
| 1.2 Implement task categorization | Medium | 4 | 1.1 | Create task categories, types, and priorities |
| 1.3 Develop task assignment system | High | 6 | 1.1, Sprint 2: 1.1 | Create functionality for assigning tasks to users |
| 1.4 Create task status workflow | Medium | 8 | 1.1 | Implement task status transitions and workflow |
| 1.5 Implement task reminder system | Medium | 6 | 1.1, 1.3 | Create notification and reminder functionality |

**Suggested Packages**:
- `laravel/notifications ^10.0` - [Laravel Notifications](https://laravel.com/docs/10.x/notifications) - Notification management
- `spatie/laravel-model-status ^1.15` - [Spatie Laravel Model Status](https://github.com/spatie/laravel-model-status) - Status management

### Epic 2: Calendar & Event Management
**Description**: Create calendar management with event scheduling

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 2.1 Create event model and migration | High | 6 | Sprint 1: 2.2 | Implement event data structure with migrations |
| 2.2 Implement recurring event pattern | Medium | 8 | 2.1 | Create system for recurring event management |
| 2.3 Develop event invitee management | Medium | 6 | 2.1, Sprint 2: 1.1 | Create functionality for inviting users to events |
| 2.4 Create calendar service | High | 8 | 1.1, 2.1, 2.2 | Implement service for calendar operations |
| 2.5 Implement calendar synchronization | Low | 10 | 2.4 | Create functionality for external calendar sync |

**Suggested Packages**:
- `spatie/laravel-calendar-links ^1.5` - [Spatie Laravel Calendar Links](https://github.com/spatie/calendar-links) - Calendar integration
- `rlanvin/php-rrule ^2.4` - [PHP RRule](https://github.com/rlanvin/php-rrule) - Recurring event patterns

### Epic 3: Task & Calendar UI
**Description**: Create user interface components for task and calendar management

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 3.1 Create task list component | High | 8 | 1.1, 1.2, 1.3 | Implement Livewire component for task listing and filtering |
| 3.2 Develop task details modal | Medium | 6 | 3.1 | Create detailed task view and edit functionality |
| 3.3 Implement calendar view component | High | 12 | 2.1, 2.2, 2.4 | Create interactive calendar with day, week, month views |
| 3.4 Create event creation/edit modal | Medium | 8 | 3.3, 2.1 | Implement event management UI |
| 3.5 Develop dashboard task widgets | Medium | 6 | 3.1, 3.3 | Create task and calendar widgets for dashboard |

**Suggested Packages**:
- `fullcalendar/fullcalendar ^6.1` - [FullCalendar](https://github.com/fullcalendar/fullcalendar) - JavaScript calendar library
- `wire-elements/spotlight ^1.0` - [Livewire Spotlight](https://github.com/wire-elements/spotlight) - Command palette for quick actions

### Epic 4: Analytics Dashboard
**Description**: Implement analytics dashboard using Spatie Laravel-Dashboard

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 4.1 Set up dashboard infrastructure | High | 8 | Sprint 1: 2.2 | Implement Spatie Laravel-Dashboard with basic configuration |
| 4.2 Create task statistics tile | High | 6 | 4.1, 1.1, 1.2 | Build dashboard tile for task metrics |
| 4.3 Implement calendar events tile | Medium | 6 | 4.1, 2.1, 2.4 | Create dashboard tile showing upcoming events |
| 4.4 Develop activity metrics tile | Medium | 8 | 4.1, Sprint 3: 1.1 | Build dashboard tile for contact activity metrics |
| 4.5 Create performance analytics tile | Medium | 8 | 4.1, 4.2, 4.3, 4.4 | Implement user productivity and performance metrics |

**Suggested Packages**:
- `spatie/laravel-dashboard ^3.0` - [Laravel Dashboard](https://github.com/spatie/laravel-dashboard) - Dashboard framework
- `league/commonmark ^2.4` - [League CommonMark](https://github.com/thephpleague/commonmark) - Markdown processing

## 🧩 Cursor IDE-Ready Prompts (MCPs)

### MCP 1.1: Create Task Model and Migration
```
Create a Task model and migration for Fusion CRM V4 with the following specifications:
1. Generate migration with these fields:
   - id (bigIncrements)
   - tenant_id (foreignId with constraint)
   - title (string)
   - description (text, nullable)
   - task_type (string) - call, email, meeting, other
   - status (string) - not_started, in_progress, completed, deferred
   - priority (string) - low, medium, high, urgent
   - category_id (foreignId, nullable, with constraint)
   - due_date (date, nullable)
   - due_time (time, nullable)
   - start_date (date, nullable)
   - start_time (time, nullable)
   - completed_at (timestamp, nullable)
   - assigned_to (foreignId, nullable, references users)
   - assigned_by (foreignId, references users)
   - related_type (morphs, nullable) - polymorphic relation to clients, properties, etc.
   - is_recurring (boolean, default false)
   - recurrence_pattern (json, nullable)
   - reminder_at (timestamp, nullable)
   - reminder_sent (boolean, default false)
   - created_by (foreignId with constraint)
   - timestamps
   - softDeletes

2. Create Task model with:
   - Tenant scope implementation
   - Relationships to:
     - Tenant (BelongsTo)
     - Category (BelongsTo)
     - AssignedTo (BelongsTo User)
     - AssignedBy (BelongsTo User)
     - Creator (BelongsTo User)
     - Related (MorphTo) - polymorphic relation to clients, properties, etc.
     - Comments (MorphMany)
   - Appropriate accessor methods for due_date_formatted, priority_label, etc.
   - Scope methods for filtering by status, priority, assignment, due date
   - Events for task lifecycle: created, updated, completed, assigned
   - Reminder scheduling logic

3. Set up factories and seeders for testing

Implement using Laravel 12's best practices for model definition, with proper
type hints, docblocks, and tenant isolation logic.
```

### MCP 2.4: Create Calendar Service
```
Create a comprehensive CalendarService for Fusion CRM V4 that manages tasks and events:
1. Define CalendarServiceInterface with methods:
   - getCalendarEvents($startDate, $endDate, $filters = []): Collection
   - createEvent(array $data): Event
   - updateEvent($id, array $data): bool
   - deleteEvent($id): bool
   - getEventById($id): ?Event
   - getRecurringEvents($startDate, $endDate, $filters = []): Collection
   - createRecurringEvent(array $data, array $recurrencePattern): Event
   - updateRecurringEvent($id, array $data, $updateMode = 'single'): bool
   - addEventAttendee($eventId, $userId, $status = 'invited'): bool
   - updateAttendeeStatus($eventId, $userId, $status): bool
   - getTasksForCalendar($startDate, $endDate, $filters = []): Collection
   - exportCalendarEvents($startDate, $endDate, $format = 'ics'): string
   - generateCalendarLink($eventId): string
   - getUpcomingEvents($days = 7, $userId = null): Collection
   - getOverdueEvents($userId = null): Collection

2. Implement CalendarService class that:
   - Injects EventRepository and TaskRepository
   - Implements all interface methods with proper business logic
   - Handles recurring event generation based on patterns (daily, weekly, monthly, etc.)
   - Manages date calculations and timezone handling
   - Implements proper validation and error handling
   - Maintains proper tenant isolation
   - Uses caching for performance optimization

3. Create recurrence pattern handling that supports:
   - Daily, weekly, monthly, yearly patterns
   - Interval specifications (every X days, weeks, etc.)
   - End conditions (end date, occurrence count, never)
   - Day of week specifications for weekly patterns
   - Day of month specifications for monthly patterns

4. Create ServiceProvider for binding the interface to implementation

Follow Laravel 12 best practices for service implementation with proper
error handling, validation, and clean separation of concerns.
```

### MCP 3.3: Implement Calendar View Component
```
Create a fully-featured interactive calendar component for Fusion CRM V4:
1. Implement a Livewire component CalendarViewComponent that:
   - Integrates with FullCalendar.js for UI rendering
   - Supports day, week, month, and list views
   - Handles real-time updates through Livewire events
   - Lazy-loads events as the date range changes
   - Supports event filtering by type, user, and category

2. Implement features:
   - Drag and drop for event rescheduling
   - Event resizing for duration changes
   - Click to create new events
   - Event coloring based on type and status
   - Quick view modal for event details
   - Resource view for user scheduling
   - Integration with tasks that have due dates
   - Mini-calendar for quick navigation

3. Create UI components for:
   - Calendar toolbar with view switchers and date navigation
   - Filter panel for event type and user filtering
   - Event details popover with quick actions
   - Quick create/edit forms
   - Print view for calendar

4. Add advanced features:
   - Calendar sharing options
   - User availability indication
   - Recurring event pattern display
   - Timezone handling and display
   - Working hours customization
   - Background events for holidays/special days

5. Implement with proper loading states, error handling, and responsive design
   for all screen sizes from mobile to desktop.

6. Ensure the component maintains tenant isolation and respects user permissions
   for viewing and editing events.

Follow Tailwind and Alpine.js best practices for interactive elements and
ensure the component is optimized for performance with proper event delegation
and minimal repaints.
```

### MCP 4.1: Set Up Dashboard Infrastructure
```
Integrate Spatie Laravel-Dashboard for Fusion CRM V4:

1. Install spatie/laravel-dashboard ^3.0:
   - Run composer installation
   - Publish configuration files
   - Configure schedule for tile updates
   - Set up appropriate migrations

2. Configure dashboard framework:
   - Create base dashboard routes
   - Set up dashboard layout with responsive grid
   - Configure authentication and authorization
   - Implement tenant-aware dashboard access

3. Create base dashboard components:
   - DashboardController.php
   - Dashboard layout Blade components
   - Dashboard tile wrapper components
   - Dashboard configuration interface

4. Implement dashboard customization:
   - User-specific dashboard layouts
   - Dashboard layout persistence
   - Drag-and-drop tile positioning
   - Dashboard theme options
   - Role-based default dashboards

5. Set up dashboard caching:
   - Configure efficient caching for tiles
   - Implement cache invalidation strategies
   - Set up refresh intervals by tile type

6. Add responsive features:
   - Mobile-friendly layouts
   - Tile size adaptation
   - Touch interaction support
   - Progressive loading for performance

7. Implement tenant isolation:
   - Ensure all dashboard data respects tenant boundaries
   - Configure tile visibility by tenant and user role
   - Apply tenant-specific styling if needed

Use Spatie Laravel-Dashboard as a foundation for creating insightful,
real-time analytics displays that help users make data-driven decisions
while maintaining tenant isolation and performance.
```

### MCP 4.2: Create Task Statistics Tile
```
Implement a task statistics dashboard tile using Spatie Laravel-Dashboard for Fusion CRM V4:

1. Create TaskStatisticsTile class:
   - Extend the Tile class from spatie/laravel-dashboard
   - Configure refresh interval and cache settings
   - Implement data fetching methods with tenant awareness
   - Create render method for the tile view

2. Develop TaskStatisticsStore class:
   - Create methods to store and retrieve task statistics
   - Implement tenant isolation for data storage
   - Set up efficient data aggregation queries
   - Configure automatic data refreshing via scheduled commands

3. Create TaskStatisticsCommand class:
   - Extend Illuminate\Console\Command
   - Implement logic to calculate task statistics
   - Configure tenant iteration for multi-tenant deployments
   - Add to scheduler for regular updates

4. Implement task metrics calculations:
   - Tasks by status (not_started, in_progress, completed, deferred)
   - Tasks by priority (low, medium, high, urgent)
   - Tasks by assignment (assigned vs. unassigned)
   - Overdue tasks count and percentage
   - Completion rate metrics (daily, weekly, monthly)
   - Average completion time
   - Trending task categories

5. Develop blade view for the tile:
   - Create clean, informative visualization
   - Implement mini-charts for key metrics
   - Add color-coding for status and priority
   - Include trend indicators (up/down arrows)
   - Design mobile-responsive layout

6. Add interaction capabilities:
   - Click-through to detailed task lists
   - Filter controls for time period
   - User-specific vs. team-wide toggle
   - Quick actions for task management

7. Configure settings for the tile:
   - Allow customization of displayed metrics
   - Enable/disable specific charts
   - Set preferred time ranges
   - Configure alert thresholds

Take advantage of Laravel-Dashboard's tile system to create a
focused view of task-related metrics that helps teams monitor
productivity and identify bottlenecks.
```

### MCP 4.5: Create Performance Analytics Tile
```
Implement a performance analytics dashboard tile using Spatie Laravel-Dashboard for Fusion CRM V4:

1. Create PerformanceAnalyticsTile class:
   - Extend the Tile class from spatie/laravel-dashboard
   - Configure refresh interval and cache settings
   - Implement data aggregation with tenant isolation
   - Create render method for the tile view

2. Develop PerformanceAnalyticsStore class:
   - Create methods to store and retrieve performance metrics
   - Implement efficient data storage and retrieval
   - Set up data segmentation by user, team, and tenant
   - Configure proper cache invalidation

3. Create PerformanceAnalyticsCommand class:
   - Extend Illuminate\Console\Command
   - Implement performance calculation algorithms
   - Set up scheduled execution for regular updates
   - Add tenant-aware processing

4. Implement key performance indicators:
   - Task completion rate by user/team
   - Average response time for assigned tasks
   - Meeting attendance rate
   - Client interaction frequency
   - Deal progression metrics
   - Activity logging completeness
   - Documentation thoroughness score
   - Cross-selling effectiveness metrics

5. Develop blade view for the tile:
   - Create informative data visualizations
   - Implement comparative metrics (user vs. team average)
   - Add trend indicators and historical comparisons
   - Design clean, actionable interface
   - Ensure mobile responsiveness

6. Add detailed breakdown features:
   - Click-through to user-specific performance details
   - Historical trend analysis
   - Performance improvement suggestions
   - Goal tracking visualization

7. Configure personalization options:
   - Allow users to set personal KPI targets
   - Enable customizable metrics display
   - Configure notification thresholds
   - Set comparison benchmarks

Take advantage of Laravel-Dashboard's flexibility to create a
performance analytics system that motivates users and provides
actionable insights for productivity improvement while maintaining
user privacy and tenant isolation.
```