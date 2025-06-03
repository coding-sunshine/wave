# Fusion CRM V4 - Website & API Integration Architecture

This document outlines the architecture for the Website & API Integration in Fusion CRM V4, which enables seamless connection with external systems and websites.

## Overview

Fusion CRM V4 implements a comprehensive integration system that provides:

1. **WordPress Site Hub**: Central management of WordPress site connections and data sync
2. **PHP Fast Site Engine**: High-performance property listing display for websites
3. **REST & GraphQL API**: Dual API approach for maximum flexibility and performance
4. **Zapier & Make Integration**: No-code workflow automation connectors
5. **Developer Portal**: Self-service API documentation and testing tools

## Core Components

### 1. Integration Hub Service

The central service for managing all external integrations:

```php
namespace App\Services\Integration;

use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IntegrationHub
{
    protected $tenantManager;
    protected $integrationProviders = [];
    
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Register an integration provider
     */
    public function registerProvider(string $type, IntegrationProviderInterface $provider): void
    {
        $this->integrationProviders[$type] = $provider;
    }
    
    /**
     * Get all registered providers
     */
    public function getProviders(): array
    {
        return $this->integrationProviders;
    }
    
    /**
     * Get a specific provider
     */
    public function getProvider(string $type): IntegrationProviderInterface
    {
        if (!isset($this->integrationProviders[$type])) {
            throw new \Exception("Integration provider '{$type}' not registered");
        }
        
        return $this->integrationProviders[$type];
    }
    
    /**
     * Create a new integration
     */
    public function createIntegration(array $data): Integration
    {
        $provider = $this->getProvider($data['type']);
        
        // Validate configuration
        $validationResult = $provider->validateConfig($data['configuration'] ?? []);
        
        if (!$validationResult['valid']) {
            throw new \Exception('Invalid configuration: ' . implode(', ', $validationResult['errors']));
        }
        
        // Create integration record
        $tenant = $this->tenantManager->getTenant();
        
        $integration = new Integration();
        $integration->tenant_id = $tenant->id;
        $integration->name = $data['name'];
        $integration->type = $data['type'];
        $integration->configuration = $data['configuration'] ?? [];
        $integration->metadata = $data['metadata'] ?? [];
        $integration->status = 'inactive';
        $integration->created_by = auth()->id();
        $integration->save();
        
        return $integration;
    }
    
    /**
     * Update an integration
     */
    public function updateIntegration(Integration $integration, array $data): Integration
    {
        $provider = $this->getProvider($integration->type);
        
        // Update configuration if provided
        if (isset($data['configuration'])) {
            // Validate configuration
            $validationResult = $provider->validateConfig($data['configuration']);
            
            if (!$validationResult['valid']) {
                throw new \Exception('Invalid configuration: ' . implode(', ', $validationResult['errors']));
            }
            
            $integration->configuration = $data['configuration'];
        }
        
        // Update other fields
        if (isset($data['name'])) {
            $integration->name = $data['name'];
        }
        
        if (isset($data['metadata'])) {
            $integration->metadata = array_merge($integration->metadata ?? [], $data['metadata']);
        }
        
        $integration->save();
        
        return $integration;
    }
    
    /**
     * Activate an integration
     */
    public function activateIntegration(Integration $integration): Integration
    {
        $provider = $this->getProvider($integration->type);
        
        try {
            // Test the connection
            $provider->testConnection($integration);
            
            // Activate the integration
            $integration->status = 'active';
            $integration->last_connected_at = now();
            $integration->save();
            
            // Log the activation
            $this->logIntegrationEvent($integration, 'activated', 'Integration activated successfully');
            
            return $integration;
        } catch (\Exception $e) {
            // Log the failure
            $this->logIntegrationEvent($integration, 'activation_failed', $e->getMessage());
            
            throw $e;
        }
    }
    
    /**
     * Deactivate an integration
     */
    public function deactivateIntegration(Integration $integration): Integration
    {
        $integration->status = 'inactive';
        $integration->save();
        
        // Log the deactivation
        $this->logIntegrationEvent($integration, 'deactivated', 'Integration deactivated');
        
        return $integration;
    }
    
    /**
     * Execute an integration action
     */
    public function executeAction(Integration $integration, string $action, array $params = []): mixed
    {
        if ($integration->status !== 'active') {
            throw new \Exception('Integration is not active');
        }
        
        $provider = $this->getProvider($integration->type);
        
        try {
            // Execute the action
            $result = $provider->executeAction($integration, $action, $params);
            
            // Log the action
            $this->logIntegrationEvent(
                $integration, 
                'action_executed', 
                "Action '{$action}' executed successfully", 
                ['params' => $params]
            );
            
            return $result;
        } catch (\Exception $e) {
            // Log the failure
            $this->logIntegrationEvent(
                $integration, 
                'action_failed', 
                "Action '{$action}' failed: " . $e->getMessage(), 
                ['params' => $params]
            );
            
            throw $e;
        }
    }
    
    /**
     * Sync data with an integration
     */
    public function syncData(Integration $integration, string $dataType, array $options = []): array
    {
        if ($integration->status !== 'active') {
            throw new \Exception('Integration is not active');
        }
        
        $provider = $this->getProvider($integration->type);
        
        try {
            // Execute the sync
            $result = $provider->syncData($integration, $dataType, $options);
            
            // Log the sync
            $this->logIntegrationEvent(
                $integration, 
                'data_synced', 
                "Data type '{$dataType}' synced successfully", 
                ['result' => $result]
            );
            
            // Update last synced timestamp
            $metadata = $integration->metadata ?? [];
            $metadata['last_sync'] = array_merge($metadata['last_sync'] ?? [], [
                $dataType => now()->toIso8601String()
            ]);
            $integration->metadata = $metadata;
            $integration->save();
            
            return $result;
        } catch (\Exception $e) {
            // Log the failure
            $this->logIntegrationEvent(
                $integration, 
                'sync_failed', 
                "Data sync for '{$dataType}' failed: " . $e->getMessage()
            );
            
            throw $e;
        }
    }
    
    /**
     * Log an integration event
     */
    protected function logIntegrationEvent(
        Integration $integration, 
        string $event, 
        string $message, 
        array $data = []
    ): IntegrationLog {
        $log = new IntegrationLog();
        $log->integration_id = $integration->id;
        $log->event = $event;
        $log->message = $message;
        $log->data = $data;
        $log->save();
        
        return $log;
    }
    
    /**
     * Get all integrations for current tenant
     */
    public function getAllIntegrations(): Collection
    {
        $tenant = $this->tenantManager->getTenant();
        
        return Integration::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();
    }
    
    /**
     * Get integrations by type
     */
    public function getIntegrationsByType(string $type): Collection
    {
        $tenant = $this->tenantManager->getTenant();
        
        return Integration::where('tenant_id', $tenant->id)
            ->where('type', $type)
            ->orderBy('name')
            ->get();
    }
}
```

