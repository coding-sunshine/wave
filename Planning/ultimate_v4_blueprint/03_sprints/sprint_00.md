# Sprint 0: Wave Foundation Analysis & CRM Planning

## 📅 Timeline
- **Duration**: 2 weeks
- **Sprint Goal**: Analyze existing Wave foundation, understand current architecture, and plan CRM-specific extensions

## 🏆 Epics

### Epic 1: Wave Foundation Analysis
**Description**: Comprehensive analysis of the existing Wave-based Laravel application to understand current capabilities and extension points

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 1.1 Analyze existing Wave models and relationships | High | 8 | None | Document current User, Team, Plan, Post, Page, Form models and their relationships |
| 1.2 Review existing Filament resources | High | 6 | 1.1 | Analyze UserResource, RoleResource, PermissionResource patterns for CRM extension |
| 1.3 Understand Wave's team structure for multi-tenancy | High | 4 | 1.1 | Document how Wave teams work and plan CRM organization adaptation |
| 1.4 Review authentication and permission system | Medium | 4 | 1.1 | Understand Spatie permissions setup and plan CRM role extensions |
| 1.5 Analyze Wave's theme and UI system | Medium | 4 | None | Document theme switching, Livewire components, and UI patterns |

### Epic 2: CRM Extension Planning
**Description**: Plan specific CRM models, resources, and features that will extend the Wave foundation

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 2.1 Design CRM model extensions | High | 12 | 1.1 | Plan Property, Lead, Deal, Activity models extending Wave patterns |
| 2.2 Plan Filament resource extensions | High | 8 | 1.2, 2.1 | Design CRM resources following existing Wave resource patterns |
| 2.3 Design CRM-specific permissions | Medium | 6 | 1.4, 2.1 | Plan Agent, Broker, Manager roles extending Spatie system |
| 2.4 Plan database migrations strategy | High | 6 | 2.1 | Design migration approach that extends existing Wave schema |
| 2.5 Document CRM user journey | Medium | 4 | 2.1, 2.2 | Map how CRM users will interact with extended Wave system |

### Epic 3: Development Environment Setup
**Description**: Prepare development environment for CRM extension development

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 3.1 Set up local Wave development environment | High | 4 | None | Ensure Wave is running locally with all features working |
| 3.2 Configure IDE for Wave/CRM development | Medium | 2 | 3.1 | Set up Cursor/IDE with Wave-specific configurations |
| 3.3 Create CRM development branch strategy | Medium | 2 | 3.1 | Plan Git workflow for CRM feature development |
| 3.4 Set up testing environment | Medium | 4 | 3.1 | Ensure Pest PHP tests are running and plan CRM test structure |
| 3.5 Document current Wave package ecosystem | Low | 2 | 3.1 | List all current packages and identify CRM-specific additions needed |

**Current Wave Packages to Analyze**:
- `filament/filament ^3.2` - Admin panel framework (already configured)
- `livewire/livewire ^3.0` - Frontend reactivity (already configured)
- `spatie/laravel-permission` - Role/permission system (fully configured)
- `tymon/jwt-auth` - JWT authentication (already configured)
- `intervention/image` - Image processing (already configured)
- `stripe/stripe-php` - Payment processing (already configured)

## 🧩 Cursor IDE-Ready Prompts (MCPs)

### MCP 1.1: Analyze Existing Wave Models
```
I have an existing Wave-based Laravel application and need to analyze the current models to understand how to extend them for CRM functionality. Please help me:

1. Review the existing Wave User model in app/Models/User.php and document:
   - Current fields and relationships
   - Authentication features
   - Team relationships
   - API token management

2. Analyze the Wave Team model and understand:
   - How teams are structured
   - User-team relationships
   - How this can be adapted for CRM organizations/brokerages

3. Review other Wave models (Plan, Post, Page, Form, etc.) and identify:
   - Patterns used for model structure
   - Relationship conventions
   - Traits and interfaces used

4. Document the current database schema and identify:
   - Tables that can be extended for CRM
   - Relationship patterns to follow
   - Migration strategies for CRM additions

Focus on understanding the existing architecture so we can extend it properly for CRM functionality.
```

