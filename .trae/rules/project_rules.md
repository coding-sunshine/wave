# Project Rules

You are an expert in PHP, Laravel, Livewire, AlpineJS, React, Inertia, Pest, and Tailwind.

## 1. Coding Standards

*   **PHP Version:** Use PHP v8.4 features where applicable and beneficial.
*   **Style Guide:** Strictly adhere to PSR-12 coding standards.
*   **Code Formatting:** Follow rules defined in `pint.json`. Run `composer lint` to format.
*   **Type Safety:** Enforce strict types (`declare(strict_types=1);`) and utilize PHPStan for static analysis, including array shapes where appropriate.
*   **Laravel Conventions:** Leverage Laravel's built-in features, helpers, best practices, and conventions.
*   **OOP:** Employ object-oriented programming principles, focusing on SOLID.
*   **Modularity:** Prefer iteration and modularization over code duplication.
*   **Naming:**
    *   Use descriptive variable and method names.
    *   Classes: `PascalCase`
    *   Methods/Variables: `camelCase`
    *   Database Columns/Keys: `snake_case`
*   **Dependency Injection:** Favor dependency injection and Laravel's service container over manual instantiation.
*   **Error Handling & Logging:**
    *   Utilize Laravel's exception handling and logging features.
    *   Create custom exceptions for domain-specific errors when necessary.
    *   Use `try-catch` blocks for expected exceptions that require specific handling.
*   **Validation:** Use Laravel's validation features (Form Requests preferred) for all incoming data.
*   **Middleware:** Implement middleware for request filtering, modification, and cross-cutting concerns (e.g., authentication, authorization).
*   **Database:**
    *   Use Laravel's Eloquent ORM for all database interactions.
    *   Implement proper database migrations and seeders for schema management and test data.
    *   Define database indexes strategically for optimal query performance.

## 2. Project Structure & Architecture

*   **File Management:** Delete `.gitkeep` files when adding the first file to a previously empty directory.
*   **Structure:** Adhere strictly to the existing project folder structure. Do not introduce new top-level or nested directories without team consensus.
*   **Database Queries:** Avoid direct `DB::` facade usage. Use Eloquent models and query builder exclusively (`Model::query()`).
*   **Dependencies:** Do not add, update, or remove composer or npm dependencies without prior approval.
*   **Routing:**
    *   Create separate route files per logical feature (e.g., `routes/users.php`, `routes/products.php`).
    *   Register these feature route files within `routes/web.php` or `routes/api.php`.
    *   Group related routes logically within their respective files.
    *   Use named routes for URL generation.
*   **API Responses:** Use Laravel API Resources for structuring and standardizing API responses.

### 2.1 Directory Conventions

*   **`app/Http/Controllers`**: Controllers should be lean, primarily orchestrating requests, responses, and calls to other services (like Actions).
    *   No abstract/base controllers.
*   **`app/Http/Requests`**: Use Form Requests for validation and authorization logic related to a request.
    *   Name descriptively, often using action verbs (e.g., `CreateUserRequest`, `UpdateProductRequest`, `DeletePostRequest`).
*   **`app/Actions`**: Encapsulate specific business logic units into Action classes.
    *   Use verbs in naming (e.g., `CreateUserAction`, `ProcessPaymentAction`).
    *   Inject Actions into Controllers.
    *   Example:
        ```php
        public function store(CreateTodoRequest $request, CreateTodoAction $action)
        {
            $user = $request->user();
            // Pass validated data, not the whole request
            $todo = $action->handle($user, $request->validated());
            // Return response
        }
        ```
*   **`app/Models`**: Eloquent models representing database tables.
    *   Avoid using `$fillable`. Prefer explicit mass assignment in Actions or Services after validation.
*   **`database/migrations`**: Database schema definitions.
    *   Omit the `down()` method in new migrations unless absolutely necessary for complex rollbacks.

## 3. Testing

