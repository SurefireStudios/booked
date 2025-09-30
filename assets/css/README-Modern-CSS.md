# OverBooked Modern CSS System

This document outlines the modern CSS system implemented in OverBooked v2.5.0, providing developers and theme authors with a comprehensive guide to the new design system.

## Overview

The modern CSS system introduces:
- CSS Custom Properties (CSS Variables) for consistent theming
- Modern color palette based on current design trends
- Improved typography and spacing
- Enhanced animations and transitions
- Better accessibility support
- Responsive design improvements

## CSS Custom Properties

### Color Palette
```css
--overbooked-primary: #3b82f6;        /* Primary blue */
--overbooked-primary-hover: #2563eb;   /* Darker blue for hover states */
--overbooked-primary-light: #dbeafe;   /* Light blue for backgrounds */
--overbooked-secondary: #6366f1;       /* Secondary purple */
--overbooked-success: #10b981;         /* Success green */
--overbooked-warning: #f59e0b;         /* Warning amber */
--overbooked-error: #ef4444;           /* Error red */
--overbooked-info: #06b6d4;            /* Info cyan */
```

### Neutral Colors
```css
--overbooked-gray-50: #f9fafb;   /* Lightest gray */
--overbooked-gray-100: #f3f4f6;  /* Very light gray */
--overbooked-gray-200: #e5e7eb;  /* Light gray */
--overbooked-gray-300: #d1d5db;  /* Medium light gray */
--overbooked-gray-400: #9ca3af;  /* Medium gray */
--overbooked-gray-500: #6b7280;  /* Base gray */
--overbooked-gray-600: #4b5563;  /* Medium dark gray */
--overbooked-gray-700: #374151;  /* Dark gray */
--overbooked-gray-800: #1f2937;  /* Very dark gray */
--overbooked-gray-900: #111827;  /* Darkest gray */
```

### Spacing Scale
```css
--overbooked-space-1: 0.25rem;   /* 4px */
--overbooked-space-2: 0.5rem;    /* 8px */
--overbooked-space-3: 0.75rem;   /* 12px */
--overbooked-space-4: 1rem;      /* 16px */
--overbooked-space-5: 1.25rem;   /* 20px */
--overbooked-space-6: 1.5rem;    /* 24px */
--overbooked-space-8: 2rem;      /* 32px */
--overbooked-space-10: 2.5rem;   /* 40px */
--overbooked-space-12: 3rem;     /* 48px */
```

## Component Classes

### Buttons
```html
<!-- Primary button -->
<button class="booked-button-primary">Book Appointment</button>

<!-- Standard button (default styling applied automatically) -->
<button>Cancel</button>
```

### Cards
```html
<div class="booked-card">
  <div class="booked-card-header">
    <h3>Appointment Details</h3>
  </div>
  <div class="booked-card-body">
    <p>Content goes here...</p>
  </div>
  <div class="booked-card-footer">
    <button class="booked-button-primary">Confirm</button>
  </div>
</div>
```

### Alerts/Notices
```html
<div class="booked-alert booked-alert-success">
  Appointment booked successfully!
</div>

<div class="booked-alert booked-alert-warning">
  Please check your email for confirmation.
</div>

<div class="booked-alert booked-alert-error">
  There was an error processing your request.
</div>

<div class="booked-alert booked-alert-info">
  Your appointment is pending approval.
</div>
```

### Badges/Tags
```html
<span class="booked-badge booked-badge-primary">Confirmed</span>
<span class="booked-badge booked-badge-success">Available</span>
<span class="booked-badge booked-badge-warning">Pending</span>
<span class="booked-badge booked-badge-error">Cancelled</span>
```

### Loading States
```html
<div class="booked-loading">
  <div class="booked-spinner"></div>
  Loading appointments...
</div>
```

## Typography Classes

```html
<h1 class="booked-heading-1">Main Heading</h1>
<h2 class="booked-heading-2">Section Heading</h2>
<h3 class="booked-heading-3">Subsection Heading</h3>

<p class="booked-text-muted">Muted text for less important information</p>
<p class="booked-text-small">Small text for fine print</p>
```

## Layout Utilities

### Grid System
```html
<div class="booked-grid booked-grid-cols-3">
  <div class="booked-card">Item 1</div>
  <div class="booked-card">Item 2</div>
  <div class="booked-card">Item 3</div>
</div>
```

### Flexbox Utilities
```html
<div class="booked-flex booked-items-center booked-justify-between booked-gap-4">
  <h3>Appointment</h3>
  <button class="booked-button-primary">Edit</button>
</div>
```

### Spacing Utilities
```html
<div class="booked-p-6 booked-mb-4">
  Content with padding and margin
</div>
```

## Animation Classes

```html
<!-- Slide animations -->
<div class="booked-animate-slide-in-up">Content slides up</div>
<div class="booked-animate-slide-in-down">Content slides down</div>

<!-- Fade and scale -->
<div class="booked-animate-fade-in-scale">Content fades in with scale</div>
```

## Form Styling

```html
<div class="booked-form-field">
  <label for="appointment-date">Appointment Date</label>
  <input type="date" id="appointment-date" name="appointment_date">
</div>

<div class="booked-form-field">
  <label for="notes">Additional Notes</label>
  <textarea id="notes" name="notes" rows="4"></textarea>
</div>
```

## Customization

### Overriding Colors
To customize the color scheme, override the CSS custom properties:

```css
:root {
  --overbooked-primary: #your-brand-color;
  --overbooked-primary-hover: #your-brand-color-darker;
  --overbooked-primary-light: #your-brand-color-lighter;
}
```

### Dark Mode Support
The system includes basic dark mode support. To enable:

```css
.your-theme-dark-mode {
  --overbooked-gray-50: #1f2937;
  --overbooked-gray-100: #374151;
  /* ... other dark mode overrides */
}
```

## Accessibility Features

- High contrast mode support
- Reduced motion support for users with vestibular disorders
- Proper focus indicators
- ARIA-compliant color contrast ratios
- Screen reader friendly markup

## Browser Support

- Chrome 49+
- Firefox 31+
- Safari 9.1+
- Edge 16+
- Internet Explorer 11 (with graceful degradation)

## Migration Guide

### From Old CSS Classes
- Replace old button classes with new `booked-button-*` classes
- Use new spacing utilities instead of custom margins/padding
- Replace old color references with CSS custom properties
- Update animations to use new animation classes

### Theme Integration
1. Include the modern CSS file after the main OverBooked CSS
2. Override CSS custom properties to match your theme
3. Use the provided utility classes for consistent spacing and layout
4. Test with your theme's existing styles for conflicts

## Performance Notes

- CSS custom properties provide better performance than Sass variables
- Modern animations use `transform` and `opacity` for better performance
- Utility classes reduce CSS bundle size through reusability
- Modern CSS features provide better browser optimization

## Examples

See the `/examples` directory for complete HTML examples using the modern CSS system.
