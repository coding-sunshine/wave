# Fusion CRM V4 Blueprint

This blueprint serves as a comprehensive guide for building Fusion CRM V4 using the TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire) with Wave Kit as the foundation. The blueprint is specifically optimized for development with AI-assisted IDEs like Windsurf.

## Directory Structure

- **01_overview**: High-level project overview, timeline, and technical stack
- **02_architecture**: Detailed system architecture, database schema, and component designs
- **03_sprints**: Sprint-by-sprint implementation plan with tasks and MCP prompts
- **04_resources**: Additional resources and documentation for development
- **features-v4.md**: Comprehensive feature list with priorities and categorization

## Getting Started with Windsurf IDE

This blueprint is designed to work optimally with Windsurf IDE. Follow these steps to begin implementation:

1. **Start with Sprint 0**: Begin with `03_sprints/sprint_00.md` which covers Wave Kit setup and Windsurf configuration
2. **Use MCP Prompts**: Each sprint contains Windsurf-ready prompts (MCPs) for efficient code generation
3. **Follow Architecture Guidelines**: Refer to `02_architecture` for implementation patterns and best practices
4. **Track Features**: Use `features-v4.md` to track implementation progress

## Using Blueprint with Windsurf IDE

### AI-Assisted Development Tips

1. **Reference Existing Files**: When asking Windsurf to generate code, reference specific blueprint documents:
   ```
   Generate a User model according to the architecture in 02_architecture/04_authentication_authorization.md
   ```

2. **Use Sprint MCPs**: Copy MCP prompts directly from sprint files for consistent code generation:
   ```
   Use the MCP from sprint_01.md to generate the TenantScope implementation
   ```

3. **Provide Context**: Always reference blueprint documents to maintain architectural consistency:
   ```
   Following the patterns in 02_architecture/01_system_architecture.md, implement...
   ```

4. **Create Component Templates**: Develop standard templates for recurring components:
   ```
   Generate a Livewire component for client management based on the template in .windsurf/templates/livewire.md
   ```

### PSR-12 and Coding Standards Enforcement

This blueprint adheres strictly to PSR-12 coding standards. Configure your Windsurf IDE to:

1. Use Laravel Pint for automatic code formatting
2. Run PHPStan for static analysis
3. Apply strict type declarations (`declare(strict_types=1)`)
4. Generate comprehensive docblocks for all methods

## Implementing with Wave Kit

The blueprint is designed to build upon Wave Kit as a foundation:

1. Begin with Wave Kit installation (Sprint 0)
2. Extend Wave's user and team models for multi-tenancy
3. Adapt authentication and billing for Fusion CRM's requirements
4. Build core CRM features according to sprint plans

## Technical Requirements

- PHP 8.4
- Laravel (Wave Kit version with upgrade path)
- MySQL 8.0+
- Redis 7.0+
- Node.js 18+
- Composer 2.5+

## Testing Standards

All code should be thoroughly tested using Pest PHP. The blueprint includes test specifications and strategies in each sprint document.

## Need Help?

Refer to specific documentation in the appropriate sections:
- For architecture questions: See `02_architecture/`
- For implementation sequence: See `03_sprints/`
- For technical specifications: See `01_overview/04_technical_stack.md`
