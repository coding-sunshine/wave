# Fusion CRM V4 - AI Integration Architecture

This document outlines the architecture for AI integration in Fusion CRM V4, covering the implementation of OpenAI, GPT models, and other AI services throughout the system.

## Overview

Fusion CRM V4 implements a comprehensive AI architecture that provides:

1. **GPT-Powered Content Generation**: Auto-generated emails, landing pages, follow-ups
2. **Intelligent Lead Management**: Smart scoring, prioritization, and routing
3. **Conversational AI**: Bot interactions across multiple channels
4. **Property Matching**: AI-powered property recommendations
5. **Sales Intelligence**: Deal forecasting and next best action suggestions
6. **Voice AI Integration**: Call coaching and sentiment analysis

## Performance Monitoring & Scaling

### Metrics Tracking
- Token usage per tenant/feature
- Response times and latency
- Error rates and types
- Cache hit/miss ratios
- Cost per request

### Auto-Scaling Strategy
- Dynamic model selection based on load
- Automatic fallback to smaller models
- Queue-based request throttling
- Horizontal scaling of processing workers

### Cost Optimization
- Smart caching of common responses
- Token usage budgets per tenant
- Batch processing for bulk operations
- Model selection based on complexity

## Core Components

### 1. AI Service Layer

The central service responsible for managing AI providers and requests:

```php
namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\Tenancy\TenantManager;

class AIService
{
    protected $config;
    protected $tenantManager;
    
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
        $this->initConfig();
    }
    
    /**
     * Initialize configuration based on tenant settings or global defaults
     */
    protected function initConfig(): void
    {
        $tenant = $this->tenantManager->getTenant();
        
        if ($tenant && isset($tenant->settings['ai'])) {
            $this->config = array_merge(
                config('ai'),
                $tenant->settings['ai']
            );
        } else {
            $this->config = config('ai');
        }
    }
    
    /**
     * Get AI provider client
     */
    public function getProvider(string $provider = null): AIProviderInterface
    {
        $provider = $provider ?? $this->config['default_provider'];
        
        return match ($provider) {
            'openai' => new OpenAIProvider($this->config['providers']['openai']),
            'anthropic' => new AnthropicProvider($this->config['providers']['anthropic']),
            'azure' => new AzureOpenAIProvider($this->config['providers']['azure']),
            default => throw new \InvalidArgumentException("Unsupported AI provider: {$provider}")
        };
    }
    
    /**
     * Generate text with rate limiting and caching
     */
    public function generateText(array $params): string
    {
        $cacheKey = 'ai_text_' . md5(json_encode($params));
        
        // Check cache first
        if ($this->config['enable_cache'] && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        // Apply rate limiting 
        $tenant = $this->tenantManager->getTenant();
        $limitKey = 'ai_rate_limit_' . ($tenant?->id ?? 'system');
        
        if (Cache::get($limitKey, 0) >= $this->config['rate_limit']) {
            Log::warning('AI rate limit exceeded', [
                'tenant_id' => $tenant?->id,
                'params' => $params
            ]);
            
            throw new \Exception('AI rate limit exceeded. Please try again later.');
        }
        
        // Increment rate limit counter
        Cache::increment($limitKey);
        Cache::put($limitKey, Cache::get($limitKey), now()->addMinutes(1));
        
        // Generate text
        try {
            $provider = $this->getProvider();
            $result = $provider->generateText($params);
            
            // Cache result
            if ($this->config['enable_cache']) {
                Cache::put($cacheKey, $result, now()->addHours(24));
            }
            
            // Log token usage
            if ($tenant) {
                $tenant->increment('ai_tokens_used', $provider->getLastTokenCount());
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('AI text generation failed', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Generate embeddings for text
     */
    public function generateEmbeddings(string $text): array
    {
        $provider = $this->getProvider();
        return $provider->generateEmbeddings($text);
    }
    
    /**
     * Generate image from text prompt
     */
    public function generateImage(string $prompt, array $options = []): string
    {
        $provider = $this->getProvider();
        return $provider->generateImage($prompt, $options);
    }
}
```

### 2. OpenAI Provider Implementation

