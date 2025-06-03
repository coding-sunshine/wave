# Fusion CRM V4 - Media Management

This document outlines the architecture for media management in Fusion CRM V4, addressing the handling of property images, marketing materials, documents, and other media assets.

## Overview

Fusion CRM V4 implements a comprehensive media management system that provides:

1. **Property Media Handling**: Images, floor plans, videos, and documents for properties
2. **Multi-Format Support**: Handles images, videos, PDFs, and other document types
3. **Intelligent Processing**: Automatic image optimization, metadata extraction, and categorization
4. **Secure Storage**: Tenant-isolated storage with proper access controls
5. **Dynamic Access**: CDN integration for fast global delivery
6. **Version Control**: Track changes and maintain media history

## Core Components

### 1. Media Storage Service

The central service for handling media operations:

```php
namespace App\Services\Media;

use App\Models\Media;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class MediaService
{
    protected $tenantManager;
    protected $disk;
    
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
        $this->disk = 'tenant';
    }
    
    /**
     * Store a media file uploaded by the user
     */
    public function store(UploadedFile $file, string $mediaType = 'image', array $options = []): Media
    {
        $tenant = $this->tenantManager->getTenant();
        
        // Generate a unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        
        // Determine content type based on mime type
        $contentType = $file->getMimeType();
        
        // Determine the storage path
        $path = $options['path'] ?? $mediaType . 's';
        $storagePath = $path . '/' . $filename;
        
        // Is this an image that needs processing?
        if (strpos($contentType, 'image/') === 0 && !isset($options['no_processing'])) {
            // Process using Intervention Image
            $image = Image::make($file);
            
            // Resize if needed
            if (isset($options['width']) && isset($options['height'])) {
                $image->fit($options['width'], $options['height']);
            } elseif (isset($options['max_width']) || isset($options['max_height'])) {
                $image->resize(
                    $options['max_width'] ?? null, 
                    $options['max_height'] ?? null, 
                    function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                );
            }
            
            // Add watermark if specified
            if (isset($options['watermark']) && $options['watermark']) {
                $watermarkPath = Storage::disk('local')->path('watermarks/tenant_' . $tenant->id . '.png');
                if (file_exists($watermarkPath)) {
                    $image->insert($watermarkPath, 'bottom-right', 10, 10);
                }
            }
            
            // Generate a stream to store the processed image
            $stream = $image->stream();
            Storage::disk($this->disk)->put($storagePath, $stream);
        } else {
            // Just store the original file
            Storage::disk($this->disk)->put($storagePath, file_get_contents($file));
        }
        
        // Create the media record
        $media = new Media();
        $media->tenant_id = $tenant->id;
        $media->file_name = $file->getClientOriginalName();
        $media->file_path = $storagePath;
        $media->file_size = $file->getSize();
        $media->file_type = $contentType;
        $media->media_type = $mediaType;
        $media->title = $options['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $media->description = $options['description'] ?? null;
        $media->options = $options['meta'] ?? null;
        $media->save();
        
        return $media;
    }
    
    /**
     * Get the URL for a media item
     */
    public function getUrl(Media $media): string
    {
        return Storage::disk($this->disk)->url($media->file_path);
    }
    
    /**
     * Delete a media item
     */
    public function delete(Media $media): bool
    {
        // Delete the file
        Storage::disk($this->disk)->delete($media->file_path);
        
        // Delete the media record
        return $media->delete();
    }
    
    /**
     * Process a batch of media files
     */
    public function storeBatch(array $files, string $mediaType = 'image', array $options = []): array
    {
        $mediaItems = [];
        
        foreach ($files as $file) {
            $mediaItems[] = $this->store($file, $mediaType, $options);
        }
        
        return $mediaItems;
    }
    
    /**
     * Move media from temporary storage to permanent storage
     */
    public function makePermament(Media $media, string $newPath): bool
    {
        $currentPath = $media->file_path;
        $newFullPath = $newPath . '/' . basename($currentPath);
        
        // Move the file
        if (Storage::disk($this->disk)->move($currentPath, $newFullPath)) {
            // Update the media record
            $media->file_path = $newFullPath;
            return $media->save();
        }
        
        return false;
    }
}
```

### 2. Property Media Manager

Specialized service for handling property-related media:

