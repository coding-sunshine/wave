# Tailwind CSS Guide for Wave CRM

*A complete reference for implementing and utilizing Tailwind CSS in the Wave SaaS starter kit (Laravel 12, PHP 8.3).*

---

## 1. What is Tailwind CSS?

[Tailwind CSS](https://tailwindcss.com) is a utility-first CSS framework that enables rapid UI development through composable, low-level utility classes. It provides:

* A comprehensive set of utility classes for nearly every CSS property
* Responsive design capabilities with intuitive breakpoint modifiers
* Dark mode support with simple toggle classes
* Component extraction through directives
* Easy customization through configuration
* Integration with modern CSS features
* First-class support for Laravel and modern JavaScript frameworks

Wave uses Tailwind CSS as its primary styling framework for consistent, maintainable, and responsive UI components.

---

## 2. Installation (already configured)

Tailwind CSS comes pre-configured with Wave. The core dependencies are:

```bash
npm install -D tailwindcss postcss autoprefixer
npm install @tailwindcss/forms @tailwindcss/typography
```

Wave ships with these dependencies in `package.json`:
- `tailwindcss: "^3.3.0"`
- `@tailwindcss/forms: "^0.5.2"`
- `@tailwindcss/typography: "^0.5.0"`

---

## 3. Configuration

### 3.1 Configuration File

Wave's Tailwind configuration is located in `tailwind.config.js`:

```js
/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', 'sans-serif'],
            },
            colors: {
                primary: {
                    50: '#f0f9ff',
                    100: '#e0f2fe',
                    200: '#bae6fd',
                    300: '#7dd3fc',
                    400: '#38bdf8',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    800: '#075985',
                    900: '#0c4a6e',
                    950: '#082f49',
                },
                // Add more custom color palettes as needed
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],

    darkMode: 'class',
};
```

### 3.2 PostCSS Configuration

Wave's PostCSS configuration is in `postcss.config.js`:

```js
module.exports = {
    plugins: {
        tailwindcss: {},
        autoprefixer: {},
        'postcss-nesting': {},
    },
};
```

### 3.3 Main CSS File

Tailwind is imported in `resources/css/app.css`:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom styles can be added here */
@layer components {
    .btn-primary {
        @apply py-2 px-4 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-opacity-50 transition-colors;
    }
    
    /* More component classes... */
}
```

---

## 4. Basic Usage

### 4.1 Utility-First Approach

Build components using utility classes directly in HTML:

```html
<div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
    <div class="md:flex">
        <div class="md:shrink-0">
            <img class="h-48 w-full object-cover md:h-full md:w-48" src="/img/building.jpg" alt="Modern building architecture">
        </div>
        <div class="p-8">
            <div class="uppercase tracking-wide text-sm text-indigo-500 font-semibold">Company retreats</div>
            <a href="#" class="block mt-1 text-lg leading-tight font-medium text-black hover:underline">Incredible accommodation for your team</a>
            <p class="mt-2 text-slate-500">Looking to take your team away on a retreat? We have the perfect location.</p>
        </div>
    </div>
</div>
```

### 4.2 Responsive Design

Use responsive modifiers to apply styles at specific breakpoints:

```html
<div class="text-center sm:text-left md:text-right lg:text-center xl:text-justify">
    This text's alignment changes at different screen sizes.
</div>
```

### 4.3 State Variants

Apply styles for different states:

```html
<button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
    Hover or focus me
</button>
```

---

## 5. Advanced Features

### 5.1 Custom Utilities with @apply

Extract repeated utility patterns into reusable components:

```css
/* In resources/css/app.css */
@layer components {
    .card {
        @apply bg-white rounded-xl shadow-md overflow-hidden;
    }
    
    .card-header {
        @apply p-6 border-b border-gray-200;
    }
    
    .card-body {
        @apply p-6;
    }
    
    .card-footer {
        @apply p-6 border-t border-gray-200;
    }
}
```

Usage:

```html
<div class="card">
    <div class="card-header">
        <h3 class="text-xl font-semibold">Card Title</h3>
    </div>
    <div class="card-body">
        <p>Card content goes here...</p>
    </div>
    <div class="card-footer">
        <button class="btn-primary">Submit</button>
    </div>
</div>
```

### 5.2 Dark Mode

Wave is configured to use the `class` strategy for dark mode:

```html
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
    This content adapts to light/dark mode.
</div>
```

Toggle dark mode with JavaScript:

```js
// Toggle dark mode
document.getElementById('theme-toggle').addEventListener('click', function() {
    if (document.documentElement.classList.contains('dark')) {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    } else {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
});

// Check user preference on page load
if (localStorage.getItem('theme') === 'dark' || 
    (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
} else {
    document.documentElement.classList.remove('dark');
}
```

### 5.3 Custom Plugins

Create custom Tailwind plugins for complex utilities:

```js
// In tailwind.config.js
const plugin = require('tailwindcss/plugin');

module.exports = {
    // other config...
    
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        
        // Custom plugin for text shadows
        plugin(function({ addUtilities }) {
            const newUtilities = {
                '.text-shadow-sm': {
                    textShadow: '0 1px 2px rgba(0, 0, 0, 0.05)',
                },
                '.text-shadow': {
                    textShadow: '0 2px 4px rgba(0, 0, 0, 0.1)',
                },
                '.text-shadow-lg': {
                    textShadow: '0 4px 8px rgba(0, 0, 0, 0.15)',
                },
            };
            
            addUtilities(newUtilities, ['responsive', 'hover']);
        }),
    ],
};
```

Usage:

```html
<h1 class="text-shadow-lg hover:text-shadow">This heading has a text shadow</h1>
```

---

## 6. Wave Integration Patterns

### 6.1 Blade Components with Tailwind

Wave includes pre-styled Blade components leveraging Tailwind:

```php
<!-- resources/views/components/button.blade.php -->
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
```

Usage:

```html
<x-button>
    Submit
</x-button>

<x-button class="bg-red-600 hover:bg-red-700">
    Delete
</x-button>
```

### 6.2 Combining with Livewire

Tailwind and Livewire work seamlessly together:

```html
<div>
    <!-- Livewire component -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">Contact Form</h2>
        
        @if($success)
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                Message sent successfully!
            </div>
        @endif
        
        <form wire:submit.prevent="submit">
            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-medium mb-2">Name</label>
                <input wire:model.defer="name" type="text" id="name" 
                    class="w-full border-gray-300 focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 rounded-md shadow-sm">
                @error('name') 
                    <span class="text-red-600 text-sm">{{ $message }}</span> 
                @enderror
            </div>
            
            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                <input wire:model.defer="email" type="email" id="email" 
                    class="w-full border-gray-300 focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 rounded-md shadow-sm">
                @error('email') 
                    <span class="text-red-600 text-sm">{{ $message }}</span> 
                @enderror
            </div>
            
            <div class="mb-4">
                <label for="message" class="block text-gray-700 font-medium mb-2">Message</label>
                <textarea wire:model.defer="message" id="message" rows="4" 
                    class="w-full border-gray-300 focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 rounded-md shadow-sm"></textarea>
                @error('message') 
                    <span class="text-red-600 text-sm">{{ $message }}</span> 
                @enderror
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700"
                    wire:loading.attr="disabled">
                    <svg wire:loading class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Submit
                </button>
            </div>
        </form>
    </div>
</div>
```

### 6.3 Tailwind in Filament Admin

Wave integrates Tailwind with Filament admin components:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->url(static::getResource()::getUrl())
                ->color('secondary')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
```

---

## 7. Testing with Tailwind

### 7.1 Visual Testing

For testing visual aspects of Tailwind components:

```php
<?php

declare(strict_types=1);

use Laravel\Dusk\Browser;

it('correctly displays responsive layout', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/dashboard')
                // Test mobile view (default)
                ->assertVisible('@mobile-menu-button')
                ->assertMissing('@desktop-sidebar')
                
                // Test desktop view
                ->resize(1200, 900)
                ->assertMissing('@mobile-menu-button')
                ->assertVisible('@desktop-sidebar');
    });
});
```

### 7.2 Testing Dark Mode

Testing dark mode functionality:

```php
<?php

declare(strict_types=1);

use Laravel\Dusk\Browser;

it('correctly toggles between dark and light mode', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/dashboard')
                // Start in light mode
                ->assertHasClass('html', function ($classList) {
                    return !in_array('dark', explode(' ', $classList));
                })
                // Click theme toggle
                ->click('@theme-toggle')
                // Check dark mode is active
                ->assertHasClass('html', 'dark')
                // Toggle back to light
                ->click('@theme-toggle')
                // Check light mode is active
                ->assertHasClass('html', function ($classList) {
                    return !in_array('dark', explode(' ', $classList));
                });
    });
});
```

---

## 8. Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| Classes not applying | Check for typos and ensure Tailwind is properly configured |
| Missing custom classes | Verify the class is defined in the correct `@layer` |
| Content not being scanned | Make sure file paths are included in `content` array |
| PurgeCSS removing needed classes | Add the dynamic class pattern to safelist |
| Dark mode not working | Ensure `darkMode: 'class'` is set and toggle logic is working |
| Slow build times | Optimize content patterns and consider using JIT mode |

For further assistance, see [Tailwind CSS documentation](https://tailwindcss.com/docs) or the [Tailwind CSS GitHub repository](https://github.com/tailwindlabs/tailwindcss).
