# Sprint 8: Xero Integration & Lead Management

## 📅 Timeline
- **Duration**: 2 weeks  
- **Sprint Goal**: Implement Xero accounting integration with OAuth2, contacts, and invoices
- **Sprint Goal**: Implement structured lead data management and Xero integration

## 🏆 Epics

### Epic 1: Xero OAuth2 Integration
**Description**: Implement secure OAuth2 authentication with Xero API

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 1.1 Create Xero integration model and migration | High | 6 | Sprint 1: 2.2 | Implement Xero integration data structure with migrations |
| 1.2 Implement OAuth2 authentication flow | High | 12 | 1.1 | Create secure token-based authentication with Xero |
| 1.3 Develop token refresh mechanism | High | 8 | 1.2 | Implement automatic token refresh for continued access |
| 1.4 Create Xero connection UI | Medium | 6 | 1.2, 1.3 | Create interface for connecting tenant to Xero |
| 1.5 Implement Xero webhook handling | Medium | 6 | 1.1, 1.2 | Set up webhooks for real-time updates from Xero |

**Suggested Packages**:
- `webfox/laravel-xero-oauth2 ^4.1` - [Laravel Xero OAuth2](https://github.com/webfox/laravel-xero-oauth2) - Xero OAuth2 integration
- `guzzlehttp/guzzle ^7.8` - [Guzzle](https://github.com/guzzle/guzzle) - HTTP client

### Epic 2: Xero Contact Synchronization
**Description**: Create bidirectional contact synchronization with Xero

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 2.1 Create Xero contact model and migration | High | 4 | 1.1, Sprint 3: 1.1 | Implement Xero contact mapping data structure |
| 2.2 Implement contact push service | High | 8 | 2.1, 1.3 | Create service for pushing CRM contacts to Xero |
| 2.3 Develop contact pull service | Medium | 8 | 2.1, 1.3 | Create service for pulling Xero contacts into CRM |
| 2.4 Create contact mapping UI | Medium | 6 | 2.1, 2.2, 2.3 | Implement interface for mapping contact fields |
| 2.5 Implement contact sync scheduling | Medium | 4 | 2.2, 2.3 | Create scheduled tasks for regular contact synchronization |

**Suggested Packages**:
- `laravel/horizon ^5.23` - [Laravel Horizon](https://github.com/laravel/horizon) - Queue monitoring
- `spatie/laravel-schedule-monitor ^3.3` - [Laravel Schedule Monitor](https://github.com/spatie/laravel-schedule-monitor) - Scheduling monitoring

### Epic 3: Xero Invoice Integration
**Description**: Create invoice management with Xero

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 3.1 Create Xero invoice model and migration | High | 6 | 1.1, 2.1 | Implement Xero invoice data structure |
| 3.2 Develop invoice creation service | High | 10 | 3.1, 1.3 | Create service for generating Xero invoices |
| 3.3 Implement invoice status tracking | Medium | 8 | 3.1, 3.2, 1.5 | Create functionality for tracking invoice status |
| 3.4 Create invoice management UI | Medium | 10 | 3.1, 3.2, 3.3 | Implement interface for creating and managing invoices |
| 3.5 Develop invoice payment reconciliation | Medium | 8 | 3.1, 3.3, 1.5 | Create functionality for tracking payments in Xero |

**Suggested Packages**:
- `xeroapi/xero-php-oauth2 ^3.5` - [Xero PHP OAuth2](https://github.com/XeroAPI/xero-php-oauth2) - Official Xero PHP SDK
- `league/fractal ^0.20` - [Fractal](https://github.com/thephpleague/fractal) - API data transformation

### Epic 4: Lead Management Infrastructure
**Description**: Build lead management system using Spatie Laravel-Data

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 4.1 Create lead data objects and validation | High | 8 | Sprint 1: 2.2 | Implement lead data structure using Laravel-Data |
| 4.2 Develop lead management service | High | 10 | 4.1 | Create service layer for lead operations |
| 4.3 Implement lead scoring system | Medium | 6 | 4.1, 4.2 | Build scoring logic for lead qualification |
| 4.4 Create lead assignment rules | Medium | 8 | 4.1, 4.2 | Implement rule-based lead assignment |
| 4.5 Develop lead source tracking | Medium | 6 | 4.1 | Create source attribution for leads |

**Suggested Packages**:
- `spatie/laravel-data ^4.2` - [Laravel Data](https://github.com/spatie/laravel-data) - Typed data objects
- `spatie/laravel-query-builder ^5.6` - [Laravel Query Builder](https://github.com/spatie/laravel-query-builder) - API query building

### Epic 5: Lead UI Components
**Description**: Create user interface for lead management

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 5.1 Create lead list and filtering UI | High | 8 | 4.1, 4.2 | Implement interface for viewing and filtering leads |
| 5.2 Develop lead detail view | High | 6 | 4.1, 4.2 | Create detailed lead view with actions |
| 5.3 Implement lead conversion workflow | Medium | 10 | 4.2, 5.2 | Create UI for converting leads to contacts or deals |
| 5.4 Create lead import/export tools | Medium | 8 | 4.1, 4.2 | Implement tools for bulk lead management |
| 5.5 Develop lead dashboard | Medium | 8 | 4.3, 4.5, 5.1 | Create analytics dashboard for lead performance |

**Suggested Packages**:
- `livewire/livewire ^3.3` - [Livewire](https://github.com/livewire/livewire) - Interactive UI components
- `maatwebsite/excel ^3.1` - [Laravel Excel](https://github.com/SpartnerNL/Laravel-Excel) - Excel import/export

## 🧩 Cursor IDE-Ready Prompts (MCPs)

### MCP 1.2: Implement OAuth2 Authentication Flow
```
Create a secure OAuth2 authentication flow for Xero integration in Fusion CRM V4:
1. Implement XeroAuthController with methods:
   - connect() - Initiates the OAuth2 flow and redirects to Xero authorization page
   - callback() - Handles the OAuth2 callback from Xero
   - disconnect() - Revokes tokens and disconnects from Xero

2. Create XeroTokenService with methods:
   - getAccessToken() - Retrieves a valid access token, refreshing if necessary
   - refreshToken() - Handles token refresh when expired
   - storeTokens() - Securely stores access and refresh tokens
   - revokeTokens() - Revokes tokens with Xero and removes from storage
   - getXeroTenantId() - Retrieves the Xero tenant ID for API calls
   - getXeroTokenStatus() - Returns token status (valid, expired, missing)

3. Implement secure token storage:
   - Encrypt tokens in the database
   - Store tokens with proper tenant isolation
   - Implement token events for logging and monitoring
   - Add fail-safe mechanisms for token refresh failures

4. Configure proper scopes for Xero API access:
   - offline_access
   - accounting.transactions
   - accounting.contacts
   - accounting.settings

5. Add security measures:
   - CSRF protection for OAuth endpoints
   - State parameter validation
   - Proper error handling and logging
   - Rate limiting for token operations

6. Set up tenant verification to ensure tokens are used for the correct tenant

Use best practices for OAuth2 implementation with proper separation of concerns,
error handling, and security measures. Ensure the flow works seamlessly within
the multi-tenant architecture of Fusion CRM V4.
```

### MCP 2.2: Implement Contact Push Service
```
Create a comprehensive XeroContactPushService for Fusion CRM V4 that synchronizes contacts to Xero:
1. Define XeroContactPushServiceInterface with methods:
   - pushContactToXero($clientId): ?XeroContact
   - pushContactsToXero(array $clientIds): array
   - pushAllContactsToXero($batchSize = 50): array
   - updateXeroContact($clientId): ?XeroContact
   - validateContactForXero($clientId): array
   - getXeroContactByClientId($clientId): ?XeroContact
   - getXeroContactByXeroContactId(string $xeroContactId): ?XeroContact
   - linkClientWithXeroContact($clientId, string $xeroContactId): bool
   - unlinkClientFromXero($clientId): bool
   - handleContactUpdateEvent($clientId): void
   - getContactSyncStatus($clientId): array
   - resolveContactSyncConflict($clientId, string $resolution = 'crm_wins'): bool

2. Implement XeroContactPushService class that:
   - Injects ClientService, XeroApiService, XeroContactRepository
   - Implements all interface methods with proper business logic
   - Maps CRM fields to Xero contact fields with proper formatting
   - Handles validation before pushing to prevent API errors
   - Implements proper error handling and logging
   - Maintains an audit trail of synchronization activities
   - Uses queued jobs for batch processing
   - Implements idempotent operations for reliability
   - Handles conflict resolution between systems

3. Create data mapping logic that handles:
   - Name formatting (first/last vs. organization)
   - Address standardization for Xero requirements
   - Contact detail formatting (phone, email)
   - Custom field mapping based on tenant configuration
   - Default values for required Xero fields

4. Add contact type detection and special handling:
   - Different mapping for individuals vs. companies
   - Special handling for contacts with multiple roles
   - Proper tax/VAT/GST ID formatting by country

5. Create ServiceProvider for binding the interface to implementation

Ensure all operations maintain tenant isolation and respect Xero API rate limits.
Implement comprehensive logging of all sync operations and errors for troubleshooting.
```

### MCP 3.2: Develop Invoice Creation Service
```
Create a sophisticated XeroInvoiceService for Fusion CRM V4 that handles invoice creation and management:
1. Define XeroInvoiceServiceInterface with methods:
   - createInvoice(array $data): ?XeroInvoice
   - updateInvoice($invoiceId, array $data): bool
   - getInvoiceById($invoiceId): ?XeroInvoice
   - getInvoiceByXeroId(string $xeroInvoiceId): ?XeroInvoice
   - getInvoicesByClient($clientId, $paginate = true): Collection|LengthAwarePaginator
   - getInvoicesByStatus(string $status, $paginate = true): Collection|LengthAwarePaginator
   - sendInvoiceToXero($invoiceId): bool
   - getInvoiceFromXero(string $xeroInvoiceId): ?array
   - syncInvoiceStatus($invoiceId): bool
   - syncAllInvoiceStatuses($batchSize = 50): array
   - downloadInvoicePdf($invoiceId): ?string
   - markInvoiceAsSent($invoiceId): bool
   - voidInvoice($invoiceId): bool
   - getInvoicePayments($invoiceId): Collection
   - calculateInvoiceTotals(array $items, array $taxRates = []): array
   - validateInvoiceItems(array $items): bool
   - getInvoiceTypes(): array
   - getInvoiceTemplates(): array
   - getInvoiceHistory($invoiceId): Collection

2. Implement XeroInvoiceService class that:
   - Injects XeroApiService, XeroContactService, XeroInvoiceRepository
   - Implements all interface methods with proper business logic
   - Handles complex invoice creation with line items, taxes, and discounts
   - Maps CRM data to Xero invoice format
   - Implements proper validation before API calls
   - Handles various invoice types (ACCREC, ACCPAY)
   - Maintains invoice status synchronization
   - Uses queued jobs for background processing
   - Implements proper error handling and logging
   - Maintains tenant isolation throughout

3. Create invoice item handling logic:
   - Line item creation and validation
   - Tax calculation and application
   - Discount handling
   - Quantity and unit price formatting
   - Account code mapping

4. Implement invoice tracking features:
   - Status change monitoring
   - Payment reconciliation
   - Due date tracking
   - Overdue notification
   - Payment history

5. Add special features:
   - Invoice templates and branding
   - Multi-currency support
   - Invoice attachments
   - Credit note handling
   - Partial payment tracking

Focus on creating a robust service that handles the complexities of invoice
management while providing a clean interface for other components to use.
Ensure all operations are properly logged for audit purposes and error recovery.
```

### MCP 4.1: Create Lead Data Objects and Validation
```
Implement lead data structure using Spatie Laravel-Data for Fusion CRM V4:

1. Install spatie/laravel-data ^4.2:
   - Add to composer.json
   - Configure package settings

2. Create base LeadData class:
   - Extend Spatie\LaravelData\Data
   - Define typed properties with validation rules:
     - id: int|null
     - tenant_id: int
     - first_name: string
     - last_name: string
     - email: string (with email validation)
     - phone: string|null (with phone format validation)
     - company: string|null
     - job_title: string|null
     - source: string (e.g., website, referral, campaign)
     - source_details: array|null (JSON data about the source)
     - status: enum (New, Contacted, Qualified, Disqualified)
     - score: int (0-100)
     - notes: string|null
     - assigned_to: int|null (user ID)
     - campaign_id: int|null
     - tags: array
     - custom_fields: array
     - created_at: Carbon
     - updated_at: Carbon

3. Implement additional data objects:
   - LeadScoreData - For calculating and updating lead scores
   - LeadSourceData - For tracking lead attribution
   - LeadCustomFieldData - For handling dynamic custom fields
   - LeadAssignmentData - For lead routing rules

4. Create data transformation methods:
   - fromEloquent() - Map Eloquent model to Data object
   - toEloquent() - Map Data object to Eloquent model
   - toArray() - Convert Data object to array
   - fromRequest() - Create Data object from request input

5. Add validation rules for each property:
   - Use Laravel's validation system
   - Add custom validation rules as needed
   - Implement nested validation for complex properties

6. Implement data casting:
   - Date/time casting
   - Enum casting
   - Custom field casting

7. Set up API resource transformation:
   - Configure JSON serialization
   - Handle API versioning
   - Implement pagination support

Take advantage of Laravel-Data's strong typing and validation features
to ensure data integrity throughout the lead management system. This
creates a more maintainable and type-safe codebase.
```

### MCP 4.2: Develop Lead Management Service
```
Create a lead management service using Laravel-Data for Fusion CRM V4:

1. Implement LeadManagementService with typed methods:
   - createLead(LeadData $data): Lead
   - updateLead(int $id, LeadData $data): Lead
   - deleteLead(int $id): bool
   - getLead(int $id): ?LeadData
   - listLeads(array $filters = [], int $perPage = 15): Paginator
   - assignLead(int $leadId, int $userId): bool
   - scoreLead(int $leadId, ?LeadScoreData $scoreData = null): int
   - convertToContact(int $leadId, array $additionalData = []): Contact
   - convertToDeal(int $leadId, array $dealData = []): Deal
   - importLeads(Collection|array $leadsData): array
   - exportLeads(array $filters = []): string (CSV/Excel export)

2. Implement lead filtering and search:
   - Use spatie/laravel-query-builder for API filtering
   - Create complex filter combinations (status, score range, date, etc.)
   - Set up full-text search on lead fields

3. Add lead scoring logic:
   - Define scoring algorithms based on lead attributes
   - Implement automatic score updates on lead changes
   - Create score threshold notifications

4. Develop lead assignment rules:
   - Round-robin assignment
   - Territory-based assignment
   - Score-based assignment
   - Custom rule engine

5. Set up lead activity tracking:
   - Track lead interactions
   - Log status changes
   - Record conversion events

6. Implement lead deduplication:
   - Detect potential duplicates
   - Merge duplicate leads
   - Prevent duplicate creation

7. Configure tenant isolation:
   - Ensure all operations respect tenant boundaries
   - Implement tenant-specific configurations

Take advantage of Laravel-Data's strong typing and validation to ensure
data integrity throughout lead operations, reducing bugs and improving
maintainability.
```

### MCP 5.3: Implement Lead Conversion Workflow
```
Create a lead conversion workflow using Livewire for Fusion CRM V4:

1. Implement LeadConversion Livewire component:
   - Create step-by-step wizard interface
   - Support conversion to Contact, Deal, or both
   - Validate data at each step
   - Provide summaries and confirmations

2. Develop conversion business logic:
   - Implement LeadConversionService with typed methods
   - Handle data transformation between lead and target entities
   - Manage relationships during conversion
   - Create activity records for the conversion

3. Create conversion options:
   - Keep lead after conversion (for reference)
   - Delete lead after conversion
   - Mark lead as converted but retain
   - Clone specific fields vs. all fields

4. Implement post-conversion actions:
   - Create follow-up tasks
   - Send notifications
   - Update lead source performance metrics
   - Generate conversion reports

5. Add validation rules for conversion:
   - Required fields for contact/deal creation
   - Business rules for valid conversions
   - Prevent invalid state transitions

6. Create conversion history:
   - Track all conversions with metadata
   - Provide conversion analytics
   - Support reverting conversions if needed

7. Implement UI components:
   - Multi-step form with progress indicators
   - Field mapping interface
   - Confirmation dialogs
   - Success/error messaging

Take advantage of Livewire's reactive data binding and Laravel-Data's 
typed objects to create a seamless, error-resistant conversion process
that preserves data integrity.
```

### MCP 5.4: AI Photo Extraction from Brochures (Geanelle's Requirement)
```
Implement AI-powered photo extraction from uploaded brochures and PDFs:

1. Install required packages:
   - spatie/pdf-to-image for PDF processing
   - intervention/image for image manipulation
   - openai-php/client for AI vision processing

2. Create BrochurePhotoExtractor service:
   - extractPhotosFromPdf(string $pdfPath): array
   - extractPhotosFromImage(string $imagePath): array
   - categorizeExtractedImages(array $images): array
   - processWithAIVision(string $imagePath): array

3. Implement PDF processing:
   - Convert PDF pages to images
   - Extract embedded images from PDF
   - Handle multi-page brochures
   - Maintain image quality during extraction

4. Add AI vision integration:
   - Use OpenAI Vision API to analyze extracted images
   - Categorize images (facade, floor plan, interior, amenities, etc.)
   - Extract metadata (room types, features, etc.)
   - Generate descriptive captions

5. Create image categorization logic:
   - Facade/exterior photos
   - Floor plans and layouts
   - Interior room photos
   - Amenity and feature photos
   - Location and surrounding area
   - Marketing graphics (logos, text overlays)

6. Implement storage and organization:
   - Store extracted images in organized folders
   - Create database records for each extracted image
   - Link images to property/project records
   - Generate thumbnails and optimized versions

7. Add Filament admin interface:
   - Upload brochure files
   - View extraction progress
   - Review and approve extracted images
   - Manual categorization override
   - Bulk processing capabilities

8. Create extraction workflow:
   - Queue-based processing for large files
   - Progress tracking and notifications
   - Error handling and retry logic
   - Batch processing for multiple brochures

9. Implement validation and quality control:
   - Image quality assessment
   - Duplicate detection
   - Minimum resolution requirements
   - File format validation

10. Add reporting and analytics:
    - Extraction success rates
    - Processing time metrics
    - Image category distribution
    - Quality assessment reports

This feature addresses Geanelle's specific requirement for automated photo
extraction from brochures, using AI to intelligently categorize and organize
the extracted images for easy use in property profiles.
```