*   **Framework:** Use Pest PHP for all tests.
*   **Workflow:**
    *   Run `composer lint` after making changes.
    *   Run `composer test` before finalizing work or creating pull requests.
*   **Test Integrity:** Do not remove existing tests without explicit approval and justification.
*   **Coverage:** All new code (features, bug fixes) must be accompanied by relevant tests.
*   **Factories:** Generate a corresponding `{Model}Factory` with each new Eloquent model.

### 3.1 Test Directory Structure

*   Feature Tests (HTTP, Console):
    *   Console Commands: `tests/Feature/Console`
    *   HTTP Controllers/Routes: `tests/Feature/Http`
*   Unit Tests:
    *   Actions: `tests/Unit/Actions`
    *   Models: `tests/Unit/Models`
    *   Jobs: `tests/Unit/Jobs`
    *   Other specific classes: `tests/Unit/Services`, `tests/Unit/Components`, etc.

### 3.2 Pest Best Practices

*   **Descriptions:** Write clear, descriptive test names using the `it(...)` function (e.g., `it('validates user input on creation and returns validation errors')`).
*   **Datasets:** Use datasets for testing multiple input variations against the same logic efficiently.
    ```php
    it('calculates correct tax amount', function (float $price, float $taxRate, float $expected) {
        expect(calculateTax($price, $taxRate))->toBe($expected);
    })->with([
        [100, 0.10, 10],
        [200, 0.05, 10],
        [50, 0.20, 10],
    ]);
    ```
*   **Higher-Order Tests:** Leverage Pest's higher-order expectations for more concise and readable assertions where appropriate.
    ```php
    expect($user->posts)->toHaveCount(3);
    expect($post->title)->toBe('Hello World');
    expect($response->json())->toHaveKey('data');
    ```
*   **Laravel Helpers:** Utilize Laravel's testing helpers extensively (`actingAs`, `get`, `post`, `assertDatabaseHas`, `assertStatus`, etc.).
*   **Expectations API:** Prefer Pest's expectation API over PHPUnit assertions for improved readability:
    ```php
    // Preferred
    expect($result)->toBe(true);
    expect($array)->toContain('value');
    
    // Instead of
    $this->assertTrue($result);
    $this->assertContains('value', $array);
    ```
*   **Test Structure:** Follow the Arrange-Act-Assert pattern for clear test organization:
    ```php
    it('creates a new user with valid data', function () {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
        ];
        
        // Act
        $response = post('/users', $userData);
        
        // Assert
        $response->assertStatus(201);
        expect(User::where('email', 'john@example.com')->exists())->toBeTrue();
    });
    ```
*   **Mocking:** Use Pest's integration with Mockery for isolating dependencies:
    ```php
    it('sends notification when order is placed', function () {
        // Create mock of notification service
        $notificationService = mock(NotificationService::class);
        $notificationService->shouldReceive('send')->once();
        
        app()->instance(NotificationService::class, $notificationService);
        
        // Trigger notification
        $order = Order::factory()->create();
        (new OrderProcessor())->process($order);
    });
    ```
*   **Architecture Testing:** Enforce code architecture rules:
    ```php
    // tests/Arch/ModelsTest.php
    test('models use soft deletes and timestamp columns')
        ->expect('App\Models')
        ->toUse('Illuminate\Database\Eloquent\SoftDeletes')
        ->toHaveProperties(['timestamps' => true]);
    
    test('controllers only depend on actions, not models directly')
        ->expect('App\Http\Controllers')
        ->not->toUse('App\Models')
        ->toUse('App\Actions');
    ```
*   **Test Groups:** Organize tests into groups for targeted test runs:
    ```php
    it('belongs to feature group', function () {
        // Test logic
    })->group('feature');
    
    // Run: ./vendor/bin/pest --group=feature
    ```
*   **Focus:**
    *   **Unit Tests:** Focus on testing the public API (methods) of individual classes/units in isolation. Mock dependencies.
    *   **Feature Tests:** Focus on testing user flows, component interactions, and request/response cycles through the application stack.
