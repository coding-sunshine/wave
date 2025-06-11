# Theme System Documentation

*Multi-theme support and customization for Fusion CRM v4*

---

## Overview

Fusion CRM v4 features a comprehensive theme system built on top of the Wave SaaS framework, providing multiple design options and extensive customization capabilities. The system supports theme switching, custom branding, and responsive design patterns optimized for CRM workflows.

## Current Theme Implementation

### ✅ Available Themes

| Theme | Description | Status | Primary Use |
|-------|-------------|--------|-------------|
| **Drift** | Modern, clean design with mega menu navigation | ✅ ACTIVE | Primary Fusion CRM theme |
| **Anchor** | Dashboard-focused design with sidebar navigation | ✅ AVAILABLE | Alternative layout option |

### Theme Selection

**Current Active Theme**: Drift (configured in `theme.json`)

## Theme Architecture

### 1. **File Structure**

Each theme follows a consistent directory structure:

```
resources/themes/[theme-name]/
├── assets/
│   ├── css/
│   │   └── app.css              # Main theme styles
│   └── js/
│       └── app.js               # Theme JavaScript
├── components/
│   ├── app/                     # Application components
│   │   ├── footer.blade.php
│   │   ├── header.blade.php
│   │   └── navigation.blade.php
│   ├── elements/                # UI elements
│   │   ├── button.blade.php
│   │   ├── card.blade.php
│   │   └── modal.blade.php
│   ├── layouts/                 # Layout templates
│   │   ├── app.blade.php
│   │   ├── marketing.blade.php
│   │   └── empty.blade.php
│   └── marketing/               # Marketing components
│       ├── hero.blade.php
│       ├── features.blade.php
│       └── pricing.blade.php
├── pages/                       # Folio page templates
├── partials/                    # Reusable template parts
├── theme.jpg                    # Preview image (600x400px)
└── theme.json                   # Theme metadata
```

### 2. **Theme Configuration**

#### Theme Metadata (`theme.json`)
```json
{
  "name": "Drift",
  "version": "1.0",
  "description": "Modern CRM theme with mega menu navigation",
  "author": "Fusion CRM Team",
  "screenshot": "theme.jpg",
  "demo_url": "https://demo.fusioncrm.com",
  "tags": ["modern", "responsive", "crm"]
}
```

#### System Configuration (`config/themes.php`)
```php
return [
    'active' => 'drift',
    'folder' => 'themes',
    'themes_path' => resource_path('themes'),
    'demo_mode' => env('WAVE_DEMO', false),
    'primary_color' => '#ff6b35', // Fusion CRM orange
];
```

#### Current Theme Selector (`theme.json` - root)
```json
{
  "name": "drift"
}
```

## Drift Theme (Primary)

### Design Philosophy
- **Modern & Clean**: Streamlined interface optimized for CRM workflows
- **Mega Menu Navigation**: Organized menu system for complex applications
- **Responsive First**: Mobile-optimized layouts and components
- **Fusion CRM Branding**: Custom orange/red color scheme

### Key Features

#### 1. **Navigation System**
```blade
<!-- Mega menu with organized sections -->
<nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Navigation content -->
    </div>
</nav>
```

#### 2. **Color Scheme**
```css
:root {
    --color-primary: #ff6b35;    /* Fusion orange */
    --color-secondary: #d63384;  /* Accent red */
    --color-success: #28a745;
    --color-warning: #ffc107;
    --color-danger: #dc3545;
    --color-info: #17a2b8;
}
```

#### 3. **Component Library**
- **Cards**: Modern card layouts with shadows and rounded corners
- **Buttons**: Multiple button styles with hover effects
- **Forms**: Enhanced form styling with focus states
- **Tables**: Responsive data tables with sorting
- **Modals**: Overlay modals with backdrop blur

#### 4. **Layout Options**
```blade
<!-- Application layout -->
<x-layouts.app>
    <!-- Page content -->
</x-layouts.app>

<!-- Marketing layout -->
<x-layouts.marketing>
    <!-- Marketing content -->
</x-layouts.marketing>

<!-- Empty layout -->
<x-layouts.empty>
    <!-- Minimal layout content -->
</x-layouts.empty>
```

### Drift-Specific Components

#### Navigation Component
```blade
<!-- resources/themes/drift/components/app/navigation.blade.php -->
<nav x-data="{ open: false }" class="bg-white shadow">
    <!-- Desktop navigation -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo and primary nav -->
            <div class="flex">
                <x-logo class="h-8 w-auto" />
                <!-- Main navigation items -->
            </div>
            
            <!-- User menu -->
            <div class="flex items-center space-x-4">
                <!-- User dropdown -->
            </div>
        </div>
    </div>
    
    <!-- Mobile navigation -->
    <div x-show="open" class="sm:hidden">
        <!-- Mobile menu items -->
    </div>
</nav>
```

