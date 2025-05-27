# Fusion CRM v4 Comprehensive Prompt Guide

## Introduction
This guide provides a systematic approach to building Fusion CRM v4 using Windsurf IDE and AI-assisted development. Follow these prompts in sequence to construct your application from the Wave foundation to a complete, production-ready CRM system.

## Development Workflow

### Phase 1: Foundation and Architecture (Start Here)
1. **Initial Setup and Configuration**
   - [01_setup/01_wave_installation_customization.md](01_setup/01_wave_installation_customization.md)
   - [01_setup/02_multi_tenancy_setup.md](01_setup/02_multi_tenancy_setup.md)

2. **Core Data Models**
   - [02_models/01_core_crm_models.md](02_models/01_core_crm_models.md)

3. **Authentication and Authorization**
   - [03_authentication/01_roles_permissions.md](03_authentication/01_roles_permissions.md)

### Phase 2: Core Features Implementation
4. **Lead Management System**
   - [04_core_features/01_lead_management.md](04_core_features/01_lead_management.md)

5. **Property Management System**
   - [04_core_features/02_property_management.md](04_core_features/02_property_management.md)

6. **Deal Management System**
   - [04_core_features/03_deal_management.md](04_core_features/03_deal_management.md)

7. **Client Management System**
   - [04_core_features/05_client_management.md](04_core_features/05_client_management.md)

8. **Supporting Systems**
   - [04_core_features/06_notification_system.md](04_core_features/06_notification_system.md)
   - [04_core_features/07_activity_logging.md](04_core_features/07_activity_logging.md)
   - [04_core_features/08_api_integration.md](04_core_features/08_api_integration.md)

### Phase 3: AI Integration
9. **OpenAI Integration**
   - [05_ai_integrations/01_openai_integration.md](05_ai_integrations/01_openai_integration.md)

10. **AI-Powered Features**
    - [05_ai_integrations/02_gpt_cold_outreach.md](05_ai_integrations/02_gpt_cold_outreach.md)
    - [05_ai_integrations/03_vapi_voice_integration.md](05_ai_integrations/03_vapi_voice_integration.md)
    - [05_ai_integrations/04_ai_smart_summaries.md](05_ai_integrations/04_ai_smart_summaries.md)

11. **Funnel Engine**
    - [04_core_features/04_strategy_based_funnel_engine.md](04_core_features/04_strategy_based_funnel_engine.md)

### Phase 4: User Interface
12. **UI Framework and Components**
    - [06_ui_components/01_livewire_component_system.md](06_ui_components/01_livewire_component_system.md)
    - [06_ui_components/02_dashboard_components.md](06_ui_components/02_dashboard_components.md)
    - [06_ui_components/03_data_tables_and_filters.md](06_ui_components/03_data_tables_and_filters.md)

### Phase 5: Testing and Deployment
13. **Testing Framework**
    - [07_testing/01_pest_testing_framework.md](07_testing/01_pest_testing_framework.md)
    - [07_testing/02_feature_testing.md](07_testing/02_feature_testing.md)
    - [07_testing/03_unit_testing.md](07_testing/03_unit_testing.md)

14. **Deployment**
    - [08_deployment/01_production_deployment.md](08_deployment/01_production_deployment.md)
    - [08_deployment/02_ci_cd_pipeline.md](08_deployment/02_ci_cd_pipeline.md)

## Best Practices for Using These Prompts

### Creating Context
When using these prompts in Windsurf, add specific context to help the AI generate more accurate code:

```
I want to implement the lead management system according to the prompt in 04_core_features/01_lead_management.md. 
Some additional context:
- I've already set up the tenant system as described in 01_setup/02_multi_tenancy_setup.md
- I want to focus on implementing the lead scoring algorithm first
- Please refer to the existing Client model at app/Models/Client.php for reference
```

### Incremental Development
1. Start with the foundation and build incrementally
2. Test each component thoroughly before moving to the next
3. Run `composer lint` after making changes
4. Run `composer test` before finalizing work

### Leveraging AI Effectively
1. Be specific about your requirements
2. Reference existing code and files in your prompts
3. Ask for explanations when needed
4. Provide feedback if the AI misunderstands

### Technical Standards
All code generated should follow these standards:
- PHP 8.4 with strict typing (`declare(strict_types=1);`)
- PSR-12 coding standards
- SOLID principles
- Laravel best practices
- Proper validation with Form Requests
- Complete test coverage with Pest

## Additional Resources
- Refer to the blueprint documents in `01_overview/`, `02_architecture/`, and `03_sprints/` for detailed specifications
- Review `features-v4.md` for the complete feature list and priorities
- Use the Wave documentation for understanding the foundation: https://devdojo.com/wave/docs/getting-started

## Customizing Prompts
Feel free to modify these prompts to suit your specific needs. The key is maintaining consistency in code style, architecture, and testing throughout the development process.

## Tracking Progress
As you implement each component, consider creating a progress tracking file to document what's been completed and what remains to be done.

---

This guide is designed to provide a systematic approach to building Fusion CRM v4 with AI-assisted development. Follow the prompts in sequence for the most efficient development process.
