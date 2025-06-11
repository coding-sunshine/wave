# Vite Build Tool Documentation

*Modern build tool and development server for Fusion CRM v4*

---

## Overview

Vite 6.2 is the modern build tool used in Fusion CRM v4, providing lightning-fast development experience with Hot Module Replacement (HMR) and optimized production builds. It replaces the traditional Laravel Mix workflow with a more efficient and developer-friendly approach.

## Current Implementation

### ✅ Implemented Features

| Feature | Description | Status |
|---------|-------------|--------|
| **Development Server** | Fast dev server with HMR | ✅ IMPLEMENTED |
| **Asset Compilation** | CSS and JavaScript processing | ✅ IMPLEMENTED |
| **Laravel Integration** | Laravel Vite plugin configuration | ✅ IMPLEMENTED |
| **Theme Support** | Multi-theme asset compilation | ✅ IMPLEMENTED |
| **Hot Module Replacement** | Instant updates without page refresh | ✅ IMPLEMENTED |
| **Production Builds** | Optimized production asset bundles | ✅ IMPLEMENTED |

## Configuration

### Vite Configuration (`vite.config.js`)

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Code splitting configuration
                    vendor: ['alpinejs', 'axios'],
                    livewire: ['@livewireui/ui']
                }
            }
        }
    },
    server: {
        host: true,
        port: 5173,
        hmr: {
            host: 'localhost'
        }
    }
});
```

### Package.json Scripts

```json
{
  "scripts": {
    "dev": "vite",
    "build": "vite build"
  },
  "devDependencies": {
    "vite": "^6.2",
    "laravel-vite-plugin": "^1.0",
    "@tailwindcss/forms": "^0.5.7",
    "@tailwindcss/typography": "^0.5.12",
    "alpinejs": "^3.4.2",
    "autoprefixer": "^10.4.19",
    "axios": "^1.8.2",
    "postcss": "^8.4.38",
    "postcss-nesting": "^12.1.1",
    "tailwindcss": "^3.4.17"
  }
}
```

## Asset Structure

### Input Files

```
resources/
├── css/
│   ├── app.css              # Main application styles
│   └── filament/
│       └── admin/
│           └── theme.css    # Filament admin theme
├── js/
│   ├── app.js              # Main application JavaScript
│   └── bootstrap.js        # Application bootstrap
└── themes/
    ├── drift/              # Primary theme assets
    │   ├── css/
    │   ├── js/
    │   └── images/
    └── anchor/             # Alternative theme assets
        ├── css/
        ├── js/
        └── images/
```

### Output Structure

```
public/build/
├── manifest.json           # Asset manifest for Laravel
├── assets/
│   ├── app-[hash].css     # Compiled application CSS
│   ├── app-[hash].js      # Compiled application JavaScript
│   ├── theme-[hash].css   # Theme-specific styles
│   └── vendor-[hash].js   # Vendor libraries chunk
└── hot                    # HMR development indicator
```

## Development Workflow

### 1. **Start Development Server**

```bash
# Start Vite development server
npm run dev

# Or start all services at once
composer run dev
```

This starts:
- Vite dev server on `http://localhost:5173`
- Hot Module Replacement enabled
- Asset watching and compilation
- CSS/JS processing pipeline

### 2. **Development Features**

#### Hot Module Replacement (HMR)
```javascript
// HMR is automatically enabled for:
// - CSS changes (instant updates)
// - JavaScript modules (preserves state)
// - Livewire components (automatic refresh)
// - Alpine.js components (state preservation)
```

#### Asset Processing
```css
/* CSS features available during development */
@import 'tailwindcss/base';
@import 'tailwindcss/components';
@import 'tailwindcss/utilities';

/* PostCSS nesting support */
.component {
    &:hover {
        @apply bg-gray-100;
    }
    
    .nested-element {
        @apply text-sm;
    }
}
```

### 3. **Theme Development**

```bash
# Theme assets are processed automatically
resources/themes/drift/css/     # Theme styles
resources/themes/drift/js/      # Theme scripts
resources/themes/drift/images/  # Theme images
```

## Production Builds

### 1. **Build Command**

```bash
# Create production build
npm run build

# This generates:
# - Minified CSS and JavaScript
# - Asset fingerprinting for cache busting
# - Code splitting for optimal loading
# - Source maps for debugging
```

### 2. **Build Optimization**

#### CSS Optimization
- **Tailwind CSS Purging**: Removes unused utility classes
- **PostCSS Processing**: Modern CSS features compilation
- **Minification**: Compressed CSS output
- **Critical CSS**: Above-the-fold CSS inlining

#### JavaScript Optimization
- **Tree Shaking**: Dead code elimination
- **Code Splitting**: Vendor and application bundles
- **Minification**: Compressed JavaScript output
- **Module Federation**: Shared dependencies optimization

### 3. **Asset Fingerprinting**

```php
// Laravel automatically uses fingerprinted assets
@vite(['resources/css/app.css', 'resources/js/app.js'])

// Generates:
// <link rel="stylesheet" href="/build/assets/app-a1b2c3d4.css">
// <script type="module" src="/build/assets/app-e5f6g7h8.js"></script>
```

