# Fusion CRM V4 - Design & Brand Guidelines

This document outlines the comprehensive design and brand guidelines for Fusion CRM V4, ensuring visual and brand consistency throughout the application. These guidelines are based on the Property In A Box brand identity and have been optimized for implementation with the TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire).

## Brand Identity

### Core Brand Values

- **Innovation**: Cutting-edge technology that simplifies real estate processes
- **Empowerment**: Enabling real estate professionals to achieve more with less effort
- **Reliability**: Consistent, dependable platform that supports business growth
- **Professionalism**: Enterprise-grade solution with polished user experience

### Brand Voice

- **Clear & Direct**: Straightforward communication without jargon
- **Solution-Oriented**: Focus on problem-solving and practical benefits
- **Confident**: Authoritative but approachable tone
- **Value-Driven**: Emphasize tangible outcomes and ROI

### Brand Positioning

Fusion CRM is positioned as the premium end-to-end solution for real estate professionals seeking to maximize their business potential through:

- Access to extensive property listings
- High-commission opportunities
- Streamlined client management
- AI-powered tools and automation

## Color Palette

### Primary Colors

| Color | Hex | RGB | Usage |
|-------|-----|-----|-------|
| Orange | `#FF6C2C` | `255, 108, 44` | Primary brand color, CTAs, key UI elements |
| Dark Gray | `#333333` | `51, 51, 51` | Headers, text, footers |
| White | `#FFFFFF` | `255, 255, 255` | Backgrounds, text on dark colors |

### Secondary Colors

| Color | Hex | RGB | Usage |
|-------|-----|-----|-------|
| Light Orange | `#FF8E5E` | `255, 142, 94` | Hover states, secondary elements |
| Gray | `#666666` | `102, 102, 102` | Body text, subtle UI elements |
| Light Gray | `#F5F5F5` | `245, 245, 245` | Background areas, cards |

### Accent Colors

| Color | Hex | RGB | Usage |
|-------|-----|-----|-------|
| Green | `#4CAF50` | `76, 175, 80` | Success states, positive indicators |
| Red | `#F44336` | `244, 67, 54` | Error states, warnings, attention indicators |
| Yellow | `#FFC107` | `255, 193, 7` | Warnings, notifications |

### Gradients

| Gradient | Colors | Usage |
|----------|--------|-------|
| Primary Gradient | `linear-gradient(135deg, #FF6C2C 0%, #FF8E5E 100%)` | CTAs, important sections |
| Gray Gradient | `linear-gradient(135deg, #333333 0%, #555555 100%)` | Headers, feature backgrounds |

## Typography

### Font Families

- **Headings**: Poppins, sans-serif
  - Bold (`700`) for main headings
  - Semi-bold (`600`) for subheadings
  
- **Body**: Inter, sans-serif
  - Regular (`400`) for general text
  - Medium (`500`) for emphasized text
  
- **Monospace**: JetBrains Mono, monospace (for code snippets and technical content)

### Font Sizes

| Element | Size (rem) | Weight | Line Height |
|---------|------------|--------|-------------|
| h1 | 2.5rem (40px) | 700 | 1.2 |
| h2 | 2rem (32px) | 700 | 1.25 |
| h3 | 1.5rem (24px) | 600 | 1.3 |
| h4 | 1.25rem (20px) | 600 | 1.4 |
| h5 | 1.125rem (18px) | 600 | 1.4 |
| h6 | 1rem (16px) | 600 | 1.5 |
| Body | 1rem (16px) | 400 | 1.5 |
| Small | 0.875rem (14px) | 400 | 1.5 |
| Caption | 0.75rem (12px) | 400 | 1.5 |

### Tailwind Implementation