*   **Data Setup:** Use model factories extensively for creating necessary test data preconditions.
*   **Performance:** 
    *   Use the parallel testing feature for faster test execution: `./vendor/bin/pest --parallel`
    *   Avoid unnecessary database operations in unit tests
    *   Use in-memory SQLite database for faster database tests
*   **Test Coverage:** Use Pest's coverage analysis to identify untested code:
    ```bash
    ./vendor/bin/pest --coverage
    ```

## 4. Styling & UI

*   **Framework:** Use Tailwind CSS for all styling.
*   **Design:** Keep the UI minimal and clean, focusing on functionality and user experience.

### 4.1 Tailwind CSS Best Practices

*   **`@apply`:** Use `@apply` sparingly, primarily for extracting highly reusable component-level styles. Prefer utility classes directly in the markup for most cases.
*   **Configuration:** Configure `tailwind.config.js` extensively for project-specific design tokens (colors, spacing, fonts, breakpoints) to ensure consistency.
*   **Optimization:** Ensure PurgeCSS (via Tailwind's `content` option in `tailwind.config.js`) is correctly configured for production builds to remove unused styles.
*   **Readability:** Group related utility classes logically in the markup.
*   **Components:** Create reusable Blade or React components for complex UI elements or repeated patterns instead of duplicating lengthy utility class combinations.
*   **Libraries:** Leverage pre-built components from libraries like Tailwind UI (if licensed) or headless UI libraries (like Headless UI) when appropriate for common patterns (modals, dropdowns, etc.), ensuring they are styled according to the project's design system.
*   **Tailwind CSS v4 Features:**
    *   Use CSS variables for all theme values (e.g., `var(--width-1\/2)`)
    *   Use parentheses for arbitrary value syntax (e.g., `bg-(--my-color)`)
    *   Configure dark mode with the `@custom-variant dark` approach
    *   Use proper responsive breakpoints: `sm`, `md`, `lg`, `xl`, `2xl`
    *   Structure colors and theme variables according to project design system

#### 4.1.1 Tailwind CSS v4 Setup and Configuration

*   **Installation:**
    ```bash
    npm install -D tailwindcss@latest
    npx tailwindcss init
    ```

*   **Configuration File (`tailwind.config.js`):**
    ```javascript
    module.exports = {
      content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app/View/Components/**/*.php',
      ],
      theme: {
        extend: {
          colors: {
            primary: {
              50: 'rgb(var(--color-primary-50) / <alpha-value>)',
              100: 'rgb(var(--color-primary-100) / <alpha-value>)',
              // ... other shades
              900: 'rgb(var(--color-primary-900) / <alpha-value>)',
            },
            // ... other color groups
          },
          fontFamily: {
            sans: ['Inter var', 'sans-serif'],
          },
        },
      },
      plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
      ],
    }
    ```

*   **CSS Variables:**
    ```css
    :root {
      --color-primary-50: 240 249 255;
      --color-primary-100: 224 242 254;
      /* ... other variable definitions */
    }
    ```

*   **Dark Mode Configuration:**
    ```javascript
    // tailwind.config.js
    module.exports = {
      darkMode: 'class', // or 'media' for system preference
      // ...rest of config
    }
    ```

    ```css
    /* In your CSS file */
    @custom-variant dark (&:where(.dark, .dark *));
    ```

    ```html
    <!-- Toggle dark mode with Alpine.js -->
    <button x-data @click="document.documentElement.classList.toggle('dark')">
      Toggle Dark Mode
    </button>
    ```

*   **Layout Patterns:**
    *   Responsive Design:
        ```html
        <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/6">
          <!-- Responsive width element -->
        </div>
        ```

    *   Flexbox:
        ```html
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
          <div class="flex-shrink-0">Logo</div>
          <nav class="flex gap-4">
            <a href="#" class="hover:text-primary-500">Home</a>
            <a href="#" class="hover:text-primary-500">About</a>
            <a href="#" class="hover:text-primary-500">Contact</a>
          </nav>
        </div>
        ```

    *   Grid:
        ```html
        <div class="grid grid-cols-12 gap-4">
          <div class="col-span-12 md:col-span-8">Main Content</div>
          <div class="col-span-12 md:col-span-4">Sidebar</div>
        </div>
        ```

*   **Custom Components:**
    ```css
    @layer components {
      .btn {
        @apply px-4 py-2 rounded font-medium focus:outline-none focus:ring-2 focus:ring-offset-2;
      }
      
      .btn-primary {
        @apply btn bg-primary-600 text-white hover:bg-primary-700 
               focus:ring-primary-500;
      }
      
      .btn-secondary {
        @apply btn bg-gray-200 text-gray-900 hover:bg-gray-300 
               focus:ring-gray-500;
      }
    }
    ```

### 4.2 Flux UI Component Guidelines

*   **Library Requirements:** Ensure compatibility with Flux UI prerequisites (Laravel 10+, Livewire 3.5.19+, Tailwind CSS 4+).
*   **Asset Integration:** Include required Blade directives in layout files:
    ```blade
    <head>
        <!-- Other head content -->
        @fluxAppearance
    </head>
    <body>
        <!-- Site content -->
        @fluxScripts
    </body>
    ```
*   **CSS Configuration:** Properly configure Tailwind CSS to work with Flux:
    ```css
    @import 'tailwindcss';
    @import '../../vendor/livewire/flux/dist/flux.css';
    @custom-variant dark (&:where(.dark, .dark *));
    ```
*   **Component Usage:** Use Flux components consistently throughout the application to maintain UI coherence:
    ```blade
    <x-flux::button>Default Button</x-flux::button>
    <x-flux::input wire:model="search" placeholder="Search..." />
    ```
*   **Customization:** When customization is needed, publish components using `php artisan flux:publish` rather than creating custom components from scratch.
*   **Theme Consistency:** Manage custom theming through CSS variables within the `@theme` block to maintain a consistent design system.
*   **Updates:** Keep Flux updated regularly with `composer update livewire/flux`.
*   **Integration:** Integrate Flux components with Livewire for dynamic interfaces using wire directives.

## 5. Frontend Frameworks & Libraries

### 5.1 Livewire Best Practices

*   **Component Structure:** Keep Livewire components focused on a single responsibility.
*   **Properties:** Use public properties for data binding. Avoid overly complex data structures in properties.
*   **Actions:** Define actions as public methods within the component.
*   **Validation:** Utilize Livewire's real-time validation features.
*   **Events:** Use Livewire's event system for communication between components or between Livewire and AlpineJS.
*   **Performance:** Be mindful of the data transferred between the server and client. Use `wire:model.lazy` or `wire:model.debounce` where appropriate.
*   **Testing:** Write tests for Livewire components using Laravel's testing helpers.
*   **Component Implementation:**
    ```php
    <?php

    declare(strict_types=1);

    namespace App\Livewire;

    use Livewire\Component;
    use App\Models\Post;
    use Livewire\Attributes\Validate;
    use Livewire\Attributes\Locked;

    class CreatePost extends Component
    {
        #[Validate('required|min:3|max:255')]
        public string $title = '';

        #[Validate('required|min:10')]
        public string $content = '';

        #[Locked]
        public int $userId;

        public function mount(int $userId): void
        {
            $this->userId = $userId;
        }

        public function save(): mixed
        {
            $this->validate();

            $post = new Post();
            $post->title = $this->title;
            $post->content = $this->content;
            $post->user_id = $this->userId;
            $post->save();

            return redirect()->to('/posts');
        }

        public function render(): mixed
        {
            return view('livewire.create-post');
        }
    }
    ```

### 5.2 Livewire Volt

*   **Usage:** Volt is a functional API for Livewire that offers a more concise syntax for creating components without separate class files.
*   **Definition:** Create Volt components in a single Blade file with embedded PHP:
    ```blade
    <?php

    use function Livewire\Volt\{state, rules, computed};

    state(['title' => '', 'content' => '']);

    rules(['title' => 'required|min:3', 'content' => 'required|min:10']);

    $save = function () {
        $this->validate();

        $post = new \App\Models\Post();
        $post->title = $this->title;
        $post->content = $this->content;
        $post->user_id = auth()->id();
        $post->save();

        return redirect()->to('/posts');
    };
    ?>

    <div>
        <form wire:submit="save">
            <input type="text" wire:model="title" />
            <div>@error('title') {{ $message }} @enderror</div>

            <textarea wire:model="content"></textarea>
            <div>@error('content') {{ $message }} @enderror</div>

            <button type="submit">Save</button>
        </form>
    </div>
    ```
*   **Features:** Use Volt functions like `state()`, `rules()`, `computed()`, `mount()`, and `watch()` for reactive component behavior.
*   **Benefits:** Simplifies component creation for straightforward UI elements while maintaining Livewire's power.
*   **When to Use:** Consider Volt for smaller, focused components where a full Livewire class might be overkill.

### 5.3 AlpineJS Best Practices

*   **Minimalism:** Use AlpineJS for small, localized interactivity directly within your Blade/Livewire views.
*   **`x-data`:** Define component scope and initial data.
*   **`x-init`:** Initialize component state or run code on load.
*   **`x-on` / `@`:** Handle DOM events.
*   **`x-bind` / `:`:** Bind attributes to data.
*   **`x-model`:** Create two-way data binding.
*   **`x-show` / `x-transition`:** Toggle element visibility with optional transitions.
*   **`x-for`:** Loop over data arrays.
*   **`$dispatch`:** Communicate between Alpine components or with Livewire using browser events.
*   **Keep it Simple:** For complex state management or interactions, consider if a full JavaScript framework (like React/Vue via Inertia) or a more complex Livewire component is a better fit.

#### 5.3.1 Alpine.js Setup and Core Concepts

*   **Installation:**
    ```bash
    npm install alpinejs@3.14.9
    ```

*   **Initialization (in your JavaScript entry point):**
    ```javascript
    import Alpine from 'alpinejs'
    import focus from '@alpinejs/focus'
    import persist from '@alpinejs/persist'

    // Register plugins
    Alpine.plugin(focus)
    Alpine.plugin(persist)

    // Make Alpine available globally
    window.Alpine = Alpine

    // Start Alpine
    Alpine.start()
    ```

*   **Basic Components:**
    ```html
    <div x-data="{ open: false }">
      <button @click="open = !open">Toggle Dropdown</button>
      
      <div x-show="open" 
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0 transform scale-95"
           x-transition:enter-end="opacity-100 transform scale-100"
           x-transition:leave="transition ease-in duration-100"
           x-transition:leave-start="opacity-100 transform scale-100"
           x-transition:leave-end="opacity-0 transform scale-95"
           @click.away="open = false">
        Dropdown content
      </div>
    </div>
    ```

*   **Data Binding:**
    ```html
    <div x-data="{ message: 'Hello World', count: 0 }">
      <input type="text" x-model="message">
      
      <p x-text="message"></p>
      
      <button @click="count++">Increment</button>
      <p>Count: <span x-text="count"></span></p>
    </div>
    ```

*   **Event Handling:**
    ```html
    <div x-data="{ open: false }">
      <button @click="open = !open">Toggle</button>
      <button @click.prevent="handleSubmit()">Submit</button>
      <input @keyup.escape="cancel()">
      <div @click.outside="open = false">
        Modal content
      </div>
    </div>
    ```

*   **Alpine.js 3.14.9 Event Features:**
    ```html
    <!-- Magic $event variable -->
    <button @click="console.log($event.target)">Show Target</button>

    <!-- Custom events with details -->
    <button @click="$dispatch('notification', { message: 'Success!' })">Notify</button>
    <div @notification="showNotification($event.detail.message)">
      <!-- Will handle the notification event -->
    </div>
    ```

*   **Conditionals:**
    ```html
    <div x-data="{ user: { isAdmin: true } }">
      <div x-show="user.isAdmin">Admin Panel</div>
      
      <template x-if="user.isAdmin">
        <div>Full Admin Panel (Only rendered for admins)</div>
      </template>
      
      <template x-if="user.isAdmin">
        <div>Admin content</div>
      </template>
      <template x-else>
        <div>Regular user content</div>
      </template>
    </div>
    ```

*   **Loops:**
    ```html
    <div x-data="{ users: [
      { name: 'John', role: 'Admin' },
      { name: 'Jane', role: 'Editor' },
      { name: 'Bob', role: 'Viewer' }
    ]}">
      <template x-for="(user, index) in users" :key="index">
        <div>
          <span x-text="user.name"></span>
          <span x-text="user.role"></span>
        </div>
      </template>
    </div>
    ```

*   **Computed Properties:**
    ```html
    <div x-data="{
      price: 100,
      quantity: 2,
      get subtotal() {
        return this.price * this.quantity;
      },
      get tax() {
        return this.subtotal * 0.1;
      },
      get total() {
        return this.subtotal + this.tax;
      }
    }">
      <div>Subtotal: $<span x-text="subtotal"></span></div>
      <div>Tax: $<span x-text="tax.toFixed(2)"></span></div>
      <div>Total: $<span x-text="total.toFixed(2)"></span></div>
      
      <input type="number" x-model="quantity">
    </div>
    ```

#### 5.3.2 Alpine.js 3.14.9 Advanced Features

*   **Global Stores:**
    ```javascript
    // Define a global store
    document.addEventListener('alpine:init', () => {
      Alpine.store('darkMode', {
        on: false,
        toggle() {
          this.on = !this.on
          document.documentElement.classList.toggle('dark', this.on)
        }
      })
      
      Alpine.store('cart', {
        items: [],
        count() {
          return this.items.length
        },
        total() {
          return this.items.reduce((sum, item) => sum + item.price, 0)
        },
        addItem(item) {
          this.items.push(item)
        }
      })
    })
    ```

    ```html
    <div x-data>
      <!-- Dark mode toggle -->
      <button @click="$store.darkMode.toggle()">
        <span x-show="$store.darkMode.on">🌙</span>
        <span x-show="!$store.darkMode.on">☀️</span>
      </button>
      
      <!-- Cart indicator -->
      <div>
        Cart: <span x-text="$store.cart.count()"></span> items
        (<span x-text="$store.cart.total()"></span>)
      </div>
      
      <!-- Add to cart button -->
      <button @click="$store.cart.addItem({ id: 1, name: 'Product', price: 29.99 })">
        Add to Cart
      </button>
    </div>
    ```

*   **$data Magic:** Access the current component's data:
    ```html
    <div x-data="{ name: 'John' }">
      <button @click="console.log($data)">Log data</button>
    </div>
    ```

*   **Enhanced $refs:** More flexible reference handling:
    ```html
    <div x-data>
      <input x-ref="inputField">
      <button @click="$refs.inputField.focus()">Focus</button>
    </div>
    ```

*   **Plugin Ecosystem:**
    ```html
    <!-- Focus Management (requires @alpinejs/focus plugin) -->
    <div x-data x-trap="open">
      <!-- Focus will be trapped inside this div when open is true -->
    </div>
    
    <!-- Persistent state (requires @alpinejs/persist plugin) -->
    <div x-data="{ count: $persist(0) }">
      <button @click="count++">Increment (stays after refresh)</button>
      <span x-text="count"></span>
    </div>
    ```

*   **Deferred Loading:**
    ```html
    <div x-data x-init="$nextTick(() => { /* Deferred work */ })">
      <!-- Component content -->
    </div>
    ```

#### 5.3.3 Alpine.js and Livewire Integration

*   **Combined Usage:**
    ```html
    <div x-data="{ open: false }" wire:poll.15s="updateComments">
      <button @click="open = !open" wire:click="markAsRead">
        Comments (<span x-text="$wire.unreadCount"></span>)
      </button>
      
      <div x-show="open" wire:loading.class="opacity-50">
        <!-- Comments list rendered by Livewire -->
        @foreach ($comments as $comment)
          <div wire:key="{{ $comment->id }}">{{ $comment->body }}</div>
        @endforeach
      </div>
    </div>
    ```

*   **Reusable Blade Components:**
    ```php
    // app/View/Components/Alert.php
    namespace App\View\Components;

    use Illuminate\View\Component;

    class Alert extends Component
    {
        public $type;
        public $message;
        
        public function __construct($type = 'info', $message = '')
        {
            $this->type = $type;
            $this->message = $message;
        }
        
        public function render()
        {
            return view('components.alert');
        }
    }
    ```

    ```html
    <!-- resources/views/components/alert.blade.php -->
    <div
        x-data="{ show: true }"
        x-show="show"
        x-transition
        class="rounded-lg p-4 mb-4 {{ $type === 'error' ? 'bg-red-100 text-red-700' : 
        ($type === 'success' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700') }}"
    >
        <div class="flex justify-between items-center">
            <div>{{ $message ?? $slot }}</div>
            <button @click="show = false" class="text-gray-500 hover:text-gray-800">
                &times;
            </button>
        </div>
    </div>
    ```

    ```html
    <!-- Usage -->
    <x-alert type="success" message="Operation completed successfully!" />

    <x-alert type="error">
      Something went wrong. Please try again.
    </x-alert>
    ```

### 5.4 Filament

*   **Purpose:** Filament is an admin panel and application framework built on top of TALL stack (Tailwind, Alpine, Laravel, Livewire).
*   **Resources:** Use Filament Resources to rapidly create admin interfaces for your Eloquent models:
    ```bash
    php artisan make:filament-resource User
    ```
*   **Form Components:** Use Filament's form components for rapid development:
    ```php
    use Filament\Forms\Components\TextInput;
    use Filament\Forms\Components\Select;
    use Filament\Forms\Components\DatePicker;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                    
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                    
                DatePicker::make('birth_date')
                    ->nullable(),
                    
                Select::make('role')
                    ->options([
                        'admin' => 'Administrator',
                        'user' => 'User',
                    ])
                    ->required(),
            ]);
    }
    ```
*   **Table Customization:** Configure tables with filters, actions, and bulk actions:
    ```php
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                    
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'primary' => 'user',
                        'danger' => 'admin',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Administrator',
                        'user' => 'User',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
    ```
*   **Layout Components:** Structure forms using layout components:
    ```php
    use Filament\Forms\Components\Card;
    use Filament\Forms\Components\Grid;
    use Filament\Forms\Components\Section;
    use Filament\Forms\Components\Tabs;

    Card::make()
        ->schema([
            Grid::make(2)
                ->schema([
                    TextInput::make('first_name')->required(),
                    TextInput::make('last_name')->required(),
                ]),
            
            Section::make('Contact Information')
                ->schema([
                    TextInput::make('email')->email()->required(),
                    TextInput::make('phone')->tel()->nullable(),
                ]),
        ])
    ```
*   **Security:** Apply proper authorization policies for Filament resources to prevent unauthorized access.
*   **Custom Pages:** Create custom admin pages using the Filament page builder:
    ```bash
    php artisan make:filament-page Dashboard
    ```
*   **Testing:** Write tests for Filament resources and pages using Pest with Livewire testing helpers.

## 6. Task Completion Requirements

*   **Asset Compilation:** Recompile frontend assets (`npm run build` or `npm run dev`) after making any CSS or JavaScript changes.
*   **Rule Adherence:** Ensure all rules outlined in this document are followed before marking a task or pull request as complete/ready for review.

## 7. Performance & Monitoring

*   **Development:** Use Laravel Telescope during development for debugging requests, queries, jobs, exceptions, etc.
*   **Queues:** Use Laravel Horizon for managing and monitoring background queues in production and staging environments.

## 8. Frontend Development (React & Inertia)

### 8.1 React Best Practices

*   **Core Principles:** Follow official React guidelines and best practices.
*   **Components:** Use functional components and Hooks (useState, useEffect, useContext, etc.).
*   **Structure:** Structure components logically. Consider patterns like container/presentational if complexity warrants it.
*   **Type Checking:** Use PropTypes or TypeScript for component prop type checking.
*   **State Management:**
    *   Use `useState` for simple local component state.
    *   Use `useReducer` for more complex local state logic.
    *   Use Context API for sharing state across component trees where prop drilling becomes excessive.
    *   Consider dedicated state management libraries (e.g., Zustand, Redux Toolkit) only for complex global state scenarios after team discussion.
*   **Performance Optimization:**
    *   Use `React.memo` to memoize functional components.
    *   Use `useCallback` to memoize callback functions passed to optimized child components.
    *   Use `useMemo` to memoize expensive calculations.
*   **Asynchronous Operations:** Handle asynchronous operations (e.g., API calls) correctly within `useEffect`, including proper cleanup logic to prevent memory leaks.
*   **Error Handling:** Implement React Error Boundaries to catch rendering errors in component subtrees and display fallback UI.
*   **Cursor Pagination:**
    *   Fetch the initial data set with a specified limit.
    *   Store the `next_cursor` value received from the API response (e.g., in component state).
    *   On a 'load more' trigger (e.g., button click, scroll event), make a subsequent API request including the stored cursor and limit.
    *   Append the newly fetched items to the existing list in the component's state.
    *   Provide clear visual indicators for loading states and when the end of the list is reached (i.e., when `next_cursor` is null).

### 8.2 Inertia.js Best Practices

*   **Core Integration:** Leverage Inertia's features for building single-page applications with server-side routing and controllers.
*   **Shared Data:** Use `Inertia::share` in middleware (e.g., `HandleInertiaRequests`) to provide data needed globally across pages (e.g., authenticated user, flash messages, permissions).
*   **Partial Reloads:** Utilize partial reloads (`only` option in `Inertia::render` or `inertia.visit`) to optimize subsequent page visits by fetching only the necessary data props.
*   **Forms:** Handle form submissions using Inertia's form helper (`useForm` hook) for simplified state management, validation error handling, and progress indication.
*   **Progress Indicators:** Implement progress indicators (e.g., using `nprogress` integrated with Inertia's events) for visual feedback during page navigation and form submissions.
*   **Page Structure:** Organize Inertia page components within the `resources/js/Pages` directory, mirroring the controller/route structure where logical.
*   **URL Generation:** Use Laravel's named routes (`route()` helper in PHP/Blade, Ziggy library in JS) for generating URLs passed to Inertia links or visits.
*   **Cursor Pagination (Controller & Props):**
    *   The Laravel controller should fetch the initial paginated data using cursor pagination (e.g., `Model::cursorPaginate($limit)`).
    *   Pass the paginated data (including items, `next_cursor_url`, and potentially `path`, `per_page`) as props to the Inertia view using `Inertia::render`.
    *   The React component receives these props.
    *   For 'load more', use `Inertia.visit()` or an Inertia Link (`<Link>`) pointing to the `next_cursor_url`, potentially using options like `{ preserveState: true, preserveScroll: true, only: ['itemsDataProp'] }` to fetch and append data smoothly without a full page reload.
