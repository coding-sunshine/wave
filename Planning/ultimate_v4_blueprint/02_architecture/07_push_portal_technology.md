# Fusion CRM V4 - Push Portal Technology Architecture

This document outlines the architecture for the Push Portal Technology in Fusion CRM V4, which enables properties to be published to multiple channels from a single source.

## Overview

Fusion CRM V4 implements a comprehensive multi-channel publishing system that provides:

1. **Centralized Property Management**: Single source of truth for all property data
2. **Multi-Channel Publishing**: Push to websites, portals, and external MLS systems
3. **Content Optimization**: Format content appropriately for each channel
4. **Synchronization**: Keep all channels updated with the latest information
5. **Publishing Control**: Fine-grained control over what is published where

## Core Components

### 1. Property Publishing Service

The central service responsible for managing publishing across channels:

```php
namespace App\Services\Publishing;

use App\Models\Lot;
use App\Models\PublishingChannel;
use App\Models\PublishingHistory;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Log;

class PropertyPublishingService
{
    protected $tenantManager;
    protected $channelManagers = [];
    
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Register a channel manager
     */
    public function registerChannelManager(string $channelType, ChannelManagerInterface $manager): void
    {
        $this->channelManagers[$channelType] = $manager;
    }
    
    /**
     * Get channel manager for channel type
     */
    public function getChannelManager(string $channelType): ChannelManagerInterface
    {
        if (!isset($this->channelManagers[$channelType])) {
            throw new \Exception("No channel manager registered for '{$channelType}'");
        }
        
        return $this->channelManagers[$channelType];
    }
    
    /**
     * Publish property to a specific channel
     */
    public function publishToChannel(Lot $property, PublishingChannel $channel, array $options = []): PublishingHistory
    {
        $manager = $this->getChannelManager($channel->channel_type);
        
        try {
            // Validate property data for this channel
            $validationResult = $manager->validateProperty($property, $channel);
            
            if (!$validationResult['valid']) {
                throw new \Exception("Property validation failed: " . implode(', ', $validationResult['errors']));
            }
            
            // Publish property
            $result = $manager->publish($property, $channel, $options);
            
            // Create publishing history record
            $history = new PublishingHistory();
            $history->tenant_id = $this->tenantManager->getTenant()->id;
            $history->lot_id = $property->id;
            $history->channel_id = $channel->id;
            $history->status = 'success';
            $history->external_id = $result['external_id'] ?? null;
            $history->external_url = $result['external_url'] ?? null;
            $history->published_data = $result['published_data'] ?? null;
            $history->notes = $result['notes'] ?? null;
            $history->save();
            
            // Update property publishing data
            $publishingData = $property->publishing_data ?? [];
            $publishingData[$channel->id] = [
                'status' => 'published',
                'published_at' => now()->toIso8601String(),
                'external_id' => $result['external_id'] ?? null,
                'external_url' => $result['external_url'] ?? null
            ];
            
            $property->publishing_data = $publishingData;
            $property->save();
            
            return $history;
        } catch (\Exception $e) {
            // Log error
            Log::error('Property publishing failed', [
                'property_id' => $property->id,
                'channel_id' => $channel->id,
                'error' => $e->getMessage()
            ]);
            
            // Create failed publishing history record
            $history = new PublishingHistory();
            $history->tenant_id = $this->tenantManager->getTenant()->id;
            $history->lot_id = $property->id;
            $history->channel_id = $channel->id;
            $history->status = 'failed';
            $history->notes = $e->getMessage();
            $history->save();
            
            throw $e;
        }
    }
    
    /**
     * Publish property to multiple channels
     */
    public function publishToChannels(Lot $property, array $channelIds, array $options = []): array
    {
        $results = [];
        
        foreach ($channelIds as $channelId) {
            try {
                $channel = PublishingChannel::findOrFail($channelId);
                $results[$channelId] = $this->publishToChannel($property, $channel, $options);
            } catch (\Exception $e) {
                $results[$channelId] = [
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Unpublish property from a channel
     */
    public function unpublishFromChannel(Lot $property, PublishingChannel $channel): PublishingHistory
    {
        $manager = $this->getChannelManager($channel->channel_type);
        
        try {
            // Check if property is published to this channel
            $publishingData = $property->publishing_data ?? [];
            
            if (!isset($publishingData[$channel->id])) {
                throw new \Exception("Property is not published to this channel");
            }
            
            // Unpublish property
            $result = $manager->unpublish($property, $channel);
            
            // Create publishing history record
            $history = new PublishingHistory();
            $history->tenant_id = $this->tenantManager->getTenant()->id;
            $history->lot_id = $property->id;
            $history->channel_id = $channel->id;
            $history->status = 'unpublished';
            $history->notes = $result['notes'] ?? 'Successfully unpublished';
            $history->save();
            
            // Update property publishing data
            unset($publishingData[$channel->id]);
            $property->publishing_data = $publishingData;
            $property->save();
            
            return $history;
        } catch (\Exception $e) {
            // Log error
            Log::error('Property unpublishing failed', [
                'property_id' => $property->id,
                'channel_id' => $channel->id,
                'error' => $e->getMessage()
            ]);
            
            // Create failed publishing history record
            $history = new PublishingHistory();
            $history->tenant_id = $this->tenantManager->getTenant()->id;
            $history->lot_id = $property->id;
            $history->channel_id = $channel->id;
            $history->status = 'unpublish_failed';
            $history->notes = $e->getMessage();
            $history->save();
            
            throw $e;
        }
    }
    
    /**
     * Update property in a channel
     */
    public function updateInChannel(Lot $property, PublishingChannel $channel): PublishingHistory
    {
        $manager = $this->getChannelManager($channel->channel_type);
        
        try {
            // Check if property is published to this channel
            $publishingData = $property->publishing_data ?? [];
            
            if (!isset($publishingData[$channel->id])) {
                throw new \Exception("Property is not published to this channel");
            }
            
            // Validate property data for this channel
            $validationResult = $manager->validateProperty($property, $channel);
            
            if (!$validationResult['valid']) {
                throw new \Exception("Property validation failed: " . implode(', ', $validationResult['errors']));
            }
            
            // Update property
            $result = $manager->update($property, $channel);
            
            // Create publishing history record
            $history = new PublishingHistory();
            $history->tenant_id = $this->tenantManager->getTenant()->id;
            $history->lot_id = $property->id;
            $history->channel_id = $channel->id;
            $history->status = 'updated';
            $history->external_id = $result['external_id'] ?? null;
            $history->external_url = $result['external_url'] ?? null;
            $history->published_data = $result['published_data'] ?? null;
            $history->notes = $result['notes'] ?? null;
            $history->save();
            
            // Update property publishing data
            $publishingData[$channel->id] = [
                'status' => 'published',
                'published_at' => $publishingData[$channel->id]['published_at'],
                'updated_at' => now()->toIso8601String(),
                'external_id' => $result['external_id'] ?? $publishingData[$channel->id]['external_id'] ?? null,
                'external_url' => $result['external_url'] ?? $publishingData[$channel->id]['external_url'] ?? null
            ];
            
            $property->publishing_data = $publishingData;
            $property->save();
            
            return $history;
        } catch (\Exception $e) {
            // Log error
            Log::error('Property update failed', [
                'property_id' => $property->id,
                'channel_id' => $channel->id,
                'error' => $e->getMessage()
            ]);
            
            // Create failed publishing history record
            $history = new PublishingHistory();
            $history->tenant_id = $this->tenantManager->getTenant()->id;
            $history->lot_id = $property->id;
            $history->channel_id = $channel->id;
            $history->status = 'update_failed';
            $history->notes = $e->getMessage();
            $history->save();
            
            throw $e;
        }
    }
}
```