```php
namespace App\Services\Media;

use App\Models\Lot;
use App\Models\Media;
use App\Models\Project;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\UploadedFile;

class PropertyMediaManager
{
    protected $mediaService;
    protected $tenantManager;
    
    public function __construct(MediaService $mediaService, TenantManager $tenantManager)
    {
        $this->mediaService = $mediaService;
        $this->tenantManager = $tenantManager;
    }
    
    /**
     * Add media to a property
     */
    public function addMediaToProperty(Lot $property, UploadedFile $file, string $mediaType = 'image', array $options = []): Media
    {
        // Determine the storage path for property media
        $options['path'] = 'properties/' . $property->id;
        
        // Store the media
        $media = $this->mediaService->store($file, $mediaType, $options);
        
        // Associate media with property
        $property->media()->attach($media->id, [
            'is_primary' => $options['is_primary'] ?? false,
            'order' => $options['order'] ?? 0
        ]);
        
        return $media;
    }
    
    /**
     * Add media to a project
     */
    public function addMediaToProject(Project $project, UploadedFile $file, string $mediaType = 'image', array $options = []): Media
    {
        // Determine the storage path for project media
        $options['path'] = 'projects/' . $project->id;
        
        // Store the media
        $media = $this->mediaService->store($file, $mediaType, $options);
        
        // Associate media with project
        $project->media()->attach($media->id, [
            'is_primary' => $options['is_primary'] ?? false,
            'order' => $options['order'] ?? 0
        ]);
        
        return $media;
    }
    
    /**
     * Set primary image for a property
     */
    public function setPrimaryMedia(Lot $property, Media $media): bool
    {
        // Reset all property media to non-primary
        $property->media()->updateExistingPivot($property->media->pluck('id'), [
            'is_primary' => false
        ]);
        
        // Set this media as primary
        return $property->media()->updateExistingPivot($media->id, [
            'is_primary' => true
        ]);
    }
    
    /**
     * Reorder property media
     */
    public function reorderPropertyMedia(Lot $property, array $mediaOrder): bool
    {
        foreach ($mediaOrder as $order => $mediaId) {
            $property->media()->updateExistingPivot($mediaId, [
                'order' => $order
            ]);
        }
        
        return true;
    }
    
    /**
     * Extract images from a PDF
     */
    public function extractImagesFromPdf(Media $pdfMedia, Lot $property): array
    {
        // Implementation depends on PDF processing library
        // This would extract images from a floor plan PDF and store them as media
        // Return the created media items
        return [];
    }
}
```

### 3. Media Processing Queues

Handle long-running media operations asynchronously:

```php
namespace App\Jobs;

use App\Models\Media;
use App\Services\Media\MediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\PdfToImage\Pdf;

class ProcessPdfMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    protected $mediaId;
    protected $options;
    
    /**
     * Create a new job instance.
     */
    public function __construct(int $mediaId, array $options = [])
    {
        parent::__construct();
        $this->mediaId = $mediaId;
        $this->options = $options;
    }
    
    /**
     * Execute the job.
     */
    public function handleForTenant(): void
    {
        $media = Media::findOrFail($this->mediaId);
        
        // Skip if not a PDF
        if ($media->file_type !== 'application/pdf') {
            return;
        }
        
        // Get PDF file path
        $pdfPath = storage_path('app/tenant/' . $media->file_path);
        
        // Convert PDF to images
        $pdf = new Pdf($pdfPath);
        $pageCount = $pdf->getNumberOfPages();
        
        $mediaService = app(MediaService::class);
        $extractedMedia = [];
        
        // Convert each page to an image
        for ($i = 1; $i <= $pageCount; $i++) {
            // Create temporary file for the page image
            $imagePath = storage_path('app/temp/' . uniqid() . '.jpg');
            
            // Convert page to image
            $pdf->setPage($i)
                ->setCompressionQuality(90)
                ->saveImage($imagePath);
            
            // Create a temporary UploadedFile from the image
            $file = new \Illuminate\Http\UploadedFile(
                $imagePath,
                'page_' . $i . '.jpg',
                'image/jpeg',
                null,
                true
            );
            
            // Store the image as media
            $pageMedia = $mediaService->store(
                $file,
                'image',
                array_merge($this->options, [
                    'source_media_id' => $media->id,
                    'page_number' => $i,
                    'title' => $media->title . ' - Page ' . $i
                ])
            );
            
            $extractedMedia[] = $pageMedia;
            
            // Clean up temporary file
            unlink($imagePath);
        }
        
        // Update the original PDF media record to indicate processing is complete
        $media->update([
            'options' => array_merge($media->options ?? [], [
                'processed' => true,
                'page_count' => $pageCount,
                'extracted_media_ids' => collect($extractedMedia)->pluck('id')->toArray()
            ])
        ]);
    }
}
```

### 4. AI-Powered Media Recognition

Service for intelligent media analysis and classification:

