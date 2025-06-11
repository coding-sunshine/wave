# Blade Phosphor Icons Documentation

*Modern icon library integration for Fusion CRM v4*

---

## Overview

Fusion CRM v4 uses Blade Phosphor Icons v2.0, providing access to over 1,000 modern, consistent icons through the Blade templating system. This implementation offers a clean, efficient way to use icons throughout the application with automatic optimization and theme integration.

## Current Implementation

### ✅ Implemented Features

| Feature | Description | Status |
|---------|-------------|--------|
| **Phosphor Icons Library** | 1000+ modern icons in multiple styles | ✅ IMPLEMENTED |
| **Blade Integration** | Seamless Blade component usage | ✅ IMPLEMENTED |
| **Icon Styles** | Regular, fill, duotone, and light variants | ✅ IMPLEMENTED |
| **Size Control** | Flexible sizing with Tailwind classes | ✅ IMPLEMENTED |
| **Color Customization** | Theme-aware icon coloring | ✅ IMPLEMENTED |
| **Performance Optimization** | Optimized SVG output | ✅ IMPLEMENTED |

## Package Configuration

### Installation (Already Configured)

```bash
# Package is already installed
composer require codeat3/blade-phosphor-icons
```

### Configuration (`config/blade-icons.php`)

```php
return [
    'sets' => [
        'phosphor' => [
            'path' => 'vendor/codeat3/blade-phosphor-icons/resources/svg',
            'prefix' => 'phosphor',
        ],
    ],
    'class' => '',
    'attributes' => [
        // Default attributes for all icons
    ],
];
```

### Blade Phosphor Icons Config (`config/blade-phosphor-icons.php`)

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Prefix
    |--------------------------------------------------------------------------
    */
    'prefix' => 'phosphor',

    /*
    |--------------------------------------------------------------------------
    | Fallback Icon
    |--------------------------------------------------------------------------
    */
    'fallback' => '',

    /*
    |--------------------------------------------------------------------------
    | Default Set
    |--------------------------------------------------------------------------
    */
    'set' => 'regular', // regular, fill, duotone, light, thin, bold

    /*
    |--------------------------------------------------------------------------
    | Default Class
    |--------------------------------------------------------------------------
    */
    'class' => 'w-5 h-5',

    /*
    |--------------------------------------------------------------------------
    | Default Attributes
    |--------------------------------------------------------------------------
    */
    'attributes' => [
        'fill' => 'currentColor',
    ],
];
```

## Icon Usage

### 1. **Basic Icon Usage**

```blade
<!-- Basic icon usage -->
<x-phosphor-user />

<!-- With specific style -->
<x-phosphor-user-fill />
<x-phosphor-user-duotone />
<x-phosphor-user-light />

<!-- With custom classes -->
<x-phosphor-user class="w-6 h-6 text-blue-500" />

<!-- With attributes -->
<x-phosphor-user 
    class="w-8 h-8" 
    style="color: #ff6b35;" 
    data-tooltip="User Profile" 
/>
```

### 2. **Icon Styles Available**

#### Regular Icons (Default)
```blade
<x-phosphor-house />
<x-phosphor-user />
<x-phosphor-gear />
<x-phosphor-bell />
```

#### Fill Icons
```blade
<x-phosphor-house-fill />
<x-phosphor-user-fill />
<x-phosphor-gear-fill />
<x-phosphor-bell-fill />
```

#### Duotone Icons
```blade
<x-phosphor-house-duotone />
<x-phosphor-user-duotone />
<x-phosphor-gear-duotone />
<x-phosphor-bell-duotone />
```

#### Light Icons
```blade
<x-phosphor-house-light />
<x-phosphor-user-light />
<x-phosphor-gear-light />
<x-phosphor-bell-light />
```

### 3. **Common CRM Icons**

#### User Management
```blade
<!-- Users and contacts -->
<x-phosphor-user class="w-5 h-5" />
<x-phosphor-users class="w-5 h-5" />
<x-phosphor-user-circle class="w-5 h-5" />
<x-phosphor-address-book class="w-5 h-5" />

<!-- User actions -->
<x-phosphor-user-plus class="w-5 h-5" />
<x-phosphor-user-minus class="w-5 h-5" />
<x-phosphor-user-check class="w-5 h-5" />
<x-phosphor-user-x class="w-5 h-5" />
```

#### Property Management
```blade
<!-- Properties -->
<x-phosphor-house class="w-5 h-5" />
<x-phosphor-buildings class="w-5 h-5" />
<x-phosphor-warehouse class="w-5 h-5" />
<x-phosphor-city class="w-5 h-5" />