### 2. Integration Provider Interface

Common interface for all integration providers:

```php
namespace App\Services\Integration;

use App\Models\Integration;

interface IntegrationProviderInterface
{
    /**
     * Get provider information
     */
    public function getProviderInfo(): array;
    
    /**
     * Validate integration configuration
     */
    public function validateConfig(array $config): array;
    
    /**
     * Test connection to the integration
     */
    public function testConnection(Integration $integration): bool;
    
    /**
     * Execute an action on the integration
     */
    public function executeAction(Integration $integration, string $action, array $params = []): mixed;
    
    /**
     * Sync data with the integration
     */
    public function syncData(Integration $integration, string $dataType, array $options = []): array;
    
    /**
     * Get available actions for this integration
     */
    public function getAvailableActions(): array;
    
    /**
     * Get available sync data types for this integration
     */
    public function getAvailableDataTypes(): array;
}
```

### 3. WordPress Integration Provider

Implementation of the WordPress website integration:

```php
namespace App\Services\Integration\Providers;

use App\Models\Integration;
use App\Models\Lot;
use App\Services\Integration\IntegrationProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressProvider implements IntegrationProviderInterface
{
    /**
     * Get provider information
     */
    public function getProviderInfo(): array
    {
        return [
            'type' => 'wordpress',
            'name' => 'WordPress',
            'description' => 'WordPress website integration for property listing sync',
            'icon' => 'fab fa-wordpress',
            'config_fields' => [
                'site_url' => [
                    'type' => 'url',
                    'label' => 'WordPress Site URL',
                    'required' => true,
                ],
                'api_key' => [
                    'type' => 'text',
                    'label' => 'API Key',
                    'required' => true,
                ],
                'api_secret' => [
                    'type' => 'password',
                    'label' => 'API Secret',
                    'required' => true,
                ],
                'sync_frequency' => [
                    'type' => 'select',
                    'label' => 'Sync Frequency',
                    'options' => [
                        'manual' => 'Manual Only',
                        'hourly' => 'Every Hour',
                        'daily' => 'Daily',
                        'realtime' => 'Real-time',
                    ],
                    'default' => 'daily',
                ],
            ],
        ];
    }
    
    /**
     * Validate integration configuration
     */
    public function validateConfig(array $config): array
    {
        $errors = [];
        
        // Validate site URL
        if (empty($config['site_url'])) {
            $errors[] = 'Site URL is required';
        } elseif (!filter_var($config['site_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Site URL is not valid';
        }
        
        // Validate API key and secret
        if (empty($config['api_key'])) {
            $errors[] = 'API Key is required';
        }
        
        if (empty($config['api_secret'])) {
            $errors[] = 'API Secret is required';
        }
        
        // Validate sync frequency
        if (isset($config['sync_frequency']) && !in_array($config['sync_frequency'], ['manual', 'hourly', 'daily', 'realtime'])) {
            $errors[] = 'Invalid sync frequency';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
    
    /**
     * Test connection to the integration
     */
    public function testConnection(Integration $integration): bool
    {
        $config = $integration->configuration;
        
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $config['api_key'],
                'X-API-Secret' => $config['api_secret'],
            ])->get($config['site_url'] . '/wp-json/fusion/v1/test-connection');
            
            if (!$response->successful()) {
                throw new \Exception('Failed to connect to WordPress: ' . $response->body());
            }
            
            $data = $response->json();
            
            if (!isset($data['success']) || !$data['success']) {
                throw new \Exception('Connection test failed: ' . ($data['message'] ?? 'Unknown error'));
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('WordPress connection test failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Execute an action on the integration
     */
    public function executeAction(Integration $integration, string $action, array $params = []): mixed
    {
        $config = $integration->configuration;
        
        switch ($action) {
            case 'get_site_info':
                return $this->getSiteInfo($integration);
            
            case 'publish_property':
                if (empty($params['property_id'])) {
                    throw new \Exception('Property ID is required');
                }
                
                return $this->publishProperty($integration, $params['property_id']);
            
            case 'unpublish_property':
                if (empty($params['property_id'])) {
                    throw new \Exception('Property ID is required');
                }
                
                return $this->unpublishProperty($integration, $params['property_id']);
            
            case 'check_plugin_status':
                return $this->checkPluginStatus($integration);
            
            default:
                throw new \Exception("Action '{$action}' not supported");
        }
    }
    
    /**
     * Sync data with the integration
     */
    public function syncData(Integration $integration, string $dataType, array $options = []): array
    {
        $config = $integration->configuration;
        
        switch ($dataType) {
            case 'properties':
                return $this->syncProperties($integration, $options);
            
            case 'forms':
                return $this->syncForms($integration, $options);
            
            case 'media':
                return $this->syncMedia($integration, $options);
            
            default:
                throw new \Exception("Data type '{$dataType}' not supported");
        }
    }
    
    /**
     * Get available actions for this integration
     */
    public function getAvailableActions(): array
    {
        return [
            'get_site_info' => 'Get WordPress Site Information',
            'publish_property' => 'Publish Property to WordPress',
            'unpublish_property' => 'Unpublish Property from WordPress',
            'check_plugin_status' => 'Check Fusion Plugin Status',
        ];
    }
    
    /**
     * Get available sync data types for this integration
     */
    public function getAvailableDataTypes(): array
    {
        return [
            'properties' => 'Property Listings',
            'forms' => 'Contact Forms',
            'media' => 'Media & Attachments',
        ];
    }
    
    /**
     * Get WordPress site information
     */
    protected function getSiteInfo(Integration $integration): array
    {
        $config = $integration->configuration;
        
        $response = Http::withHeaders([
            'X-API-Key' => $config['api_key'],
            'X-API-Secret' => $config['api_secret'],
        ])->get($config['site_url'] . '/wp-json/fusion/v1/site-info');
        
        if (!$response->successful()) {
            throw new \Exception('Failed to get site info: ' . $response->body());
        }
        
        return $response->json();
    }
    
    /**
     * Publish a property to WordPress
     */
    protected function publishProperty(Integration $integration, int $propertyId): array
    {
        $config = $integration->configuration;
        
        // Get property details
        $property = Lot::findOrFail($propertyId);
        $property->load(['media', 'features']);
        
        // Prepare property data
        $propertyData = [
            'id' => $property->id,
            'title' => $property->title,
            'description' => $property->description,
            'price' => $property->price,
            'price_text' => $property->price_text,
            'status' => $property->status,
            'property_type' => $property->property_type,
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'car_spaces' => $property->car_spaces,
            'land_size' => $property->land_size,
            'building_size' => $property->building_size,
            'address' => [
                'street_number' => $property->street_number,
                'street_name' => $property->street_name,
                'suburb' => $property->suburb,
                'state' => $property->state,
                'postcode' => $property->postcode,
                'country' => $property->country,
                'display_address' => $property->display_address,
            ],
            'features' => $property->features->pluck('name')->toArray(),
            'media' => $property->media->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->url,
                    'type' => $media->media_type,
                    'is_primary' => $media->pivot->is_primary ?? false,
                    'title' => $media->title,
                    'order' => $media->pivot->order ?? 0,
                ];
            })->toArray(),
        ];
        
        // Send to WordPress
        $response = Http::withHeaders([
            'X-API-Key' => $config['api_key'],
            'X-API-Secret' => $config['api_secret'],
        ])->post($config['site_url'] . '/wp-json/fusion/v1/properties', $propertyData);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to publish property: ' . $response->body());
        }
        
        $result = $response->json();
        
        // Store external ID in metadata
        $metadata = $property->metadata ?? [];
        $metadata['wordpress'] = array_merge($metadata['wordpress'] ?? [], [
            'integration_id' => $integration->id,
            'external_id' => $result['id'] ?? null,
            'external_url' => $result['url'] ?? null,
            'published_at' => now()->toIso8601String(),
        ]);
        
        $property->metadata = $metadata;
        $property->save();
        
        return $result;
    }
    
    /**
     * Unpublish a property from WordPress
     */
    protected function unpublishProperty(Integration $integration, int $propertyId): array
    {
        $config = $integration->configuration;
        
        // Get property
        $property = Lot::findOrFail($propertyId);
        
        // Check if property is published to this WordPress site
        $metadata = $property->metadata ?? [];
        $wordpressData = $metadata['wordpress'] ?? [];
        
        if (empty($wordpressData['external_id']) || ($wordpressData['integration_id'] ?? null) !== $integration->id) {
            throw new \Exception('Property not published to this WordPress site');
        }
        
        // Send delete request
        $response = Http::withHeaders([
            'X-API-Key' => $config['api_key'],
            'X-API-Secret' => $config['api_secret'],
        ])->delete($config['site_url'] . '/wp-json/fusion/v1/properties/' . $wordpressData['external_id']);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to unpublish property: ' . $response->body());
        }
        
        // Update property metadata
        unset($metadata['wordpress']);
        $property->metadata = $metadata;
        $property->save();
        
        return $response->json();
    }
    
    /**
     * Check Fusion plugin status on WordPress
     */
    protected function checkPluginStatus(Integration $integration): array
    {
        $config = $integration->configuration;
        
        $response = Http::withHeaders([
            'X-API-Key' => $config['api_key'],
            'X-API-Secret' => $config['api_secret'],
        ])->get($config['site_url'] . '/wp-json/fusion/v1/plugin-status');
        
        if (!$response->successful()) {
            throw new \Exception('Failed to check plugin status: ' . $response->body());
        }
        
        return $response->json();
    }
    
    /**
     * Sync properties with WordPress
     */
    protected function syncProperties(Integration $integration, array $options = []): array
    {
        $config = $integration->configuration;
        
        // Get properties to sync
        $query = Lot::query();
        
        if (!empty($options['status'])) {
            $query->where('status', $options['status']);
        }
        
        if (!empty($options['updated_since'])) {
            $query->where('updated_at', '>=', $options['updated_since']);
        }
        
        if (!empty($options['property_ids'])) {
            $query->whereIn('id', $options['property_ids']);
        }
        
        $properties = $query->get();
        
        // Sync each property
        $results = [
            'total' => $properties->count(),
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];
        
        foreach ($properties as $property) {
            try {
                $metadata = $property->metadata ?? [];
                $wordpressData = $metadata['wordpress'] ?? [];
                
                // Skip if not published to this site
                if (isset($wordpressData['integration_id']) && $wordpressData['integration_id'] !== $integration->id) {
                    $results['skipped']++;
                    continue;
                }
                
                // Create or update
                $result = $this->publishProperty($integration, $property->id);
                
                if (isset($wordpressData['external_id'])) {
                    $results['updated']++;
                } else {
                    $results['created']++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to sync property', [
                    'property_id' => $property->id,
                    'integration_id' => $integration->id,
                    'error' => $e->getMessage(),
                ]);
                
                $results['failed']++;
            }
        }
        
        // Handle deleted properties if requested
        if (!empty($options['sync_deleted']) && $options['sync_deleted']) {
            // TODO: Implement syncing deleted properties
        }
        
        return $results;
    }
    
    /**
     * Sync forms with WordPress
     */
    protected function syncForms(Integration $integration, array $options = []): array
    {
        // TODO: Implement form synchronization
        return [
            'status' => 'not_implemented',
            'message' => 'Form synchronization not implemented yet',
        ];
    }
    
    /**
     * Sync media with WordPress
     */
    protected function syncMedia(Integration $integration, array $options = []): array
    {
        // TODO: Implement media synchronization
        return [
            'status' => 'not_implemented',
            'message' => 'Media synchronization not implemented yet',
        ];
    }
}
```