```js
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        'primary': {
          DEFAULT: '#FF6C2C',
          '50': '#FFF5F0',
          '100': '#FFECE0',
          '200': '#FFD4BC',
          '300': '#FFBC97',
          '400': '#FF8E5E',
          '500': '#FF6C2C',
          '600': '#F24A00',
          '700': '#BA3800',
          '800': '#822800',
          '900': '#4A1700',
        },
        'dark': {
          DEFAULT: '#333333',
          '50': '#F5F5F5',
          '100': '#E0E0E0',
          '200': '#C2C2C2',
          '300': '#A3A3A3',
          '400': '#858585',
          '500': '#666666',
          '600': '#525252',
          '700': '#3D3D3D',
          '800': '#292929',
          '900': '#141414',
        },
        'gray': {
          DEFAULT: '#666666',
          '50': '#F5F5F5',
          '100': '#EBEBEB',
          '200': '#D6D6D6',
          '300': '#B8B8B8',
          '400': '#999999',
          '500': '#666666',
          '600': '#525252',
          '700': '#3D3D3D',
          '800': '#292929',
          '900': '#141414',
        },
        'success': '#4CAF50',
        'error': '#F44336',
        'warning': '#FFC107',
      },
      fontFamily: {
        'sans': ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
        'heading': ['Poppins', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
        'mono': ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', 'Liberation Mono', 'Courier New', 'monospace'],
      },
      backgroundImage: {
        'primary-gradient': 'linear-gradient(135deg, #FF6C2C 0%, #FF8E5E 100%)',
        'dark-gradient': 'linear-gradient(135deg, #333333 0%, #555555 100%)',
      },
    },
  },
  // ...
}
```

## UI Components

### Buttons

#### Primary Button
- Background: Orange (`#FF6C2C`)
- Text: White
- Hover: Darker Orange (`#F24A00`)
- Border: None
- Border Radius: 0.375rem (6px)
- Padding: 0.75rem 1.5rem
- Font: Inter Medium (500)
- Text Transform: None

#### Secondary Button
- Background: Transparent
- Text: Dark Gray (`#333333`)
- Border: 2px solid Dark Gray
- Hover: Light gray background (`rgba(51, 51, 51, 0.1)`)
- Border Radius: 0.375rem (6px)
- Padding: 0.75rem 1.5rem
- Font: Inter Medium (500)
- Text Transform: None

#### Tertiary Button
- Background: Transparent
- Text: Dark Gray (`#333333`) or Orange (`#FF6C2C`)
- Border: None
- Hover: Light background (`rgba(51, 51, 51, 0.05)`)
- Padding: 0.5rem 1rem
- Font: Inter Medium (500)
- Text Transform: None

### Forms

#### Input Fields
- Background: White
- Border: 1px solid Gray (`#666666`)
- Border Radius: 0.375rem (6px)
- Focus: 2px Orange (`#FF6C2C`) outline
- Padding: 0.75rem 1rem
- Font: Inter Regular (400)
- Font Size: 1rem (16px)

#### Labels
- Font: Inter Medium (500)
- Color: Dark Gray (`#333333`)
- Margin Bottom: 0.5rem
- Font Size: 0.875rem (14px)

#### States
- Error: Red text (`#F44336`), red border
- Success: Green text (`#4CAF50`), green border
- Disabled: Light gray background (`#F5F5F5`), gray text

### Cards

All cards should have consistent styling with slight variations based on purpose.

#### Feature Card
- Background: White
- Border-Left: 4px solid Orange (`#FF6C2C`)
- Border Radius: 0.5rem (8px)
- Shadow: `0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)`
- Padding: 1.5rem

#### Dashboard Card
- Background: White
- Border-Top: 4px solid Dark Gray (`#333333`)
- Border Radius: 0.5rem (8px)
- Shadow: `0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)`
- Padding: 1.5rem

### Tables

#### Standard Table
- Header Background: Light Gray (`#F5F5F5`)
- Header Text: Dark Gray (`#333333`)
- Header Font: Inter Semi-Bold (600)
- Row Border: 1px solid Light Gray (`#E0E0E0`)
- Alternating Rows: White and Light Gray (`#F8F8F8`)
- Hover: Light orange background (`rgba(255, 108, 44, 0.05)`)

