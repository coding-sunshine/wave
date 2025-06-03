# Sprint 1: CRM Model Extension & Filament Resources

## 📅 Timeline
- **Duration**: 2 weeks
- **Sprint Goal**: Extend Wave foundation with CRM-specific models, migrations, and Filament resources

## 🏆 Epics

### Epic 1: CRM Model Creation
**Description**: Create CRM-specific models that extend Wave's existing architecture and patterns

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 1.1 Extend Wave User model for CRM contacts | High | 6 | Sprint 0 | Add CRM-specific fields and relationships to existing User model |
| 1.2 Create Property model with Wave patterns | High | 8 | 1.1 | Create Property model following Wave's model conventions |
| 1.3 Create Lead model with team integration | High | 6 | 1.1, 1.2 | Create Lead model using Wave's team structure for multi-tenancy |
| 1.4 Create Deal model with pipeline stages | High | 8 | 1.2, 1.3 | Create Deal model for sales pipeline management |
| 1.5 Create Activity model for interaction tracking | Medium | 6 | 1.1-1.4 | Create Activity model for tracking all CRM interactions |

**Wave Integration Points**:
- Extend existing `app/Models/User.php` with CRM fields
- Use Wave's team structure for multi-tenancy
- Follow Wave's model trait patterns
- Integrate with Wave's existing media system

### Epic 2: Database Migrations
**Description**: Create database migrations that extend Wave's existing schema

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 2.1 Create migration to extend users table | High | 4 | 1.1 | Add CRM-specific fields to existing users table |
| 2.2 Create properties table migration | High | 6 | 1.2 | Create properties table with proper relationships |
| 2.3 Create leads table migration | High | 4 | 1.3 | Create leads table with team and user relationships |
| 2.4 Create deals table migration | High | 6 | 1.4 | Create deals table with pipeline and relationship structure |
| 2.5 Create activities table migration | Medium | 4 | 1.5 | Create activities table for interaction tracking |

**Migration Strategy**:
- Extend existing Wave tables where possible
- Maintain Wave's existing foreign key patterns
- Use Wave's team_id for multi-tenancy
- Follow Wave's timestamp and soft delete patterns

### Epic 3: Filament Resource Creation
**Description**: Create CRM-specific Filament resources following Wave's established patterns

#### Tasks

| Task | Priority | Effort (Hours) | Dependencies | Description |
|------|----------|----------------|--------------|-------------|
| 3.1 Extend UserResource for CRM functionality | High | 8 | 2.1 | Enhance existing UserResource with CRM fields and relationships |
| 3.2 Create PropertyResource | High | 12 | 2.2 | Create PropertyResource following Wave's resource patterns |
| 3.3 Create LeadResource | High | 10 | 2.3 | Create LeadResource with lead management functionality |
| 3.4 Create DealResource | High | 12 | 2.4 | Create DealResource with pipeline management |
| 3.5 Create ActivityResource | Medium | 8 | 2.5 | Create ActivityResource for interaction tracking |

**Filament Integration**:
- Follow patterns from existing Wave resources (UserResource, RoleResource, etc.)
- Integrate with Wave's existing navigation structure
- Use Wave's form and table conventions
- Maintain Wave's UI/UX consistency

## 🧩 Cursor IDE-Ready Prompts (MCPs)

### MCP 1.1: Extend Wave User Model for CRM
```
I need to extend the existing Wave User model to support CRM functionality. Please help me:

1. Analyze the current Wave User model in app/Models/User.php and identify:
   - Existing fields and relationships
   - Current traits and interfaces
   - Authentication and team relationships

2. Add CRM-specific fields to the users table migration:
   - phone (string, nullable)
   - address (text, nullable)
   - license_number (string, nullable)
   - contact_preference (enum: email, phone, sms)
   - crm_settings (json, nullable)
   - lead_source (string, nullable)
   - status (enum: active, inactive, prospect)

3. Extend the User model with CRM relationships:
   - hasMany leads (as assigned agent)
   - hasMany properties (as listing agent)
   - hasMany deals (as agent)
   - hasMany activities (as user)

4. Add CRM-specific methods:
   - getFullAddressAttribute()
   - getPreferredContactMethodAttribute()
   - scopeAgents()
   - scopeClients()

Ensure all changes maintain compatibility with existing Wave functionality and follow Wave's coding patterns.
```

### MCP 1.2: Create Property Model with Wave Patterns
```
Create a Property model that integrates seamlessly with the Wave foundation. The model should:

1. Follow Wave's model structure and conventions:
   - Use Wave's base model patterns
   - Implement proper relationships
   - Include appropriate traits (SoftDeletes, HasFactory)
   - Use Wave's team structure for multi-tenancy

2. Include these fields:
   - team_id (foreign key to Wave's teams table)
   - title, description, price, address
   - property_type (enum: house, apartment, commercial, land)
   - status (enum: available, under_contract, sold, withdrawn)
   - listed_by (foreign key to users table)
   - features (json for property features)
   - coordinates (latitude, longitude)
   - created_at, updated_at, deleted_at

3. Define relationships:
   - belongsTo Team (using Wave's team model)
   - belongsTo User (listing agent)
   - hasMany Leads (interested leads)
   - hasMany Deals (related deals)
   - hasMany Activities (property activities)
   - morphMany Media (using Wave's media system)

4. Include scopes and methods:
   - scopeAvailable()
   - scopeByType()
   - scopeInPriceRange()
   - getFormattedPriceAttribute()
   - getFullAddressAttribute()

5. Create the corresponding migration file that:
   - Uses Wave's migration patterns
   - Includes proper indexes
   - Sets up foreign key constraints
   - Follows Wave's naming conventions

Ensure the model integrates with Wave's existing team structure for proper multi-tenancy.
```

