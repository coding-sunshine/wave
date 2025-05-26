# AI Smart Summaries Implementation

## Context
The AI Smart Summaries feature is a key AI component in Fusion CRM v4, generating automated summaries of lead activities, meetings, deals, and performance metrics to save users time and highlight important information.

## Task
Implement a comprehensive AI Smart Summaries system that:

1. Automatically generates summaries for various data types (leads, meetings, deals)
2. Extracts action items from meeting transcriptions and conversations
3. Creates performance metrics digests for users and teams
4. Integrates with the notification system for delivery

## Technical Requirements
- PHP 8.4 with strict typing (`declare(strict_types=1);`)
- PSR-12 coding standards
- OpenAI integration via the established service
- Tenant-based usage tracking and permissions
- Asynchronous processing via queues
- Complete test coverage with Pest

## Implementation Details
- Create a dedicated SummaryService class with specialized methods for different summary types
- Implement prompt templates optimized for different summary needs
- Design a caching strategy for generated summaries
- Build a scheduled task system for periodic summary generation
- Create a user preference system for summary delivery
- Implement markdown formatting for rich summaries

## Expected Output
- SummaryService with comprehensive generation methods
- Database schema for storing and retrieving summaries
- Queue jobs for asynchronous processing
- Controllers and routes for summary management
- Livewire components for displaying summaries
- User preference settings for summary delivery
- Complete test coverage with Pest tests
