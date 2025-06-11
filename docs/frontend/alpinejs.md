# AlpineJS Guide for Wave CRM

*A complete reference for implementing lightweight JavaScript interactivity in the Wave SaaS starter kit (Laravel 12, PHP 8.3).*

---

## 1. What is AlpineJS?

[Alpine.js](https://alpinejs.dev) is a lightweight JavaScript framework for adding interactivity to your HTML with minimal effort. It provides:

* Declarative reactivity through HTML attributes
* Component-like abstractions without complex build steps
* Seamless integration with backend frameworks like Laravel
* Small footprint (~10KB gzipped) for excellent performance
* Full compatibility with Livewire components
* Event handling, conditional rendering, and transitions

Wave uses AlpineJS for frontend interactivity that doesn't require the full complexity of React or Vue.

---

## 2. Installation (already configured)

AlpineJS comes pre-configured with Wave. The core dependency is:

```bash
npm install alpinejs
```

Wave ships with `alpinejs:^3.4.2` in `package.json`.

---

## 3. Configuration

### 3.1 Including Alpine

Alpine is automatically included in the main JavaScript file and initialized:

```js
// resources/js/app.js
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
```

### 3.2 Global Alpine Stores (Optional)

For sharing state across components:

```js
// resources/js/app.js
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Define a global store for notifications
Alpine.store('notifications', {
    items: [],
    add(message, type = 'info') {
        this.items.push({ message, type, id: Date.now() });
    },
    remove(id) {
        this.items = this.items.filter(item => item.id !== id);
    }
});

Alpine.start();
```

---

## 4. Basic Usage

### 4.1 Simple Alpine Component

```html
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle Menu</button>
    
    <div x-show="open" x-transition>
        This content will show/hide when the button is clicked.
    </div>
</div>
```

### 4.2 Data Binding

```html
<div x-data="{ name: '', email: '' }">
    <input type="text" x-model="name" placeholder="Name">
    <input type="email" x-model="email" placeholder="Email">
    
    <div>
        <p>Name: <span x-text="name || 'Not provided'"></span></p>
        <p>Email: <span x-text="email || 'Not provided'"></span></p>
    </div>
</div>
```

### 4.3 Event Handling

```html
<div x-data="{ count: 0 }">
    <button @click="count++">Increment</button>
    <button @click="count--">Decrement</button>
    
    <p>The count is: <span x-text="count"></span></p>
</div>
```

---

## 5. Advanced Features

### 5.1 Creating Reusable Components

Using Alpine's `x-data` to create reusable components:

```html
<div x-data="dropdown()">
    <button @click="toggle">Open Dropdown</button>
    
    <div x-show="open" @click.outside="close" x-transition>
        Dropdown content here
    </div>
</div>

<script>
    function dropdown() {
        return {
            open: false,
            toggle() {
                this.open = !this.open
            },
            close() {
                this.open = false
            }
        }
    }
</script>
```

### 5.2 Fetching Data with Alpine

```html
<div x-data="loadUsers()">
    <button @click="fetch" :disabled="loading">
        <span x-show="loading">Loading...</span>
        <span x-show="!loading">Load Users</span>
    </button>
    
    <div x-show="error" x-text="error" class="text-red-500"></div>
    
    <ul x-show="users.length > 0">
        <template x-for="user in users" :key="user.id">
            <li x-text="user.name"></li>
        </template>
    </ul>
</div>

<script>
    function loadUsers() {
        return {
            users: [],
            loading: false,
            error: null,
            async fetch() {
                this.loading = true;
                this.error = null;
                
                try {
                    const response = await fetch('/api/users');
                    if (!response.ok) throw new Error('Failed to load users');
                    this.users = await response.json();
                } catch (e) {
                    this.error = e.message;
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
```

### 5.3 Working with Transitions

```html
<div x-data="{ expanded: false }">
    <button @click="expanded = !expanded">Toggle Content</button>
    
    <div
        x-show="expanded"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-90"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-90"
    >
        Animated content here
    </div>
</div>
```

---

## 6. Wave Integration Patterns

### 6.1 AlpineJS with Livewire

Combining Livewire server-side logic with Alpine client-side interactions:

```html
<div>
    <!-- Livewire component root -->
    <div wire:loading>Loading...</div>
    
    <!-- Alpine integration -->
    <div x-data="{ showOptions: false }">
        <button @click="showOptions = !showOptions" wire:click="loadOptions">
            Toggle Options
        </button>
        
        <div x-show="showOptions" x-transition>
            <!-- Livewire-provided data -->
            @foreach($options as $option)
                <div wire:click="selectOption({{ $option->id }})">
                    {{ $option->name }}
                </div>
            @endforeach
        </div>
    </div>
</div>
```

### 6.2 Using Alpine in Blade Components

Creating reusable UI components:

```html
<!-- resources/views/components/dropdown.blade.php -->
<div x-data="{ open: false }">
    <button @click="open = !open" class="dropdown-toggle">
        {{ $trigger }}
    </button>
    
    <div 
        x-show="open" 
        @click.outside="open = false" 
        class="dropdown-menu"
        x-transition
    >
        {{ $slot }}
    </div>
</div>
```

Usage:

```html
<x-dropdown>
    <x-slot name="trigger">
        User Menu
    </x-slot>
    
    <a href="/profile">Profile</a>
    <a href="/settings">Settings</a>
    <a href="/logout">Logout</a>
</x-dropdown>
```

### 6.3 Enhancing Forms

Improving form UX with Alpine:

```html
<form x-data="{ 
    submitting: false,
    error: null,
    success: false,
    async submit() {
        this.submitting = true;
        this.error = null;
        this.success = false;
        
        try {
            const response = await fetch('/api/contact', {
                method: 'POST',
                body: new FormData(this.$el),
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                }
            });
            
            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'An error occurred');
            }
            
            this.success = true;
            this.$el.reset();
        } catch (e) {
            this.error = e.message;
        } finally {
            this.submitting = false;
        }
    }
}" @submit.prevent="submit">
    <div x-show="error" x-text="error" class="alert alert-danger"></div>
    <div x-show="success" class="alert alert-success">Message sent successfully!</div>
    
    <!-- Form fields -->
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <textarea name="message" required></textarea>
    
    <button type="submit" :disabled="submitting">
        <span x-show="submitting">Sending...</span>
        <span x-show="!submitting">Send Message</span>
    </button>
</form>
```

---

## 7. Testing Alpine Components

### 7.1 Component Testing

Using Laravel Dusk for testing Alpine components:

```php
<?php

use Laravel\Dusk\Browser;

test('dropdown opens and closes correctly', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/page-with-dropdown')
                ->assertDontSee('Dropdown content here')
                ->click('@dropdown-toggle')
                ->assertSee('Dropdown content here')
                ->click('body')
                ->assertDontSee('Dropdown content here');
    });
});
```

### 7.2 Unit Testing Alpine Logic

Testing Alpine functions with Jest:

```js
// resources/js/dropdown.js
export default function dropdown() {
    return {
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        }
    };
}

// tests/js/dropdown.test.js
import dropdown from '../../resources/js/dropdown';

describe('Dropdown component', () => {
    test('toggle changes open state', () => {
        const component = dropdown();
        expect(component.open).toBe(false);
        
        component.toggle();
        expect(component.open).toBe(true);
        
        component.toggle();
        expect(component.open).toBe(false);
    });
    
    test('close sets open to false', () => {
        const component = dropdown();
        component.open = true;
        
        component.close();
        expect(component.open).toBe(false);
    });
});
```

---

## 8. Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| Alpine not initializing | Check for JavaScript errors in console |
| `x-data` not working | Verify Alpine is imported before use |
| Event handlers not firing | Check for typos in event names |
| Reactivity issues | Ensure you're not using const/let variables outside Alpine's scope |
| Performance problems | Minimize watcher usage and prefer x-if over x-show for large DOM trees |

For further assistance, see [Alpine.js documentation](https://alpinejs.dev/start-here) or the [Alpine.js GitHub repository](https://github.com/alpinejs/alpine).
