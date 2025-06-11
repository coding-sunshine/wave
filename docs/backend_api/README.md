# Backend API Documentation

*Comprehensive API documentation for Fusion CRM v4*

---

## Overview

The Fusion CRM API provides RESTful endpoints for all CRM functionality, built on Laravel with Wave SaaS framework extensions. The API supports both JWT authentication for external integrations and session-based authentication for web applications.

## Current API Implementation

### ✅ Implemented Features

| Feature | Description | Status | Location |
|---------|-------------|--------|----------|
| **JWT Authentication** | Token-based authentication for API access | ✅ IMPLEMENTED | `wave/src/Http/Controllers/API/AuthController.php` |
| **User Management** | Basic user operations via Filament | ✅ IMPLEMENTED | Filament admin interface |
| **Simple Blog API** | Basic post retrieval | ✅ IMPLEMENTED | `app/Http/Controllers/Api/ApiController.php` |
| **Wave Foundation** | Core Wave API structure | ✅ IMPLEMENTED | `wave/routes/api.php` |

### 🔴 Not Yet Implemented (CRM APIs)

| Feature | Description | Priority | Implementation Phase |
|---------|-------------|----------|---------------------|
| **Contact API** | Contact and lead management endpoints | HIGH | Phase 1 (Weeks 1-4) |
| **Property API** | Property listing management endpoints | HIGH | Phase 1 (Weeks 5-8) |
| **Deal API** | Sales pipeline and deal tracking | HIGH | Phase 1 (Weeks 9-12) |
| **Activity API** | Activity and interaction logging | MEDIUM | Phase 2 (Weeks 13-16) |
| **Task API** | Task management and assignment | MEDIUM | Phase 2 (Weeks 17-20) |
| **Document API** | File upload and management | MEDIUM | Phase 2 (Weeks 21-24) |

## Current API Endpoints

### Authentication Endpoints
Located in: `wave/src/Http/Controllers/API/AuthController.php`

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | Authenticate user and return JWT token |
| POST | `/api/v1/auth/logout` | Invalidate JWT token |
| POST | `/api/v1/auth/refresh` | Refresh JWT token |
| POST | `/api/v1/auth/register` | Register new user account |
| GET | `/api/v1/auth/user` | Get authenticated user details |

### Current Simple API
Located in: `app/Http/Controllers/Api/ApiController.php`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/posts` | Get all blog posts |

## API Configuration

### JWT Configuration (`config/jwt.php`)
- JWT secret key management
- Token TTL: 60 minutes (configurable)
- Refresh TTL: 20160 minutes (2 weeks)
- Algorithm: HS256

### Authentication
- JWT tokens for API access
- Session-based auth for web app
- Spatie permissions integration ready

## Required CRM API Development

### Phase 1: Core APIs (Weeks 1-12)

#### Contact Management API
```bash
GET    /api/v1/contacts              # List contacts
POST   /api/v1/contacts              # Create contact
GET    /api/v1/contacts/{id}         # Get contact
PUT    /api/v1/contacts/{id}         # Update contact
DELETE /api/v1/contacts/{id}         # Delete contact
POST   /api/v1/contacts/{id}/activities # Log activity
```

#### Property Management API
```bash
GET    /api/v1/properties            # List properties
POST   /api/v1/properties            # Create property
GET    /api/v1/properties/{id}       # Get property
PUT    /api/v1/properties/{id}       # Update property
DELETE /api/v1/properties/{id}       # Delete property
POST   /api/v1/properties/{id}/media # Upload media
```

#### Deal Management API
```bash
GET    /api/v1/deals                 # List deals
POST   /api/v1/deals                 # Create deal
GET    /api/v1/deals/{id}            # Get deal
PUT    /api/v1/deals/{id}            # Update deal
POST   /api/v1/deals/{id}/stage      # Move stage
POST   /api/v1/deals/{id}/close      # Close deal
```

## Available Backend Technologies

### ✅ Implemented Technologies

| Technology | Description | Documentation |
|------------|-------------|---------------|
| **Laravel Folio** | File-based routing system | [Folio](./folio.md) |
| **Laravel Pail** | Enhanced log viewer for development | [Development Tools](../development/laravel_pail.md) |
| **Spatie Permissions** | Role and permission management | [Permissions](../auth_user_management/spatie_permissions.md) |
| **JWT Authentication** | Token-based API authentication | [JWT Authentication](../auth_user_management/jwt_authentication.md) |
| **Google Analytics** | User behavior tracking and analytics | [Google Analytics](./google_analytics.md) |
| **Prism PHP** | AI integration framework for GPT-4 | [Prism](../prism_relay_doc/prism.md) |
| **Prism Relay** | Tool execution system for AI assistants | [Relay](../prism_relay_doc/relay.md) |

### Backend Infrastructure

1. **Laravel 12.16 Framework**
   - Latest Laravel features
   - PSR-12 coding standards
   - Eloquent ORM with relationships

2. **Database Layer**
   - MySQL/SQLite support
   - Migration system
   - Seeders for development data

3. **Queue System**
   - Background job processing
   - Email queue handling
   - Task scheduling ready

4. **Caching Layer**
   - Redis support configured
   - Model caching patterns
   - API response caching ready

## Development Patterns

### Controller Structure
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function index(): JsonResponse
    {
        // Implementation needed
    }
}
```

### Route Structure (`routes/api.php`)
```php
Route::middleware(['auth:api'])->prefix('v1')->group(function () {
    // CRM API routes will be added here
});
```

### Response Format
```json
{
  "success": true,
  "data": {},
  "message": "Success",
  "meta": {
    "pagination": {}
  }
}
```

## Security & Middleware

### Available Middleware
- `auth:api` - JWT authentication
- `throttle:api` - Rate limiting
- `cors` - Cross-origin resource sharing
- `permission:name` - Spatie permission checking

### Security Features
- JWT token security
- Rate limiting configured
- Input validation ready
- CORS support

## Development Guidelines

### API Development Steps
1. Create model and migration
2. Create API controller
3. Add validation rules
4. Define routes with middleware
5. Add permission checks
6. Write API tests

### Testing Structure
- API tests in `tests/Feature/Api/`
- Use Pest PHP framework
- JWT authentication testing
- Permission testing

## Next Steps

### Immediate (Phase 1)
1. Create Contact API endpoints
2. Create Property API endpoints
3. Create Deal API endpoints
4. Add comprehensive validation
5. Implement permission checks

### Future (Phase 2)
1. Add file upload endpoints
2. Implement webhook system
3. Add advanced search/filtering
4. Create bulk operation endpoints

---

*For current implementation details, see individual documentation files. For CRM development progress, check the [main features documentation](../features.md).*
