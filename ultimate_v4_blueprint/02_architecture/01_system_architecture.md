# Fusion CRM V4 - System Architecture

This document provides a comprehensive overview of the system architecture for Fusion CRM V4, outlining the key components, patterns, and relationships that form the foundation of the application.

## System Overview

Fusion CRM V4 is built as a modern web application using the TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire) with a multi-tenant architecture. The system is designed to be modular, scalable, and extensible, with clear separation of concerns and well-defined boundaries between components.

### Performance & Monitoring

#### Infrastructure Monitoring
- Real-time performance metrics dashboard
- Resource utilization tracking (CPU, memory, disk)
- Database query performance analysis
- Cache effectiveness monitoring
- Network latency tracking

#### AI Performance Metrics
- Token usage analytics per tenant
- Model response time tracking
- Error rate monitoring
- Cost optimization analytics
- Resource scaling triggers

#### Automated Testing Pipeline
- Continuous integration with GitHub Actions
- Automated performance benchmarking
- Load testing with predefined scenarios
- AI response quality assessment
- Security vulnerability scanning

### Subscription & White-Label Support

#### Multi-Tier Subscription System
- Standard tiered subscription model with various feature sets
- White-label subscription tier with full platform customization
- Subscription management with automatic billing and invoicing
- Usage tracking and quota enforcement per subscription tier

#### White-Label Configuration
- Tenant-specific branding (logos, colors, domains)
- Custom email domains and templates
- White-labeled API endpoints and documentation
- Customizable user interface elements
- Tenant-specific notification settings

#### Property Customization System
- Tenant-specific property customization layer
- Private properties exclusive to specific tenants
- Tenant-specific notes and annotations
- Customizable metadata and display settings
- Visibility controls for property details and features

## Architectural Style

The application follows a layered architecture with the following key layers:

### Presentation Layer
- **Livewire Components**: Reactive UI components
- **Blade Templates**: HTML markup with PHP logic
- **Alpine.js**: Frontend interactivity and state management
- **Tailwind CSS**: Styling and UI framework

### Application Layer
- **Controllers**: Handle HTTP requests and responses
- **Livewire Component Classes**: Backend logic for Livewire components
- **Services**: Encapsulate business logic and orchestrate operations
- **Jobs**: Background processing for long-running tasks
- **Events & Listeners**: Decoupled communication between components

### Domain Layer
- **Models**: Eloquent models representing business entities
- **Value Objects**: Immutable objects representing concepts
- **Actions**: Single-purpose classes for specific business operations
- **Policies**: Authorization logic for domain entities
- **Validators**: Input validation logic

### Infrastructure Layer
- **Repositories**: Data access abstractions (when needed)
- **Integrations**: Third-party service integrations (OpenAI, Xero, Vapi.ai)
- **Queue Handlers**: Queue processing logic
- **Cache Handlers**: Caching logic
- **File Storage**: Media and file storage logic

## Key Architectural Patterns

### Multi-Tenant Architecture

Fusion CRM V4 uses a single-database architecture pattern for multi-tenancy, where each tenant (organization) has its data isolated from other tenants:

1. **Row-Level Isolation**: Uses a shared database with tenant_id columns for data isolation
2. **Tenant Middleware**: Routes requests to the appropriate tenant context
3. **Global Scope**: Automatically applies tenant scoping to queries
4. **Tenant-aware Services**: Services operate within the context of the current tenant

```php
// Example Tenant Scope Implementation
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }
        
        $tenantId = app(TenantManager::class)->getTenantId();
        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}
```

### Service-Repository Pattern

For complex domain logic, the system uses the Service-Repository pattern:

1. **Services**: Encapsulate business logic and orchestrate operations
2. **Repositories**: Provide data access abstraction where complexity warrants it
3. **Dependency Injection**: Services and repositories are injected where needed

```php
// Example Service Pattern
class PropertyService
{
    protected $propertyRepository;
    
    public function __construct(PropertyRepository $propertyRepository)
    {
        $this->propertyRepository = $propertyRepository;
    }
    
    public function publishToChannels(Property $property, array $channels)
    {
        // Business logic for publishing to multiple channels
        foreach ($channels as $channel) {
            // Logic to publish to each channel
        }
        
        // Update property status
        $this->propertyRepository->updateStatus($property, 'published');
        
        // Fire events, etc.
        event(new PropertyPublished($property, $channels));
    }
}
```

