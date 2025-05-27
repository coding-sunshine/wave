# Fusion CRM V4 - Self-Service Signup & Guided Onboarding Architecture

This document outlines the architecture for the Self-Service Signup and Guided Onboarding system in Fusion CRM V4, providing a streamlined process for new users to register, select plans, and effectively learn the system.

## Overview

Fusion CRM V4 implements a comprehensive self-service system with these key capabilities:

1. **Automated Signup**: Public-facing registration with multiple plan options
2. **Subscription Management**: Seamless integration with payment providers
3. **Tenant Provisioning**: Automatic setup of new tenant environments
4. **Guided Onboarding**: Interactive tutorial and setup checklist
5. **Progress Tracking**: Completion status for onboarding steps

## Core Components

### 1. Registration Service

The central service for handling new user signups and account creation:

```php
namespace App\Services\Registration;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Services\Billing\BillingService;
use App\Services\Tenancy\TenantProvisioningService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationService
{
    protected $billingService;
    protected $tenantProvisioningService;
    
    public function __construct(
        BillingService $billingService,
        TenantProvisioningService $tenantProvisioningService
    ) {
        $this->billingService = $billingService;
        $this->tenantProvisioningService = $tenantProvisioningService;
    }
    
    /**
     * Register a new tenant with the first admin user
     */
    public function registerTenant(array $data): array
    {
        // Validate data
        $this->validateRegistrationData($data);
        
        // Start database transaction
        return DB::transaction(function () use ($data) {
            // Create tenant
            $tenant = $this->createTenant($data);
            
            // Create admin user
            $user = $this->createAdminUser($tenant, $data);
            
            // Process subscription payment
            $subscription = $this->processSubscription($tenant, $user, $data);
            
            // Provision tenant environment
            $this->tenantProvisioningService->provision($tenant, $data['plan']);
            
            // Create onboarding progress record
            $onboardingProgress = $this->createOnboardingProgress($tenant, $user);
            
            // Send welcome email
            $this->sendWelcomeEmail($user);
            
            return [
                'tenant' => $tenant,
                'user' => $user,
                'subscription' => $subscription,
                'onboarding_progress' => $onboardingProgress,
            ];
        });
    }
    
    /**
     * Validate registration data
     */
    protected function validateRegistrationData(array $data): void
    {
        $requiredFields = ['business_name', 'first_name', 'last_name', 'email', 'phone', 'plan', 'password'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }
        
        // Validate email is not already in use
        if (User::where('email', $data['email'])->exists()) {
            throw new \Exception('Email address is already in use');
        }
        
        // Validate ABN for Australian businesses
        if (!empty($data['abn']) && !$this->isValidABN($data['abn'])) {
            throw new \Exception('Invalid ABN format');
        }
        
        // Validate plan is valid
        if (!in_array($data['plan'], ['monthly', 'monthly_no_setup', 'annual'])) {
            throw new \Exception('Invalid subscription plan');
        }
    }
    
    /**
     * Create tenant record
     */
    protected function createTenant(array $data): Tenant
    {
        $tenant = new Tenant();
        $tenant->name = $data['business_name'];
        $tenant->slug = $this->generateUniqueSlug($data['business_name']);
        $tenant->email = $data['email'];
        $tenant->phone = $data['phone'];
        $tenant->abn = $data['abn'] ?? null;
        $tenant->address = $data['address'] ?? null;
        $tenant->suburb = $data['suburb'] ?? null;
        $tenant->state = $data['state'] ?? null;
        $tenant->postcode = $data['postcode'] ?? null;
        $tenant->country = $data['country'] ?? 'Australia';
        $tenant->timezone = $data['timezone'] ?? 'Australia/Sydney';
        $tenant->status = 'active';
        $tenant->referral_code = $data['referral_code'] ?? null;
        $tenant->created_via = 'self_signup';
        $tenant->save();
        
        return $tenant;
    }
    
    /**
     * Create admin user for the tenant
     */
    protected function createAdminUser(Tenant $tenant, array $data): User
    {
        $user = new User();
        $user->tenant_id = $tenant->id;
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'];
        $user->password = Hash::make($data['password']);
        $user->status = 'active';
        $user->email_verified_at = now(); // Auto-verify for now
        $user->save();
        
        // Assign admin role
        $user->assignRole('admin');
        
        return $user;
    }
    
    /**
     * Process subscription payment
     */
    protected function processSubscription(Tenant $tenant, User $user, array $data): Subscription
    {
        // Get plan details
        $planDetails = $this->getPlanDetails($data['plan']);
        
        // Create subscription record
        $subscription = new Subscription();
        $subscription->tenant_id = $tenant->id;
        $subscription->user_id = $user->id;
        $subscription->plan = $data['plan'];
        $subscription->status = 'pending';
        $subscription->amount = $planDetails['amount'];
        $subscription->billing_cycle = $planDetails['billing_cycle'];
        $subscription->features = $planDetails['features'];
        $subscription->trial_ends_at = now()->addDays(14); // 14-day trial
        $subscription->save();
        
        // Process payment
        if (!empty($data['payment_token'])) {
            $paymentResult = $this->billingService->processPayment($subscription, $data['payment_token']);
            
            if ($paymentResult['success']) {
                $subscription->status = 'active';
                $subscription->payment_id = $paymentResult['payment_id'];
                $subscription->started_at = now();
                $subscription->next_billing_at = $this->calculateNextBillingDate($data['plan']);
                $subscription->save();
            } else {
                throw new \Exception('Payment processing failed: ' . $paymentResult['message']);
            }
        }
        
        return $subscription;
    }
    
    /**
     * Create onboarding progress record
     */
    protected function createOnboardingProgress(Tenant $tenant, User $user): array
    {
        $steps = [
            'set_password' => ['completed' => true, 'completed_at' => now()],
            'sign_agreement' => ['completed' => false, 'completed_at' => null],
            'complete_crm_tour' => ['completed' => false, 'completed_at' => null],
            'upload_contacts' => ['completed' => false, 'completed_at' => null],
            'connect_website' => ['completed' => false, 'completed_at' => null],
            'launch_first_flyer' => ['completed' => false, 'completed_at' => null],
            'meet_bdm' => ['completed' => false, 'completed_at' => null],
        ];
        
        // Save onboarding progress
        $tenant->metadata = array_merge($tenant->metadata ?? [], [
            'onboarding_progress' => $steps,
            'onboarding_started_at' => now()->toIso8601String(),
        ]);
        $tenant->save();
        
        return $steps;
    }
    
    /**
     * Send welcome email
     */
    protected function sendWelcomeEmail(User $user): void
    {
        // In a real implementation, this would use Laravel's mail functionality
        // Mail::to($user->email)->send(new WelcomeEmail($user));
    }
    
    /**
     * Generate unique slug for tenant
     */
    protected function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;
        
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Get plan details based on plan code
     */
    protected function getPlanDetails(string $plan): array
    {
        return match ($plan) {
            'monthly' => [
                'amount' => 330,
                'setup_fee' => 990,
                'billing_cycle' => 'monthly',
                'features' => [
                    'is_feed_access' => true,
                    'is_php_site_access' => true,
                    'is_wordpress_site_access' => true,
                    'is_ai_enabled' => true,
                ],
            ],
            'monthly_no_setup' => [
                'amount' => 415,
                'setup_fee' => 0,
                'billing_cycle' => 'monthly',
                'features' => [
                    'is_feed_access' => true,
                    'is_php_site_access' => true,
                    'is_wordpress_site_access' => true,
                    'is_ai_enabled' => true,
                ],
            ],
            'annual' => [
                'amount' => 3960,
                'setup_fee' => 0,
                'billing_cycle' => 'annual',
                'features' => [
                    'is_feed_access' => true,
                    'is_php_site_access' => true,
                    'is_wordpress_site_access' => true,
                    'is_ai_enabled' => true,
                ],
            ],
            default => throw new \Exception('Invalid plan'),
        };
    }
    
    /**
     * Calculate next billing date based on plan
     */
    protected function calculateNextBillingDate(string $plan): \DateTime
    {
        return match ($plan) {
            'monthly', 'monthly_no_setup' => now()->addMonth(),
            'annual' => now()->addYear(),
            default => now()->addMonth(),
        };
    }
    
    /**
     * Validate ABN
     */
    protected function isValidABN(string $abn): bool
    {
        // Remove spaces and non-numeric characters
        $abn = preg_replace('/[^0-9]/', '', $abn);
        
        // ABN must be 11 digits
        if (strlen($abn) !== 11) {
            return false;
        }
        
        // Simple validation - in a real system, this would include checksum validation
        return true;
    }
}
```