### MCP 1.2: Review Existing Filament Resources
```
I need to analyze the existing Filament resources in my Wave application to understand the patterns for creating CRM-specific resources. Please help me:

1. Review the UserResource in app/Filament/Resources/UserResource.php and document:
   - Form field patterns and validation
   - Table column configurations
   - Action implementations
   - Page structure and navigation

2. Analyze the RoleResource and PermissionResource to understand:
   - How Spatie permissions are integrated
   - Resource relationship handling
   - Admin panel navigation patterns

3. Review other resources (PostResource, PageResource, etc.) and identify:
   - Common patterns across resources
   - Reusable components and widgets
   - Form and table conventions

4. Plan how to create CRM resources (PropertyResource, LeadResource, DealResource) that:
   - Follow established Wave patterns
   - Integrate with existing navigation
   - Maintain consistent UI/UX
   - Leverage existing components

Provide specific examples of how to extend these patterns for CRM entities.
```

### MCP 2.1: Design CRM Model Extensions
```
Based on my analysis of the existing Wave foundation, help me design CRM-specific models that extend the current architecture. I need:

1. Property model that:
   - Extends Wave's model patterns
   - Integrates with Wave's team structure for multi-tenancy
   - Uses Wave's media system for property photos
   - Follows Wave's relationship conventions

2. Lead model that:
   - Connects to Wave's User model for assignment
   - Uses Wave's team structure for organization isolation
   - Integrates with Wave's form system for lead capture
   - Follows Wave's status/workflow patterns

3. Deal model that:
   - Links to Property and Lead models
   - Tracks sales pipeline stages
   - Integrates with Wave's user system for agent assignment
   - Uses Wave's activity/event patterns

4. Activity model that:
   - Tracks interactions across all CRM entities
   - Integrates with Wave's user system
   - Uses Wave's timestamp and tracking patterns

Provide complete model code with:
- Proper relationships following Wave conventions
- Appropriate traits and interfaces
- Database migration files
- Model factories for testing
- Proper tenant scoping using Wave's team structure

Ensure all models integrate seamlessly with the existing Wave architecture.
```

### MCP 2.2: Plan Filament Resource Extensions
```
Help me plan CRM-specific Filament resources that extend the existing Wave admin panel. I need:

1. PropertyResource that:
   - Follows the pattern established by Wave's existing resources
   - Integrates with Wave's media system for photo uploads
   - Uses Wave's form validation patterns
   - Maintains consistent navigation with existing resources

2. LeadResource that:
   - Extends Wave's user management patterns
   - Integrates with the Property model for lead-property relationships
   - Uses Wave's status management patterns
   - Follows Wave's table and form conventions

3. DealResource that:
   - Creates a sales pipeline interface
   - Integrates with both Lead and Property resources
   - Uses Wave's relationship management patterns
   - Implements Kanban-style pipeline views

4. Enhanced dashboard widgets that:
   - Extend Wave's existing dashboard
   - Show CRM-specific metrics
   - Follow Wave's widget patterns
   - Integrate with Wave's analytics foundation

Provide complete resource implementations with:
- Form schemas following Wave patterns
- Table configurations matching Wave style
- Actions and bulk actions
- Page classes and navigation
- Proper integration with existing Wave admin panel

Ensure all resources feel native to the existing Wave admin experience.
```

## 📋 Deliverables

### Week 1 Deliverables
- **Wave Foundation Documentation**: Comprehensive analysis of existing models, resources, and patterns
- **CRM Extension Plan**: Detailed plan for extending Wave with CRM functionality
- **Development Environment**: Fully configured development setup for CRM extension

### Week 2 Deliverables
- **CRM Model Designs**: Complete specifications for Property, Lead, Deal, and Activity models
- **Filament Resource Plans**: Detailed plans for CRM resources following Wave patterns
- **Migration Strategy**: Database migration approach that extends existing Wave schema
- **Testing Strategy**: Plan for testing CRM extensions building on Wave's Pest framework

## 🎯 Success Criteria

- [ ] Complete understanding of Wave's existing architecture and patterns
- [ ] Detailed plan for CRM model extensions that integrate seamlessly with Wave
- [ ] Filament resource designs that maintain Wave's UI/UX consistency
- [ ] Development environment ready for CRM extension development
- [ ] Clear migration path from Wave foundation to CRM functionality
- [ ] Testing strategy that builds upon Wave's existing test suite

## 📝 Notes

This sprint is crucial for understanding the existing Wave foundation and planning CRM extensions that feel native to the existing system. The goal is to leverage Wave's proven architecture while adding comprehensive CRM functionality.

**Key Focus Areas:**
- Understand Wave's team structure for CRM multi-tenancy
- Analyze Wave's Filament resource patterns for CRM resource creation
- Plan database extensions that maintain Wave's schema integrity
- Design CRM features that integrate seamlessly with existing Wave functionality
