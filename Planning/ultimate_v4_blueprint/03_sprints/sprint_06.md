# Sprint 6: Document Management System

## 📅 Timeline
- **Duration**: 2 weeks
- **Sprint Goal**: Implement document management core infrastructure
- **Priority Adjustment**: Moved after authentication/pipeline systems (Sprints 2-5)

## 🏆 Epics

### Epic 1: Document Core Services
**Description**: Implement essential document storage and version control (V3 upgrade focus) utilizing Spatie Laravel-MediaLibrary

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 1.1 Integrate Laravel-MediaLibrary | High | 6 | Sprint 1: 2.2 | Implement models with MediaLibrary traits and migrations |
| 1.2 Configure media collections | Medium | 4 | 1.1 | Set up media collections and conversion presets |
| 1.3 Set up document storage service | High | 8 | 1.1 | Configure cloud storage integration with Flysystem |
| 1.4 Implement document versioning | Medium | 10 | 1.1, 1.3 | Create version tracking for document changes |
| 1.5 Develop document metadata extraction | Medium | 6 | 1.1, 1.3 | Implement metadata extraction from uploaded files |

**Suggested Packages**:
- `spatie/laravel-medialibrary ^11.0` - [Spatie Laravel MediaLibrary](https://github.com/spatie/laravel-medialibrary) - Complete media management with conversions
- `league/flysystem-aws-s3-v3 ^3.22` - [Flysystem S3](https://github.com/thephpleague/flysystem-aws-s3-v3) - S3 storage adapter

### Epic 2: Document Management Services
**Description**: Create service layer for document operations

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 2.1 Create document repository | High | 6 | 1.1, 1.2 | Implement repository pattern for document data access |
| 2.2 Develop document service | High | 8 | 2.1, 1.3 | Create service layer for document business logic |
| 2.3 Implement document sharing service | Medium | 6 | 2.2 | Create functionality for document sharing |
| 2.4 Set up document permission system | Medium | 8 | 2.2, 2.3 | Implement granular permissions for documents |
| 2.5 Create document search service | Medium | 6 | 2.1, 1.5 | Implement full-text search for documents |

**Suggested Packages**:
- `laravel/scout ^10.5` - [Laravel Scout](https://github.com/laravel/scout) - Full-text search
- `smalot/pdfparser ^2.7` - [PDF Parser](https://github.com/smalot/pdfparser) - Extract text from PDF documents

### Epic 3: Document Management UI
**Description**: Build user interface components for document management

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 3.1 Create document library component | High | 10 | 2.1, 2.2 | Implement Livewire component for document browsing |
| 3.2 Develop document upload component | High | 8 | 1.3, 2.2 | Create drag-and-drop file upload interface |
| 3.3 Implement document viewer | Medium | 12 | 3.1, 2.2 | Create in-browser viewer for various document types |
| 3.4 Create document sharing UI | Medium | 6 | 2.3, 3.1 | Implement interface for document sharing |
| 3.5 Develop document version history UI | Medium | 6 | 1.4, 3.1 | Create interface for viewing document versions |

**Suggested Packages**:
- `alpinejs/alpine ^3.13` - [Alpine.js](https://github.com/alpinejs/alpine) - JavaScript framework for interactions
- `filepond/filepond ^4.30` - [FilePond](https://github.com/pqina/filepond) - File upload library

## 🧩 Cursor IDE-Ready Prompts (MCPs)

### MCP 1.1: Integrate Laravel-MediaLibrary
```
Integrate Spatie Laravel-MediaLibrary into Fusion CRM V4 with the following specifications:

1. Install and configure spatie/laravel-medialibrary ^11.0:
   - Run composer installation
   - Publish migrations and config
   - Set up S3 disk configuration
   - Configure media conversions settings

2. Create base models with MediaLibrary integration:
   - Implement HasMedia and InteractsWithMedia trait in appropriate models
   - Set up media collections for different document types:
     - properties: 'images', 'floor_plans', 'virtual_tours'
     - clients: 'documents', 'avatars'
     - deals: 'contracts', 'agreements'
     - users: 'avatars'
   - Configure responsive image conversions for:
     - Thumbnails (200x200)
     - Preview (800x600)
     - Full size with optimization

3. Configure custom path generators for tenant isolation:
   - Implement TenantPathGenerator class
   - Ensure all media is stored in tenant-specific paths
   - Set up proper URL generation

4. Set up document metadata extraction:
   - Extract metadata during upload
   - Store as custom properties on media object
   - Create indexes for searchable properties

5. Implement document versioning:
   - Configure replacements with versioning
   - Add version tracking through custom properties
   - Set up version comparison capabilities

Take advantage of Laravel-MediaLibrary's built-in features for handling file uploads,
generating thumbnails, and managing media across filesystems. Leverage the package's
ability to associate files with multiple Eloquent models and generate responsive images.
```

### MCP 2.2: Develop Document Service
```
Create a DocumentService class for Fusion CRM V4 that implements the following:
1. Define DocumentServiceInterface with methods:
   - getAllDocuments($filters = [], $perPage = 15): LengthAwarePaginator
   - getDocumentById($id): ?Document
   - getDocumentsByType(string $type, $perPage = 15): LengthAwarePaginator
   - getDocumentsByCategory($categoryId, $perPage = 15): LengthAwarePaginator
   - getDocumentsByRelated(string $relatedType, $relatedId, $perPage = 15): LengthAwarePaginator
   - createDocument(UploadedFile $file, array $data): Document
   - updateDocument($id, array $data, ?UploadedFile $file = null): bool
   - deleteDocument($id): bool
   - restoreDocument($id): bool
   - getDocumentVersions($documentId): Collection
   - createNewVersion($documentId, UploadedFile $file, ?string $changeSummary = null): DocumentVersion
   - restoreVersion($documentId, $versionId): bool
   - shareDocument($documentId, array $userIds, array $permissions = []): bool
   - unshareDocument($documentId, array $userIds): bool
   - getSharedUsers($documentId): Collection
   - isDocumentSharedWithUser($documentId, $userId): bool
   - getPublicUrl($documentId, $expiresInMinutes = 60): string
   - recordDocumentAccess($documentId, $userId): void
   - searchDocuments(string $term, $filters = []): Collection
   - generateThumbnail($documentId): ?string
   - getRecentDocuments($limit = 5): Collection

2. Implement DocumentService class that:
   - Injects DocumentRepository
   - Implements all interface methods with proper business logic
   - Handles file storage using configured disk (local, S3, etc.)
   - Manages document versioning
   - Implements document sharing with permission checks
   - Handles file type validations and security checks
   - Maintains proper tenant isolation
   - Uses transactions where appropriate
   - Implements caching strategy for frequently accessed data

3. Create methods for extracting and storing metadata from different file types
   (PDF, Word, Excel, etc.)

4. Create ServiceProvider for binding the interface to implementation

Follow Laravel 12 best practices for service implementation with secure
file handling, proper validation, and clean separation of concerns.
```

### MCP 3.1: Create Document Library Component
```
Create a comprehensive document library component for Fusion CRM V4:
1. Implement a Livewire component DocumentLibraryComponent that:
   - Displays documents in both grid and list views
   - Supports sorting by name, date, size, type
   - Implements folder/category navigation
   - Includes search and filtering capabilities
   - Handles pagination and lazy loading
   - Adapts UI based on user permissions

2. Implement document item component (DocumentItemComponent) with:
   - Thumbnail preview based on file type
   - Quick actions (download, share, delete, rename)
   - Context menu for additional actions
   - Selection capability for bulk operations
   - Visual indicators for shared documents

3. Create document action bar with:
   - Upload button with drag-and-drop zone
   - New folder creation
   - Bulk actions (download, share, delete)
   - View toggles (grid/list)
   - Sort and filter controls

4. Add features:
   - Breadcrumb navigation through folder hierarchy
   - Folder tree sidebar for quick navigation
   - Recent documents section
   - Favorites/pinned documents
   - Shared documents section
   - Drag-and-drop organization

5. Implement with:
   - Proper loading states and skeleton UI
   - Responsive design for all screen sizes
   - Keyboard navigation and accessibility
   - Real-time updates when documents change

Ensure all components maintain tenant isolation and respect user permissions.
Follow Tailwind and Alpine.js best practices for a clean, modern UI with
optimal performance. Use FilePond for advanced upload handling and leverage
browser capabilities for client-side features.