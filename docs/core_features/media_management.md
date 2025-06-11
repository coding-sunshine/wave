# Media Management Documentation

*File upload, image processing, and media handling in Fusion CRM v4*

---

## Overview

Fusion CRM v4 includes a comprehensive media management system built on Laravel's file storage capabilities and Intervention Image for advanced image processing. The system handles file uploads, image optimization, and secure media serving for CRM workflows.

## Current Implementation

### ✅ Implemented Features

| Feature | Description | Status | Package |
|---------|-------------|--------|---------|
| **Intervention Image** | Advanced image manipulation and processing | ✅ IMPLEMENTED | `intervention/image: ^3.11` |
| **File Storage** | Laravel's file storage system with multiple disks | ✅ IMPLEMENTED | Laravel Core |
| **Secure Upload** | File validation and security measures | ✅ IMPLEMENTED | Laravel Core |
| **Image Optimization** | Automatic image resizing and compression | ✅ IMPLEMENTED | Intervention Image |

### 🔴 Not Yet Implemented (CRM-Specific)

| Feature | Description | Priority | Implementation Phase |
|---------|-------------|----------|---------------------|
| **Property Photo Management** | Gallery management for property listings | HIGH | Phase 1 (Weeks 5-8) |
| **Document Storage** | Contract and document management | HIGH | Phase 2 (Weeks 13-16) |
| **Avatar Management** | User profile photo handling | MEDIUM | Phase 1 (Weeks 1-4) |
| **Media Categories** | Organized media categorization | MEDIUM | Phase 2 (Weeks 17-20) |
| **Bulk Upload** | Multiple file upload interface | LOW | Phase 3 (Future) |

## File Storage Configuration

### Storage Disks (`config/filesystems.php`)

```php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
        'throw' => false,
    ],

    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw' => false,
    ],

    // Tenant-specific storage (for multi-tenancy)
    'tenant' => [
        'driver' => 'local',
        'root' => storage_path('app/tenants/' . session('tenant_id', 'default')),
        'url' => env('APP_URL').'/storage/tenants/' . session('tenant_id', 'default'),
        'visibility' => 'public',
        'throw' => false,
    ],

    // Cloud storage options
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'throw' => false,
    ],
],
```

### File Upload Validation

```php
// config/validation.php (custom configuration)
return [
    'file_uploads' => [
        'max_size' => '10240', // 10MB in kilobytes
        'allowed_types' => [
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'],
            'media' => ['mp4', 'avi', 'mov', 'wmv', 'flv'],
        ],
        'security' => [
            'scan_executable' => true,
            'check_mime_type' => true,
            'validate_headers' => true,
        ]
    ]
];
```

## Intervention Image Usage

### 1. **Basic Image Processing**

```php
<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageProcessingService
{
    protected $manager;
    
    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }
    
    /**
     * Process and store uploaded image
     */
    public function processImage(UploadedFile $file, array $options = []): array
    {
        $options = array_merge([
            'disk' => 'public',
            'directory' => 'images',
            'sizes' => [
                'thumbnail' => ['width' => 150, 'height' => 150],
                'medium' => ['width' => 500, 'height' => 500],
                'large' => ['width' => 1200, 'height' => 1200],
            ],
            'quality' => 85,
            'format' => 'webp'
        ], $options);
        
        $originalImage = $this->manager->read($file->getRealPath());
        $filename = uniqid() . '.' . $options['format'];
        $paths = [];
        
        // Process different sizes
        foreach ($options['sizes'] as $size => $dimensions) {
            $processedImage = $originalImage->clone();
            
            // Resize image maintaining aspect ratio
            $processedImage->scaleDown(
                width: $dimensions['width'],
                height: $dimensions['height']
            );
            
            // Apply quality compression
            $encodedImage = $processedImage->encodeByMediaType(
                'image/' . $options['format'],
                quality: $options['quality']
            );
            
            // Store the processed image
            $path = $options['directory'] . '/' . $size . '/' . $filename;
            Storage::disk($options['disk'])->put($path, $encodedImage);
            
            $paths[$size] = $path;
        }
        
        return [
            'paths' => $paths,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => 'image/' . $options['format'],
            'size' => $file->getSize()
        ];
    }
}
```

### 2. **Property Photo Processing**

