# Sprint 2: AI Integration Foundation (Weeks 3-4)

## Overview

**Duration**: 2 weeks  
**Focus**: Set up AI services and basic automation  
**Building on**: Sprint 1 CRM Foundation  
**Team**: 1-2 developers with AI integration experience

## Sprint Goals

### Primary Objectives
- Set up OpenAI integration for content generation
- Implement Vapi.ai for voice AI coaching
- Create basic AI Smart Summaries for leads and deals
- Build GPT Concierge Bot foundation
- Establish Auto-Generated Content system

### Success Criteria
- ✅ OpenAI API integration working with content generation
- ✅ Vapi.ai setup complete with basic voice capabilities
- ✅ AI summaries generating for leads and deals
- ✅ GPT bot responding to basic property queries
- ✅ Automated content generation for properties
- ✅ All AI features tested and documented

## Technical Requirements

### AI Service Integrations
- **OpenAI API**: GPT-4 for content generation and analysis
- **Vapi.ai**: Voice AI for coaching and follow-ups
- **Content Generation**: Automated property descriptions and emails
- **Smart Summaries**: AI-powered lead and deal insights
- **Bot Framework**: Conversational AI for property matching

### Infrastructure Setup
- **Queue System**: Extend Wave's queue for AI processing
- **Caching**: Redis caching for AI responses
- **Rate Limiting**: API rate limiting for AI services
- **Error Handling**: Robust error handling for AI failures
- **Logging**: Comprehensive logging for AI interactions

## Tasks Breakdown

### Week 1: OpenAI Integration & Content Generation

#### Day 1-2: OpenAI Service Setup
- [ ] Install OpenAI PHP client package
- [ ] Create OpenAI service class extending Wave patterns
- [ ] Set up API key management in Wave settings
- [ ] Create AI prompt management system
- [ ] Implement rate limiting and error handling

#### Day 3-4: Content Generation System
- [ ] Build property description generator
- [ ] Create email template generator
- [ ] Implement market update content generation
- [ ] Add social media post generator
- [ ] Create content approval workflow

#### Day 5: AI Smart Summaries
- [ ] Implement lead summary generation
- [ ] Create deal progress summaries
- [ ] Add property analysis summaries
- [ ] Build communication history summaries
- [ ] Test summary accuracy and relevance

### Week 2: Voice AI & Bot Framework

#### Day 6-7: Vapi.ai Integration
- [ ] Set up Vapi.ai account and API access
- [ ] Create voice AI service class
- [ ] Implement call recording and transcription
- [ ] Add voice coaching framework
- [ ] Test voice quality and responsiveness

#### Day 8-9: GPT Concierge Bot
- [ ] Build conversational AI framework
- [ ] Create property matching logic
- [ ] Implement lead qualification bot
- [ ] Add appointment scheduling bot
- [ ] Create bot training and improvement system

#### Day 10: Testing & Documentation
- [ ] Comprehensive AI feature testing
- [ ] Performance optimization
- [ ] Documentation and API specs
- [ ] User training materials
- [ ] Sprint review and demo

## Implementation Details

### 1. OpenAI Service Integration

```php
// app/Services/OpenAIService.php
<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Property;
use App\Models\Lead;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    public function generatePropertyDescription(Property $property): string
    {
        $cacheKey = "property_description_{$property->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($property) {
            try {
                $prompt = $this->buildPropertyPrompt($property);
                
                $response = OpenAI::completions()->create([
                    'model' => 'gpt-4',
                    'prompt' => $prompt,
                    'max_tokens' => 500,
                    'temperature' => 0.7,
                ]);
                
                return $response['choices'][0]['text'];
            } catch (\Exception $e) {
                Log::error('OpenAI property description failed', [
                    'property_id' => $property->id,
                    'error' => $e->getMessage()
                ]);
                
                return $this->getFallbackDescription($property);
            }
        });
    }
    
    public function generateLeadSummary(Lead $lead): array
    {
        $prompt = $this->buildLeadSummaryPrompt($lead);
        
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a real estate CRM assistant.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 300,
        ]);
        
        return [
            'summary' => $response['choices'][0]['message']['content'],
            'priority_score' => $this->calculatePriorityScore($lead),
            'next_actions' => $this->suggestNextActions($lead),
        ];
    }
    
    private function buildPropertyPrompt(Property $property): string
    {
        return "Write an engaging property description for a {$property->type} with {$property->bedrooms} bedrooms, {$property->bathrooms} bathrooms, located in {$property->suburb}. Price: {$property->price}. Features: " . implode(', ', $property->features ?? []);
    }
    
    private function buildLeadSummaryPrompt(Lead $lead): string
    {
        $interactions = $lead->interactions()->latest()->take(5)->get();
        $interactionText = $interactions->pluck('notes')->implode(' ');
        
        return "Summarize this lead's status and provide insights: Name: {$lead->name}, Source: {$lead->source}, Budget: {$lead->budget}, Recent interactions: {$interactionText}";
    }
}
```