### Icons

Icons should be consistent throughout the application and follow these guidelines:

- Use outlined or filled icons from a single icon set (e.g., Heroicons, Bootstrap Icons)
- Default icon size: 24px for navigation, 20px for buttons, 16px for inline
- Default color: Dark Gray (`#333333`)
- Active/Hover: Orange (`#FF6C2C`)
- Icons in buttons should be 16px and have appropriate spacing from text

## Layout

### Grid System

Use Tailwind's responsive grid system with the following breakpoints:

- Mobile: < 640px
- Tablet: 640px - 1023px
- Desktop: 1024px+

Default container width constraints:
- Small screens: 95% of viewport width
- Medium screens: 90% of viewport width
- Large screens: 1280px

### Spacing System

Follow consistent spacing using Tailwind's default spacing scale:

- Extra small (xs): 0.25rem (4px)
- Small (sm): 0.5rem (8px)
- Medium (md): 1rem (16px)
- Large (lg): 1.5rem (24px)
- Extra large (xl): 2rem (32px)
- 2xl: 2.5rem (40px)
- 3xl: 3rem (48px)

### Vertical Rhythm

- Section spacing: 3rem (48px) minimum between major sections
- Component spacing: 1.5rem (24px) between UI components
- Text spacing: 0.75rem (12px) between text blocks

## Imagery & Graphics

### Property Images

- Aspect ratio: 16:9 or 4:3 for consistency
- Minimum resolution: 1200px wide
- Always include alt text for accessibility
- Use object-fit: cover to maintain aspect ratio
- Use lazy loading for performance

### Icons & Illustrations

- Use SVG format when possible for sharp rendering
- Ensure illustrations match the brand color palette
- Maintain consistent styling across all illustrations
- Minimum touch target size: 44px × 44px for interactive icons

## Animation & Transitions

Keep animations subtle and purposeful:

- Duration: 150ms-300ms for most UI interactions
- Easing: ease-in-out for natural movement
- Hover effects: subtle scale (1.02-1.05) or color changes
- Loading states: use consistent loading indicators
- Page transitions: fade in/out with 200ms duration

## Accessibility

### Color Contrast

- Ensure minimum contrast ratios according to WCAG 2.1 AA standards:
  - 4.5:1 for normal text
  - 3:1 for large text and UI components
- Use tools like WebAIM's Contrast Checker to verify

### Focus States

- All interactive elements must have visible focus states
- Focus styles should be distinct but not visually disruptive
- Use consistent focus indicators throughout the application
- Never remove focus outlines without providing alternatives

### Semantic Markup

- Use proper heading hierarchy (h1-h6)
- Use appropriate ARIA attributes when necessary
- Ensure form elements have associated labels
- Implement proper button vs. link usage

## Responsive Design

### Mobile-First Approach

- Design for mobile devices first, then enhance for larger screens
- Use responsive utilities for layout adjustments
- Consider touch targets: minimum 44px × 44px
- Simplify complex components on smaller screens

### Breakpoint Strategy

Tailor UI components at these key breakpoints:

- Small (sm): 640px
- Medium (md): 768px
- Large (lg): 1024px
- Extra Large (xl): 1280px
- 2XL: 1536px

## Brand Application

### Logo Usage

- Minimum spacing: Allow for proper padding around the logo (equal to 25% of the logo height)
- Size constraints: Never display the logo smaller than 100px wide
- Do not distort, rotate, or alter the logo colors
- Use the appropriate logo version (full color on light backgrounds, white on dark backgrounds)

### Voice & Tone

- **Professional**: Authoritative but not overly formal
- **Helpful**: Focus on problem-solving and guidance
- **Direct**: Clear and concise communication
- **Empowering**: Emphasis on user capabilities and potential outcomes

### Documentation