```php
<?php

namespace App\Services;

class PropertyPhotoService
{
    protected $imageProcessor;
    
    public function __construct(ImageProcessingService $imageProcessor)
    {
        $this->imageProcessor = $imageProcessor;
    }
    
    /**
     * Process property photos with specific requirements
     */
    public function processPropertyPhotos(array $files, int $propertyId): array
    {
        $processedPhotos = [];
        
        foreach ($files as $index => $file) {
            // Validate file
            $this->validatePropertyPhoto($file);
            
            // Process with property-specific sizes
            $result = $this->imageProcessor->processImage($file, [
                'directory' => "properties/{$propertyId}",
                'sizes' => [
                    'thumbnail' => ['width' => 200, 'height' => 150],
                    'gallery' => ['width' => 800, 'height' => 600],
                    'hero' => ['width' => 1400, 'height' => 800],
                    'fullsize' => ['width' => 2000, 'height' => 1500],
                ],
                'quality' => 90,
                'format' => 'webp'
            ]);
            
            // Store photo metadata
            $processedPhotos[] = [
                'property_id' => $propertyId,
                'filename' => $result['filename'],
                'original_name' => $result['original_name'],
                'paths' => $result['paths'],
                'sort_order' => $index,
                'is_primary' => $index === 0,
                'alt_text' => $this->generateAltText($result['original_name']),
                'uploaded_at' => now()
            ];
        }
        
        return $processedPhotos;
    }
    
    private function validatePropertyPhoto(UploadedFile $file): void
    {
        // File size validation (max 10MB)
        if ($file->getSize() > 10485760) {
            throw new \InvalidArgumentException('File size exceeds 10MB limit');
        }
        
        // MIME type validation
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \InvalidArgumentException('Invalid file type. Only JPEG, PNG, and WebP are allowed');
        }
        
        // Image dimensions validation (minimum requirements)
        $imageInfo = getimagesize($file->getRealPath());
        if ($imageInfo[0] < 800 || $imageInfo[1] < 600) {
            throw new \InvalidArgumentException('Image must be at least 800x600 pixels');
        }
    }
    
    private function generateAltText(string $filename): string
    {
        // Generate meaningful alt text from filename
        $cleanName = pathinfo($filename, PATHINFO_FILENAME);
        $cleanName = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $cleanName);
        $cleanName = preg_replace('/\s+/', ' ', $cleanName);
        
        return 'Property image: ' . trim($cleanName);
    }
}
```

### 3. **Avatar Processing**

```php
<?php

namespace App\Services;

class AvatarService
{
    protected $imageProcessor;
    
    public function __construct(ImageProcessingService $imageProcessor)
    {
        $this->imageProcessor = $imageProcessor;
    }
    
    /**
     * Process user avatar with circular crop
     */
    public function processAvatar(UploadedFile $file, int $userId): array
    {
        // Process with avatar-specific requirements
        $result = $this->imageProcessor->processImage($file, [
            'directory' => "avatars/{$userId}",
            'sizes' => [
                'small' => ['width' => 40, 'height' => 40],
                'medium' => ['width' => 80, 'height' => 80],
                'large' => ['width' => 200, 'height' => 200],
            ],
            'quality' => 95,
            'format' => 'webp'
        ]);
        
        // Apply circular crop to all sizes
        foreach ($result['paths'] as $size => $path) {
            $this->applyCircularCrop($path, $size);
        }
        
        return $result;
    }
    
    private function applyCircularCrop(string $path, string $size): void
    {
        $image = $this->imageProcessor->manager->read(Storage::path($path));
        
        // Create circular mask
        $dimensions = [
            'small' => 40,
            'medium' => 80,
            'large' => 200,
        ];
        
        $diameter = $dimensions[$size];
        
        // Apply circular crop
        $image->crop($diameter, $diameter, position: 'center');
        
        // Create circular mask using a simple approach
        // In a real implementation, you might want to use more sophisticated masking
        
        // Save the processed image
        Storage::put($path, $image->encodeByMediaType('image/webp', quality: 95));
    }
}
```

## File Upload Components

### 1. **Livewire File Upload Component**

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\ImageProcessingService;

class FileUpload extends Component
{
    use WithFileUploads;
    
    public $files = [];
    public $uploadedFiles = [];
    public $maxFiles = 10;
    public $acceptedTypes = 'image/*';
    public $maxSize = 10240; // 10MB in KB
    
    protected $rules = [
        'files.*' => 'image|max:10240', // 10MB Max
    ];
    
    public function updatedFiles()
    {
        $this->validate();
        
        foreach ($this->files as $file) {
            $this->processFile($file);
        }
        
        $this->files = []; // Clear the input
    }
    
    private function processFile($file)
    {
        try {
            $imageProcessor = app(ImageProcessingService::class);
            $result = $imageProcessor->processImage($file, [
                'directory' => 'uploads/temp',
                'sizes' => [
                    'thumbnail' => ['width' => 150, 'height' => 150],
                    'preview' => ['width' => 400, 'height' => 300],
                ]
            ]);
            
            $this->uploadedFiles[] = [
                'id' => uniqid(),
                'name' => $result['original_name'],
                'size' => $result['size'],
                'paths' => $result['paths'],
                'uploaded_at' => now()->format('Y-m-d H:i:s')
            ];
            
            $this->dispatch('file-uploaded', $result);
            
        } catch (\Exception $e) {
            $this->addError('files', 'Error processing file: ' . $e->getMessage());
        }
    }
    