### 2. Vapi.ai Voice Integration

```php
// app/Services/VapiService.php
<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\Lead;
use App\Models\Agent;
use Illuminate\Support\Facades\Log;

class VapiService
{
    private Client $client;
    private string $apiKey;
    
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.vapi.ai/',
            'timeout' => 30,
        ]);
        $this->apiKey = config('services.vapi.api_key');
    }
    
    public function createVoiceCoachingCall(Agent $agent, Lead $lead): array
    {
        $payload = [
            'assistant' => [
                'model' => 'gpt-4',
                'voice' => 'jennifer',
                'firstMessage' => $this->generateCoachingPrompt($agent, $lead),
            ],
            'phoneNumber' => $lead->phone,
            'metadata' => [
                'agent_id' => $agent->id,
                'lead_id' => $lead->id,
                'type' => 'coaching_call',
            ],
        ];
        
        try {
            $response = $this->client->post('calls', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Vapi call creation failed', [
                'agent_id' => $agent->id,
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    public function scheduleFollowUpCall(Lead $lead, \DateTime $scheduledTime): array
    {
        $payload = [
            'assistant' => [
                'model' => 'gpt-4',
                'voice' => 'jennifer',
                'firstMessage' => $this->generateFollowUpPrompt($lead),
            ],
            'phoneNumber' => $lead->phone,
            'scheduledTime' => $scheduledTime->format('c'),
            'metadata' => [
                'lead_id' => $lead->id,
                'type' => 'follow_up_call',
            ],
        ];
        
        $response = $this->client->post('calls', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);
        
        return json_decode($response->getBody(), true);
    }
    
    private function generateCoachingPrompt(Agent $agent, Lead $lead): string
    {
        return "Hello {$agent->name}, this is your AI coaching assistant. I'm calling to help you prepare for your upcoming meeting with {$lead->name}. Based on their profile, here are some key talking points and strategies...";
    }
    
    private function generateFollowUpPrompt(Lead $lead): string
    {
        return "Hi {$lead->name}, this is a follow-up call regarding your property inquiry. I wanted to check if you have any questions about the properties we discussed...";
    }
}
```

### 3. GPT Concierge Bot

```php
// app/Services/ConciergeBot.php
<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Property;
use OpenAI\Laravel\Facades\OpenAI;

class ConciergeBot
{
    private OpenAIService $openAI;
    
    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }
    
    public function handlePropertyInquiry(string $message, Lead $lead = null): array
    {
        $context = $this->buildContext($lead);
        
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt()],
                ['role' => 'user', 'content' => $context . "\n\nUser message: " . $message]
            ],
            'max_tokens' => 400,
            'functions' => [
                [
                    'name' => 'search_properties',
                    'description' => 'Search for properties based on criteria',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'bedrooms' => ['type' => 'integer'],
                            'bathrooms' => ['type' => 'integer'],
                            'min_price' => ['type' => 'number'],
                            'max_price' => ['type' => 'number'],
                            'suburb' => ['type' => 'string'],
                            'property_type' => ['type' => 'string'],
                        ],
                    ],
                ],
                [
                    'name' => 'schedule_viewing',
                    'description' => 'Schedule a property viewing',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'property_id' => ['type' => 'integer'],
                            'preferred_time' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ]);
        
        return $this->processResponse($response, $lead);
    }
    
    private function getSystemPrompt(): string
    {
        return "You are a helpful real estate concierge assistant. You help clients find properties, answer questions about listings, and schedule viewings. Be friendly, professional, and informative. If you need to search for properties or schedule viewings, use the provided functions.";
    }
    
    private function buildContext(Lead $lead = null): string
    {
        if (!$lead) {
            return "New visitor inquiry.";
        }
        
        $context = "Lead: {$lead->name}\n";
        $context .= "Budget: " . ($lead->budget ?? 'Not specified') . "\n";
        $context .= "Preferred areas: " . ($lead->preferred_suburbs ?? 'Not specified') . "\n";
        $context .= "Property type: " . ($lead->property_type ?? 'Not specified') . "\n";
        
        return $context;
    }
    
    private function processResponse(array $response, Lead $lead = null): array
    {
        $message = $response['choices'][0]['message'];
        
        if (isset($message['function_call'])) {
            return $this->handleFunctionCall($message['function_call'], $lead);
        }
        
        return [
            'type' => 'message',
            'content' => $message['content'],
        ];
    }
    
    private function handleFunctionCall(array $functionCall, Lead $lead = null): array
    {
        $functionName = $functionCall['name'];
        $arguments = json_decode($functionCall['arguments'], true);
        
        switch ($functionName) {
            case 'search_properties':
                $properties = $this->searchProperties($arguments);
                return [
                    'type' => 'property_results',
                    'properties' => $properties,
                    'message' => 'I found ' . count($properties) . ' properties matching your criteria.',
                ];
                
            case 'schedule_viewing':
                $viewing = $this->scheduleViewing($arguments, $lead);
                return [
                    'type' => 'viewing_scheduled',
                    'viewing' => $viewing,
                    'message' => 'I\'ve scheduled your viewing. You\'ll receive a confirmation shortly.',
                ];
                
            default:
                return [
                    'type' => 'error',
                    'message' => 'I\'m sorry, I couldn\'t process that request.',
                ];
        }
    }
    
    private function searchProperties(array $criteria): array
    {
        $query = Property::query()->where('status', 'available');
        
        if (isset($criteria['bedrooms'])) {
            $query->where('bedrooms', '>=', $criteria['bedrooms']);
        }
        
        if (isset($criteria['bathrooms'])) {
            $query->where('bathrooms', '>=', $criteria['bathrooms']);
        }
        
        if (isset($criteria['min_price'])) {
            $query->where('price', '>=', $criteria['min_price']);
        }
        
        if (isset($criteria['max_price'])) {
            $query->where('price', '<=', $criteria['max_price']);
        }
        
        if (isset($criteria['suburb'])) {
            $query->where('suburb', 'like', '%' . $criteria['suburb'] . '%');
        }
        
        if (isset($criteria['property_type'])) {
            $query->where('type', $criteria['property_type']);
        }
        
        return $query->limit(5)->get()->toArray();
    }
}
```