<!-- Property features -->
<x-phosphor-key class="w-5 h-5" />
<x-phosphor-map-pin class="w-5 h-5" />
<x-phosphor-currency-dollar class="w-5 h-5" />
<x-phosphor-calendar class="w-5 h-5" />
```

#### Communication
```blade
<!-- Communication -->
<x-phosphor-envelope class="w-5 h-5" />
<x-phosphor-phone class="w-5 h-5" />
<x-phosphor-chat class="w-5 h-5" />
<x-phosphor-video-camera class="w-5 h-5" />

<!-- Notifications -->
<x-phosphor-bell class="w-5 h-5" />
<x-phosphor-bell-ringing class="w-5 h-5" />
<x-phosphor-warning class="w-5 h-5" />
<x-phosphor-info class="w-5 h-5" />
```

#### Navigation & UI
```blade
<!-- Navigation -->
<x-phosphor-house class="w-5 h-5" />
<x-phosphor-gear class="w-5 h-5" />
<x-phosphor-chart-line class="w-5 h-5" />
<x-phosphor-folder class="w-5 h-5" />

<!-- Actions -->
<x-phosphor-plus class="w-5 h-5" />
<x-phosphor-minus class="w-5 h-5" />
<x-phosphor-x class="w-5 h-5" />
<x-phosphor-check class="w-5 h-5" />

<!-- Directional -->
<x-phosphor-caret-down class="w-4 h-4" />
<x-phosphor-caret-up class="w-4 h-4" />
<x-phosphor-caret-left class="w-4 h-4" />
<x-phosphor-caret-right class="w-4 h-4" />
```

## Styling and Theming

### 1. **Size Control with Tailwind**

```blade
<!-- Size variations -->
<x-phosphor-user class="w-3 h-3" />   <!-- Extra small -->
<x-phosphor-user class="w-4 h-4" />   <!-- Small -->
<x-phosphor-user class="w-5 h-5" />   <!-- Default -->
<x-phosphor-user class="w-6 h-6" />   <!-- Medium -->
<x-phosphor-user class="w-8 h-8" />   <!-- Large -->
<x-phosphor-user class="w-12 h-12" /> <!-- Extra large -->

<!-- Custom sizes -->
<x-phosphor-user class="w-[18px] h-[18px]" />
<x-phosphor-user style="width: 20px; height: 20px;" />
```

### 2. **Color Customization**

```blade
<!-- Using Tailwind color classes -->
<x-phosphor-user class="w-5 h-5 text-gray-500" />
<x-phosphor-user class="w-5 h-5 text-blue-600" />
<x-phosphor-user class="w-5 h-5 text-green-500" />
<x-phosphor-user class="w-5 h-5 text-red-500" />

<!-- Fusion CRM theme colors -->
<x-phosphor-user class="w-5 h-5 text-orange-500" />  <!-- Primary -->
<x-phosphor-user class="w-5 h-5 text-red-600" />     <!-- Accent -->

<!-- Hover states -->
<x-phosphor-user class="w-5 h-5 text-gray-400 hover:text-gray-600" />

<!-- CSS custom properties -->
<x-phosphor-user 
    class="w-5 h-5" 
    style="color: var(--color-primary);" 
/>
```

### 3. **Theme Integration**

```blade
<!-- Theme-aware icon colors -->
<x-phosphor-user class="w-5 h-5 text-primary" />
<x-phosphor-user class="w-5 h-5 text-secondary" />

<!-- Dark mode support -->
<x-phosphor-user class="w-5 h-5 text-gray-600 dark:text-gray-300" />

<!-- State-based coloring -->
<x-phosphor-check class="w-5 h-5 text-green-600" />  <!-- Success -->
<x-phosphor-x class="w-5 h-5 text-red-600" />        <!-- Error -->
<x-phosphor-warning class="w-5 h-5 text-yellow-600" /> <!-- Warning -->
<x-phosphor-info class="w-5 h-5 text-blue-600" />    <!-- Info -->
```

## Component Integration

### 1. **Button Icons**

```blade
<!-- Button with icon -->
<button class="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-md">
    <x-phosphor-plus class="w-4 h-4" />
    <span>Add Contact</span>
</button>

<!-- Icon-only button -->
<button class="p-2 text-gray-500 hover:text-gray-700 rounded-md hover:bg-gray-100">
    <x-phosphor-gear class="w-5 h-5" />
