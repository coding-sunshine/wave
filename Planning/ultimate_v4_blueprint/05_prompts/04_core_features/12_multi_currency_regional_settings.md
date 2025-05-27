# Multi-Currency and Regional Settings

## Context
Fusion CRM v4 requires comprehensive multi-currency and regional settings capabilities to support international clients while maintaining the default Australian Dollar (AUD) base currency. This system should provide flexible currency display options, regional formatting preferences, and localization support for property details.

## Task
Implement a robust multi-currency and regional settings system that:

1. Provides tenant-specific currency display preferences (default: AUD)
2. Enables dynamic currency conversion for international clients
3. Supports regional date and measurement format settings
4. Implements localized property feature terminology
5. Offers multi-language support for property descriptions
6. Provides timezone-aware scheduling and notifications
7. Manages regional compliance and disclosure requirements

## Technical Requirements
- PHP 8.4 with strict typing (`declare(strict_types=1);`)
- PSR-12 coding standards
- Laravel framework with tenant awareness
- Integration with currency exchange rate APIs
- Internationalization (i18n) and localization (l10n) support
- Regional date and number formatting
- Proper timezone handling with Carbon

## Implementation Details

### Currency Management
- Implement a currency configuration system with AUD as default
- Design a dynamic currency switching mechanism
- Integrate with a reliable exchange rate API
- Create a caching strategy for exchange rates
- Support manual rate adjustments for specific scenarios
- Implement proper rounding and decimal handling for different currencies
- Display original and converted prices with appropriate notation

### Regional Formatting
- Support region-specific date formats
- Implement number formatting with configurable separators
- Enable measurement unit conversion (metric/imperial)
- Create address formatting for different regions
- Support phone number formatting by region
- Implement price formatting according to regional conventions
- Design a flexible UI that adapts to regional preferences

### Localization System
- Create a translation management system
- Support dynamic content localization
- Implement property feature terminology localization
- Enable tenant-specific translation customization
- Support right-to-left (RTL) languages where needed
- Create localized templates for emails and notifications
- Implement fallback language preferences

### Timezone Management
- Implement proper timezone handling throughout the application
- Create a user preference system for timezone selection
- Support timezone-aware scheduling for activities and tasks
- Design timezone-aware notifications and reminders
- Implement proper timezone conversion for reporting
- Support daylight saving time adjustments
- Create a timezone detection system for new users

### Regional Compliance
- Support region-specific disclosure requirements
- Implement regional terms and conditions templates
- Create compliance checkbox requirements by region
- Design a flexible system for regional legal disclaimers
- Support region-specific fields and validation rules
- Implement regional privacy policy requirements
- Enable region-based feature restrictions

## Database Design
The system will utilize these key tables:

1. `tenant_currency_settings` - Tenant-specific currency preferences
2. `currency_conversion_rates` - Exchange rate data
3. `tenant_regional_settings` - Regional format preferences
4. `translations` - Localized text content
5. `region_compliance_settings` - Regional compliance requirements
6. `tenant_language_preferences` - Language preferences

## Models and Relationships
The system will implement these key models:

1. `TenantCurrencySetting` - For currency preferences
2. `CurrencyConversionRate` - For exchange rates
3. `TenantRegionalSetting` - For regional preferences
4. `Translation` - For localized content
5. `RegionComplianceSetting` - For compliance requirements
6. `TenantLanguagePreference` - For language preferences

## Service Layer
The multi-currency and regional system will be built with these core services:

1. `CurrencyManagementService` - Handles currency preferences and display
2. `ExchangeRateService` - Manages rate fetching and conversion
3. `RegionalFormattingService` - Formats dates, numbers, and addresses
4. `TranslationService` - Handles localized content
5. `TimezoneService` - Manages timezone conversions
6. `ComplianceService` - Handles regional compliance requirements
7. `LocalizationService` - Provides localized property content

## Frontend Components
The system will include these Livewire components:

1. `CurrencySwitcher` - For changing display currency
2. `RegionalSettingsManager` - For managing regional preferences
3. `LocaleSelector` - For selecting language preferences
4. `TimezoneSelector` - For timezone selection
5. `LocalizedPropertyDisplay` - For displaying localized property details
6. `RegionalComplianceNotice` - For showing region-specific notices
7. `FormatPreferencesManager` - For managing format preferences

## Testing Requirements
- Unit testing for currency conversion functions
- Feature testing for regional formatting
- Integration testing for exchange rate API
- Visual regression testing for RTL language support
- Performance testing for translation loading

## Expected Output
- Complete multi-currency display system (default: AUD)
- Exchange rate management functionality
- Regional formatting preferences system
- Translation and localization framework
- Timezone-aware scheduling and notifications
- Regional compliance management
- Full documentation and testing coverage

## Best Practices
- Use ISO standards for currency and country codes
- Implement proper caching for exchange rates
- Create a flexible translation management system
- Use Carbon for all datetime handling
- Ensure proper decimal precision for financial calculations
- Implement proper validation for regional formats
- Design for extensibility as new regions are supported