## Integration with Laravel

### 1. **Laravel Vite Plugin**

```javascript
// Automatic Laravel integration
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true, // Enable page refresh on Blade changes
        }),
    ],
});
```

### 2. **Blade Integration**

```php
<!-- In your Blade templates -->
<!DOCTYPE html>
<html>
<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <!-- Application content -->
</body>
</html>
```

### 3. **Environment Detection**

```php
// Vite automatically detects environment
if (app()->environment('local')) {
    // Development mode - uses Vite dev server
    // http://localhost:5173/resources/css/app.css
} else {
    // Production mode - uses built assets
    // /build/assets/app-[hash].css
}
```

## Theme System Integration

### 1. **Multi-Theme Support**

```javascript
// Vite configuration for multiple themes
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/themes/drift/css/theme.css',
                'resources/themes/anchor/css/theme.css'
            ]
        }),
    ],
});
```

### 2. **Theme Switching**

```php
<!-- Dynamic theme loading -->
@if(theme() === 'drift')
    @vite(['resources/themes/drift/css/theme.css'])
@else
    @vite(['resources/themes/anchor/css/theme.css'])
@endif
```

### 3. **Theme Assets**

```
public/themes/
├── drift/
│   ├── css/
│   ├── js/
│   └── images/
└── anchor/
    ├── css/
    ├── js/
    └── images/
```

## Performance Features

### 1. **Code Splitting**

```javascript
// Automatic vendor chunk splitting
build: {
    rollupOptions: {
        output: {
            manualChunks: {
                vendor: ['alpinejs', 'axios'],
                ui: ['@headlessui/alpine'],
                charts: ['chart.js']
            }
        }
    }
}
```

### 2. **Lazy Loading**

```javascript
// Dynamic imports for code splitting
const chartModule = () => import('./components/chart.js');
const dashboardModule = () => import('./pages/dashboard.js');
```

### 3. **Asset Optimization**

```javascript
// Build optimizations
build: {
    cssCodeSplit: true,
    sourcemap: true,
    minify: 'terser',
    terserOptions: {
        compress: {
            drop_console: true, // Remove console.log in production
            drop_debugger: true
        }
    }
}
```

## Troubleshooting

### Common Issues

#### 1. **HMR Not Working**
```bash
# Check if Vite dev server is running
npm run dev

# Verify port is accessible
curl http://localhost:5173

# Check for firewall/proxy issues
```

#### 2. **Asset Not Found**
```bash
# Clear Vite cache
rm -rf node_modules/.vite

# Rebuild assets
npm run build

# Clear Laravel cache
php artisan view:clear
php artisan config:clear
```

#### 3. **Slow Build Times**
```javascript
// Optimize build configuration
export default defineConfig({
    build: {
        target: 'es2015', // Adjust target for faster builds
        cssCodeSplit: false, // Disable if not needed
    },
    optimizeDeps: {
        include: ['alpinejs', 'axios'], // Pre-bundle dependencies
    }
});
```

## Advanced Configuration

### 1. **Environment Variables**

```javascript
// Access environment variables
export default defineConfig(({ command, mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    
    return {
        define: {
            __APP_ENV__: JSON.stringify(env.APP_ENV),
        },
        // ... other config
    };
});
```

### 2. **Custom Plugins**

```javascript
// Add custom Vite plugins
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        // Custom plugin for additional processing
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '~': resolve(__dirname, 'resources/css'),
        }
    }
});
```

### 3. **Build Analysis**

```bash
# Analyze bundle size
npm install --save-dev rollup-plugin-visualizer

# Add to vite.config.js
import { visualizer } from 'rollup-plugin-visualizer';

export default defineConfig({
    plugins: [
        laravel(/* ... */),
        visualizer({
            filename: 'build/bundle-analysis.html',
            open: true,
        }),
    ],
});
```

## Integration with Other Tools

### 1. **Tailwind CSS**
```javascript
// Vite automatically processes Tailwind
// through PostCSS configuration
import 'tailwindcss/tailwind.css';
```

### 2. **Alpine.js**
```javascript
// Alpine.js integration
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
```

### 3. **Livewire**
```javascript
// Livewire assets are handled automatically
// by the Laravel Vite plugin
```

## Best Practices

### 1. **Development**
- Keep the Vite dev server running during development
- Use HMR for faster feedback loops
- Organize assets logically in the resources directory
- Use proper import/export statements for better tree shaking

### 2. **Production**
- Always build assets before deployment
- Use asset fingerprinting for cache invalidation
- Monitor bundle sizes and optimize when necessary
- Enable source maps for debugging in staging

### 3. **Performance**
- Split vendor libraries into separate chunks
- Use dynamic imports for route-based code splitting
- Optimize images and other static assets
- Configure proper caching headers for built assets

---

*For integration with specific frontend technologies, see the [Frontend Documentation](./README.md). For backend integration, refer to the [Laravel Integration Guide](../backend_api/README.md).*