### Action Pattern

For simpler operations, the system uses single-purpose Action classes:

```php
// Example Action Pattern
class CreatePropertyAction
{
    public function execute(array $data): Property
    {
        return Property::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $data['name'],
            'description' => $data['description'],
            // Other property attributes
        ]);
    }
}
```

### CQRS-Inspired Approach

For complex domains, the system uses a CQRS-inspired approach to separate read and write operations:

1. **Commands**: Represent intentions to change the system state
2. **Queries**: Represent requests for information without side effects
3. **Handlers**: Process commands and queries

## Component Architecture

### Core Components

The system is organized into the following core components:

1. **Authentication & Authorization**:
   - User authentication
   - Role-based access control
   - Permission management
   - Tenant isolation
   - API key management for white-label support

2. **User Management**:
   - User CRUD operations
   - Profile management
   - Team management
   - Role assignment
   - Tenant subscription management

3. **Property Management**:
   - Property CRUD operations
   - Project, stage, and lot management
   - Media management
   - Property publishing

4. **CRM Core**:
   - Lead management
   - Contact management
   - Deal tracking
   - Task management
   - Notes and history

5. **Marketing Tools**:
   - Campaign management
   - Content generation
   - Analytics
   - Lead source tracking

6. **AI Integration**:
   - OpenAI service
   - Prompt management
   - Content generation
   - Voice integration

7. **Financial Integration**:
   - Xero connection
   - Invoice management
   - Commission tracking
   - Payment processing

8. **White-Label Management**:
   - Tenant branding configuration
   - Custom domain management
   - API key and credential storage
   - Email template customization
   - Interface customization controls

9. **Property Customization**:
   - Tenant-specific property customization
   - Private property management
   - Custom metadata and fields
   - Property notes and annotations
   - Visibility and display settings

10. **API Layer**:
   - RESTful API endpoints
   - GraphQL API
   - API authentication
   - Rate limiting

## System Communication Patterns

### Synchronous Communication

For direct user interactions and immediate feedback:

- HTTP requests and responses
- Livewire component updates
- Alpine.js state management

### Asynchronous Communication

For background processing and long-running tasks:

- Queue-based processing (Laravel Queues with Redis)
- Event-driven architecture (Laravel Events)
- Scheduled tasks (Laravel Scheduler)

### External Integration Communication

For third-party service integration:

- RESTful API calls
- OAuth 2.0 authentication
- Webhook receivers
- Webhook dispatchers

## Data Flow Architecture

### User Request Flow

1. HTTP request arrives at the application
2. Middleware processes the request (authentication, tenant resolution, etc.)
3. Route dispatcher directs the request to the appropriate controller or Livewire component
4. Controller/Livewire component processes the request, interacting with services and models
5. Response is generated and returned to the user

### Background Processing Flow

1. Job is dispatched to a queue
2. Queue worker picks up the job
3. Job is processed, interacting with services and models
4. Results are stored and/or events are dispatched
5. Notification is sent if necessary

### Data Integration Flow

1. Trigger initiates data synchronization (user action, schedule, webhook)
2. Integration service fetches data from external service or prepares data for export
3. Data is transformed to match internal/external schema
4. Data is stored or transmitted
5. Confirmation/error handling occurs

## Scalability Considerations

The architecture is designed for horizontal and vertical scalability:

1. **Queue-Based Processing**: Offloads intensive tasks to background workers
2. **Caching Strategy**: Implements strategic caching for performance optimization
3. **Database Optimization**: Uses indexes, query optimization, and efficient schema design
4. **Stateless Components**: Enables horizontal scaling of web servers
5. **CDN Integration**: For media and static assets

## Security Architecture

Security is built into the architecture at multiple levels:

1. **Authentication**: Laravel Sanctum for cookie-based and token-based auth
2. **Authorization**: Spatie Permissions for role-based access control
3. **Tenant Isolation**: Data segregation at the query level
4. **CSRF Protection**: Token-based protection against cross-site request forgery
5. **XSS Protection**: Content security policy and output encoding
6. **Data Encryption**: Encryption at rest for sensitive data
7. **Audit Logging**: Comprehensive activity logging