### 4. PHP Fast Site Engine

The high-performance property listing display system:

```php
namespace App\Services\FastSite;

use App\Models\Lot;
use App\Models\FastSite;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class FastSiteService
{
    protected $tenantManager;
    
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Create a new Fast Site
     */
    public function createFastSite(array $data): FastSite
    {
        $tenant = $this->tenantManager->getTenant();
        
        // Validate subdomain
        if (!$this->isValidSubdomain($data['subdomain'])) {
            throw new \Exception('Invalid subdomain. Use only letters, numbers, and hyphens.');
        }
        
        // Check if subdomain is available
        if ($this->isSubdomainTaken($data['subdomain'])) {
            throw new \Exception('Subdomain is already taken.');
        }
        
        // Create fast site record
        $fastSite = new FastSite();
        $fastSite->tenant_id = $tenant->id;
        $fastSite->name = $data['name'];
        $fastSite->subdomain = $data['subdomain'];
        $fastSite->custom_domain = $data['custom_domain'] ?? null;
        $fastSite->theme = $data['theme'] ?? 'default';
        $fastSite->configuration = $data['configuration'] ?? [
            'colors' => [
                'primary' => '#3b82f6',
                'secondary' => '#10b981',
                'accent' => '#ef4444',
                'background' => '#ffffff',
                'text' => '#111827',
            ],
            'logo' => null,
            'contact_details' => [
                'phone' => $tenant->phone ?? null,
                'email' => $tenant->email ?? null,
                'address' => $tenant->address ?? null,
            ],
            'social_links' => [],
            'analytics_id' => null,
        ];
        $fastSite->status = 'setup';
        $fastSite->save();
        
        // Create site directory
        $this->createSiteDirectory($fastSite);
        
        return $fastSite;
    }
    
    /**
     * Update a Fast Site
     */
    public function updateFastSite(FastSite $fastSite, array $data): FastSite
    {
        // Update name if provided
        if (isset($data['name'])) {
            $fastSite->name = $data['name'];
        }
        
        // Update custom domain if provided
        if (isset($data['custom_domain'])) {
            $fastSite->custom_domain = $data['custom_domain'];
        }
        
        // Update theme if provided
        if (isset($data['theme'])) {
            $fastSite->theme = $data['theme'];
        }
        
        // Update configuration if provided
        if (isset($data['configuration'])) {
            $fastSite->configuration = array_merge($fastSite->configuration ?? [], $data['configuration']);
        }
        
        $fastSite->save();
        
        // Reset cache
        $this->clearSiteCache($fastSite);
        
        return $fastSite;
    }
    
    /**
     * Activate a Fast Site
     */
    public function activateFastSite(FastSite $fastSite): FastSite
    {
        // Generate site files
        $this->generateSiteFiles($fastSite);
        
        // Update status
        $fastSite->status = 'active';
        $fastSite->activated_at = now();
        $fastSite->save();
        
        return $fastSite;
    }
    
    /**
     * Deactivate a Fast Site
     */
    public function deactivateFastSite(FastSite $fastSite): FastSite
    {
        $fastSite->status = 'inactive';
        $fastSite->save();
        
        // Clear cache
        $this->clearSiteCache($fastSite);
        
        return $fastSite;
    }
    
    /**
     * Delete a Fast Site
     */
    public function deleteFastSite(FastSite $fastSite): bool
    {
        // Delete site directory
        $this->deleteSiteDirectory($fastSite);
        
        // Clear cache
        $this->clearSiteCache($fastSite);
        
        // Delete record
        return $fastSite->delete();
    }
    
    /**
     * Sync properties to a Fast Site
     */
    public function syncProperties(FastSite $fastSite, array $options = []): array
    {
        $tenant = $this->tenantManager->getTenant();
        
        // Get properties to sync
        $query = Lot::where('tenant_id', $tenant->id);
        
        if (!empty($options['status'])) {
            $query->where('status', $options['status']);
        }
        
        if (!empty($options['property_type'])) {
            $query->where('property_type', $options['property_type']);
        }
        
        $properties = $query->get();
        
        // Generate property data
        $propertiesData = [];
        
        foreach ($properties as $property) {
            $propertiesData[] = $this->preparePropertyData($property);
        }
        
        // Save to JSON file
        $siteDirectory = $this->getSiteDirectory($fastSite);
        Storage::put($siteDirectory . '/data/properties.json', json_encode($propertiesData));
        
        // Update last sync time
        $fastSite->last_sync_at = now();
        $fastSite->save();
        
        // Clear cache
        $this->clearSiteCache($fastSite);
        
        return [
            'total_properties' => count($propertiesData),
            'sync_time' => now()->toIso8601String(),
        ];
    }
    
    /**
     * Prepare property data for the Fast Site
     */
    protected function preparePropertyData(Lot $property): array
    {
        $property->load(['media', 'features']);
        
        return [
            'id' => $property->id,
            'title' => $property->title,
            'slug' => $property->slug,
            'description' => $property->description,
            'short_description' => $property->short_description,
            'price' => $property->price,
            'price_text' => $property->price_text,
            'status' => $property->status,
            'property_type' => $property->property_type,
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'car_spaces' => $property->car_spaces,
            'land_size' => $property->land_size,
            'building_size' => $property->building_size,
            'address' => [
                'street_number' => $property->street_number,
                'street_name' => $property->street_name,
                'suburb' => $property->suburb,
                'state' => $property->state,
                'postcode' => $property->postcode,
                'country' => $property->country,
                'display_address' => $property->display_address,
            ],
            'features' => $property->features->pluck('name')->toArray(),
            'media' => $property->media->map(function ($media) {
                return [
                    'url' => $media->url,
                    'type' => $media->media_type,
                    'is_primary' => $media->pivot->is_primary ?? false,
                    'title' => $media->title,
                    'order' => $media->pivot->order ?? 0,
                ];
            })->sortBy('order')->values()->toArray(),
            'created_at' => $property->created_at->toIso8601String(),
            'updated_at' => $property->updated_at->toIso8601String(),
        ];
    }
    
    /**
     * Generate site files
     */
    protected function generateSiteFiles(FastSite $fastSite): void
    {
        $siteDirectory = $this->getSiteDirectory($fastSite);
        
        // Create directories
        Storage::makeDirectory($siteDirectory . '/data');
        Storage::makeDirectory($siteDirectory . '/public');
        Storage::makeDirectory($siteDirectory . '/public/assets');
        
        // Generate index.php
        $indexContent = view('fastsites.templates.index', [
            'site' => $fastSite,
        ])->render();
        
        Storage::put($siteDirectory . '/public/index.php', $indexContent);
        
        // Generate .htaccess
        $htaccessContent = view('fastsites.templates.htaccess')->render();
        Storage::put($siteDirectory . '/public/.htaccess', $htaccessContent);
        
        // Generate config.php
        $configContent = view('fastsites.templates.config', [
            'site' => $fastSite,
        ])->render();
        
        Storage::put($siteDirectory . '/config.php', $configContent);
        
        // Copy theme assets
        $themeAssets = resource_path('fastsites/themes/' . $fastSite->theme . '/assets');
        
        if (is_dir($themeAssets)) {
            $files = scandir($themeAssets);
            
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $content = file_get_contents($themeAssets . '/' . $file);
                    Storage::put($siteDirectory . '/public/assets/' . $file, $content);
                }
            }
        }
        
        // Create empty properties.json
        Storage::put($siteDirectory . '/data/properties.json', '[]');
    }
    
    /**
     * Create site directory
     */
    protected function createSiteDirectory(FastSite $fastSite): void
    {
        $siteDirectory = $this->getSiteDirectory($fastSite);
        Storage::makeDirectory($siteDirectory);
    }
    
    /**
     * Delete site directory
     */
    protected function deleteSiteDirectory(FastSite $fastSite): void
    {
        $siteDirectory = $this->getSiteDirectory($fastSite);
        Storage::deleteDirectory($siteDirectory);
    }
    
    /**
     * Get site directory path
     */
    protected function getSiteDirectory(FastSite $fastSite): string
    {
        return 'fastsites/' . $fastSite->subdomain;
    }
    
    /**
     * Clear site cache
     */
    protected function clearSiteCache(FastSite $fastSite): void
    {
        Cache::forget('fastsite_' . $fastSite->id);
        Cache::forget('fastsite_subdomain_' . $fastSite->subdomain);
        
        if ($fastSite->custom_domain) {
            Cache::forget('fastsite_domain_' . $fastSite->custom_domain);
        }
    }
    
    /**
     * Check if subdomain is valid
     */
    protected function isValidSubdomain(string $subdomain): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9\-]{2,61}[a-z0-9]$/', $subdomain);
    }
    
    /**
     * Check if subdomain is taken
     */
    protected function isSubdomainTaken(string $subdomain): bool
    {
        return FastSite::where('subdomain', $subdomain)->exists();
    }
    
    /**
     * Get all Fast Sites for current tenant
     */
    public function getAllFastSites(): array
    {
        $tenant = $this->tenantManager->getTenant();
        
        return FastSite::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get()
            ->toArray();
    }
    
    /**
     * Get Fast Site by subdomain
     */
    public function getFastSiteBySubdomain(string $subdomain): ?FastSite
    {
        return FastSite::where('subdomain', $subdomain)->first();
    }
    
    /**
     * Get Fast Site by custom domain
     */
    public function getFastSiteByCustomDomain(string $domain): ?FastSite
    {
        return FastSite::where('custom_domain', $domain)->first();
    }
}
```

