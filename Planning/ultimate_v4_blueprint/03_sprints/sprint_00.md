# Sprint 0: Wave Kit Setup & Windsurf IDE Configuration

## 📅 Timeline
- **Duration**: 1 week
- **Sprint Goal**: Initialize project with Wave Kit, configure development environment, and prepare for AI-assisted development

## 🏆 Epics

### Epic 1: Wave Kit Installation & Configuration
**Description**: Set up the initial project using Wave Kit as the foundation for Fusion CRM V4

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 0.1.1 Install Wave Kit | High | 4 | None | Set up Wave Kit following DevDojo documentation |
| 0.1.2 Configure environment variables | High | 2 | 0.1.1 | Set up .env file with necessary configuration |
| 0.1.3 Configure database connection | High | 1 | 0.1.2 | Set up database connection and run migrations |
| 0.1.4 Test Wave Kit core functionality | High | 4 | 0.1.3 | Verify auth, teams, billing, and user management |
| 0.1.5 Update composer dependencies | Medium | 2 | 0.1.4 | Update packages for PHP 8.4 compatibility |

**Installation Commands**:
```bash
# Install Wave Kit
composer create-project devdojo/wave

# Install dependencies
composer install

# Copy .env file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Install NPM dependencies
npm install && npm run dev
```

### Epic 2: Windsurf IDE Configuration
**Description**: Configure project for optimal AI-assisted development with Windsurf IDE

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 0.2.1 Create .windsurf directory | High | 1 | 0.1.1 | Set up directory for Windsurf IDE configuration |
| 0.2.2 Define code generation prompts | High | 4 | 0.2.1 | Create AI prompts for generating controllers, models, etc. |
| 0.2.3 Create blueprint reference files | Medium | 6 | 0.2.2 | Create reference files for architecture, patterns, etc. |
| 0.2.4 Define test generation templates | Medium | 4 | 0.2.3 | Create templates for generating Pest tests |
| 0.2.5 Configure code quality tools | Medium | 3 | 0.2.4 | Set up Laravel Pint, PHPStan with Windsurf integration |

**Suggested Files**:
- `.windsurf/templates/controller.md` - Controller template with PSR-12 compliance
- `.windsurf/templates/model.md` - Model template with tenant-aware implementation
- `.windsurf/templates/livewire.md` - Livewire component template
- `.windsurf/templates/test.md` - Pest test template

### Epic 3: Wave Kit Customization for Fusion CRM
**Description**: Adapt Wave kit's existing structure for Fusion CRM's multi-tenant architecture

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 0.3.1 Analyze Wave's team structure | High | 4 | 0.1.4 | Evaluate how to adapt team model for tenancy |
| 0.3.2 Define tenant model strategy | High | 6 | 0.3.1 | Design tenant model extension from Wave's team model |
| 0.3.3 Adapt Wave's user model | High | 4 | 0.3.2 | Modify user model for tenant association |
| 0.3.4 Update authentication flow | Medium | 6 | 0.3.3 | Modify auth flow for tenant context |
| 0.3.5 Adapt Wave's billing system | Medium | 8 | 0.3.4 | Modify billing system for tenant-based billing |

## 🧩 Windsurf IDE-Ready Prompts

### Prompt 1: Wave Kit Tenant Adaptation
```
Create a tenant-aware architecture by extending Wave Kit's existing User and Team models:

1. Create a Tenant model that extends or relates to Wave's Team model:
   - Add tenant-specific fields like API keys, custom settings, etc.
   - Implement tenant settings management
   - Set up tenant database schema migration

2. Modify the User model:
   - Add tenant relationship (many-to-one)
   - Implement tenant scoping trait
   - Update authentication to set current tenant context

3. Create TenantScope class:
   - Implement global scope for tenant filtering
   - Handle console command exceptions
   - Add tenant context management

4. Add TenantMiddleware:
   - Set current tenant based on authenticated user
   - Handle cross-tenant access prevention
   - Configure middleware in Kernel.php

Use PHP 8.4 features where applicable and follow PSR-12 standards.
Implement strict typing with declare(strict_types=1) and proper type hints.
Add PHPStan compatible docblocks for all methods.
```

### Prompt 2: Setting Up Laravel Pint for PSR-12 Compliance
```
Configure Laravel Pint for Fusion CRM V4 with PSR-12 compliance:

1. Create pint.json in project root with the following configuration:
   - Set preset to "psr12"
   - Configure rules for strict_types declaration
   - Set up PHP 8.4 compatibility
   - Configure custom rules for Fusion CRM conventions

2. Add Composer script for linting:
   - Add "lint" command to run pint
   - Add pre-commit hook configuration

3. Create GitHub workflow for automated linting:
   - Set up action to run on pull requests
   - Configure GitHub Actions YAML file

4. Configure Windsurf IDE integration:
   - Add lint command to .windsurf configuration
   - Configure auto-formatting on save

Ensure all configuration follows the project's coding standards and best practices.
```

### Prompt 3: Setting Up Tenant-Aware Middleware
```
Create a tenant-aware middleware implementation for Fusion CRM V4:

1. Generate TenantMiddleware class:
   - Implement handle method to set tenant context
   - Add tenant resolution logic
   - Handle tenant-switching capability
   - Implement proper error handling for missing tenant

2. Register middleware in Kernel.php:
   - Add to web middleware group
   - Configure route middleware alias

3. Create tests for tenant middleware:
   - Test tenant resolution from authenticated user
   - Test tenant-switching functionality
   - Test error handling for missing tenant

4. Implement tenant context service:
   - Create TenantManager service
   - Add current tenant getter/setter methods
   - Implement tenant context caching
   - Add event dispatching for tenant context changes

Implement strict typing, proper exception handling, and comprehensive docblocks.
Follow PSR-12 standards and use PHP 8.4 features where applicable.
Add appropriate unit and feature tests using Pest.
```