### MCP 1.3: Create Lead Model with Team Integration
```
Create a Lead model that leverages Wave's team structure for multi-tenancy. The model should:

1. Follow Wave's model patterns and include:
   - team_id (foreign key to Wave's teams table)
   - name, email, phone, address
   - source (enum: website, referral, cold_call, social_media, advertisement)
   - status (enum: new, contacted, qualified, unqualified, converted)
   - assigned_to (foreign key to users table)
   - score (integer, 0-100)
   - notes (text)
   - contact_preference (enum: email, phone, sms)
   - last_contacted_at (timestamp)

2. Define relationships following Wave patterns:
   - belongsTo Team (Wave's team model)
   - belongsTo User (assigned agent)
   - hasMany Activities (lead activities)
   - belongsToMany Properties (interested properties)
   - hasOne Deal (if converted)

3. Include scopes and methods:
   - scopeByStatus()
   - scopeBySource()
   - scopeAssignedTo()
   - scopeHighScore()
   - getScoreColorAttribute()
   - getTimeSinceContactAttribute()
   - markAsContacted()

4. Create migration with:
   - Proper foreign key constraints to Wave tables
   - Indexes for performance
   - Wave's timestamp patterns

5. Add model factory for testing that:
   - Uses Wave's existing User and Team factories
   - Creates realistic lead data
   - Follows Wave's factory patterns

Ensure the model works seamlessly with Wave's existing team-based multi-tenancy.
```

### MCP 3.2: Create PropertyResource Following Wave Patterns
```
Create a PropertyResource for Filament that follows the patterns established by Wave's existing resources. The resource should:

1. Analyze Wave's existing resources (UserResource, RoleResource, etc.) and follow their patterns for:
   - Form field organization and validation
   - Table column configuration
   - Action implementations
   - Navigation integration

2. Create PropertyResource with these features:
   - Form with sections for basic info, location, features, and media
   - File upload integration using Wave's media system
   - Proper validation following Wave's patterns
   - Relationship management for team and user associations

3. Include table configuration with:
   - Sortable and searchable columns
   - Filters for property type, status, and price range
   - Bulk actions following Wave's patterns
   - Custom actions for property management

4. Add resource pages:
   - ListProperties page with advanced filtering
   - CreateProperty page with step-by-step form
   - EditProperty page with media management
   - ViewProperty page with detailed information

5. Integrate with Wave's navigation:
   - Add to appropriate navigation group
   - Use Wave's icon system
   - Follow Wave's permission patterns
   - Maintain consistent UI/UX

6. Include widgets for dashboard:
   - Property statistics widget
   - Recent properties widget
   - Property status overview

Ensure the resource feels native to Wave's existing admin panel and follows all established patterns.
```

## 📋 Deliverables

### Week 1 Deliverables
- **Extended User Model**: Wave User model extended with CRM fields and relationships
- **Core CRM Models**: Property, Lead, Deal, and Activity models following Wave patterns
- **Database Migrations**: All necessary migrations extending Wave's existing schema
- **Model Factories**: Test factories for all new models

### Week 2 Deliverables
- **Filament Resources**: Complete PropertyResource, LeadResource, DealResource, and ActivityResource
- **Enhanced UserResource**: Extended UserResource with CRM functionality
- **Navigation Integration**: CRM resources integrated into Wave's admin navigation
- **Basic Testing**: Unit tests for all models and resources

## 🎯 Success Criteria

- [ ] All CRM models integrate seamlessly with Wave's existing architecture
- [ ] Database migrations extend Wave's schema without breaking existing functionality
- [ ] Filament resources follow Wave's established patterns and UI/UX
- [ ] CRM functionality is accessible through Wave's existing admin panel
- [ ] All new code follows Wave's coding standards and conventions
- [ ] Multi-tenancy works correctly using Wave's team structure
- [ ] Comprehensive test coverage for all new functionality

## 📝 Notes

This sprint focuses on extending Wave's solid foundation with CRM-specific functionality. The key is to make all new features feel native to the existing Wave system while adding comprehensive real estate CRM capabilities.

**Key Integration Points:**
- Use Wave's team structure for CRM organization multi-tenancy
- Follow Wave's Filament resource patterns for consistent admin experience
- Leverage Wave's existing media system for property photos
- Build upon Wave's authentication and permission system
- Maintain Wave's coding standards and architectural patterns