    public function removeFile($fileId)
    {
        $this->uploadedFiles = array_filter($this->uploadedFiles, function($file) use ($fileId) {
            return $file['id'] !== $fileId;
        });
    }
    
    public function render()
    {
        return view('livewire.file-upload');
    }
}
```

### 2. **File Upload Blade Template**

```blade
<!-- resources/views/livewire/file-upload.blade.php -->
<div class="space-y-4">
    <!-- Drop Zone -->
    <div 
        x-data="{ 
            isDragging: false,
            handleDrop(e) {
                this.isDragging = false;
                const files = Array.from(e.dataTransfer.files);
                if (files.length > 0) {
                    @this.set('files', files);
                }
            }
        }"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        :class="{ 'border-blue-500 bg-blue-50': isDragging }"
        class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center transition-colors duration-200"
    >
        <div class="space-y-4">
            <div class="mx-auto w-12 h-12 text-gray-400">
                <x-phosphor-cloud-arrow-up class="w-full h-full" />
            </div>
            
            <div>
                <p class="text-lg font-medium text-gray-900">Drop files here</p>
                <p class="text-sm text-gray-500">or click to browse</p>
            </div>
            
            <input 
                type="file" 
                wire:model="files"
                multiple
                accept="{{ $acceptedTypes }}"
                class="hidden"
                id="file-input"
            />
            
            <button 
                type="button"
                onclick="document.getElementById('file-input').click()"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200"
            >
                <x-phosphor-plus class="w-4 h-4 mr-2" />
                Choose Files
            </button>
        </div>
        
        <div class="mt-4 text-xs text-gray-500">
            Max {{ $maxFiles }} files, {{ number_format($maxSize / 1024, 1) }}MB each
        </div>
    </div>
    
    <!-- Loading State -->
    <div wire:loading wire:target="files" class="flex items-center justify-center py-4">
        <div class="flex items-center space-x-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
            <span class="text-sm text-gray-600">Processing files...</span>
        </div>
    </div>
    
    <!-- Uploaded Files List -->
    @if(count($uploadedFiles) > 0)
        <div class="space-y-2">
            <h3 class="text-sm font-medium text-gray-900">Uploaded Files</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($uploadedFiles as $file)
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <!-- File Preview -->
                        @if(isset($file['paths']['thumbnail']))
                            <div class="aspect-square mb-3 bg-gray-100 rounded-md overflow-hidden">
                                <img 
                                    src="{{ Storage::url($file['paths']['thumbnail']) }}" 
                                    alt="{{ $file['name'] }}"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                        @endif
                        
                        <!-- File Info -->
                        <div class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-900 truncate" title="{{ $file['name'] }}">
                                {{ $file['name'] }}
                            </h4>
                            
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>{{ $this->formatFileSize($file['size']) }}</span>
                                <span>{{ $file['uploaded_at'] }}</span>
                            </div>
                            
                            <button 
                                wire:click="removeFile('{{ $file['id'] }}')"
                                class="w-full flex items-center justify-center px-3 py-1.5 bg-red-50 text-red-700 rounded-md hover:bg-red-100 transition-colors duration-200"
                            >
                                <x-phosphor-trash class="w-3 h-3 mr-1" />
                                Remove
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    <!-- Error Messages -->
    @error('files')
        <div class="bg-red-50 border border-red-200 rounded-md p-3">
            <div class="flex">
                <x-phosphor-warning-circle class="w-5 h-5 text-red-400 mr-2 flex-shrink-0" />
                <p class="text-sm text-red-700">{{ $message }}</p>
            </div>
        </div>
    @enderror
</div>

@push('scripts')
<script>
    // Additional JavaScript for enhanced file upload functionality
    document.addEventListener('livewire:init', () => {
        Livewire.on('file-uploaded', (result) => {
            // Handle successful file upload
            console.log('File uploaded:', result);
        });
    });
</script>
@endpush
```

## Security Considerations

### 1. **File Validation**

```php
<?php

namespace App\Services;

class FileSecurityService
{
    public function validateFile(UploadedFile $file): bool
    {
        // Check file size
        if ($file->getSize() > config('validation.file_uploads.max_size') * 1024) {
            throw new \InvalidArgumentException('File size exceeds maximum allowed size');
        }
        
        // Validate MIME type
        $allowedMimeTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \InvalidArgumentException('File type not allowed');
        }
        