```php
namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider implements AIProviderInterface
{
    protected $config;
    protected $lastTokenCount = 0;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Generate text using OpenAI API
     */
    public function generateText(array $params): string
    {
        $this->lastTokenCount = 0;
        
        $modelName = $params['model'] ?? $this->config['default_model'];
        $temperature = $params['temperature'] ?? 0.7;
        $maxTokens = $params['max_tokens'] ?? 500;
        
        $messages = $params['messages'] ?? [
            [
                'role' => 'system',
                'content' => $params['system_prompt'] ?? 'You are a helpful assistant.'
            ],
            [
                'role' => 'user',
                'content' => $params['prompt'] ?? ''
            ]
        ];
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $modelName,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ]);
        
        if (!$response->successful()) {
            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'params' => $params
            ]);
            
            throw new \Exception('OpenAI API error: ' . $response->body());
        }
        
        $data = $response->json();
        $this->lastTokenCount = $data['usage']['total_tokens'] ?? 0;
        
        return $data['choices'][0]['message']['content'] ?? '';
    }
    
    /**
     * Generate embeddings for text
     */
    public function generateEmbeddings(string $text): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/embeddings', [
            'model' => 'text-embedding-ada-002',
            'input' => $text
        ]);
        
        if (!$response->successful()) {
            Log::error('OpenAI embeddings API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            throw new \Exception('OpenAI embeddings API error: ' . $response->body());
        }
        
        $data = $response->json();
        return $data['data'][0]['embedding'] ?? [];
    }
    
    /**
     * Generate image from text prompt
     */
    public function generateImage(string $prompt, array $options = []): string
    {
        $size = $options['size'] ?? '1024x1024';
        $style = $options['style'] ?? 'vivid';
        $quality = $options['quality'] ?? 'standard';
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/images/generations', [
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size,
            'style' => $style,
            'quality' => $quality
        ]);
        
        if (!$response->successful()) {
            Log::error('OpenAI image generation API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            throw new \Exception('OpenAI image generation API error: ' . $response->body());
        }
        
        $data = $response->json();
        return $data['data'][0]['url'] ?? '';
    }
    
    /**
     * Get token count from last request
     */
    public function getLastTokenCount(): int
    {
        return $this->lastTokenCount;
    }
}
```

### 3. AI Module Manager

Service for integrating AI capabilities into specific CRM modules:

```php
namespace App\Services\AI;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lot;
use App\Services\Tenancy\TenantManager;

class AIModuleManager
{
    protected $aiService;
    protected $tenantManager;
    
    public function __construct(AIService $aiService, TenantManager $tenantManager)
    {
        $this->aiService = $aiService;
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Generate follow-up email for a contact
     */
    public function generateFollowUpEmail(Contact $contact, string $context = null): string
    {
        // Gather contact information
        $contactInfo = [
            'name' => $contact->first_name . ' ' . $contact->last_name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'lastInteraction' => $contact->last_interaction_at?->diffForHumans(),
            'notes' => $contact->notes ?? 'No notes available',
            'interests' => $contact->interests ?? 'Unknown',
        ];
        
        // Generate system prompt
        $systemPrompt = "You are a professional real estate agent. Generate a personalized follow-up email for a potential client. Keep it concise, friendly, and professional.";
        
        // Generate user prompt
        $userPrompt = "Generate a follow-up email for {$contactInfo['name']}. ";
        
        if ($context) {
            $userPrompt .= "Context: {$context}. ";
        }
        
        $userPrompt .= "Their interests: {$contactInfo['interests']}. ";
        
        if ($contactInfo['lastInteraction']) {
            $userPrompt .= "Last interaction: {$contactInfo['lastInteraction']}. ";
        }
        
        $userPrompt .= "Recent notes: {$contactInfo['notes']}. ";
        $userPrompt .= "Create a brief, personalized email that specifically references their interests and our last interaction. Do not include a subject line. Maintain a professional tone.";
        
        // Generate email
        return $this->aiService->generateText([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 300,
        ]);
    }
    
    /**
     * Generate property description
     */
    public function generatePropertyDescription(Lot $property): string
    {
        // Gather property information
        $propertyInfo = [
            'address' => $property->full_address,
            'type' => $property->property_type,
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'parking' => $property->parking,
            'landSize' => $property->land_size,
            'features' => $property->features ?? [],
            'price' => $property->price,
            'description' => $property->description ?? 'No description available',
        ];
        
        // Generate system prompt
        $systemPrompt = "You are a professional real estate copywriter. Generate a compelling property description that highlights key features and benefits. Use descriptive language that appeals to emotions. Keep it concise and factual.";
        
        // Generate user prompt
        $userPrompt = "Generate a compelling property description for this property: ";
        $userPrompt .= "Address: {$propertyInfo['address']}. ";
        $userPrompt .= "Type: {$propertyInfo['type']}. ";
        $userPrompt .= "Bedrooms: {$propertyInfo['bedrooms']}. ";
        $userPrompt .= "Bathrooms: {$propertyInfo['bathrooms']}. ";
        $userPrompt .= "Parking: {$propertyInfo['parking']}. ";
        $userPrompt .= "Land size: {$propertyInfo['landSize']}. ";
        
        if (!empty($propertyInfo['features'])) {
            $userPrompt .= "Features: " . implode(', ', $propertyInfo['features']) . ". ";
        }
        
        $userPrompt .= "Price: {$propertyInfo['price']}. ";
        $userPrompt .= "Create a compelling property description in 150-200 words. Highlight key features and benefits. Use descriptive language that appeals to emotions while remaining professional and factual.";
        
        // Generate description
        return $this->aiService->generateText([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 500,
        ]);
    }
    
    /**
     * Generate deal insight
     */
    public function generateDealInsight(Deal $deal): array
    {
        // Gather deal information
        $dealInfo = [
            'stage' => $deal->stage,
            'value' => $deal->value,
            'createdAt' => $deal->created_at->diffForHumans(),
            'expectedCloseDate' => $deal->expected_close_date?->format('Y-m-d'),
            'lastActivity' => $deal->last_activity_at?->diffForHumans(),
            'notes' => $deal->notes ?? 'No notes available',
            'interactions' => $deal->interactions_count ?? 0,
        ];
        
        // Generate system prompt
        $systemPrompt = "You are a sales analytics expert. Analyze this deal and provide insights on probability of closing and suggest next steps. Format your response as JSON with keys: 'probability', 'reasoning', 'next_steps', and 'risks'.";
        
        // Generate user prompt
        $userPrompt = "Analyze this deal and provide insights: ";
        $userPrompt .= "Stage: {$dealInfo['stage']}. ";
        $userPrompt .= "Value: {$dealInfo['value']}. ";
        $userPrompt .= "Created: {$dealInfo['createdAt']}. ";
        $userPrompt .= "Expected close: {$dealInfo['expectedCloseDate']}. ";
        $userPrompt .= "Last activity: {$dealInfo['lastActivity']}. ";
        $userPrompt .= "Interaction count: {$dealInfo['interactions']}. ";
        $userPrompt .= "Notes: {$dealInfo['notes']}. ";
        $userPrompt .= "Provide: 1) Probability of closing (percentage), 2) Brief reasoning, 3) Three specific next steps to advance the deal, and 4) Potential risks to closing. Format as JSON with keys: 'probability', 'reasoning', 'next_steps', and 'risks'.";
        
        // Generate insight
        $insight = $this->aiService->generateText([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 500,
        ]);
        
        // Decode JSON response
        try {
            return json_decode($insight, true);
        } catch (\Exception $e) {
            // If JSON parsing fails, return structured error
            return [
                'probability' => null,
                'reasoning' => 'Unable to analyze deal',
                'next_steps' => ['Review deal details', 'Update missing information', 'Schedule follow-up'],
                'risks' => ['Insufficient data for accurate analysis']
            ];
        }
    }
    
    /**
     * Match properties to a contact's preferences
     */
    public function matchPropertiesToContact(Contact $contact, array $properties): array
    {
        // Implementation would analyze contact preferences and rank properties
        // This would use embeddings and similarity matching
        
        return [];
    }
}
```

### 4. Bot Integration Service