### 2. Channel Manager Interface

Common interface for all channel managers:

```php
namespace App\Services\Publishing;

use App\Models\Lot;
use App\Models\PublishingChannel;

interface ChannelManagerInterface
{
    /**
     * Validate a property for publishing to this channel
     */
    public function validateProperty(Lot $property, PublishingChannel $channel): array;
    
    /**
     * Publish a property to this channel
     */
    public function publish(Lot $property, PublishingChannel $channel, array $options = []): array;
    
    /**
     * Update a property in this channel
     */
    public function update(Lot $property, PublishingChannel $channel): array;
    
    /**
     * Unpublish a property from this channel
     */
    public function unpublish(Lot $property, PublishingChannel $channel): array;
}
```

### 3. Website Channel Manager

Implementation for publishing to PHP/WordPress websites:

```php
namespace App\Services\Publishing\Channels;

use App\Models\Lot;
use App\Models\PublishingChannel;
use App\Services\Publishing\ChannelManagerInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebsiteChannelManager implements ChannelManagerInterface
{
    /**
     * Validate a property for publishing to this channel
     */
    public function validateProperty(Lot $property, PublishingChannel $channel): array
    {
        $errors = [];
        
        // Validate required fields
        if (empty($property->title)) {
            $errors[] = 'Property title is required';
        }
        
        if (empty($property->description)) {
            $errors[] = 'Property description is required';
        }
        
        if (empty($property->price) && empty($property->price_text)) {
            $errors[] = 'Property price or price text is required';
        }
        
        // Validate media
        if ($property->media->isEmpty()) {
            $errors[] = 'At least one image is required';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Publish a property to this channel
     */
    public function publish(Lot $property, PublishingChannel $channel, array $options = []): array
    {
        // Prepare property data
        $data = $this->preparePropertyData($property, $channel);
        
        // Get endpoint URL from channel configuration
        $config = $channel->configuration;
        $apiUrl = $config['api_url'];
        $apiKey = $config['api_key'];
        
        // Post to website API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($apiUrl . '/properties', $data);
        
        if (!$response->successful()) {
            Log::error('Website publishing failed', [
                'property_id' => $property->id,
                'response' => $response->body()
            ]);
            
            throw new \Exception('Failed to publish to website: ' . $response->body());
        }
        
        $result = $response->json();
        
        return [
            'status' => 'success',
            'external_id' => $result['id'] ?? null,
            'external_url' => $result['url'] ?? null,
            'published_data' => $data,
            'notes' => 'Successfully published to website'
        ];
    }
    
    /**
     * Update a property in this channel
     */
    public function update(Lot $property, PublishingChannel $channel): array
    {
        // Prepare property data
        $data = $this->preparePropertyData($property, $channel);
        
        // Get endpoint URL from channel configuration
        $config = $channel->configuration;
        $apiUrl = $config['api_url'];
        $apiKey = $config['api_key'];
        
        // Get external ID from property publishing data
        $publishingData = $property->publishing_data ?? [];
        $externalId = $publishingData[$channel->id]['external_id'] ?? null;
        
        if (!$externalId) {
            throw new \Exception('External ID not found for this property');
        }
        
        // Put to website API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->put($apiUrl . '/properties/' . $externalId, $data);
        
        if (!$response->successful()) {
            Log::error('Website update failed', [
                'property_id' => $property->id,
                'response' => $response->body()
            ]);
            
            throw new \Exception('Failed to update on website: ' . $response->body());
        }
        
        $result = $response->json();
        
        return [
            'status' => 'success',
            'external_id' => $result['id'] ?? $externalId,
            'external_url' => $result['url'] ?? null,
            'published_data' => $data,
            'notes' => 'Successfully updated on website'
        ];
    }
    
    /**
     * Unpublish a property from this channel
     */
    public function unpublish(Lot $property, PublishingChannel $channel): array
    {
        // Get endpoint URL from channel configuration
        $config = $channel->configuration;
        $apiUrl = $config['api_url'];
        $apiKey = $config['api_key'];
        
        // Get external ID from property publishing data
        $publishingData = $property->publishing_data ?? [];
        $externalId = $publishingData[$channel->id]['external_id'] ?? null;
        
        if (!$externalId) {
            throw new \Exception('External ID not found for this property');
        }
        
        // Delete from website API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->delete($apiUrl . '/properties/' . $externalId);
        
        if (!$response->successful()) {
            Log::error('Website unpublish failed', [
                'property_id' => $property->id,
                'response' => $response->body()
            ]);
            
            throw new \Exception('Failed to unpublish from website: ' . $response->body());
        }
        
        return [
            'status' => 'success',
            'notes' => 'Successfully unpublished from website'
        ];
    }
    
    /**
     * Prepare property data for this channel
     */
    protected function preparePropertyData(Lot $property, PublishingChannel $channel): array
    {
        // Load property relationships
        $property->load(['media', 'features', 'project']);
        
        // Prepare media URLs
        $mediaUrls = $property->media->map(function ($media) {
            return [
                'url' => $media->url,
                'type' => $media->media_type,
                'is_primary' => $media->pivot->is_primary,
                'title' => $media->title,
                'order' => $media->pivot->order
            ];
        })->toArray();
        
        // Get property key features
        $features = $property->features->pluck('name')->toArray();
        
        // Base data
        $data = [
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
                'display_address' => $property->display_address
            ],
            'features' => $features,
            'media' => $mediaUrls,
            'meta' => [
                'seo_title' => $property->seo_title ?? $property->title,
                'seo_description' => $property->seo_description ?? substr(strip_tags($property->description), 0, 160),
                'seo_keywords' => $property->seo_keywords
            ]
        ];
        
        // Add project data if available
        if ($property->project) {
            $data['project'] = [
                'id' => $property->project->id,
                'name' => $property->project->name,
                'slug' => $property->project->slug
            ];
        }
        
        // Add custom fields
        if ($property->custom_fields) {
            $data['custom_fields'] = $property->custom_fields;
        }
        
        return $data;
    }
}
```