### 5. API Service

Core API management service:

```php
namespace App\Services\API;

use App\Models\ApiKey;
use App\Models\ApiLog;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Str;

class ApiService
{
    protected $tenantManager;
    
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Create a new API key
     */
    public function createApiKey(array $data): ApiKey
    {
        $tenant = $this->tenantManager->getTenant();
        
        // Generate API key and secret
        $key = Str::random(32);
        $secret = Str::random(64);
        
        // Create API key record
        $apiKey = new ApiKey();
        $apiKey->tenant_id = $tenant->id;
        $apiKey->name = $data['name'];
        $apiKey->key = $key;
        $apiKey->secret = bcrypt($secret);
        $apiKey->permissions = $data['permissions'] ?? ['read'];
        $apiKey->restrictions = $data['restrictions'] ?? [];
        $apiKey->expires_at = isset($data['expires_at']) ? now()->addDays($data['expires_at']) : null;
        $apiKey->created_by = auth()->id();
        $apiKey->save();
        
        // Return with plaintext secret (won't be accessible again)
        $apiKey->plain_secret = $secret;
        
        return $apiKey;
    }
    
    /**
     * Update an API key
     */
    public function updateApiKey(ApiKey $apiKey, array $data): ApiKey
    {
        // Update name if provided
        if (isset($data['name'])) {
            $apiKey->name = $data['name'];
        }
        
        // Update permissions if provided
        if (isset($data['permissions'])) {
            $apiKey->permissions = $data['permissions'];
        }
        
        // Update restrictions if provided
        if (isset($data['restrictions'])) {
            $apiKey->restrictions = $data['restrictions'];
        }
        
        // Update expiration if provided
        if (isset($data['expires_at'])) {
            $apiKey->expires_at = $data['expires_at'] ? now()->addDays($data['expires_at']) : null;
        }
        
        $apiKey->save();
        
        return $apiKey;
    }
    
    /**
     * Revoke an API key
     */
    public function revokeApiKey(ApiKey $apiKey): bool
    {
        return $apiKey->delete();
    }
    
    /**
     * Validate API key and secret
     */
    public function validateApiCredentials(string $key, string $secret): ?ApiKey
    {
        $apiKey = ApiKey::where('key', $key)->first();
        
        if (!$apiKey) {
            return null;
        }
        
        // Check if key is expired
        if ($apiKey->expires_at && $apiKey->expires_at < now()) {
            return null;
        }
        
        // Verify secret
        if (!password_verify($secret, $apiKey->secret)) {
            return null;
        }
        
        return $apiKey;
    }
    
    /**
     * Check if API key has permission
     */
    public function hasPermission(ApiKey $apiKey, string $permission): bool
    {
        return in_array('*', $apiKey->permissions) || in_array($permission, $apiKey->permissions);
    }
    
    /**
     * Check if API key has restriction
     */
    public function hasRestriction(ApiKey $apiKey, string $restrictionType, string $value): bool
    {
        if (!isset($apiKey->restrictions[$restrictionType])) {
            return false;
        }
        
        $restrictions = $apiKey->restrictions[$restrictionType];
        
        return in_array($value, $restrictions);
    }
    
    /**
     * Log API request
     */
    public function logRequest(
        ?ApiKey $apiKey,
        string $method,
        string $endpoint,
        array $params = [],
        int $responseCode = 200,
        array $responseData = [],
        ?string $error = null
    ): ApiLog {
        $log = new ApiLog();
        $log->tenant_id = $apiKey?->tenant_id ?? $this->tenantManager->getTenant()->id;
        $log->api_key_id = $apiKey?->id;
        $log->method = $method;
        $log->endpoint = $endpoint;
        $log->params = $params;
        $log->ip_address = request()->ip();
        $log->user_agent = request()->userAgent();
        $log->response_code = $responseCode;
        $log->response_data = $responseData;
        $log->error = $error;
        $log->save();
        
        return $log;
    }
    
    /**
     * Get API keys for current tenant
     */
    public function getApiKeys(): array
    {
        $tenant = $this->tenantManager->getTenant();
        
        return ApiKey::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
    
    /**
     * Get API request logs
     */
    public function getApiLogs(array $filters = [], int $perPage = 50): array
    {
        $tenant = $this->tenantManager->getTenant();
        
        $query = ApiLog::where('tenant_id', $tenant->id);
        
        // Apply filters
        if (!empty($filters['api_key_id'])) {
            $query->where('api_key_id', $filters['api_key_id']);
        }
        
        if (!empty($filters['method'])) {
            $query->where('method', $filters['method']);
        }
        
        if (!empty($filters['endpoint'])) {
            $query->where('endpoint', 'like', "%{$filters['endpoint']}%");
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'success') {
                $query->whereBetween('response_code', [200, 299]);
            } elseif ($filters['status'] === 'error') {
                $query->where('response_code', '>=', 400);
            }
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        // Paginate results
        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
        
        return [
            'total' => $logs->total(),
            'per_page' => $logs->perPage(),
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'data' => $logs->items(),
        ];
    }
}
```