```php
namespace App\Services\AI;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Services\Tenancy\TenantManager;

class BotService
{
    protected $aiService;
    protected $tenantManager;
    
    public function __construct(AIService $aiService, TenantManager $tenantManager)
    {
        $this->aiService = $aiService;
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Process incoming message and generate response
     */
    public function processMessage(Conversation $conversation, string $message, array $context = []): ConversationMessage
    {
        // Get conversation history
        $history = $this->getConversationHistory($conversation);
        
        // Determine conversation purpose
        $purpose = $conversation->purpose ?? 'general';
        
        // Get appropriate system prompt
        $systemPrompt = $this->getBotSystemPrompt($purpose, $context);
        
        // Prepare messages for AI
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];
        
        // Add conversation history
        foreach ($history as $historyMessage) {
            $messages[] = [
                'role' => $historyMessage->direction === 'incoming' ? 'user' : 'assistant',
                'content' => $historyMessage->content
            ];
        }
        
        // Add current message
        $messages[] = ['role' => 'user', 'content' => $message];
        
        // Generate response
        $response = $this->aiService->generateText([
            'model' => 'gpt-4',
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 500,
        ]);
        
        // Save incoming message
        $incomingMessage = new ConversationMessage();
        $incomingMessage->conversation_id = $conversation->id;
        $incomingMessage->content = $message;
        $incomingMessage->direction = 'incoming';
        $incomingMessage->save();
        
        // Save bot response
        $botMessage = new ConversationMessage();
        $botMessage->conversation_id = $conversation->id;
        $botMessage->content = $response;
        $botMessage->direction = 'outgoing';
        $botMessage->save();
        
        return $botMessage;
    }
    
    /**
     * Get appropriate system prompt based on conversation purpose
     */
    protected function getBotSystemPrompt(string $purpose, array $context = []): string
    {
        return match ($purpose) {
            'property_inquiry' => $this->getPropertyInquiryPrompt($context),
            'lead_qualification' => $this->getLeadQualificationPrompt($context),
            'support' => $this->getSupportPrompt($context),
            default => $this->getGeneralPrompt($context)
        };
    }
    
    /**
     * Get system prompt for property inquiry
     */
    protected function getPropertyInquiryPrompt(array $context = []): string
    {
        $tenant = $this->tenantManager->getTenant();
        $companyName = $tenant->name ?? 'Our real estate company';
        
        $prompt = "You are a helpful assistant for {$companyName}, a real estate agency. ";
        $prompt .= "Your role is to provide information about properties, answer questions about availability, features, and pricing. ";
        $prompt .= "Be friendly, professional, and concise in your responses. ";
        
        if (isset($context['property'])) {
            $property = $context['property'];
            $prompt .= "You are specifically providing information about this property: ";
            $prompt .= "{$property['address']}, a {$property['type']} with {$property['bedrooms']} bedrooms ";
            $prompt .= "and {$property['bathrooms']} bathrooms, priced at {$property['price']}. ";
            
            if (!empty($property['features'])) {
                $prompt .= "Key features include: " . implode(', ', $property['features']) . ". ";
            }
        }
        
        $prompt .= "Always offer to connect the person with a live agent for viewings or specific questions you cannot answer. ";
        $prompt .= "If asked for personal opinions on investment potential or market predictions, clarify that you're an assistant and recommend speaking with a licensed agent.";
        
        return $prompt;
    }
    
    /**
     * Get system prompt for lead qualification
     */
    protected function getLeadQualificationPrompt(array $context = []): string
    {
        $tenant = $this->tenantManager->getTenant();
        $companyName = $tenant->name ?? 'Our real estate company';
        
        $prompt = "You are a lead qualification assistant for {$companyName}, a real estate agency. ";
        $prompt .= "Your goal is to politely gather information about the potential client's needs and preferences. ";
        $prompt .= "Ask questions about: budget range, preferred locations, property type, timeline for purchase/move, ";
        $prompt .= "financing situation, must-have features, and how they heard about us. ";
        $prompt .= "Be conversational and friendly, not interrogative. Space out your questions rather than asking everything at once. ";
        $prompt .= "After gathering sufficient information, offer to connect them with an appropriate agent. ";
        
        return $prompt;
    }
    
    /**
     * Get system prompt for support
     */
    protected function getSupportPrompt(array $context = []): string
    {
        $tenant = $this->tenantManager->getTenant();
        $companyName = $tenant->name ?? 'Our real estate company';
        
        $prompt = "You are a support assistant for {$companyName}, a real estate agency. ";
        $prompt .= "Your role is to help with basic questions about our services, process, and website functionality. ";
        $prompt .= "Be friendly, helpful, and concise. If you cannot resolve an issue, offer to connect them with human support. ";
        
        return $prompt;
    }
    
    /**
     * Get general system prompt
     */
    protected function getGeneralPrompt(array $context = []): string
    {
        $tenant = $this->tenantManager->getTenant();
        $companyName = $tenant->name ?? 'Our real estate company';
        
        $prompt = "You are a helpful assistant for {$companyName}, a real estate agency. ";
        $prompt .= "Provide friendly, professional responses to inquiries. ";
        $prompt .= "For specific property questions, offer to connect them with an agent. ";
        $prompt .= "Never make up information about properties or services.";
        
        return $prompt;
    }
    
    /**
     * Get conversation history
     */
    protected function getConversationHistory(Conversation $conversation): array
    {
        // Get last 10 messages
        return $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->take(10)
            ->get()
            ->toArray();
    }
}
```