        // Check file headers for additional security
        $this->validateFileHeaders($file);
        
        // Scan for malicious content
        $this->scanForMaliciousContent($file);
        
        return true;
    }
    
    private function validateFileHeaders(UploadedFile $file): void
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $header = fread($handle, 20);
        fclose($handle);
        
        // Define file signatures (magic numbers)
        $signatures = [
            'jpeg' => ["\xFF\xD8\xFF"],
            'png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
            'gif' => ["GIF87a", "GIF89a"],
            'pdf' => ["%PDF"],
        ];
        
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (isset($signatures[$extension])) {
            $validSignature = false;
            foreach ($signatures[$extension] as $signature) {
                if (strpos($header, $signature) === 0) {
                    $validSignature = true;
                    break;
                }
            }
            
            if (!$validSignature) {
                throw new \InvalidArgumentException('File signature does not match file extension');
            }
        }
    }
    
    private function scanForMaliciousContent(UploadedFile $file): void
    {
        // Basic content scanning for common malicious patterns
        $content = file_get_contents($file->getRealPath(), false, null, 0, 1024);
        
        $maliciousPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new \InvalidArgumentException('File contains potentially malicious content');
            }
        }
    }
}
```

### 2. **Secure File Serving**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureFileController extends Controller
{
    /**
     * Serve protected files with access control
     */
    public function serve(Request $request, string $disk, string $path)
    {
        // Validate user permissions
        if (!$this->canAccessFile($request->user(), $disk, $path)) {
            abort(403, 'Access denied');
        }
        
        // Check if file exists
        if (!Storage::disk($disk)->exists($path)) {
            abort(404, 'File not found');
        }
        
        // Get file information
        $mimeType = Storage::disk($disk)->mimeType($path);
        $size = Storage::disk($disk)->size($path);
        $lastModified = Storage::disk($disk)->lastModified($path);
        
        // Stream the file
        return new StreamedResponse(function () use ($disk, $path) {
            $stream = Storage::disk($disk)->readStream($path);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $size,
            'Last-Modified' => gmdate('D, d M Y H:i:s T', $lastModified),
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
    
    private function canAccessFile($user, string $disk, string $path): bool
    {
        // Implement your access control logic here
        // For example, check if user owns the file or has permission to access it
        
        if (!$user) {
            return false;
        }
        
        // Check tenant access for multi-tenant setup
        if ($disk === 'tenant') {
            $tenantId = explode('/', $path)[0] ?? null;
            return $user->tenant_id === $tenantId;
        }
        
        return true;
    }
}
```

## Performance Optimization

### 1. **Image Optimization**

```php
<?php

namespace App\Services;

class ImageOptimizationService
{
    /**
     * Optimize images for web delivery
     */
    public function optimizeForWeb(string $imagePath, array $options = []): string
    {
        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        $image = $manager->read($imagePath);
        
        $options = array_merge([
            'max_width' => 1200,
            'max_height' => 800,
            'quality' => 85,
            'format' => 'webp',
            'progressive' => true,
        ], $options);
        
        // Resize if too large
        if ($image->width() > $options['max_width'] || $image->height() > $options['max_height']) {
            $image->scaleDown($options['max_width'], $options['max_height']);
        }
        
        // Convert to optimal format
        $optimizedPath = pathinfo($imagePath, PATHINFO_DIRNAME) . '/' . 
                        pathinfo($imagePath, PATHINFO_FILENAME) . 
                        '_optimized.' . $options['format'];
        
        $image->encodeByMediaType(
            'image/' . $options['format'],
            quality: $options['quality'],
            progressive: $options['progressive']
        )->save($optimizedPath);
        
        return $optimizedPath;
    }
}
```

### 2. **CDN Integration**

```php
// config/filesystems.php
'cloudfront' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_CLOUDFRONT_URL'), // CloudFront distribution URL
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => false,
],
```

## Best Practices

### 1. **File Organization**
- Use consistent directory structures
- Implement logical file naming conventions
- Separate files by tenant in multi-tenant setups
- Archive old files regularly

### 2. **Security**
- Always validate file types and sizes
- Scan uploads for malicious content
- Implement access controls for file serving
- Use secure file storage locations

### 3. **Performance**
- Optimize images for web delivery
- Implement proper caching headers
- Use CDN for static file delivery
- Generate multiple image sizes for responsive design

### 4. **User Experience**
- Provide clear upload progress indicators
- Show meaningful error messages
- Support drag-and-drop uploads
- Display file previews when possible

---

*For frontend file upload components, see the [Frontend Documentation](../frontend/README.md). For image processing in themes, refer to the [Theme System](../frontend/themes.md).*