## API Architecture

### 1. RESTful API Structure

The RESTful API follows standard REST conventions with appropriate HTTP methods:

```php
// routes/api.php
Route::middleware(['auth:api', 'tenant'])->prefix('api/v1')->group(function () {
    // Properties
    Route::apiResource('properties', 'Api\PropertyController');
    Route::get('properties/{property}/media', 'Api\PropertyController@media');
    Route::get('properties/{property}/features', 'Api\PropertyController@features');
    
    // Contacts
    Route::apiResource('contacts', 'Api\ContactController');
    Route::get('contacts/{contact}/interactions', 'Api\ContactController@interactions');
    Route::post('contacts/{contact}/interactions', 'Api\ContactController@storeInteraction');
    
    // Deals
    Route::apiResource('deals', 'Api\DealController');
    Route::post('deals/{deal}/stage', 'Api\DealController@changeStage');
    Route::get('deals/{deal}/activities', 'Api\DealController@activities');
    
    // Users
    Route::apiResource('users', 'Api\UserController');
    
    // Teams
    Route::apiResource('teams', 'Api\TeamController');
    
    // Reports
    Route::get('reports', 'Api\ReportController@index');
    Route::post('reports/execute/{report}', 'Api\ReportController@execute');
    
    // Dashboard
    Route::get('dashboard/summary', 'Api\DashboardController@summary');
    Route::get('dashboard/performance', 'Api\DashboardController@performance');
    
    // Me (Current User)
    Route::get('me', 'Api\MeController@show');
    Route::put('me', 'Api\MeController@update');
    Route::get('me/permissions', 'Api\MeController@permissions');
});
```