</button>

<!-- Using with Blade components -->
<x-button>
    <x-phosphor-download class="w-4 h-4 mr-2" />
    Download Report
</x-button>
```

### 2. **Form Input Icons**

```blade
<!-- Input with leading icon -->
<div class="relative">
    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <x-phosphor-magnifying-glass class="w-5 h-5 text-gray-400" />
    </div>
    <input 
        type="text" 
        class="pl-10 pr-3 py-2 border border-gray-300 rounded-md w-full"
        placeholder="Search contacts..."
    />
</div>

<!-- Input with trailing icon -->
<div class="relative">
    <input 
        type="email" 
        class="pr-10 pl-3 py-2 border border-gray-300 rounded-md w-full"
        placeholder="Email address"
    />
    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
        <x-phosphor-envelope class="w-5 h-5 text-gray-400" />
    </div>
</div>
```

### 3. **Navigation Icons**

```blade
<!-- Sidebar navigation -->
<nav class="space-y-1">
    <a href="/dashboard" class="flex items-center px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">
        <x-phosphor-house class="w-5 h-5 mr-3" />
        Dashboard
    </a>
    
    <a href="/contacts" class="flex items-center px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">
        <x-phosphor-users class="w-5 h-5 mr-3" />
        Contacts
    </a>
    
    <a href="/properties" class="flex items-center px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">
        <x-phosphor-buildings class="w-5 h-5 mr-3" />
        Properties
    </a>
</nav>

<!-- Tab navigation -->
<div class="border-b border-gray-200">
    <nav class="flex space-x-8">
        <a href="#overview" class="flex items-center py-2 px-1 border-b-2 border-blue-500 text-blue-600">
            <x-phosphor-chart-line class="w-4 h-4 mr-2" />
            Overview
        </a>
        
        <a href="#details" class="flex items-center py-2 px-1 text-gray-500 hover:text-gray-700">
            <x-phosphor-list class="w-4 h-4 mr-2" />
            Details
        </a>
    </nav>
</div>
```

### 4. **Status Indicators**

```blade
<!-- Status badges with icons -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
    <x-phosphor-check class="w-3 h-3 mr-1" />
    Active
</span>

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
    <x-phosphor-clock class="w-3 h-3 mr-1" />
    Pending
</span>

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
    <x-phosphor-x class="w-3 h-3 mr-1" />
    Inactive
</span>
```

## Advanced Usage

### 1. **Dynamic Icon Selection**

```blade
<!-- Dynamic icon based on data -->
@php
    $statusIcons = [
        'active' => 'phosphor-check',
        'pending' => 'phosphor-clock',
        'inactive' => 'phosphor-x'
    ];
    $iconComponent = $statusIcons[$status] ?? 'phosphor-question';
@endphp

<x-dynamic-component :component="$iconComponent" class="w-4 h-4" />

<!-- Using conditional rendering -->
@switch($contact->status)
    @case('active')
        <x-phosphor-check class="w-4 h-4 text-green-600" />
        @break
    @case('pending')
        <x-phosphor-clock class="w-4 h-4 text-yellow-600" />
        @break
    @default
        <x-phosphor-x class="w-4 h-4 text-red-600" />
@endswitch
```

### 2. **Custom Icon Components**

```blade
<!-- Create reusable icon components -->
<!-- resources/views/components/status-icon.blade.php -->
@props(['status', 'size' => 'w-4 h-4'])

@switch($status)
    @case('success')
        <x-phosphor-check {{ $attributes->merge(['class' => "$size text-green-600"]) }} />
        @break
    @case('warning')
        <x-phosphor-warning {{ $attributes->merge(['class' => "$size text-yellow-600"]) }} />
        @break
    @case('error')
        <x-phosphor-x {{ $attributes->merge(['class' => "$size text-red-600"]) }} />
        @break
    @default
        <x-phosphor-info {{ $attributes->merge(['class' => "$size text-blue-600"]) }} />
@endswitch

<!-- Usage -->
<x-status-icon status="success" size="w-5 h-5" />
<x-status-icon status="warning" class="mr-2" />
```

### 3. **Icon with Alpine.js**

```blade
<!-- Interactive icon with Alpine.js -->
<div x-data="{ expanded: false }">
    <button 
        @click="expanded = !expanded"
        class="flex items-center justify-between w-full px-4 py-2 text-left"
    >
        <span>Expand Section</span>
        <x-phosphor-caret-down 
            class="w-4 h-4 transition-transform duration-200"
            x-bind:class="{ 'rotate-180': expanded }"
        />
    </button>
    
    <div x-show="expanded" x-transition class="mt-2">
        <!-- Expanded content -->
    </div>