## Deployment Architecture

The system is designed for modern cloud deployment:

1. **Container-Based**: Deployable via Docker containers
2. **Database Separation**: Database can be deployed separately from application
3. **Queue Workers**: Separate queue worker processes
4. **Redis**: Separate Redis instance for caching and queues
5. **File Storage**: Cloud storage (S3 or compatible) for files and media

## Technology Stack Integration

The architecture integrates the technologies specified in the technical stack:

1. **Laravel 12**: Core framework providing routing, ORM, authentication, etc.
2. **Livewire 3**: Server-side rendering with reactive updates
3. **Alpine.js 3.14.9**: Frontend interactivity and state management
4. **Tailwind CSS 4**: Utility-first styling
5. **MySQL**: Primary database
6. **Redis**: Caching and queue management
7. **S3**: File storage
8. **OpenAI**: AI capabilities
9. **Xero**: Financial management
10. **Vapi.ai**: Voice processing

## Architecture Diagrams

### High-Level System Architecture

```
┌────────────────────────────────────────────────────────────┐
│                   Presentation Layer                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Livewire   │  │    Blade     │  │   Alpine.js  │      │
│  │  Components  │  │  Templates   │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────────┐
│                   Application Layer                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Controllers/ │  │   Services   │  │ Jobs/Events  │      │
│  │  Components  │  │              │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────────┐
│                   Domain Layer                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │    Models    │  │    Actions   │  │   Policies   │      │
│  │              │  │              │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────────┐
│                   Infrastructure Layer                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Repositories │  │ Integrations │  │Cache/Storage │      │
│  │              │  │              │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────────┐
│                   External Services                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Database   │  │     Xero     │  │    OpenAI    │      │
│  │              │  │              │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└────────────────────────────────────────────────────────────┘
```

### Component Interaction Flow

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Frontend   │     │   Backend    │     │  Database    │
│ (Livewire +  │◄───►│  (Laravel)   │◄───►│   (MySQL)    │
│  Alpine.js)  │     │              │     │              │
└──────────────┘     └──────────────┘     └──────────────┘
                           │
                           ▼
                    ┌──────────────┐
                    │   Services   │
                    │(Business    │
                    │   Logic)    │
                    └──────────────┘
                    ┌──────▲───────┐
                    │      │       │
                    ▼      │       ▼
          ┌──────────────┐ │ ┌──────────────┐
          │ Background   │ │ │ External     │
          │ Jobs/Queues  │ │ │ Integrations │
          │ (Redis)      │ │ │ (APIs)       │
          └──────────────┘ │ └──────────────┘
                           │
                           ▼
                    ┌──────────────┐
                    │ Event System │
                    │ (Pub/Sub)    │
                    └──────────────┘
```

## Implementation Guidelines

When implementing the architecture, developers should follow these guidelines:

1. **Respect Boundaries**: Maintain clear separation between layers
2. **Single Responsibility**: Each class should have a single responsibility
3. **Dependency Injection**: Use constructor injection for dependencies
4. **Thin Controllers**: Keep controllers/components thin, push logic to services
5. **Tenant Awareness**: All data operations must be tenant-aware
6. **Domain-Driven Design**: Model the domain accurately using appropriate patterns
7. **Event-Driven**: Use events for cross-cutting concerns and decoupling
8. **Queue Long-Running Tasks**: Move slow operations to background jobs
9. **Cache Strategically**: Implement caching for performance-critical sections
10. **Test Boundaries**: Write tests for the public API of each component

## Architectural Decision Records

Key architectural decisions are documented to provide context and rationale:

1. **Single-Database Multi-Tenancy**: Chosen for simplicity and cost-effectiveness over separate databases per tenant
2. **Service-Repository Pattern**: Used for complex domains to improve testability and separation of concerns
3. **Livewire over Vue/React**: Selected for simpler development workflow and reduced context switching
4. **Queue-Based Processing**: Implemented for all potentially long-running tasks to maintain UI responsiveness
5. **Global Tenant Scope**: Applied automatically to enforce data isolation without repeated code

By adhering to this architectural design, the Fusion CRM V4 system will achieve a balance of maintainability, scalability, and developer productivity while delivering a robust, feature-rich application.