### 2. GraphQL Schema

GraphQL provides a more flexible query option:

```php
namespace App\GraphQL\Schemas;

use App\GraphQL\Types;
use App\GraphQL\Queries;
use App\GraphQL\Mutations;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;

class FusionSchema
{
    /**
     * Create the GraphQL schema
     */
    public static function create(): Schema
    {
        // Define query type
        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                // Properties
                'property' => Queries\PropertyQuery::field(),
                'properties' => Queries\PropertiesQuery::field(),
                
                // Contacts
                'contact' => Queries\ContactQuery::field(),
                'contacts' => Queries\ContactsQuery::field(),
                
                // Deals
                'deal' => Queries\DealQuery::field(),
                'deals' => Queries\DealsQuery::field(),
                
                // Users
                'user' => Queries\UserQuery::field(),
                'users' => Queries\UsersQuery::field(),
                
                // Teams
                'team' => Queries\TeamQuery::field(),
                'teams' => Queries\TeamsQuery::field(),
                
                // Dashboard
                'dashboardSummary' => Queries\DashboardSummaryQuery::field(),
                
                // Me
                'me' => Queries\MeQuery::field(),
            ],
        ]);
        
        // Define mutation type
        $mutationType = new ObjectType([
            'name' => 'Mutation',
            'fields' => [
                // Properties
                'createProperty' => Mutations\CreatePropertyMutation::field(),
                'updateProperty' => Mutations\UpdatePropertyMutation::field(),
                'deleteProperty' => Mutations\DeletePropertyMutation::field(),
                
                // Contacts
                'createContact' => Mutations\CreateContactMutation::field(),
                'updateContact' => Mutations\UpdateContactMutation::field(),
                'deleteContact' => Mutations\DeleteContactMutation::field(),
                'addContactInteraction' => Mutations\AddContactInteractionMutation::field(),
                
                // Deals
                'createDeal' => Mutations\CreateDealMutation::field(),
                'updateDeal' => Mutations\UpdateDealMutation::field(),
                'deleteDeal' => Mutations\DeleteDealMutation::field(),
                'changeDealStage' => Mutations\ChangeDealStageMutation::field(),
                
                // Users
                'updateMe' => Mutations\UpdateMeMutation::field(),
            ],
        ]);
        
        // Create schema
        return new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
        ]);
    }
}
```