#### Hero Component
```blade
<!-- resources/themes/drift/components/marketing/hero.blade.php -->
<section class="relative bg-gradient-to-r from-orange-500 to-red-600">
    <div class="absolute inset-0 bg-black opacity-25"></div>
    <div class="relative max-w-7xl mx-auto py-24 px-4 sm:py-32 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
            {{ $title }}
        </h1>
        <p class="mt-6 text-xl text-gray-100 max-w-3xl">
            {{ $subtitle }}
        </p>
        <div class="mt-10">
            {{ $slot }}
        </div>
    </div>
</section>
```

## Anchor Theme (Alternative)

### Design Philosophy
- **Dashboard Focused**: Sidebar navigation optimized for admin interfaces
- **Traditional Layout**: Familiar CRM layout patterns
- **Data Dense**: Optimized for displaying large amounts of information

### Key Features

#### 1. **Sidebar Navigation**
```blade
<!-- Fixed sidebar layout -->
<div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <div class="hidden md:flex md:flex-shrink-0">
        <div class="flex flex-col w-64">
            <!-- Sidebar content -->
        </div>
    </div>
    
    <!-- Main content -->
    <div class="flex flex-col w-0 flex-1 overflow-hidden">
        <!-- Page content -->
    </div>
</div>
```

#### 2. **Component Variations**
- **Compact Tables**: Dense data display options
- **Dashboard Cards**: Metrics-focused card designs
- **Sidebar Forms**: Optimized form layouts for narrow spaces

## Theme Switching

### 1. **Admin Interface**

Navigate to `/admin/themes` to access the theme management interface:

```php
// app/Filament/Pages/Themes.php
class Themes extends Page
{
    protected static string $view = 'filament.pages.themes';
    
    public function activateTheme($themeName)
    {
        // Theme activation logic
        file_put_contents(
            base_path('theme.json'),
            json_encode(['name' => $themeName])
        );
        
        // Clear caches
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        
        // Redirect with success message
        return redirect()->back()->with('success', 'Theme activated successfully');
    }
}
```

### 2. **Programmatic Switching**

```php
// Switch theme programmatically
use Illuminate\Support\Facades\File;

function switchTheme($themeName)
{
    $themeConfig = ['name' => $themeName];
    
    File::put(
        base_path('theme.json'),
        json_encode($themeConfig, JSON_PRETTY_PRINT)
    );
    
    // Clear relevant caches
    Artisan::call('config:clear');
    Artisan::call('view:clear');
}
```

### 3. **Theme Detection**

```php
// Get current theme
function getCurrentTheme()
{
    $themeFile = base_path('theme.json');
    
    if (File::exists($themeFile)) {
        $theme = json_decode(File::get($themeFile), true);
        return $theme['name'] ?? 'drift';
    }
    
    return 'drift'; // Default theme
}

// Use in Blade templates
@if(getCurrentTheme() === 'drift')
    <!-- Drift-specific content -->
@else
    <!-- Anchor-specific content -->
@endif
```

## Customization

### 1. **Brand Colors**

#### CSS Custom Properties
```css
/* resources/themes/drift/assets/css/app.css */
:root {
    /* Primary brand colors */
    --color-primary: #ff6b35;
    --color-primary-50: #fff5f3;
    --color-primary-100: #ffebe5;
    --color-primary-500: #ff6b35;
    --color-primary-600: #e55a2b;
    --color-primary-700: #cc4d22;
    
    /* Secondary colors */
    --color-secondary: #d63384;
    --color-accent: #6366f1;
}
```

#### Tailwind Configuration
```javascript
// tailwind.config.js
module.exports = {
    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#fff5f3',
                    100: '#ffebe5',
                    500: '#ff6b35',
                    600: '#e55a2b',
                    700: '#cc4d22',
                },
                secondary: {
                    500: '#d63384',
                    600: '#b02a5b',
                }
            }
        }
    }
}
```

### 2. **Logo Customization**

```blade
<!-- resources/themes/drift/components/elements/logo.blade.php -->
<div {{ $attributes->merge(['class' => 'flex items-center']) }}>
    @if(config('wave.logo'))
        <img src="{{ asset(config('wave.logo')) }}" alt="{{ config('app.name') }}" class="h-8 w-auto">
    @else
        <svg class="h-8 w-auto text-primary-500" viewBox="0 0 100 40">
            <!-- Fusion CRM logo SVG -->
            <text x="0" y="25" class="text-lg font-bold fill-current">Fusion CRM</text>
        </svg>
    @endif
</div>
```

### 3. **Component Overrides**

#### Creating Custom Components
```bash
# Copy theme component for customization
cp resources/themes/drift/components/elements/button.blade.php \
   resources/themes/drift/components/elements/button-custom.blade.php
```

```blade
<!-- Custom button component -->
<!-- resources/themes/drift/components/elements/button-custom.blade.php -->
@props([
    'variant' => 'primary',
    'size' => 'md',
    'disabled' => false
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200';

$variants = [
    'primary' => 'bg-primary-600 hover:bg-primary-700 text-white focus:ring-primary-500',
    'secondary' => 'bg-gray-200 hover:bg-gray-300 text-gray-900 focus:ring-gray-500',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
];

$sizes = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
];

$classes = $baseClasses . ' ' . $variants[$variant] . ' ' . $sizes[$size];

if ($disabled) {
    $classes .= ' opacity-50 cursor-not-allowed';
}
@endphp

<button 
    {{ $attributes->merge(['class' => $classes, 'disabled' => $disabled]) }}
>
    {{ $slot }}
</button>
```