### 4. Auto-Generated Content System

```php
// app/Services/ContentGenerationService.php
<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Lead;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Queue;

class ContentGenerationService
{
    private OpenAIService $openAI;
    
    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }
    
    public function generatePropertyContent(Property $property): array
    {
        return [
            'description' => $this->openAI->generatePropertyDescription($property),
            'social_media_post' => $this->generateSocialMediaPost($property),
            'email_template' => $this->generateEmailTemplate($property),
            'brochure_content' => $this->generateBrochureContent($property),
        ];
    }
    
    public function generateLeadNurtureEmail(Lead $lead): string
    {
        $prompt = "Write a personalized follow-up email for a real estate lead named {$lead->name}. Their budget is {$lead->budget} and they're interested in {$lead->property_type} properties in {$lead->preferred_suburbs}. Make it warm, helpful, and include a call to action.";
        
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional real estate agent writing personalized emails.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 400,
        ]);
        
        return $response['choices'][0]['message']['content'];
    }
    
    public function generateMarketUpdate(string $suburb): string
    {
        $prompt = "Write a market update for {$suburb} including recent sales trends, price movements, and market insights. Make it informative and engaging for property buyers and sellers.";
        
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a real estate market analyst providing suburb insights.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 500,
        ]);
        
        return $response['choices'][0]['message']['content'];
    }
    
    private function generateSocialMediaPost(Property $property): string
    {
        $prompt = "Create an engaging social media post for a {$property->type} property with {$property->bedrooms} bedrooms in {$property->suburb}. Include relevant hashtags and a call to action.";
        
        $response = OpenAI::completions()->create([
            'model' => 'gpt-4',
            'prompt' => $prompt,
            'max_tokens' => 200,
        ]);
        
        return $response['choices'][0]['text'];
    }
    
    private function generateEmailTemplate(Property $property): string
    {
        $prompt = "Write an email template to send to potential buyers about a {$property->type} property in {$property->suburb}. Include property highlights and encourage them to book a viewing.";
        
        $response = OpenAI::completions()->create([
            'model' => 'gpt-4',
            'prompt' => $prompt,
            'max_tokens' => 300,
        ]);
        
        return $response['choices'][0]['text'];
    }
    
    private function generateBrochureContent(Property $property): array
    {
        return [
            'headline' => $this->generateHeadline($property),
            'features_description' => $this->generateFeaturesDescription($property),
            'location_benefits' => $this->generateLocationBenefits($property),
            'investment_highlights' => $this->generateInvestmentHighlights($property),
        ];
    }
}
```

## Testing Strategy