### 3. API Authentication Middleware

```php
namespace App\Http\Middleware;

use App\Services\API\ApiService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthentication
{
    protected $apiService;
    
    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get API credentials from request
        $apiKey = $request->header('X-API-Key');
        $apiSecret = $request->header('X-API-Secret');
        
        // Check if credentials are provided
        if (!$apiKey || !$apiSecret) {
            return response()->json([
                'error' => 'Missing API credentials',
                'message' => 'API key and secret are required',
            ], 401);
        }
        
        // Validate credentials
        $apiKeyModel = $this->apiService->validateApiCredentials($apiKey, $apiSecret);
        
        if (!$apiKeyModel) {
            return response()->json([
                'error' => 'Invalid API credentials',
                'message' => 'The provided API key or secret is invalid',
            ], 401);
        }
        
        // Check permissions for the current endpoint
        $endpoint = $request->route()->uri();
        $method = strtolower($request->method());
        
        // Determine required permission
        $permission = $this->getRequiredPermission($method);
        
        if (!$this->apiService->hasPermission($apiKeyModel, $permission)) {
            return response()->json([
                'error' => 'Insufficient permissions',
                'message' => "The API key doesn't have the required permission: {$permission}",
            ], 403);
        }
        
        // Check for IP restrictions
        $ipAddress = $request->ip();
        
        if ($this->apiService->hasRestriction($apiKeyModel, 'ips', '*') && 
            !$this->apiService->hasRestriction($apiKeyModel, 'ips', $ipAddress)) {
            return response()->json([
                'error' => 'IP restriction',
                'message' => 'Your IP address is not allowed to use this API key',
            ], 403);
        }
        
        // Store API key in request for controllers to use
        $request->attributes->set('api_key', $apiKeyModel);
        
        // Log API request after response is generated
        $response = $next($request);
        
        $this->apiService->logRequest(
            $apiKeyModel,
            $request->method(),
            $endpoint,
            $request->all(),
            $response->getStatusCode(),
            $this->getResponseData($response),
            $response->getStatusCode() >= 400 ? $this->getErrorMessage($response) : null
        );
        
        return $response;
    }
    
    /**
     * Get required permission based on HTTP method
     */
    protected function getRequiredPermission(string $method): string
    {
        return match ($method) {
            'get' => 'read',
            'post' => 'create',
            'put', 'patch' => 'update',
            'delete' => 'delete',
            default => 'read',
        };
    }
    
    /**
     * Extract response data for logging
     */
    protected function getResponseData(Response $response): array
    {
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $content = $response->getContent();
            $data = json_decode($content, true);
            
            // Limit data size for logging
            if (strlen($content) > 1000) {
                return [
                    'truncated' => true,
                    'summary' => array_slice($data, 0, 5),
                ];
            }
            
            return $data;
        }
        
        return [];
    }
    
    /**
     * Extract error message from response
     */
    protected function getErrorMessage(Response $response): ?string
    {
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = json_decode($response->getContent(), true);
            
            return $data['message'] ?? $data['error'] ?? null;
        }
        
        return null;
    }
}
```