</div>

<!-- Loading state icon -->
<div x-data="{ loading: false }">
    <button 
        @click="loading = true; setTimeout(() => loading = false, 2000)"
        class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-md"
    >
        <template x-if="loading">
            <x-phosphor-spinner class="w-4 h-4 mr-2 animate-spin" />
        </template>
        
        <template x-if="!loading">
            <x-phosphor-download class="w-4 h-4 mr-2" />
        </template>
        
        <span x-text="loading ? 'Downloading...' : 'Download'"></span>
    </button>
</div>
```

## Performance Optimization

### 1. **Icon Caching**

```php
// Icons are automatically cached by the Blade Icons package
// Cache location: storage/framework/cache/blade-icons/
```

### 2. **Bundle Optimization**

```html
<!-- Icons are inlined as SVG, no additional HTTP requests -->
<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 256 256">
    <!-- SVG content -->
</svg>
```

### 3. **Conditional Loading**

```blade
<!-- Only include icons when needed -->
@if($showIcon)
    <x-phosphor-user class="w-5 h-5" />
@endif

<!-- Lazy loading with Livewire -->
<div wire:init="loadIcons">
    @if($iconsLoaded)
        <!-- Icons content -->
    @endif
</div>
```

## Common Icon Sets for CRM

### Dashboard Icons
```blade
<x-phosphor-chart-line />      <!-- Analytics -->
<x-phosphor-gauge />           <!-- Metrics -->
<x-phosphor-trend-up />        <!-- Growth -->
<x-phosphor-calendar />        <!-- Schedule -->
<x-phosphor-clock />           <!-- Time -->
```

### Contact Management
```blade
<x-phosphor-user />            <!-- Contact -->
<x-phosphor-users />           <!-- Contacts list -->
<x-phosphor-address-book />    <!-- Address book -->
<x-phosphor-phone />           <!-- Phone -->
<x-phosphor-envelope />        <!-- Email -->
```

### Property Management
```blade
<x-phosphor-house />           <!-- Property -->
<x-phosphor-buildings />       <!-- Properties -->
<x-phosphor-map-pin />         <!-- Location -->
<x-phosphor-key />             <!-- Access -->
<x-phosphor-camera />          <!-- Photos -->
```

### Sales & Deals
```blade
<x-phosphor-handshake />       <!-- Deal -->
<x-phosphor-currency-dollar /> <!-- Price -->
<x-phosphor-chart-bar />       <!-- Performance -->
<x-phosphor-target />          <!-- Goals -->
<x-phosphor-trophy />          <!-- Success -->
```

## Troubleshooting

### Common Issues

#### 1. **Icon Not Displaying**
```blade
<!-- Check icon name spelling -->
<x-phosphor-user />  ✅ Correct
<x-phosphor-users /> ✅ Correct
<x-phosphor-usr />   ❌ Incorrect

<!-- Verify the icon exists -->
@if(view()->exists('components.phosphor-user'))
    <x-phosphor-user />
@endif
```

#### 2. **Styling Not Applied**
```blade
<!-- Ensure classes are properly applied -->
<x-phosphor-user class="w-5 h-5 text-blue-500" />

<!-- Check for CSS conflicts -->
<x-phosphor-user style="width: 20px; height: 20px; color: blue;" />
```

#### 3. **Performance Issues**
```bash
# Clear icon cache
php artisan cache:clear

# Clear view cache
php artisan view:clear

# Rebuild icons
composer dump-autoload
```

## Best Practices

### 1. **Consistency**
- Use consistent icon sizes across similar components
- Maintain color consistency with your design system
- Choose appropriate icon styles (regular, fill, etc.) consistently

### 2. **Accessibility**
```blade
<!-- Add aria-labels for screen readers -->
<x-phosphor-user 
    class="w-5 h-5" 
    aria-label="User profile"
    role="img"
/>

<!-- Use with meaningful text -->
<button aria-label="Delete contact">
    <x-phosphor-trash class="w-4 h-4" />
</button>
```

### 3. **Performance**
- Only include icons that are actually used
- Use consistent sizing to leverage browser caching
- Consider icon sprite sheets for frequently used icons

---

*For integration with other frontend technologies, see the [Frontend Documentation](./README.md). For theme customization, refer to the [Theme System Guide](./themes.md).*