### 4. Real Estate Portal Channel Manager

Integration with external real estate portals like REA and Domain:

```php
namespace App\Services\Publishing\Channels;

use App\Models\Lot;
use App\Models\PublishingChannel;
use App\Services\Publishing\ChannelManagerInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RealEstatePortalManager implements ChannelManagerInterface
{
    /**
     * Validate a property for publishing to this channel
     */
    public function validateProperty(Lot $property, PublishingChannel $channel): array
    {
        $errors = [];
        $config = $channel->configuration;
        $portalType = $config['portal_type'] ?? 'unknown';
        
        // Common validations
        if (empty($property->title)) {
            $errors[] = 'Property title is required';
        }
        
        if (empty($property->description)) {
            $errors[] = 'Property description is required';
        }
        
        if (empty($property->price) && empty($property->price_text)) {
            $errors[] = 'Property price or price text is required';
        }
        
        if ($property->media->isEmpty()) {
            $errors[] = 'At least one image is required';
        }
        
        // Portal-specific validations
        if ($portalType === 'rea') {
            // REA-specific validations
            if (empty($property->street_number) || empty($property->street_name)) {
                $errors[] = 'Street number and name are required for REA';
            }
            
            if (empty($property->property_type)) {
                $errors[] = 'Property type is required for REA';
            }
        } elseif ($portalType === 'domain') {
            // Domain-specific validations
            if (empty($property->suburb) || empty($property->state) || empty($property->postcode)) {
                $errors[] = 'Full address (suburb, state, postcode) is required for Domain';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Publish a property to this channel
     */
    public function publish(Lot $property, PublishingChannel $channel, array $options = []): array
    {
        $config = $channel->configuration;
        $portalType = $config['portal_type'] ?? 'unknown';
        
        // Prepare property data based on portal type
        $method = 'prepare' . ucfirst($portalType) . 'Data';
        
        if (method_exists($this, $method)) {
            $data = $this->$method($property, $channel);
        } else {
            throw new \Exception("Unsupported portal type: {$portalType}");
        }
        
        // Get API credentials from channel config
        $apiUrl = $config['api_url'];
        $apiKey = $config['api_key'];
        $apiClientId = $config['api_client_id'] ?? null;
        
        // Post to portal API
        $response = Http::withHeaders($this->getApiHeaders($portalType, $apiKey, $apiClientId))
            ->post($apiUrl . '/listings', $data);
        
        if (!$response->successful()) {
            Log::error("Portal publishing failed ({$portalType})", [
                'property_id' => $property->id,
                'response' => $response->body()
            ]);
            
            throw new \Exception("Failed to publish to {$portalType}: " . $response->body());
        }
        
        $result = $response->json();
        
        return [
            'status' => 'success',
            'external_id' => $result['id'] ?? $result['listing_id'] ?? null,
            'external_url' => $result['url'] ?? null,
            'published_data' => $data,
            'notes' => "Successfully published to {$portalType}"
        ];
    }
    
    /**
     * Update a property in this channel
     */
    public function update(Lot $property, PublishingChannel $channel): array
    {
        $config = $channel->configuration;
        $portalType = $config['portal_type'] ?? 'unknown';
        
        // Prepare property data based on portal type
        $method = 'prepare' . ucfirst($portalType) . 'Data';
        
        if (method_exists($this, $method)) {
            $data = $this->$method($property, $channel);
        } else {
            throw new \Exception("Unsupported portal type: {$portalType}");
        }
        
        // Get API credentials from channel config
        $apiUrl = $config['api_url'];
        $apiKey = $config['api_key'];
        $apiClientId = $config['api_client_id'] ?? null;
        
        // Get external ID from property publishing data
        $publishingData = $property->publishing_data ?? [];
        $externalId = $publishingData[$channel->id]['external_id'] ?? null;
        
        if (!$externalId) {
            throw new \Exception('External ID not found for this property');
        }
        
        // Put to portal API
        $response = Http::withHeaders($this->getApiHeaders($portalType, $apiKey, $apiClientId))
            ->put($apiUrl . '/listings/' . $externalId, $data);
        
        if (!$response->successful()) {
            Log::error("Portal update failed ({$portalType})", [
                'property_id' => $property->id,
                'response' => $response->body()
            ]);
            
            throw new \Exception("Failed to update on {$portalType}: " . $response->body());
        }
        
        $result = $response->json();
        
        return [
            'status' => 'success',
            'external_id' => $result['id'] ?? $result['listing_id'] ?? $externalId,
            'external_url' => $result['url'] ?? null,
            'published_data' => $data,
            'notes' => "Successfully updated on {$portalType}"
        ];
    }
    
    /**
     * Unpublish a property from this channel
     */
    public function unpublish(Lot $property, PublishingChannel $channel): array
    {
        $config = $channel->configuration;
        $portalType = $config['portal_type'] ?? 'unknown';
        
        // Get API credentials from channel config
        $apiUrl = $config['api_url'];
        $apiKey = $config['api_key'];
        $apiClientId = $config['api_client_id'] ?? null;
        
        // Get external ID from property publishing data
        $publishingData = $property->publishing_data ?? [];
        $externalId = $publishingData[$channel->id]['external_id'] ?? null;
        
        if (!$externalId) {
            throw new \Exception('External ID not found for this property');
        }
        
        // Delete from portal API
        $response = Http::withHeaders($this->getApiHeaders($portalType, $apiKey, $apiClientId))
            ->delete($apiUrl . '/listings/' . $externalId);
        
        if (!$response->successful()) {
            Log::error("Portal unpublish failed ({$portalType})", [
                'property_id' => $property->id,
                'response' => $response->body()
            ]);
            
            throw new \Exception("Failed to unpublish from {$portalType}: " . $response->body());
        }
        
        return [
            'status' => 'success',
            'notes' => "Successfully unpublished from {$portalType}"
        ];
    }
    
    /**
     * Get API headers for a specific portal
     */
    protected function getApiHeaders(string $portalType, string $apiKey, ?string $apiClientId = null): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];
        
        if ($portalType === 'rea') {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
            if ($apiClientId) {
                $headers['X-Client-ID'] = $apiClientId;
            }
        } elseif ($portalType === 'domain') {
            $headers['X-API-Key'] = $apiKey;
            if ($apiClientId) {
                $headers['X-Client-ID'] = $apiClientId;
            }
        } else {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }
        
        return $headers;
    }
    
    /**
     * Prepare property data for REA
     */
    protected function prepareReaData(Lot $property, PublishingChannel $channel): array
    {
        // Load property relationships
        $property->load(['media', 'features', 'project']);
        
        // Prepare media URLs
        $images = $property->media
            ->where('media_type', 'image')
            ->sortBy('pivot.order')
            ->map(function ($media) {
                return [
                    'url' => $media->url,
                    'title' => $media->title
                ];
            })->values()->toArray();
        
        // Get property features
        $features = $property->features->pluck('name')->toArray();
        
        // Map property type to REA format
        $reaPropertyType = $this->mapPropertyTypeToRea($property->property_type);
        
        // REA-specific data structure
        $data = [
            'listingType' => $property->listing_type === 'sale' ? 'SALE' : 'RENT',
            'propertyType' => $reaPropertyType,
            'headline' => $property->title,
            'description' => $property->description,
            'addressComponents' => [
                'streetNumber' => $property->street_number,
                'street' => $property->street_name,
                'suburb' => $property->suburb,
                'state' => $property->state,
                'postcode' => $property->postcode,
                'displayOption' => $property->display_address ? 'FULL' : 'SUBURB_ONLY'
            ],
            'features' => [
                'bedrooms' => $property->bedrooms,
                'bathrooms' => $property->bathrooms,
                'parkingSpaces' => $property->car_spaces,
                'landArea' => [
                    'value' => $property->land_size,
                    'unit' => 'SQM'
                ]
            ],
            'images' => $images,
            'generalFeatures' => $features,
            'priceDetails' => [
                'displayPrice' => $property->price_text ?: ('$' . number_format($property->price))
            ]
        ];
        
        // Add project reference if applicable
        if ($property->project) {
            $data['projectReference'] = $property->project->external_id;
        }
        
        return $data;
    }
    
    /**
     * Prepare property data for Domain
     */
    protected function prepareDomainData(Lot $property, PublishingChannel $channel): array
    {
        // Load property relationships
        $property->load(['media', 'features', 'project']);
        
        // Prepare media URLs
        $images = $property->media
            ->where('media_type', 'image')
            ->sortBy('pivot.order')
            ->map(function ($media) {
                return [
                    'url' => $media->url,
                    'name' => $media->title
                ];
            })->values()->toArray();
        
        // Map property type to Domain format
        $domainPropertyType = $this->mapPropertyTypeToDomain($property->property_type);
        
        // Domain-specific data structure
        $data = [
            'listingType' => $property->listing_type === 'sale' ? 'Sale' : 'Rent',
            'propertyTypes' => [$domainPropertyType],
            'headline' => $property->title,
            'description' => $property->description,
            'address' => [
                'streetNumber' => $property->street_number,
                'streetName' => $property->street_name,
                'suburb' => $property->suburb,
                'state' => $property->state,
                'postcode' => $property->postcode,
                'displayOption' => $property->display_address ? 'Full' : 'SuburbOnly'
            ],
            'features' => [
                'bedrooms' => $property->bedrooms,
                'bathrooms' => $property->bathrooms,
                'carSpaces' => $property->car_spaces,
                'landArea' => $property->land_size
            ],
            'media' => [
                'images' => $images
            ],
            'price' => $property->price_text ?: ('$' . number_format($property->price))
        ];
        
        // Add project reference if applicable
        if ($property->project) {
            $data['projectId'] = $property->project->external_id;
        }
        
        return $data;
    }
    
    /**
     * Map property types to REA format
     */
    protected function mapPropertyTypeToRea(string $propertyType): string
    {
        $mapping = [
            'house' => 'HOUSE',
            'apartment' => 'APARTMENT_UNIT_FLAT',
            'unit' => 'APARTMENT_UNIT_FLAT',
            'land' => 'VACANT_LAND',
            'townhouse' => 'TOWNHOUSE',
            'villa' => 'VILLA',
            'acreage' => 'ACREAGE_RURAL',
            'rural' => 'ACREAGE_RURAL',
            'commercial' => 'COMMERCIAL'
        ];
        
        return $mapping[$propertyType] ?? 'HOUSE';
    }
    
    /**
     * Map property types to Domain format
     */
    protected function mapPropertyTypeToDomain(string $propertyType): string
    {
        $mapping = [
            'house' => 'House',
            'apartment' => 'ApartmentUnitFlat',
            'unit' => 'ApartmentUnitFlat',
            'land' => 'VacantLand',
            'townhouse' => 'Townhouse',
            'villa' => 'Villa',
            'acreage' => 'Rural',
            'rural' => 'Rural',
            'commercial' => 'Commercial'
        ];
        
        return $mapping[$propertyType] ?? 'House';
    }
}
```