## Integration Database Schema

```sql
-- Integrations
CREATE TABLE integrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    configuration JSON NOT NULL,
    metadata JSON NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'inactive',
    last_connected_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Integration logs
CREATE TABLE integration_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    integration_id BIGINT UNSIGNED NOT NULL,
    event VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE CASCADE
);

-- Fast Sites
CREATE TABLE fast_sites (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    subdomain VARCHAR(63) NOT NULL,
    custom_domain VARCHAR(255) NULL,
    theme VARCHAR(50) NOT NULL DEFAULT 'default',
    configuration JSON NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'setup',
    activated_at TIMESTAMP NULL,
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE INDEX subdomain (subdomain),
    UNIQUE INDEX custom_domain (custom_domain)
);

-- API keys
CREATE TABLE api_keys (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    key VARCHAR(64) NOT NULL,
    secret VARCHAR(255) NOT NULL,
    permissions JSON NOT NULL,
    restrictions JSON NULL,
    expires_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE INDEX key_index (key)
);

-- API logs
CREATE TABLE api_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    api_key_id BIGINT UNSIGNED NULL,
    method VARCHAR(10) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    params JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    response_code INT NOT NULL,
    response_data JSON NULL,
    error TEXT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE SET NULL
);
```

## Zapier & Make Integration

Fusion CRM V4 provides special endpoints for workflow automation platforms:

```php
// routes/api.php
Route::middleware(['api.webhook'])->prefix('api/v1/webhook')->group(function () {
    // Zapier triggers
    Route::get('triggers/new-contact', 'Api\Webhooks\ZapierController@newContact');
    Route::get('triggers/updated-contact', 'Api\Webhooks\ZapierController@updatedContact');
    Route::get('triggers/new-deal', 'Api\Webhooks\ZapierController@newDeal');
    Route::get('triggers/deal-stage-changed', 'Api\Webhooks\ZapierController@dealStageChanged');
    Route::get('triggers/new-property', 'Api\Webhooks\ZapierController@newProperty');
    
    // Zapier actions
    Route::post('actions/create-contact', 'Api\Webhooks\ZapierController@createContact');
    Route::post('actions/update-contact', 'Api\Webhooks\ZapierController@updateContact');
    Route::post('actions/create-deal', 'Api\Webhooks\ZapierController@createDeal');
    Route::post('actions/change-deal-stage', 'Api\Webhooks\ZapierController@changeDealStage');
    
    // Make.com (Integromat) specific endpoints
    Route::post('make/webhook', 'Api\Webhooks\MakeController@handleWebhook');
});
```

## Implementation Strategy

### Phase 1: Core Integration Framework

1. **Integration Hub**
   - Basic integration management
   - WordPress provider implementation
   - Integration logging

2. **Fast Site Engine**
   - Site creation and management
   - Property data synchronization
   - Theme implementation

### Phase 2: API Implementation

3. **REST API**
   - Authentication and authorization
   - Resource endpoints
   - Documentation generation

4. **GraphQL API**
   - Schema definition
   - Query and mutation implementation
   - Authentication integration

### Phase 3: External Integrations

5. **Zapier & Make Integration**
   - Trigger endpoints
   - Action endpoints
   - Custom app definition

6. **Developer Portal**
   - API key management
   - Documentation interface
   - Playground environment

## Security Considerations

1. **API Authentication**
   - API key and secret validation
   - Permission-based access control
   - Rate limiting for all endpoints

2. **Data Validation**
   - Input validation for all API endpoints
   - Output sanitization to prevent data leakage
   - Secure storage of integration credentials

3. **Cross-Site Protection**
   - CORS configuration for API endpoints
   - CSP headers for Fast Sites
   - Protection against common web vulnerabilities

## Conclusion

The Website & API Integration architecture in Fusion CRM V4 provides:

1. **Seamless Website Integration**: WordPress and Fast Site options for property display
2. **Flexible API Access**: Both REST and GraphQL APIs for maximum compatibility
3. **Secure Authentication**: Robust API key system with granular permissions
4. **Developer Experience**: Comprehensive documentation and testing tools
5. **Workflow Automation**: Built-in support for Zapier and Make platform integration

This architecture enables Fusion CRM V4 to serve as a central hub for all real estate operations, with secure and efficient data exchange with external systems. 