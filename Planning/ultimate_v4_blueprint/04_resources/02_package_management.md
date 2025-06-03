# Package Management Guide

## Composer Packages

### Core Dependencies
```json
{
    "require": {
        "php": "^8.4",
        "laravel/framework": "^12.0",
        "livewire/livewire": "^3.0",
        "spatie/laravel-permission": "^6.0",
        "spatie/laravel-medialibrary": "^11.0",
        "laravel/horizon": "^5.0",
        "spatie/laravel-multitenancy": "^3.0",
        "webfox/laravel-xero-oauth2": "^4.0",
        "openai-php/client": "^1.0"
    }
}
```

### Development Dependencies
```json
{
    "require-dev": {
        "pestphp/pest": "^2.0",
        "pestphp/pest-plugin-laravel": "^2.0",
        "laravel/pint": "^1.0",
        "laravel/sail": "^1.0",
        "mockery/mockery": "^1.0",
        "nunomaduro/collision": "^7.0",
        "phpunit/phpunit": "^10.0",
        "spatie/laravel-ignition": "^2.0"
    }
}
```

## NPM Packages

### Frontend Dependencies
```json
{
    "dependencies": {
        "@alpinejs/focus": "^3.14.9",
        "@alpinejs/mask": "^3.14.9",
        "@alpinejs/persist": "^3.14.9",
        "alpinejs": "^3.14.9",
        "@tailwindcss/forms": "^4.0",
        "@tailwindcss/typography": "^4.0",
        "autoprefixer": "^10.0",
        "tailwindcss": "^4.0"
    }
}
```

### Development Dependencies
```json
{
    "devDependencies": {
        "@tailwindcss/aspect-ratio": "^4.0",
        "@vitejs/plugin-vue": "^4.0",
        "laravel-vite-plugin": "^1.0",
        "postcss": "^8.0",
        "vite": "^4.0"
    }
}
```

## Package Installation

### Initial Setup
```bash
# Install Composer dependencies
composer install

# Install NPM packages
npm install

# Development setup
php artisan key:generate
php artisan storage:link
php artisan migrate
```

### Development Commands
```bash
# Start development server
php artisan serve

# Watch for asset changes
npm run dev

# Run tests
php artisan test

# Format code
php artisan pint
```

## Package Updates

### Regular Updates
```bash
# Update Composer packages
composer update

# Update NPM packages
npm update

# Update specific package
composer update vendor/package
```

### Version Constraints
- Use `^` for minor version updates
- Use `~` for patch updates only
- Use exact versions for critical packages

## Security Best Practices

### Package Auditing
```bash
# Check for security vulnerabilities
composer audit

# Check NPM packages
npm audit
```

### Dependency Management
- Regularly review and update dependencies
- Monitor security advisories
- Keep lock files in version control
- Use trusted package sources only

## Custom Package Development

### Package Structure
```
package/
├── src/
├── tests/
├── composer.json
├── phpunit.xml
├── README.md
└── LICENSE.md
```

### Development Workflow
1. Create package using composer
2. Develop and test locally
3. Version control with Git
4. Publish to Packagist
5. Implement in projects