### Unit Tests
```php
// tests/Unit/Services/OpenAIServiceTest.php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\OpenAIService;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OpenAIServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_generates_property_description()
    {
        $property = Property::factory()->create([
            'type' => 'house',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'suburb' => 'Melbourne',
            'price' => 800000,
        ]);
        
        $service = new OpenAIService();
        $description = $service->generatePropertyDescription($property);
        
        $this->assertNotEmpty($description);
        $this->assertStringContainsString('3 bedrooms', $description);
        $this->assertStringContainsString('Melbourne', $description);
    }
    
    public function test_generates_lead_summary()
    {
        $lead = Lead::factory()->create([
            'name' => 'John Doe',
            'budget' => 500000,
            'source' => 'website',
        ]);
        
        $service = new OpenAIService();
        $summary = $service->generateLeadSummary($lead);
        
        $this->assertArrayHasKey('summary', $summary);
        $this->assertArrayHasKey('priority_score', $summary);
        $this->assertArrayHasKey('next_actions', $summary);
    }
}
```

### Integration Tests
```php
// tests/Feature/AI/ConciergebotTest.php
<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Services\ConciergeBot;
use App\Models\Lead;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConciergebotTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_handles_property_search_inquiry()
    {
        Property::factory()->count(3)->create([
            'bedrooms' => 3,
            'suburb' => 'Melbourne',
            'status' => 'available',
        ]);
        
        $bot = new ConciergeBot(new OpenAIService());
        $response = $bot->handlePropertyInquiry('I need a 3 bedroom house in Melbourne');
        
        $this->assertEquals('property_results', $response['type']);
        $this->assertGreaterThan(0, count($response['properties']));
    }
    
    public function test_schedules_property_viewing()
    {
        $lead = Lead::factory()->create();
        $property = Property::factory()->create();
        
        $bot = new ConciergeBot(new OpenAIService());
        $response = $bot->handlePropertyInquiry(
            "I'd like to schedule a viewing for property {$property->id} tomorrow at 2pm",
            $lead
        );
        
        $this->assertEquals('viewing_scheduled', $response['type']);
        $this->assertArrayHasKey('viewing', $response);
    }
}
```

## Deployment Checklist

### Environment Setup
- [ ] OpenAI API key configured in environment
- [ ] Vapi.ai API key configured in environment
- [ ] Redis cache configured for AI responses
- [ ] Queue workers configured for AI processing
- [ ] Rate limiting configured for AI APIs

### Database Migrations
- [ ] AI prompt templates table
- [ ] AI interaction logs table
- [ ] Voice call records table
- [ ] Content generation cache table

### Configuration
- [ ] AI service rate limits configured
- [ ] Error handling and fallbacks tested
- [ ] Monitoring and alerting set up
- [ ] Performance benchmarks established

### Documentation
- [ ] AI service API documentation
- [ ] User guides for AI features
- [ ] Troubleshooting guides
- [ ] Performance optimization tips

## AI-Assisted Development Prompts

### For Cursor/Windsurf IDE

```
Create an OpenAI service class for Fusion CRM that extends the existing Wave patterns. The service should:

1. Follow Wave's service layer architecture
2. Include proper error handling and logging
3. Use Laravel's caching for API responses
4. Implement rate limiting for API calls
5. Generate property descriptions, lead summaries, and email content
6. Include comprehensive PHPDoc comments

Reference the existing Wave service classes in the codebase for patterns and structure.
```

```
Build a Vapi.ai integration service that:

1. Handles voice AI calls for lead coaching
2. Schedules follow-up calls automatically
3. Records and transcribes conversations
4. Integrates with the existing Lead and Agent models
5. Follows Wave's error handling patterns
6. Includes proper logging and monitoring

Use the existing Wave notification system patterns for call status updates.
```

```
Create a GPT Concierge Bot service that:

1. Handles property inquiries via chat
2. Uses OpenAI function calling for property search
3. Schedules property viewings automatically
4. Maintains conversation context
5. Integrates with existing Property and Lead models
6. Follows Wave's API response patterns

Include comprehensive testing for all bot interactions.
```

## Success Metrics

### Technical Metrics
- **AI Response Time**: <3 seconds average
- **API Success Rate**: >95% for all AI services
- **Content Quality**: >80% approval rate for generated content
- **Voice Call Quality**: >4.0/5.0 average rating

### Business Metrics
- **Lead Engagement**: 40% increase in lead response rates
- **Content Efficiency**: 70% reduction in manual content creation
- **Agent Productivity**: 30% increase in calls per day
- **Lead Qualification**: 50% improvement in lead scoring accuracy

---

**Sprint 2 establishes the AI foundation that powers all advanced features in subsequent sprints, providing intelligent automation and content generation capabilities.**