- All design decisions should be documented in this guide
- New components should follow existing patterns
- Maintain a UI component inventory for reference
- Update this guide as the design system evolves

## Code Examples

### Button Component Example

```html
<!-- Primary Button -->
<button class="bg-primary-500 hover:bg-primary-600 text-white font-medium py-3 px-6 rounded-md transition duration-150">
  Primary Button
</button>

<!-- Secondary Button -->
<button class="border-2 border-dark-500 text-dark-500 hover:bg-dark-50 font-medium py-3 px-6 rounded-md transition duration-150">
  Secondary Button
</button>

<!-- Tertiary Button -->
<button class="text-primary-500 hover:bg-primary-50 font-medium py-2 px-4 transition duration-150">
  Tertiary Button
</button>
```

### Card Component Example

```html
<!-- Feature Card -->
<div class="bg-white rounded-lg shadow-md border-l-4 border-primary-500 p-6">
  <h3 class="font-heading font-semibold text-xl text-dark-500 mb-2">Feature Title</h3>
  <p class="text-gray-500 mb-4">Feature description goes here, explaining the benefits and functionality.</p>
  <a href="#" class="text-primary-500 hover:text-primary-600 font-medium inline-flex items-center">
    Learn more
    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
    </svg>
  </a>
</div>
```

### Form Component Example

```html
<form class="space-y-6">
  <div>
    <label for="name" class="block font-medium text-sm text-dark-500 mb-2">Name</label>
    <input
      type="text"
      id="name"
      class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
      placeholder="Enter your name"
    >
  </div>
  
  <div>
    <label for="email" class="block font-medium text-sm text-dark-500 mb-2">Email</label>
    <input
      type="email"
      id="email"
      class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
      placeholder="Enter your email"
    >
    <p class="mt-1 text-sm text-error-500">Please enter a valid email address.</p>
  </div>
  
  <button type="submit" class="w-full bg-primary-500 hover:bg-primary-600 text-white font-medium py-3 px-6 rounded-md transition duration-150">
    Submit Form
  </button>
</form>
```

### Text Styling Example

```html
<h1 class="font-heading font-bold text-4xl text-dark-500 leading-tight">Main Heading</h1>
<h2 class="font-heading font-bold text-3xl text-dark-500 leading-tight">Subheading</h2>
<p class="font-sans text-base text-gray-700 leading-normal">Body text</p>

<!-- Buttons -->
<button class="bg-primary-500 hover:bg-primary-600 text-white font-medium py-3 px-6 rounded-md transition duration-150">
  Primary Button
</button>

<!-- Info Text -->
<p class="text-sm text-gray-500">Additional information here</p>
```

## Implementation Guidelines

### Development Workflow

1. Start with base components (buttons, inputs, typography)
2. Build compound components (cards, tables, forms)
3. Assemble page layouts using the component library
4. Ensure responsive behavior at all breakpoints
5. Validate accessibility compliance

### Tailwind Configuration

Extend the Tailwind configuration to include all brand colors, font families, and custom utilities:

```js
// Example tailwind.config.js setup
module.exports = {
  theme: {
    extend: {
      // Colors and fonts as defined above
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
```

### Asset Management

- Store all SVG icons in a central directory
- Use consistent naming conventions for all assets
- Optimize images before implementation
- Document usage patterns for common UI components

## Quality Assurance

Before release, verify that all UI components:

1. Render correctly across supported browsers (Chrome, Firefox, Safari, Edge)
2. Are responsive across all target screen sizes
3. Meet WCAG 2.1 AA accessibility standards
4. Maintain visual consistency with these guidelines
5. Use semantic HTML and follow best practices

## Future Considerations

This design system should evolve with the application. Consider these future enhancements:

- Creating a dedicated component library package
- Implementing a visual regression testing suite
- Adding dark mode support
- Expanding the animation and microinteraction guidelines
- Developing additional specialized components for real estate features