### 5. Voice AI Integration

```php
namespace App\Services\AI;

use App\Models\Call;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoiceAIService
{
    protected $config;
    protected $tenantManager;
    
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
        $this->initConfig();
    }
    
    /**
     * Initialize configuration
     */
    protected function initConfig(): void
    {
        $tenant = $this->tenantManager->getTenant();
        
        if ($tenant && isset($tenant->settings['voice_ai'])) {
            $this->config = array_merge(
                config('voice_ai'),
                $tenant->settings['voice_ai']
            );
        } else {
            $this->config = config('voice_ai');
        }
    }
    
    /**
     * Analyze call recording
     */
    public function analyzeCallRecording(Call $call): array
    {
        try {
            $recordingUrl = $call->recording_url;
            
            if (!$recordingUrl) {
                throw new \Exception('No recording URL available for analysis');
            }
            
            // Call Vapi API for analysis
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['vapi_api_key'],
                'Content-Type' => 'application/json',
            ])->post($this->config['vapi_endpoint'] . '/analyze', [
                'recording_url' => $recordingUrl,
                'analysis_types' => [
                    'transcription',
                    'sentiment',
                    'key_points',
                    'action_items',
                    'speaking_time'
                ]
            ]);
            
            if (!$response->successful()) {
                Log::error('Vapi API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'call_id' => $call->id
                ]);
                
                throw new \Exception('Vapi API error: ' . $response->body());
            }
            
            $analysisData = $response->json();
            
            // Update call with analysis results
            $call->update([
                'transcription' => $analysisData['transcription'] ?? null,
                'sentiment_score' => $analysisData['sentiment']['overall'] ?? null,
                'key_points' => $analysisData['key_points'] ?? null,
                'action_items' => $analysisData['action_items'] ?? null,
                'analysis_data' => $analysisData
            ]);
            
            return $analysisData;
        } catch (\Exception $e) {
            Log::error('Call analysis failed', [
                'error' => $e->getMessage(),
                'call_id' => $call->id
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Generate AI coaching insights from call
     */
    public function generateCoachingInsights(Call $call): array
    {
        try {
            // Ensure call has been analyzed
            if (empty($call->transcription)) {
                throw new \Exception('Call must be analyzed before generating coaching insights');
            }
            
            // Prepare analysis data
            $analysisData = $call->analysis_data;
            
            // Extract segments
            $agentSegments = array_filter($analysisData['speaker_segments'] ?? [], function ($segment) {
                return $segment['speaker'] === 'agent';
            });
            
            $clientSegments = array_filter($analysisData['speaker_segments'] ?? [], function ($segment) {
                return $segment['speaker'] === 'client';
            });
            
            // Calculate metrics
            $agentTalkRatio = $analysisData['speaking_time']['agent'] / $analysisData['speaking_time']['total'];
            $interruptionCount = $analysisData['interruptions']['agent'] ?? 0;
            $overallSentiment = $analysisData['sentiment']['overall'];
            $clientSentiment = $analysisData['sentiment']['client'];
            
            // Generate coaching insights using OpenAI
            $aiService = app(AIService::class);
            
            $systemPrompt = "You are an expert sales coach for real estate agents. Analyze this call data and provide specific, actionable coaching advice. Focus on conversation flow, objection handling, and closing techniques.";
            
            $userPrompt = "Analyze this real estate sales call data:\n\n";
            $userPrompt .= "Call duration: {$call->duration} seconds\n";
            $userPrompt .= "Agent talk ratio: " . round($agentTalkRatio * 100) . "%\n";
            $userPrompt .= "Interruptions by agent: {$interruptionCount}\n";
            $userPrompt .= "Overall sentiment: {$overallSentiment}\n";
            $userPrompt .= "Client sentiment: {$clientSentiment}\n\n";
            $userPrompt .= "Key points discussed:\n" . implode("\n", $analysisData['key_points'] ?? []) . "\n\n";
            $userPrompt .= "Action items identified:\n" . implode("\n", $analysisData['action_items'] ?? []) . "\n\n";
            $userPrompt .= "Based on this data, provide: 1) Three specific strengths demonstrated by the agent, 2) Three specific improvement opportunities with actionable advice, and 3) One key follow-up suggestion. Format as JSON with keys: 'strengths', 'improvements', and 'follow_up'. Keep each point brief but specific.";
            
            $coaching = $aiService->generateText([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.4,
                'max_tokens' => 500,
            ]);
            
            // Parse coaching insights
            try {
                $insights = json_decode($coaching, true);
                
                // Update call with coaching insights
                $call->update([
                    'coaching_insights' => $insights
                ]);
                
                return $insights;
            } catch (\Exception $e) {
                // If JSON parsing fails, return structured error
                return [
                    'strengths' => ['Unable to analyze call data'],
                    'improvements' => ['Review call manually for coaching opportunities'],
                    'follow_up' => 'Ensure proper call recording and analysis'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Coaching insights generation failed', [
                'error' => $e->getMessage(),
                'call_id' => $call->id
            ]);
            
            throw $e;
        }
    }
}
```