### 2. Onboarding Service

Service for managing the user onboarding process:

```php
namespace App\Services\Onboarding;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Log;

class OnboardingService
{
    protected $tenantManager;
    
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Get onboarding progress for current tenant
     */
    public function getOnboardingProgress(): array
    {
        $tenant = $this->tenantManager->getTenant();
        
        if (!$tenant) {
            throw new \Exception('Tenant not found');
        }
        
        $metadata = $tenant->metadata ?? [];
        
        if (empty($metadata['onboarding_progress'])) {
            // Initialize onboarding progress if it doesn't exist
            return $this->initializeOnboardingProgress($tenant);
        }
        
        return [
            'steps' => $metadata['onboarding_progress'],
            'started_at' => $metadata['onboarding_started_at'] ?? null,
            'completion_percentage' => $this->calculateCompletionPercentage($metadata['onboarding_progress']),
        ];
    }
    
    /**
     * Initialize onboarding progress
     */
    public function initializeOnboardingProgress(Tenant $tenant): array
    {
        $steps = [
            'set_password' => ['completed' => true, 'completed_at' => now()->toIso8601String()],
            'sign_agreement' => ['completed' => false, 'completed_at' => null],
            'complete_crm_tour' => ['completed' => false, 'completed_at' => null],
            'upload_contacts' => ['completed' => false, 'completed_at' => null],
            'connect_website' => ['completed' => false, 'completed_at' => null],
            'launch_first_flyer' => ['completed' => false, 'completed_at' => null],
            'meet_bdm' => ['completed' => false, 'completed_at' => null],
        ];
        
        $metadata = $tenant->metadata ?? [];
        $metadata['onboarding_progress'] = $steps;
        $metadata['onboarding_started_at'] = now()->toIso8601String();
        
        $tenant->metadata = $metadata;
        $tenant->save();
        
        return [
            'steps' => $steps,
            'started_at' => $metadata['onboarding_started_at'],
            'completion_percentage' => $this->calculateCompletionPercentage($steps),
        ];
    }
    
    /**
     * Mark onboarding step as completed
     */
    public function completeStep(string $step): array
    {
        $tenant = $this->tenantManager->getTenant();
        
        if (!$tenant) {
            throw new \Exception('Tenant not found');
        }
        
        $metadata = $tenant->metadata ?? [];
        
        if (empty($metadata['onboarding_progress'])) {
            $this->initializeOnboardingProgress($tenant);
            $metadata = $tenant->metadata;
        }
        
        if (!isset($metadata['onboarding_progress'][$step])) {
            throw new \Exception("Invalid onboarding step: {$step}");
        }
        
        // Mark step as completed
        $metadata['onboarding_progress'][$step]['completed'] = true;
        $metadata['onboarding_progress'][$step]['completed_at'] = now()->toIso8601String();
        
        // If all steps are completed, mark onboarding as completed
        $allCompleted = true;
        foreach ($metadata['onboarding_progress'] as $stepData) {
            if (!$stepData['completed']) {
                $allCompleted = false;
                break;
            }
        }
        
        if ($allCompleted) {
            $metadata['onboarding_completed_at'] = now()->toIso8601String();
        }
        
        // Save changes
        $tenant->metadata = $metadata;
        $tenant->save();
        
        // Log completion
        Log::info('Onboarding step completed', [
            'tenant_id' => $tenant->id,
            'step' => $step,
            'user_id' => auth()->id(),
        ]);
        
        return [
            'steps' => $metadata['onboarding_progress'],
            'started_at' => $metadata['onboarding_started_at'] ?? null,
            'completed_at' => $metadata['onboarding_completed_at'] ?? null,
            'completion_percentage' => $this->calculateCompletionPercentage($metadata['onboarding_progress']),
        ];
    }
    
    /**
     * Skip onboarding step
     */
    public function skipStep(string $step): array
    {
        $tenant = $this->tenantManager->getTenant();
        
        if (!$tenant) {
            throw new \Exception('Tenant not found');
        }
        
        $metadata = $tenant->metadata ?? [];
        
        if (empty($metadata['onboarding_progress'])) {
            $this->initializeOnboardingProgress($tenant);
            $metadata = $tenant->metadata;
        }
        
        if (!isset($metadata['onboarding_progress'][$step])) {
            throw new \Exception("Invalid onboarding step: {$step}");
        }
        
        // Mark step as skipped
        $metadata['onboarding_progress'][$step]['completed'] = true;
        $metadata['onboarding_progress'][$step]['completed_at'] = now()->toIso8601String();
        $metadata['onboarding_progress'][$step]['skipped'] = true;
        
        // Save changes
        $tenant->metadata = $metadata;
        $tenant->save();
        
        // Log skip
        Log::info('Onboarding step skipped', [
            'tenant_id' => $tenant->id,
            'step' => $step,
            'user_id' => auth()->id(),
        ]);
        
        return [
            'steps' => $metadata['onboarding_progress'],
            'started_at' => $metadata['onboarding_started_at'] ?? null,
            'completed_at' => $metadata['onboarding_completed_at'] ?? null,
            'completion_percentage' => $this->calculateCompletionPercentage($metadata['onboarding_progress']),
        ];
    }
    
    /**
     * Calculate completion percentage
     */
    protected function calculateCompletionPercentage(array $steps): int
    {
        $totalSteps = count($steps);
        
        if ($totalSteps === 0) {
            return 0;
        }
        
        $completedSteps = 0;
        
        foreach ($steps as $step) {
            if ($step['completed']) {
                $completedSteps++;
            }
        }
        
        return (int) round(($completedSteps / $totalSteps) * 100);
    }
    
    /**
     * Get onboarding content
     */
    public function getOnboardingContent(string $step): array
    {
        // This would be stored in a database or configuration in a real implementation
        $content = match ($step) {
            'set_password' => [
                'title' => 'Set Your Password',
                'description' => 'Create a secure password for your account',
                'video_url' => null,
                'help_text' => 'Your password should be at least 8 characters long and include a mix of letters, numbers, and symbols.',
            ],
            'sign_agreement' => [
                'title' => 'Sign User Agreement',
                'description' => 'Review and sign the user agreement',
                'video_url' => null,
                'help_text' => 'Please review our terms of service and privacy policy before proceeding.',
            ],
            'complete_crm_tour' => [
                'title' => 'Complete CRM Tour',
                'description' => 'Take a guided tour of the CRM',
                'video_url' => 'https://example.com/videos/crm-tour.mp4',
                'help_text' => 'This tour will show you the main features of the CRM and how to use them.',
            ],
            'upload_contacts' => [
                'title' => 'Upload Your Contacts',
                'description' => 'Import your existing contacts',
                'video_url' => 'https://example.com/videos/import-contacts.mp4',
                'help_text' => 'You can import contacts from a CSV file or connect with your email provider.',
            ],
            'connect_website' => [
                'title' => 'Connect Your Website',
                'description' => 'Link your website to the CRM',
                'video_url' => 'https://example.com/videos/website-connection.mp4',
                'help_text' => 'Connecting your website allows you to capture leads directly into the CRM.',
            ],
            'launch_first_flyer' => [
                'title' => 'Create Your First Property Flyer',
                'description' => 'Create and publish a property flyer',
                'video_url' => 'https://example.com/videos/create-flyer.mp4',
                'help_text' => 'This guide will help you create and publish your first property flyer.',
            ],
            'meet_bdm' => [
                'title' => 'Meet Your Business Development Manager',
                'description' => 'Schedule a call with your dedicated BDM',
                'video_url' => null,
                'help_text' => 'Your BDM can provide personalized guidance and support for your specific needs.',
            ],
            default => throw new \Exception("Invalid onboarding step: {$step}"),
        };
        
        return $content;
    }
}
```