### 4. **CSS Customization**

#### Theme-Specific Styles
```css
/* resources/themes/drift/assets/css/app.css */

/* Fusion CRM specific overrides */
.fusion-card {
    @apply bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200;
}

.fusion-button {
    @apply bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200;
}

.fusion-input {
    @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500;
}

/* CRM-specific components */
.contact-card {
    @apply fusion-card p-6;
}

.property-listing {
    @apply fusion-card overflow-hidden;
}

.deal-pipeline {
    @apply bg-gray-50 rounded-lg p-4;
}
```

## Advanced Features

### 1. **Dark Mode Support**

```blade
<!-- Theme with dark mode toggle -->
<div x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" 
     x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
     :class="{ 'dark': darkMode }">
     
    <!-- Dark mode toggle -->
    <button @click="darkMode = !darkMode" 
            class="p-2 rounded-md bg-gray-200 dark:bg-gray-700">
        <x-phosphor-moon x-show="!darkMode" class="w-5 h-5" />
        <x-phosphor-sun x-show="darkMode" class="w-5 h-5" />
    </button>
    
    <!-- Theme content with dark mode classes -->
    <div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        <!-- Content -->
    </div>
</div>
```

### 2. **Responsive Breakpoints**

```css
/* Custom responsive breakpoints for CRM layouts */
@media (min-width: 768px) {
    .crm-sidebar {
        @apply w-64 flex-shrink-0;
    }
}

@media (min-width: 1024px) {
    .crm-main-content {
        @apply ml-64;
    }
}

@media (min-width: 1280px) {
    .crm-dashboard-grid {
        @apply grid-cols-4;
    }
}
```

### 3. **Animation System**

```css
/* Theme animations */
.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

.slide-in {
    animation: slideIn 0.3s ease-out;
}

.pulse-primary {
    animation: pulse-primary 2s infinite;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { transform: translateX(-100%); }
    to { transform: translateX(0); }
}

@keyframes pulse-primary {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255, 107, 53, 0.7); }
    50% { box-shadow: 0 0 0 10px rgba(255, 107, 53, 0); }
}
```

## Performance Optimization

### 1. **Asset Optimization**

```javascript
// vite.config.js - Theme-aware asset compilation
export default defineConfig({
    build: {
        rollupOptions: {
            input: {
                app: 'resources/css/app.css',
                drift: 'resources/themes/drift/assets/css/app.css',
                anchor: 'resources/themes/anchor/assets/css/app.css',
            }
        }
    }
});
```

### 2. **Component Caching**

```php
// Cache theme components
public function boot()
{
    View::composer('*', function ($view) {
        $theme = $this->getCurrentTheme();
        $view->with('currentTheme', $theme);
    });
}
```

### 3. **Lazy Loading**

```blade
<!-- Lazy load theme assets -->
@pushOnce('styles')
    <link rel="preload" href="{{ asset('themes/' . getCurrentTheme() . '/app.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
@endPushOnce
```

## Development Guidelines

### 1. **Creating New Themes**

```bash
# Create new theme structure
mkdir -p resources/themes/mytheme/{assets/css,assets/js,components,pages,partials}

# Create theme metadata
cat > resources/themes/mytheme/theme.json << EOL
{
  "name": "My Theme",
  "version": "1.0",
  "description": "Custom theme description"
}
EOL
```

### 2. **Component Development**

```blade
<!-- Follow consistent component patterns -->
@props([
    'variant' => 'default',
    'size' => 'md'
])

@php
// Component logic
@endphp

<div {{ $attributes->merge(['class' => $computedClasses]) }}>
    {{ $slot }}
</div>
```

### 3. **Testing Themes**

```php
// Theme testing
test('can switch themes', function () {
    $this->get('/admin/themes')
        ->assertStatus(200);
        
    $this->post('/admin/themes/activate', ['theme' => 'anchor'])
        ->assertRedirect();
        
    $this->assertEquals('anchor', getCurrentTheme());
});
```

## Best Practices

### 1. **Consistency**
- Follow established design patterns within each theme
- Maintain consistent spacing and typography scales
- Use theme variables for colors and sizing

### 2. **Performance**
- Optimize images and assets for web delivery
- Use efficient CSS selectors and minimal nesting
- Implement proper caching strategies

### 3. **Accessibility**
- Ensure proper color contrast ratios
- Include ARIA labels and semantic markup
- Test with screen readers and keyboard navigation

### 4. **Maintainability**
- Document custom components and their usage
- Use semantic class names and consistent naming conventions
- Keep theme-specific logic organized and modular

---

*For component usage examples, see the [Components Guide](./components.md). For styling details, refer to the [Tailwind CSS Documentation](./tailwind_css.md).*