## Publishing Database Schema

```sql
CREATE TABLE publishing_channels (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    channel_type VARCHAR(50) NOT NULL,
    description TEXT NULL,
    configuration JSON NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE publishing_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    channel_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL,
    external_id VARCHAR(255) NULL,
    external_url VARCHAR(255) NULL,
    published_data JSON NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (lot_id) REFERENCES lots(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES publishing_channels(id) ON DELETE CASCADE
);

CREATE TABLE scheduled_publications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    channel_id BIGINT UNSIGNED NOT NULL,
    scheduled_at TIMESTAMP NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    options JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (lot_id) REFERENCES lots(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES publishing_channels(id) ON DELETE CASCADE
);
```

## Implementation Strategy

### Phase 1: Core Publishing Framework

1. **Channel Management**
   - Implement base framework for managing publishing channels
   - Create channel configuration UI
   - Develop tenant-specific channel settings

2. **Website Publishing**
   - PHP Fast Site integration
   - WordPress integration
   - Basic image and content formatting

### Phase 2: Portal Integration

3. **Major Portal Support**
   - REA integration
   - Domain integration
   - Agent ID and account management

4. **Media Optimization**
   - Image resizing and optimization for each channel
   - Floor plan conversion and enhancement
   - Video processing and embedding

### Phase 3: Advanced Features

5. **Scheduling & Automation**
   - Scheduled publishing and unpublishing
   - Automatic updates when property changes
   - Bulk publishing operations

6. **Analytics & Reporting**
   - View performance by channel
   - Track clicks and inquiries
   - ROI analysis by publishing channel

## Security Considerations

1. **API Key Management**
   - Secure storage of API credentials
   - Regular key rotation
   - Permissions-based access to publishing

2. **Tenant Isolation**
   - Channel configurations are tenant-specific
   - No cross-tenant data exposure
   - Audit logs for all publishing actions

3. **Content Validation**
   - Input sanitization for all content
   - Validation against portal requirements
   - Error handling and reporting

## Conclusion

The Push Portal Technology in Fusion CRM V4 provides:

1. **Streamlined Publishing**: Manage listings across multiple channels from one interface
2. **Consistent Branding**: Maintain consistent property information across all platforms
3. **Efficiency**: Save time with automated publishing and updates
4. **Optimized Content**: Format content appropriately for each channel
5. **Control**: Fine-grained control over what properties are published where

This architecture ensures that real estate professionals can maximize their property exposure with minimal effort, while maintaining control over their listings across all channels. 