### 3. Tenant Provisioning Service

Service for setting up the new tenant environment:

```php
namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TenantProvisioningService
{
    /**
     * Provision a new tenant environment
     */
    public function provision(Tenant $tenant, string $plan): void
    {
        try {
            // Create tenant database
            $this->createTenantDatabase($tenant);
            
            // Run tenant migrations
            $this->runTenantMigrations($tenant);
            
            // Seed tenant with default data
            $this->seedTenantData($tenant, $plan);
            
            // Provision storage
            $this->provisionStorage($tenant);
            
            // Set up feature flags based on plan
            $this->setupFeatureFlags($tenant, $plan);
            
            // Log successful provisioning
            Log::info('Tenant provisioned successfully', [
                'tenant_id' => $tenant->id,
                'plan' => $plan,
            ]);
        } catch (\Exception $e) {
            // Log error
            Log::error('Tenant provisioning failed', [
                'tenant_id' => $tenant->id,
                'plan' => $plan,
                'error' => $e->getMessage(),
            ]);
            
            // Attempt cleanup
            $this->cleanup($tenant);
            
            // Rethrow exception
            throw $e;
        }
    }
    
    /**
     * Create tenant database
     */
    protected function createTenantDatabase(Tenant $tenant): void
    {
        // In a real implementation, this would create a dedicated database or schema
        // For Fusion CRM V4, we're using the multi-tenant architecture established earlier
        
        // Set up tenant configuration
        config(['database.connections.tenant.database' => "tenant_{$tenant->id}"]);
        
        // Create tenant database if it doesn't exist
        Artisan::call('tenants:create-database', [
            'tenant' => $tenant->id,
        ]);
    }
    
    /**
     * Run tenant migrations
     */
    protected function runTenantMigrations(Tenant $tenant): void
    {
        Artisan::call('tenants:migrate', [
            'tenant' => $tenant->id,
        ]);
    }
    
    /**
     * Seed tenant with default data
     */
    protected function seedTenantData(Tenant $tenant, string $plan): void
    {
        // This would set up default roles, permissions, etc.
        Artisan::call('tenants:seed', [
            'tenant' => $tenant->id,
            '--class' => 'TenantDefaultSeeder',
            '--force' => true,
        ]);
        
        // Additionally seed demo data for new tenants
        if ($plan !== 'demo') {
            Artisan::call('tenants:seed', [
                'tenant' => $tenant->id,
                '--class' => 'TenantDemoDataSeeder',
                '--force' => true,
            ]);
        }
    }
    
    /**
     * Provision storage
     */
    protected function provisionStorage(Tenant $tenant): void
    {
        // Create tenant storage directories
        $directories = [
            "tenants/{$tenant->id}/uploads",
            "tenants/{$tenant->id}/uploads/properties",
            "tenants/{$tenant->id}/uploads/documents",
            "tenants/{$tenant->id}/uploads/profiles",
            "tenants/{$tenant->id}/temp",
        ];
        
        foreach ($directories as $directory) {
            if (!is_dir(storage_path("app/public/{$directory}"))) {
                mkdir(storage_path("app/public/{$directory}"), 0755, true);
            }
        }
    }
    
    /**
     * Set up feature flags based on plan
     */
    protected function setupFeatureFlags(Tenant $tenant, string $plan): void
    {
        $features = match ($plan) {
            'monthly', 'monthly_no_setup', 'annual' => [
                'is_feed_access' => true,
                'is_php_site_access' => true,
                'is_wordpress_site_access' => true,
                'is_ai_enabled' => true,
                'max_properties' => 1000,
                'max_users' => 10,
                'max_storage_gb' => 50,
            ],
            'demo' => [
                'is_feed_access' => true,
                'is_php_site_access' => true,
                'is_wordpress_site_access' => false,
                'is_ai_enabled' => true,
                'max_properties' => 20,
                'max_users' => 3,
                'max_storage_gb' => 1,
                'is_demo' => true,
            ],
            default => [
                'is_feed_access' => true,
                'is_php_site_access' => true,
                'is_wordpress_site_access' => false,
                'is_ai_enabled' => false,
                'max_properties' => 100,
                'max_users' => 5,
                'max_storage_gb' => 10,
            ],
        };
        
        // Save feature flags
        $tenant->features = $features;
        $tenant->save();
    }
    
    /**
     * Clean up failed provisioning
     */
    protected function cleanup(Tenant $tenant): void
    {
        try {
            // Drop tenant database if it exists
            Artisan::call('tenants:drop-database', [
                'tenant' => $tenant->id,
                '--force' => true,
            ]);
            
            // Delete tenant storage directories
            $basePath = storage_path("app/public/tenants/{$tenant->id}");
            if (is_dir($basePath)) {
                $this->deleteDirectory($basePath);
            }
            
            // Mark tenant as failed
            $tenant->status = 'provisioning_failed';
            $tenant->save();
        } catch (\Exception $e) {
            Log::error('Tenant cleanup failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Delete a directory and its contents
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }
}
```