## Implementation Use Cases

### 1. Email & Content Generation

```php
// In a controller or service
$contact = Contact::find($contactId);
$aiModuleManager = app(AIModuleManager::class);

// Generate follow-up email
$emailContent = $aiModuleManager->generateFollowUpEmail($contact, 'Following up on our property viewing last week');

// Generate property description
$property = Lot::find($propertyId);
$description = $aiModuleManager->generatePropertyDescription($property);

// Send email
Mail::to($contact->email)->send(new FollowUpEmail($contact, $emailContent));

// Update property
$property->update(['description' => $description]);
```

### 2. Chatbot Integration

```php
// In a controller
public function chatbotMessage(Request $request)
{
    $conversationId = $request->input('conversation_id');
    $message = $request->input('message');
    
    // Get or create conversation
    $conversation = Conversation::firstOrCreate(
        ['id' => $conversationId],
        [
            'tenant_id' => app('tenant_id'),
            'source' => 'website',
            'purpose' => 'property_inquiry',
            'status' => 'active'
        ]
    );
    
    // Get context
    $propertyId = $request->input('property_id');
    $context = [];
    
    if ($propertyId) {
        $property = Lot::find($propertyId);
        
        if ($property) {
            $context['property'] = [
                'address' => $property->full_address,
                'type' => $property->property_type,
                'bedrooms' => $property->bedrooms,
                'bathrooms' => $property->bathrooms,
                'price' => $property->price,
                'features' => $property->features ?? []
            ];
        }
    }
    
    // Process message
    $botService = app(BotService::class);
    $response = $botService->processMessage($conversation, $message, $context);
    
    return response()->json([
        'conversation_id' => $conversation->id,
        'message' => $response->content
    ]);
}
```

### 3. Deal Insights & Forecasting

