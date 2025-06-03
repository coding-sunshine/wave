<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Relay\Facades\Relay;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Exceptions\PrismException;

use function Laravel\Prompts\text;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\note;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\textarea;

class AiAssistant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:assistant
                            {--mode=interactive : Mode to run (interactive, research, analyze, automate)}
                            {--model=claude-3-7-sonnet-latest : AI model to use}
                            {--provider=anthropic : Provider to use (anthropic, openai)}
                            {--save : Save the response to a file}
                            {--debug : Show debug information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AI assistant powered by Prism and Relay for Wave CRM';

    /**
     * Available AI models by provider
     */
    protected array $models = [
        'anthropic' => [
            'claude-3-7-sonnet-latest',
            'claude-3-5-sonnet-latest',
            'claude-3-opus-latest',
            'claude-3-haiku-latest',
        ],
        'openai' => [
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-4-turbo',
            'gpt-3.5-turbo',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->showBanner();
        
        $mode = $this->option('mode');
        
        if ($mode === 'interactive') {
            $this->runInteractiveMode();
        } elseif ($mode === 'research') {
            $this->runResearchMode();
        } elseif ($mode === 'analyze') {
            $this->runAnalyzeMode();
        } elseif ($mode === 'automate') {
            $this->runAutomateMode();
        } else {
            $this->runInteractiveMode();
        }
    }
    
    /**
     * Show the assistant banner
     */
    protected function showBanner()
    {
        $this->newLine();
        $this->line('┌───────────────────────────────────────────────┐');
        $this->line('│ <fg=blue;options=bold>Wave CRM AI Assistant</>                         │');
        $this->line('│ <fg=gray>Powered by Prism + Relay MCP</>                    │');
        $this->line('└───────────────────────────────────────────────┘');
        $this->newLine();
    }
    
    /**
     * Run the interactive mode
     */
    protected function runInteractiveMode()
    {
        $action = select(
            'What would you like to do?',
            [
                'research' => 'Web Research (browse websites, take screenshots)',
                'analyze' => 'CRM Data Analysis (analyze customer data)',
                'automate' => 'Workflow Automation (generate content, emails)',
                'chat' => 'Chat with AI (no tools, just conversation)',
                'exit' => 'Exit',
            ]
        );
        
        if ($action === 'exit') {
            return;
        }
        
        if ($action === 'research') {
            $this->runResearchMode();
        } elseif ($action === 'analyze') {
            $this->runAnalyzeMode();
        } elseif ($action === 'automate') {
            $this->runAutomateMode();
        } elseif ($action === 'chat') {
            $this->runChatMode();
        }
    }
    
    /**
     * Run the research mode (web browsing with Puppeteer)
     */
    protected function runResearchMode()
    {
        note('Web Research Mode', 'Using Puppeteer to browse the web and gather information.');
        
        $prompt = textarea('What would you like to research? (Be specific about websites, screenshots, etc.)');
        
        if (empty($prompt)) {
            warning('No prompt provided. Returning to main menu.');
            $this->runInteractiveMode();
            return;
        }
        
        $systemPrompt = "You are a web research assistant for Wave CRM. Your goal is to help the user research information on the web.
You have access to Puppeteer tools that let you navigate to websites, take screenshots, click elements, and extract data.
Always be thorough in your research and explain what you're doing.
If you take screenshots, describe what's in them.
If you need to navigate multiple pages to find information, do so methodically.
Format your responses with markdown for readability.";
        
        try {
            $response = spin(function () use ($prompt, $systemPrompt) {
                return $this->createAgent($prompt, $systemPrompt, ['puppeteer'])->asText();
            }, 'Researching...');
            
            $this->displayResponse($response->text);
            
            if ($this->option('save') || confirm('Would you like to save this research?')) {
                $filename = 'research_' . date('Y-m-d_H-i-s') . '.md';
                Storage::disk('local')->put('ai_assistant/' . $filename, $response->text);
                info("Research saved to storage/app/ai_assistant/{$filename}");
            }
            
            if (confirm('Would you like to do another research?')) {
                $this->runResearchMode();
            } else {
                $this->runInteractiveMode();
            }
        } catch (PrismException $e) {
            error('Error: ' . $e->getMessage());
            if ($this->option('debug')) {
                $this->error($e->getTraceAsString());
            }
            if (confirm('Would you like to try again?')) {
                $this->runResearchMode();
            }
        }
    }
    
    /**
     * Run the analyze mode (CRM data analysis)
     */
    protected function runAnalyzeMode()
    {
        note('CRM Data Analysis Mode', 'Analyze customer data and generate insights.');
        
        $dataSource = select(
            'Which data would you like to analyze?',
            [
                'customers' => 'Customer Information',
                'deals' => 'Sales Pipeline & Deals',
                'marketing' => 'Marketing Campaigns',
                'support' => 'Support Tickets',
                'custom' => 'Custom Query',
            ]
        );
        
        $sampleData = $this->getSampleData($dataSource);
        $prompt = textarea('What analysis would you like to perform on this data?');
        
        if (empty($prompt)) {
            warning('No prompt provided. Returning to main menu.');
            $this->runInteractiveMode();
            return;
        }
        
        $systemPrompt = "You are a data analysis assistant for Wave CRM. Your goal is to analyze CRM data and provide insights.
You'll be given data about {$dataSource} and asked to analyze it.
Provide clear insights, identify patterns, and suggest actionable steps.
Use markdown formatting to make your analysis readable, including tables and bullet points.
If appropriate, suggest visualizations that could be created (describe them).";
        
        $fullPrompt = "Here is the {$dataSource} data to analyze:\n\n```json\n" . json_encode($sampleData, JSON_PRETTY_PRINT) . "\n```\n\n" . $prompt;
        
        try {
            $response = spin(function () use ($fullPrompt, $systemPrompt) {
                return $this->createAgent($fullPrompt, $systemPrompt)->asText();
            }, 'Analyzing data...');
            
            $this->displayResponse($response->text);
            
            if ($this->option('save') || confirm('Would you like to save this analysis?')) {
                $filename = 'analysis_' . $dataSource . '_' . date('Y-m-d_H-i-s') . '.md';
                Storage::disk('local')->put('ai_assistant/' . $filename, $response->text);
                info("Analysis saved to storage/app/ai_assistant/{$filename}");
            }
            
            if (confirm('Would you like to perform another analysis?')) {
                $this->runAnalyzeMode();
            } else {
                $this->runInteractiveMode();
            }
        } catch (PrismException $e) {
            error('Error: ' . $e->getMessage());
            if ($this->option('debug')) {
                $this->error($e->getTraceAsString());
            }
            if (confirm('Would you like to try again?')) {
                $this->runAnalyzeMode();
            }
        }
    }
    
    /**
     * Run the automate mode (workflow automation)
     */
    protected function runAutomateMode()
    {
        note('Workflow Automation Mode', 'Generate content and automate CRM workflows.');
        
        $task = select(
            'What would you like to automate?',
            [
                'email' => 'Email Campaign Generation',
                'social' => 'Social Media Content',
                'followup' => 'Follow-up Sequences',
                'report' => 'Report Generation',
                'custom' => 'Custom Automation',
            ]
        );
        
        $prompt = '';
        $systemPrompt = '';
        
        switch ($task) {
            case 'email':
                $audience = select('Target audience?', ['prospects', 'customers', 'churned', 'enterprise', 'custom']);
                $industry = text('Industry focus (optional):');
                $goal = select('Campaign goal?', ['awareness', 'conversion', 'retention', 'upsell', 'other']);
                
                $prompt = "Generate an email campaign for {$audience} in " . ($industry ? "the {$industry} industry " : "") . 
                          "with the goal of {$goal}. Include subject lines and email body content for a sequence of 3 emails.";
                
                $systemPrompt = "You are an email marketing specialist for Wave CRM. Create compelling email campaigns that drive results.
Format the emails with clear subject lines, personalization tokens, and engaging content.
Follow email marketing best practices and suggest A/B testing opportunities.";
                break;
                
            case 'social':
                $platform = multiselect('Which platforms?', ['LinkedIn', 'Twitter', 'Facebook', 'Instagram'], ['LinkedIn']);
                $contentType = select('Content type?', ['thought leadership', 'product updates', 'customer stories', 'tips & tricks', 'mixed']);
                $count = text('How many posts?', '5');
                
                $prompt = "Generate {$count} social media posts for " . implode(', ', $platform) . " focused on {$contentType}.";
                
                $systemPrompt = "You are a social media manager for a SaaS CRM company. Create engaging posts that resonate with our audience.
Each post should have a clear message, appropriate hashtags, and a call to action when relevant.
Format posts appropriately for each platform's best practices and character limits.";
                break;
                
            case 'followup':
                $scenario = select('Follow-up scenario?', ['post-demo', 'quote sent', 'meeting no-show', 'onboarding check-in', 'renewal']);
                $tone = select('Communication tone?', ['professional', 'friendly', 'direct', 'consultative']);
                
                $prompt = "Create a follow-up sequence for {$scenario} with a {$tone} tone. Include email templates and call scripts.";
                
                $systemPrompt = "You are a sales enablement specialist for Wave CRM. Create effective follow-up sequences that help close deals.
Include timing recommendations (when to send each message), subject lines, and full message content.
Suggest personalization opportunities and alternative approaches based on customer responses.";
                break;
                
            case 'report':
                $reportType = select('Report type?', ['executive summary', 'sales performance', 'customer health', 'marketing ROI', 'custom']);
                $timeframe = select('Time period?', ['weekly', 'monthly', 'quarterly', 'annual', 'custom']);
                
                $prompt = "Generate a {$reportType} report template for {$timeframe} reporting. Include sections, metrics to highlight, and visualization suggestions.";
                
                $systemPrompt = "You are a business intelligence analyst for Wave CRM. Create comprehensive report templates that highlight key insights.
Structure reports with clear sections, actionable insights, and data visualization recommendations.
Focus on metrics that drive business decisions and include guidance on interpreting the data.";
                break;
                
            case 'custom':
                $prompt = textarea('Describe the automation task in detail:');
                
                $systemPrompt = "You are a CRM automation specialist for Wave. Help the user automate their workflow efficiently.
Provide detailed, step-by-step guidance on implementing the automation.
Consider integration points, data flows, and potential edge cases.";
                break;
        }
        
        if (empty($prompt)) {
            warning('No prompt provided. Returning to main menu.');
            $this->runInteractiveMode();
            return;
        }
        
        try {
            $response = spin(function () use ($prompt, $systemPrompt) {
                return $this->createAgent($prompt, $systemPrompt)->asText();
            }, 'Generating automation...');
            
            $this->displayResponse($response->text);
            
            if ($this->option('save') || confirm('Would you like to save this content?')) {
                $filename = 'automation_' . $task . '_' . date('Y-m-d_H-i-s') . '.md';
                Storage::disk('local')->put('ai_assistant/' . $filename, $response->text);
                info("Content saved to storage/app/ai_assistant/{$filename}");
            }
            
            if (confirm('Would you like to create another automation?')) {
                $this->runAutomateMode();
            } else {
                $this->runInteractiveMode();
            }
        } catch (PrismException $e) {
            error('Error: ' . $e->getMessage());
            if ($this->option('debug')) {
                $this->error($e->getTraceAsString());
            }
            if (confirm('Would you like to try again?')) {
                $this->runAutomateMode();
            }
        }
    }
    
    /**
     * Run the chat mode (simple conversation)
     */
    protected function runChatMode()
    {
        note('Chat Mode', 'Have a conversation with the AI assistant without using tools.');
        
        $prompt = textarea('What would you like to talk about?');
        
        if (empty($prompt)) {
            warning('No prompt provided. Returning to main menu.');
            $this->runInteractiveMode();
            return;
        }
        
        $systemPrompt = "You are a helpful assistant for Wave CRM users. Provide concise, accurate information and guidance.
You can discuss CRM best practices, sales strategies, marketing tips, customer success, and general business topics.
Format your responses with markdown for readability when appropriate.";
        
        try {
            $response = spin(function () use ($prompt, $systemPrompt) {
                return $this->createAgent($prompt, $systemPrompt)->asText();
            }, 'Thinking...');
            
            $this->displayResponse($response->text);
            
            if (confirm('Would you like to continue the conversation?')) {
                $this->runChatMode();
            } else {
                $this->runInteractiveMode();
            }
        } catch (PrismException $e) {
            error('Error: ' . $e->getMessage());
            if ($this->option('debug')) {
                $this->error($e->getTraceAsString());
            }
            if (confirm('Would you like to try again?')) {
                $this->runChatMode();
            }
        }
    }
    
    /**
     * Create a Prism agent with the specified configuration
     */
    protected function createAgent(string $prompt, string $systemPrompt = '', array $toolServers = []): PendingRequest
    {
        $provider = $this->option('provider');
        $model = $this->option('model');
        
        // Validate provider
        if (!in_array($provider, array_keys($this->models))) {
            $provider = 'anthropic';
        }
        
        // Validate model for provider
        if (!in_array($model, $this->models[$provider])) {
            $model = $this->models[$provider][0];
        }
        
        // Convert provider string to enum
        $providerEnum = match($provider) {
            'anthropic' => Provider::Anthropic,
            'openai' => Provider::OpenAI,
            default => Provider::Anthropic,
        };
        
        $agent = Prism::text()
            ->using($providerEnum, $model)
            ->withPrompt($prompt);
            
        if (!empty($systemPrompt)) {
            $agent->withSystemPrompt($systemPrompt);
        }
        
        // Add tools if specified
        if (!empty($toolServers)) {
            $tools = [];
            foreach ($toolServers as $server) {
                $tools = array_merge($tools, Relay::tools($server)->toArray());
            }
            $agent->withTools($tools);
        }
        
        return $agent->withMaxTokens(4096);
    }
    
    /**
     * Display the AI response with proper formatting
     */
    protected function displayResponse(string $response)
    {
        $this->newLine();
        $this->line('┌───────────────────────────────────────────────┐');
        $this->line('│ <fg=green;options=bold>AI Response</>                                   │');
        $this->line('└───────────────────────────────────────────────┘');
        $this->newLine();
        
        // Split response into lines and display
        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            $this->line($line);
        }
        
        $this->newLine(2);
    }
    
    /**
     * Get sample data for analysis
     */
    protected function getSampleData(string $dataType): array
    {
        // In a real application, this would fetch actual data from your database
        // For this example, we'll use sample data
        
        switch ($dataType) {
            case 'customers':
                return [
                    [
                        'id' => 1,
                        'name' => 'Acme Corporation',
                        'industry' => 'Technology',
                        'employees' => 250,
                        'annual_revenue' => 5000000,
                        'customer_since' => '2023-02-15',
                        'plan' => 'Enterprise',
                        'mrr' => 2500,
                        'contacts' => [
                            ['name' => 'John Smith', 'role' => 'CTO', 'email' => 'john@acme.com'],
                            ['name' => 'Sarah Jones', 'role' => 'CEO', 'email' => 'sarah@acme.com'],
                        ],
                        'nps_score' => 9,
                        'last_interaction' => '2025-05-20',
                    ],
                    [
                        'id' => 2,
                        'name' => 'Global Services Inc',
                        'industry' => 'Professional Services',
                        'employees' => 120,
                        'annual_revenue' => 2800000,
                        'customer_since' => '2024-07-10',
                        'plan' => 'Professional',
                        'mrr' => 1200,
                        'contacts' => [
                            ['name' => 'Michael Brown', 'role' => 'Operations Director', 'email' => 'michael@globalservices.com'],
                        ],
                        'nps_score' => 7,
                        'last_interaction' => '2025-05-28',
                    ],
                    [
                        'id' => 3,
                        'name' => 'Sunrise Retail',
                        'industry' => 'Retail',
                        'employees' => 85,
                        'annual_revenue' => 1500000,
                        'customer_since' => '2024-11-05',
                        'plan' => 'Professional',
                        'mrr' => 950,
                        'contacts' => [
                            ['name' => 'Lisa Chen', 'role' => 'Marketing Manager', 'email' => 'lisa@sunriseretail.com'],
                            ['name' => 'David Wilson', 'role' => 'IT Manager', 'email' => 'david@sunriseretail.com'],
                        ],
                        'nps_score' => 8,
                        'last_interaction' => '2025-06-01',
                    ],
                    [
                        'id' => 4,
                        'name' => 'EcoSolutions',
                        'industry' => 'Environmental',
                        'employees' => 45,
                        'annual_revenue' => 900000,
                        'customer_since' => '2023-05-22',
                        'plan' => 'Starter',
                        'mrr' => 450,
                        'contacts' => [
                            ['name' => 'Emma Green', 'role' => 'Founder', 'email' => 'emma@ecosolutions.com'],
                        ],
                        'nps_score' => 10,
                        'last_interaction' => '2025-05-15',
                    ],
                    [
                        'id' => 5,
                        'name' => 'MediHealth Group',
                        'industry' => 'Healthcare',
                        'employees' => 320,
                        'annual_revenue' => 7500000,
                        'customer_since' => '2022-09-18',
                        'plan' => 'Enterprise',
                        'mrr' => 3200,
                        'contacts' => [
                            ['name' => 'Robert Johnson', 'role' => 'CIO', 'email' => 'robert@medihealth.com'],
                            ['name' => 'Amanda Lee', 'role' => 'Operations Director', 'email' => 'amanda@medihealth.com'],
                        ],
                        'nps_score' => 6,
                        'last_interaction' => '2025-05-10',
                    ],
                ];
                
            case 'deals':
                return [
                    [
                        'id' => 101,
                        'company' => 'TechNova Solutions',
                        'value' => 75000,
                        'stage' => 'Proposal',
                        'probability' => 60,
                        'expected_close_date' => '2025-07-15',
                        'owner' => 'Alex Martinez',
                        'products' => ['CRM Pro', 'Analytics Add-on'],
                        'first_contact_date' => '2025-04-10',
                        'last_activity' => '2025-06-02',
                        'next_step' => 'Follow up on proposal',
                    ],
                    [
                        'id' => 102,
                        'company' => 'Bright Finance',
                        'value' => 120000,
                        'stage' => 'Negotiation',
                        'probability' => 80,
                        'expected_close_date' => '2025-06-30',
                        'owner' => 'Jessica Wong',
                        'products' => ['CRM Enterprise', 'API Access', 'Premium Support'],
                        'first_contact_date' => '2025-03-05',
                        'last_activity' => '2025-05-28',
                        'next_step' => 'Schedule contract review',
                    ],
                    [
                        'id' => 103,
                        'company' => 'GreenField Agriculture',
                        'value' => 45000,
                        'stage' => 'Discovery',
                        'probability' => 30,
                        'expected_close_date' => '2025-08-15',
                        'owner' => 'Marcus Johnson',
                        'products' => ['CRM Pro'],
                        'first_contact_date' => '2025-05-20',
                        'last_activity' => '2025-05-25',
                        'next_step' => 'Product demo',
                    ],
                    [
                        'id' => 104,
                        'company' => 'Metro Hospitality Group',
                        'value' => 95000,
                        'stage' => 'Closed Won',
                        'probability' => 100,
                        'expected_close_date' => '2025-05-10',
                        'owner' => 'Samantha Lee',
                        'products' => ['CRM Enterprise', 'Workflow Automation'],
                        'first_contact_date' => '2025-02-15',
                        'last_activity' => '2025-05-10',
                        'next_step' => 'Implementation kickoff',
                    ],
                    [
                        'id' => 105,
                        'company' => 'Quantum Research',
                        'value' => 65000,
                        'stage' => 'Qualification',
                        'probability' => 20,
                        'expected_close_date' => '2025-09-01',
                        'owner' => 'Alex Martinez',
                        'products' => ['CRM Pro', 'Data Migration Service'],
                        'first_contact_date' => '2025-05-28',
                        'last_activity' => '2025-06-01',
                        'next_step' => 'Technical requirements review',
                    ],
                ];
                
            case 'marketing':
                return [
                    [
                        'id' => 201,
                        'name' => 'Spring Product Launch',
                        'type' => 'Email',
                        'status' => 'Completed',
                        'start_date' => '2025-04-01',
                        'end_date' => '2025-04-15',
                        'budget' => 5000,
                        'spend' => 4850,
                        'metrics' => [
                            'emails_sent' => 15000,
                            'open_rate' => 22.5,
                            'click_rate' => 3.8,
                            'conversion_rate' => 1.2,
                            'revenue_attributed' => 28500,
                        ],
                        'target_audience' => 'Existing Customers',
                        'owner' => 'Marketing Team',
                    ],
                    [
                        'id' => 202,
                        'name' => 'Summer Webinar Series',
                        'type' => 'Webinar',
                        'status' => 'Active',
                        'start_date' => '2025-06-01',
                        'end_date' => '2025-08-31',
                        'budget' => 12000,
                        'spend' => 4200,
                        'metrics' => [
                            'registrations' => 850,
                            'attendance_rate' => 65.3,
                            'lead_conversion' => 8.5,
                            'revenue_attributed' => 35000,
                        ],
                        'target_audience' => 'Prospects',
                        'owner' => 'Webinar Team',
                    ],
                    [
                        'id' => 203,
                        'name' => 'Industry Conference Sponsorship',
                        'type' => 'Event',
                        'status' => 'Planned',
                        'start_date' => '2025-09-15',
                        'end_date' => '2025-09-17',
                        'budget' => 25000,
                        'spend' => 5000,
                        'metrics' => [
                            'booth_visitors' => 0,
                            'leads_collected' => 0,
                            'meetings_scheduled' => 0,
                            'revenue_attributed' => 0,
                        ],
                        'target_audience' => 'Industry Professionals',
                        'owner' => 'Events Team',
                    ],
                    [
                        'id' => 204,
                        'name' => 'Q1 PPC Campaign',
                        'type' => 'Paid Search',
                        'status' => 'Completed',
                        'start_date' => '2025-01-01',
                        'end_date' => '2025-03-31',
                        'budget' => 15000,
                        'spend' => 14950,
                        'metrics' => [
                            'impressions' => 250000,
                            'clicks' => 12500,
                            'ctr' => 5.0,
                            'conversions' => 375,
                            'cost_per_conversion' => 39.87,
                            'revenue_attributed' => 56250,
                        ],
                        'target_audience' => 'New Prospects',
                        'owner' => 'Digital Marketing Team',
                    ],
                ];
                
            case 'support':
                return [
                    [
                        'id' => 301,
                        'customer' => 'Acme Corporation',
                        'subject' => 'Integration with Salesforce not working',
                        'status' => 'Open',
                        'priority' => 'High',
                        'created_at' => '2025-06-01 09:15:22',
                        'updated_at' => '2025-06-02 14:30:45',
                        'assigned_to' => 'Technical Support Team',
                        'category' => 'Integration',
                        'first_response_time' => '00:45:12',
                        'resolution_time' => null,
                        'satisfaction_score' => null,
                    ],
                    [
                        'id' => 302,
                        'customer' => 'Global Services Inc',
                        'subject' => 'Need help setting up email templates',
                        'status' => 'Closed',
                        'priority' => 'Medium',
                        'created_at' => '2025-05-28 13:22:10',
                        'updated_at' => '2025-05-29 10:15:33',
                        'assigned_to' => 'Customer Success',
                        'category' => 'Usage Question',
                        'first_response_time' => '01:12:45',
                        'resolution_time' => '21:53:23',
                        'satisfaction_score' => 9,
                    ],
                    [
                        'id' => 303,
                        'customer' => 'EcoSolutions',
                        'subject' => 'Dashboard showing incorrect data',
                        'status' => 'In Progress',
                        'priority' => 'High',
                        'created_at' => '2025-05-30 16:05:17',
                        'updated_at' => '2025-06-02 11:22:40',
                        'assigned_to' => 'Engineering',
                        'category' => 'Bug',
                        'first_response_time' => '00:32:18',
                        'resolution_time' => null,
                        'satisfaction_score' => null,
                    ],
                    [
                        'id' => 304,
                        'customer' => 'MediHealth Group',
                        'subject' => 'Request for additional user licenses',
                        'status' => 'Closed',
                        'priority' => 'Low',
                        'created_at' => '2025-05-25 09:45:30',
                        'updated_at' => '2025-05-25 11:30:22',
                        'assigned_to' => 'Account Management',
                        'category' => 'Billing',
                        'first_response_time' => '00:55:10',
                        'resolution_time' => '01:45:52',
                        'satisfaction_score' => 10,
                    ],
                    [
                        'id' => 305,
                        'customer' => 'Sunrise Retail',
                        'subject' => 'API rate limit exceeded',
                        'status' => 'Open',
                        'priority' => 'Critical',
                        'created_at' => '2025-06-02 08:12:45',
                        'updated_at' => '2025-06-02 08:15:22',
                        'assigned_to' => 'Engineering',
                        'category' => 'API',
                        'first_response_time' => '00:02:37',
                        'resolution_time' => null,
                        'satisfaction_score' => null,
                    ],
                ];
                
            case 'custom':
            default:
                // Return a combination of different data types
                return [
                    'customers' => array_slice($this->getSampleData('customers'), 0, 2),
                    'deals' => array_slice($this->getSampleData('deals'), 0, 2),
                    'marketing' => array_slice($this->getSampleData('marketing'), 0, 2),
                    'support' => array_slice($this->getSampleData('support'), 0, 2),
                ];
        }
    }
}