## Onboarding Checklist Implementation

The onboarding checklist is implemented as a Livewire component that guides users through setup:

```php
namespace App\Http\Livewire\Onboarding;

use Livewire\Component;
use App\Services\Onboarding\OnboardingService;

class OnboardingChecklist extends Component
{
    public $steps = [];
    public $currentStep = '';
    public $completionPercentage = 0;
    public $showChecklist = true;
    public $content = [];
    
    protected $onboardingService;
    
    protected $listeners = [
        'stepCompleted' => 'refreshProgress',
        'skipStep' => 'skipCurrentStep',
    ];
    
    public function boot(OnboardingService $onboardingService)
    {
        $this->onboardingService = $onboardingService;
    }
    
    public function mount()
    {
        $this->loadProgress();
    }
    
    public function loadProgress()
    {
        $progress = $this->onboardingService->getOnboardingProgress();
        $this->steps = $progress['steps'];
        $this->completionPercentage = $progress['completion_percentage'];
        
        // Determine current step (first incomplete step)
        foreach ($this->steps as $stepKey => $step) {
            if (!$step['completed']) {
                $this->currentStep = $stepKey;
                break;
            }
        }
        
        // If all steps are completed, set current step to last one
        if (!$this->currentStep && count($this->steps) > 0) {
            $stepKeys = array_keys($this->steps);
            $this->currentStep = end($stepKeys);
        }
        
        // Load content for current step
        if ($this->currentStep) {
            $this->content = $this->onboardingService->getOnboardingContent($this->currentStep);
        }
    }
    
    public function selectStep($step)
    {
        $this->currentStep = $step;
        $this->content = $this->onboardingService->getOnboardingContent($step);
    }
    
    public function completeCurrentStep()
    {
        if ($this->currentStep) {
            $progress = $this->onboardingService->completeStep($this->currentStep);
            $this->steps = $progress['steps'];
            $this->completionPercentage = $progress['completion_percentage'];
            
            // Find next incomplete step
            $foundCurrent = false;
            $nextStep = null;
            
            foreach ($this->steps as $stepKey => $step) {
                if ($stepKey === $this->currentStep) {
                    $foundCurrent = true;
                    continue;
                }
                
                if ($foundCurrent && !$step['completed']) {
                    $nextStep = $stepKey;
                    break;
                }
            }
            
            // Move to next step if available
            if ($nextStep) {
                $this->currentStep = $nextStep;
                $this->content = $this->onboardingService->getOnboardingContent($nextStep);
            }
            
            $this->emit('onboardingUpdated', $this->completionPercentage);
        }
    }
    
    public function skipCurrentStep()
    {
        if ($this->currentStep) {
            $progress = $this->onboardingService->skipStep($this->currentStep);
            $this->steps = $progress['steps'];
            $this->completionPercentage = $progress['completion_percentage'];
            
            // Find next incomplete step
            $foundCurrent = false;
            $nextStep = null;
            
            foreach ($this->steps as $stepKey => $step) {
                if ($stepKey === $this->currentStep) {
                    $foundCurrent = true;
                    continue;
                }
                
                if ($foundCurrent && !$step['completed']) {
                    $nextStep = $stepKey;
                    break;
                }
            }
            
            // Move to next step if available
            if ($nextStep) {
                $this->currentStep = $nextStep;
                $this->content = $this->onboardingService->getOnboardingContent($nextStep);
            }
            
            $this->emit('onboardingUpdated', $this->completionPercentage);
        }
    }
    
    public function refreshProgress()
    {
        $this->loadProgress();
    }
    
    public function toggleChecklist()
    {
        $this->showChecklist = !$this->showChecklist;
    }
    
    public function render()
    {
        return view('livewire.onboarding.checklist');
    }
}
```

