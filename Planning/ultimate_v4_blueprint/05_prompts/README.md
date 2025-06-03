# Fusion CRM V4 - AI-Assisted Development Prompts

This directory contains **Wave-aware prompts** specifically designed for extending the existing Wave Kit foundation with CRM functionality. These prompts are optimized for AI-assisted development using Cursor IDE and similar tools.

## Current Wave Foundation Context

All prompts in this directory assume you have an existing Wave-based Laravel application with:

### ✅ Already Implemented via Wave
- **Laravel 11** with PHP 8.1+ support
- **Filament 3.2** admin panel with existing resources (User, Role, Permission, Post, Page, etc.)
- **Livewire 3** reactive components and established patterns
- **Alpine.js 3.4.2** frontend interactivity
- **Tailwind CSS 3.4.3** with dynamic theme system (Anchor, Drift themes)
- **Spatie Permissions** fully configured with role management
- **JWT Authentication** with API token management and social providers
- **Multi-tenant Foundation** via Wave's team structure
- **Stripe Integration** for billing and subscription management
- **Form Builder** with dynamic fields and entries
- **Media Management** with file uploads and processing
- **Testing Framework** with Pest PHP configured

## Prompt Categories

### 01_setup/
**Wave Foundation Analysis & CRM Planning**
- Prompts for analyzing existing Wave components
- Planning CRM extensions that integrate with Wave
- Setting up development environment for Wave extension

### 02_models/
**CRM Model Extension**
- Extending Wave's existing User model for CRM contacts
- Creating Property, Lead, Deal models that integrate with Wave's team structure
- Following Wave's model patterns and relationships

### 03_resources/
**Filament Resource Development**
- Creating CRM resources that follow Wave's existing resource patterns
- Extending Wave's admin panel with CRM functionality
- Maintaining Wave's UI/UX consistency

### 04_features/
**CRM Feature Implementation**
- Building CRM features on Wave's foundation
- Integrating with Wave's existing services and patterns
- Leveraging Wave's queue system for automation

### 05_integrations/
**Third-party Integration**
- Adding AI capabilities to Wave-based system
- Financial integrations building on Wave's Stripe foundation
- API extensions for CRM functionality

### 06_testing/
**Testing CRM Extensions**
- Testing strategies that build on Wave's existing Pest framework
- CRM-specific test cases and factories
- Integration testing with Wave components

## Usage Guidelines

### For AI Assistants (Cursor, etc.)

1. **Always reference Wave foundation**: Each prompt includes context about existing Wave components to leverage
2. **Follow Wave patterns**: Prompts guide you to extend existing Wave patterns rather than create new ones
3. **Maintain compatibility**: All prompts ensure Wave functionality remains intact
4. **Use established infrastructure**: Leverage Wave's team structure, auth system, and admin panel

### For Developers

1. **Start with analysis prompts** (01_setup) to understand current Wave foundation
2. **Follow sequential development** using prompts in order
3. **Customize prompts** based on your specific Wave configuration
4. **Test extensions** using Wave-compatible testing approaches

## Prompt Structure

Each prompt follows this structure:

```markdown
# Context
Brief description of the Wave foundation context and what exists

# Task  
Specific task for extending Wave with CRM functionality

# Wave Integration Points
- Existing Wave components to leverage
- Wave patterns to follow
- Integration requirements

# Expected Output
- Code that extends Wave patterns
- Maintains Wave compatibility
- Follows Wave coding standards

# Success Criteria
- Integration with existing Wave functionality
- Consistent with Wave UI/UX
- Maintains Wave's architectural patterns
```

## Key Integration Principles

### Extend, Don't Replace
- Build upon Wave's existing models and resources
- Enhance Wave's admin panel rather than creating separate interfaces
- Use Wave's team structure for CRM multi-tenancy

### Follow Wave Patterns
- Use established Wave/Filament resource patterns
- Follow Wave's service layer architecture
- Maintain Wave's coding standards and conventions

### Leverage Existing Infrastructure
- Use Wave's authentication and permission system
- Build upon Wave's media management system
- Extend Wave's existing API foundation

## Development Workflow

1. **Analyze Wave Foundation** (01_setup prompts)
   - Understand existing Wave components
   - Plan CRM extensions
   - Set up development environment

2. **Extend Models** (02_models prompts)
   - Extend Wave User model for CRM
   - Create CRM models following Wave patterns
   - Integrate with Wave's team structure

3. **Build Resources** (03_resources prompts)
   - Create Filament resources following Wave patterns
   - Extend Wave's admin navigation
   - Maintain Wave's UI consistency

4. **Implement Features** (04_features prompts)
   - Build CRM functionality on Wave foundation
   - Use Wave's existing services and patterns
   - Integrate with Wave's queue system

5. **Add Integrations** (05_integrations prompts)
   - Extend Wave's API capabilities
   - Add third-party integrations
   - Build upon Wave's existing integrations

6. **Test Extensions** (06_testing prompts)
   - Test CRM functionality with Wave
   - Ensure Wave compatibility
   - Comprehensive integration testing

## Benefits of Wave-Aware Prompts

### Development Speed
- **60% faster development** by leveraging existing Wave foundation
- **Proven patterns** reduce decision-making time
- **Established infrastructure** accelerates feature development

### Code Quality
- **Consistent architecture** following Wave's proven patterns
- **Battle-tested foundation** with Wave's Laravel architecture
- **Comprehensive testing** building on Wave's existing framework

### Maintenance
- **Single codebase** easier to maintain than separate systems
- **Wave updates** benefit the entire application
- **Established patterns** make onboarding easier

These prompts ensure efficient development by building upon Wave's solid foundation while adding comprehensive CRM functionality that feels native to the existing system.