```php
// In a job or scheduled task
public function generateDealInsights()
{
    // Get active deals
    $deals = Deal::where('status', 'active')->get();
    
    $aiModuleManager = app(AIModuleManager::class);
    
    foreach ($deals as $deal) {
        try {
            // Generate insights
            $insights = $aiModuleManager->generateDealInsight($deal);
            
            // Update deal with insights
            $deal->update([
                'ai_probability' => $insights['probability'],
                'ai_next_steps' => $insights['next_steps'],
                'ai_risks' => $insights['risks'],
                'ai_insight_generated_at' => now()
            ]);
            
            // Create task for next steps if probability is below threshold
            if ($insights['probability'] < 50) {
                foreach ($insights['next_steps'] as $index => $step) {
                    Task::create([
                        'tenant_id' => $deal->tenant_id,
                        'user_id' => $deal->assigned_to,
                        'related_type' => 'deal',
                        'related_id' => $deal->id,
                        'title' => $step,
                        'description' => 'AI-suggested task to increase deal probability',
                        'priority' => 'high',
                        'due_date' => now()->addDays($index + 1),
                        'status' => 'pending'
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Deal insight generation failed', [
                'deal_id' => $deal->id,
                'error' => $e->getMessage()
            ]);
            
            continue;
        }
    }
}
```

## Database Schema

```sql
-- AI Usage Tracking
CREATE TABLE ai_usage_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    feature VARCHAR(100) NOT NULL,
    token_count INT NOT NULL,
    request_data JSON NULL,
    successful BOOLEAN NOT NULL DEFAULT true,
    response_time INT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Chatbot Conversations
CREATE TABLE conversations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED NULL,
    source VARCHAR(50) NOT NULL,
    purpose VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL
);

CREATE TABLE conversation_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    direction ENUM('incoming', 'outgoing') NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
);

-- Call Analysis
CREATE TABLE calls (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    direction ENUM('inbound', 'outbound') NOT NULL,
    status VARCHAR(20) NOT NULL,
    duration INT NULL,
    recording_url VARCHAR(255) NULL,
    transcription TEXT NULL,
    sentiment_score DECIMAL(3,2) NULL,
    key_points JSON NULL,
    action_items JSON NULL,
    analysis_data JSON NULL,
    coaching_insights JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL
);
```

## Security & Privacy Considerations

1. **Data Privacy**
   - All AI requests are tenant-isolated
   - Sensitive PII is removed from AI inputs when possible
   - AI usage is logged for audit purposes

2. **Rate Limiting**
   - Tenant-based rate limiting prevents abuse
   - Tiered access based on subscription plan
   - Circuit breakers prevent runaway costs

3. **Content Safety**
   - All generated content is validated before use
   - Guardrails prevent harmful or inappropriate outputs
   - Human review for high-risk content (e.g., mass emails)

4. **API Security**
   - API keys securely stored using encrypted environment variables
   - Regular rotation of API credentials
   - Monitoring for unusual usage patterns

## Implementation Strategy

### Phase 1: Core AI Integration

1. **OpenAI Integration**
   - Implement base service layer
   - Connect basic text generation capabilities
   - Add tenant-aware configuration

2. **Content Generation**
   - Property descriptions
   - Email templates
   - Follow-up messages

### Phase 2: Conversational AI

3. **Chatbot Framework**
   - Implement conversation management
   - Create context-aware prompting
   - Add multiple conversation purposes (support, sales, etc.)

4. **Lead Qualification**
   - Smart lead scoring
   - Automated follow-up sequences
   - Handoff to human agents

### Phase 3: Advanced AI Features

5. **Voice AI Integration**
   - Call recording analysis
   - Sentiment detection
   - Sales coaching insights

6. **Predictive Analytics**
   - Deal forecasting
   - Next best action recommendations
   - Market trend analysis

## Conclusion

The AI integration architecture in Fusion CRM V4 provides:

1. **Efficiency**: Automated content generation and lead qualification
2. **Intelligence**: Smart insights and recommendations for users
3. **Scalability**: Modular design that can incorporate new AI capabilities
4. **Security**: Tenant isolation and proper privacy controls
5. **Flexibility**: Support for multiple AI providers and models

This AI-first approach sets Fusion CRM V4 apart from competitors by deeply embedding AI throughout the system rather than treating it as a separate add-on. The architecture enables continual enhancement as AI technologies evolve.