## Registration Form

The public-facing registration form implemented as a Laravel controller:

```php
namespace App\Http\Controllers;

use App\Http\Requests\RegistrationRequest;
use App\Services\Registration\RegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    protected $registrationService;
    
    public function __construct(RegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }
    
    /**
     * Show registration form
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }
    
    /**
     * Handle registration submission
     */
    public function register(RegistrationRequest $request)
    {
        try {
            // Register tenant
            $result = $this->registrationService->registerTenant($request->validated());
            
            // Log registration
            Log::info('New tenant registered', [
                'tenant_id' => $result['tenant']->id,
                'user_id' => $result['user']->id,
                'plan' => $request->input('plan'),
                'referral_code' => $request->input('referral_code'),
            ]);
            
            // Auto login
            auth()->login($result['user']);
            
            // Redirect to dashboard with onboarding
            return redirect()->route('dashboard')->with('registration_success', true);
        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'data' => $request->except(['password', 'password_confirmation']),
            ]);
            
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['registration_error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get available plans (AJAX)
     */
    public function getPlans()
    {
        return response()->json([
            'plans' => [
                [
                    'id' => 'monthly',
                    'name' => 'Monthly',
                    'price' => 330,
                    'setup_fee' => 990,
                    'billing_frequency' => 'monthly',
                    'commitment' => '12 months',
                    'description' => 'Monthly billing with setup fee',
                    'features' => [
                        'All CRM features',
                        'Website integration',
                        'Property feed',
                        'AI tools',
                    ],
                ],
                [
                    'id' => 'monthly_no_setup',
                    'name' => 'Monthly (No Setup)',
                    'price' => 415,
                    'setup_fee' => 0,
                    'billing_frequency' => 'monthly',
                    'commitment' => '12 months',
                    'description' => 'Higher monthly fee with no setup fee',
                    'features' => [
                        'All CRM features',
                        'Website integration',
                        'Property feed',
                        'AI tools',
                    ],
                ],
                [
                    'id' => 'annual',
                    'name' => 'Annual Saver',
                    'price' => 3960,
                    'setup_fee' => 0,
                    'billing_frequency' => 'annual',
                    'commitment' => 'None',
                    'description' => 'Annual billing with no setup fee (save 20%)',
                    'features' => [
                        'All CRM features',
                        'Website integration',
                        'Property feed',
                        'AI tools',
                        'Priority support',
                    ],
                ],
            ],
        ]);
    }
    
    /**
     * Validate business name availability (AJAX)
     */
    public function checkBusinessNameAvailability(Request $request)
    {
        $businessName = $request->input('business_name');
        
        // Check if business name is available for slug generation
        $slug = \Illuminate\Support\Str::slug($businessName);
        $isAvailable = !\App\Models\Tenant::where('slug', $slug)->exists();
        
        return response()->json([
            'available' => $isAvailable,
        ]);
    }
}
```