```php
namespace App\Services\Media;

use App\Models\Media;
use App\Services\AI\OpenAiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MediaRecognitionService
{
    protected $openAiService;
    
    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }
    
    /**
     * Analyze an image and generate tags and description
     */
    public function analyzeImage(Media $media): array
    {
        try {
            // Skip if not an image
            if (strpos($media->file_type, 'image/') !== 0) {
                return [
                    'success' => false,
                    'message' => 'Not an image'
                ];
            }
            
            // Get image URL
            $imageUrl = url(Storage::disk('tenant')->url($media->file_path));
            
            // Call OpenAI Vision API to analyze image
            $response = $this->openAiService->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a real estate image analysis expert. Describe the image in detail, identifying key features relevant to real estate. Then provide property features visible in the image and suggest tags for categorization.'
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Analyze this real estate image. Identify if it shows: interior/exterior, room type, key features, property style. Format response as JSON with: description, features (array), tags (array).'
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $imageUrl
                            ]
                        ]
                    ]
                ]
            ], [
                'model' => 'gpt-4-vision-preview',
                'max_tokens' => 800
            ]);
            
            // Extract JSON from response
            $content = $response['choices'][0]['message']['content'] ?? '';
            
            // Parse results (handle potential non-JSON response)
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonContent = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
                $data = json_decode($jsonContent, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Update media with AI-generated data
                    $media->update([
                        'description' => $data['description'] ?? $media->description,
                        'options' => array_merge($media->options ?? [], [
                            'ai_analysis' => [
                                'features' => $data['features'] ?? [],
                                'tags' => $data['tags'] ?? [],
                                'analyzed_at' => now()->toIso8601String()
                            ]
                        ])
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $data
                    ];
                }
            }
            
            return [
                'success' => false,
                'message' => 'Failed to parse AI response'
            ];
        } catch (\Exception $e) {
            Log::error('Error analyzing image', [
                'media_id' => $media->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract text from a document
     */
    public function extractTextFromDocument(Media $media): array
    {
        try {
            // Only process PDFs and supported document types
            $supportedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            
            if (!in_array($media->file_type, $supportedTypes)) {
                return [
                    'success' => false,
                    'message' => 'Unsupported document type'
                ];
            }
            
            // Implementation depends on document processing library
            // This could use a service like AWS Textract, Google Document AI, or similar
            
            return [
                'success' => true,
                'text' => 'Extracted text would be here'
            ];
        } catch (\Exception $e) {
            Log::error('Error extracting text from document', [
                'media_id' => $media->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract floor plan details from an image
     */
    public function extractFloorPlanDetails(Media $media): array
    {
        // This would use specialized AI to extract measurements, room layouts, etc.
        // Implementation would depend on third-party AI services for floor plan recognition
        return [];
    }
}
```

## Media Related Database Schema

```sql
CREATE TABLE media (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INTEGER NOT NULL,
    file_type VARCHAR(255) NOT NULL,
    media_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NULL,
    description TEXT NULL,
    options JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE mediables (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    media_id BIGINT UNSIGNED NOT NULL,
    mediable_type VARCHAR(255) NOT NULL,
    mediable_id BIGINT UNSIGNED NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT false,
    order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
);
```

## UI Components

### 1. Media Uploader Component

```php
namespace App\Http\Livewire;

use App\Services\Media\MediaService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaUploader extends Component
{
    use WithFileUploads;

    public $files = [];
    public $mediaType = 'image';
    public $associateWith = null;
    public $associationType = null;
    public $uploadedMedia = [];
    public $maxSize = 10240; // 10MB
    public $acceptedFileTypes = 'image/*,.pdf,.doc,.docx';
    
    protected $rules = [
        'files.*' => 'file|max:10240',
        'mediaType' => 'required|string',
    ];
    
    public function mount($mediaType = 'image', $associateWith = null, $associationType = null)
    {
        $this->mediaType = $mediaType;
        $this->associateWith = $associateWith;
        $this->associationType = $associationType;
        
        if ($mediaType === 'document') {
            $this->acceptedFileTypes = '.pdf,.doc,.docx,.xls,.xlsx,.txt';
        } elseif ($mediaType === 'video') {
            $this->acceptedFileTypes = 'video/*';
        }
    }
    
    public function updatedFiles()
    {
        $this->validate([
            'files.*' => 'file|max:' . $this->maxSize,
        ]);
    }
    
    public function upload()
    {
        $this->validate();
        
        $mediaService = app(MediaService::class);
        $uploadedMedia = [];
        
        foreach ($this->files as $file) {
            $media = $mediaService->store($file, $this->mediaType, [
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            ]);
            
            $uploadedMedia[] = [
                'id' => $media->id,
                'name' => $media->file_name,
                'preview' => strpos($media->file_type, 'image') === 0 
                    ? Storage::disk('tenant')->url($media->file_path) 
                    : null,
                'size' => $media->file_size,
                'type' => $media->file_type,
            ];
        }
        
        $this->uploadedMedia = $uploadedMedia;
        $this->reset('files');
        
        // Associate media if needed
        if ($this->associateWith && $this->associationType) {
            $this->associateMedia();
        }
        
        $this->emitUp('mediaUploaded', $uploadedMedia);
    }
    
    protected function associateMedia()
    {
        // This would associate the uploaded media with the specified entity
        // Implementation depends on the entity type
    }
    
    public function render()
    {
        return view('livewire.media-uploader');
    }
}
```