## Database Schema

```sql
-- Subscriptions
CREATE TABLE subscriptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    plan VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    billing_cycle VARCHAR(20) NOT NULL,
    features JSON NOT NULL,
    payment_id VARCHAR(255) NULL,
    payment_method VARCHAR(100) NULL,
    trial_ends_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    next_billing_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payment transactions
CREATE TABLE payment_transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    transaction_id VARCHAR(255) NOT NULL,
    provider VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    status VARCHAR(50) NOT NULL,
    payment_method VARCHAR(100) NULL,
    payment_method_details JSON NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Feature flags (already in tenant.features column)
```

## Implementation Strategy

### Phase 1: Registration System

1. **Public Signup Form**
   - Account creation
   - Plan selection
   - Business info collection

2. **Payment Integration**
   - Integration with Xero
   - Secure payment processing
   - Receipt generation

### Phase 2: Onboarding Process

3. **Guided Setup**
   - Welcome flow
   - Interactive tutorial
   - Progress tracking

4. **Initial Configuration**
   - User and tenant setup
   - Default settings
   - Example data

### Phase 3: Retention & Engagement

5. **Email Journey**
   - Welcome series
   - Training content
   - Engagement campaigns

6. **Account Management**
   - Plan upgrades
   - Billing management
   - User provisioning

## Security Considerations

1. **Payment Security**
   - PCI compliance for payment processing
   - Secure storage of billing information
   - Audit logs for all transactions

2. **Account Protection**
   - Verification of business information
   - Anti-fraud measures
   - Secure password requirements

3. **Data Privacy**
   - GDPR/CCPA compliance from signup
   - Clear terms and privacy policy
   - Consent management

## Conclusion

The Self-Service Signup & Guided Onboarding architecture in Fusion CRM V4 provides:

1. **Streamlined Acquisition**: Frictionless signup process for new users
2. **Flexible Pricing**: Multiple plan options to fit different business needs
3. **Guided Learning**: Step-by-step onboarding to ensure successful adoption
4. **Automated Setup**: Immediate provisioning of tenant environments
5. **Clear Progress**: Visual tracking of onboarding completion

This architecture ensures that new users can quickly get started with Fusion CRM V4, understand its features, and successfully implement it in their business without requiring manual intervention from support staff. 