### 2. Property Media Manager Component

```php
namespace App\Http\Livewire\Properties;

use App\Models\Lot;
use App\Services\Media\PropertyMediaManager;
use Livewire\Component;
use Livewire\WithFileUploads;

class PropertyMediaManager extends Component
{
    use WithFileUploads;
    
    public $propertyId;
    public $property;
    public $media = [];
    public $files = [];
    public $processingFloorPlan = false;
    
    protected $mediaManager;
    
    protected $listeners = [
        'mediaUploaded' => 'refreshMedia',
        'floorPlanProcessed' => 'handleFloorPlanProcessed'
    ];
    
    public function boot(PropertyMediaManager $mediaManager)
    {
        $this->mediaManager = $mediaManager;
    }
    
    public function mount(Lot $property)
    {
        $this->property = $property;
        $this->propertyId = $property->id;
        $this->refreshMedia();
    }
    
    public function refreshMedia()
    {
        $this->property->load(['media' => function ($query) {
            $query->orderBy('mediables.order');
        }]);
        
        $this->media = $this->property->media->map(function ($media) {
            return [
                'id' => $media->id,
                'title' => $media->title,
                'description' => $media->description,
                'type' => $media->media_type,
                'url' => Storage::disk('tenant')->url($media->file_path),
                'is_primary' => $media->pivot->is_primary,
                'order' => $media->pivot->order,
            ];
        })->toArray();
    }
    
    public function setPrimary($mediaId)
    {
        $media = \App\Models\Media::find($mediaId);
        $this->mediaManager->setPrimaryMedia($this->property, $media);
        $this->refreshMedia();
    }
    
    public function updateOrder($orderedIds)
    {
        $this->mediaManager->reorderPropertyMedia($this->property, $orderedIds);
        $this->refreshMedia();
    }
    
    public function deleteMedia($mediaId)
    {
        $media = \App\Models\Media::find($mediaId);
        app(\App\Services\Media\MediaService::class)->delete($media);
        $this->refreshMedia();
    }
    
    public function processFloorPlan($mediaId)
    {
        $this->processingFloorPlan = true;
        
        // Dispatch job to process floor plan PDF
        \App\Jobs\ProcessPdfMedia::dispatch($mediaId, [
            'property_id' => $this->propertyId
        ]);
        
        // Note: in a real implementation, this would use queues and events
        // to handle the asynchronous processing
    }
    
    public function handleFloorPlanProcessed($data)
    {
        $this->processingFloorPlan = false;
        $this->refreshMedia();
    }
    
    public function render()
    {
        return view('livewire.properties.property-media-manager');
    }
}
```

## Implementation Strategy

### Phase 1: Core Media Management

1. **Storage Infrastructure Setup**
   - Configure S3 or compatible storage
   - Implement tenant isolation for media
   - Setup CDN for fast global delivery

2. **Basic Media Upload and Management**
   - Implement file upload components
   - Basic image processing
   - Media association with entities

### Phase 2: Enhanced Processing

3. **Advanced Image Processing**
   - Automatic image optimization
   - Watermarking capabilities
   - Batch processing for uploads

4. **Document Processing**
   - PDF handling and conversion
   - Floor plan extraction
   - Document text extraction

### Phase 3: AI and Intelligence

5. **AI-Powered Media Analysis**
   - Image recognition and tagging
   - Automatic property feature detection
   - Content moderation

6. **Media Discovery and Search**
   - Visual similarity search
   - Content-based media search
   - Advanced media filtering

## Conclusion

The media management system in Fusion CRM V4 provides a robust foundation for handling property images, marketing materials, and documentation. By implementing intelligent processing, proper tenant isolation, and an intuitive user interface, the system enables effective management of all media assets in the CRM.

Key benefits of this architecture include:

1. **Scalability**: Independent scaling of storage and processing
2. **Tenant Isolation**: Secure separation of tenant media assets
3. **Performance**: Optimized media delivery using CDN technology
4. **Intelligence**: AI-powered analysis and categorization of media
5. **Flexibility**: Support for diverse media types across the platform

This architecture supports the upgraded media management features required in Fusion CRM V4 while providing a foundation for future enhancements. 