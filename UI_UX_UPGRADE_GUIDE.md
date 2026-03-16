# UI/UX Upgrade Guide

Dokumentasi lengkap untuk semua upgrade UI/UX yang telah dilakukan.

## Table of Contents

1. [Toast Notification System](#toast-notification-system)
2. [Loading States & Skeleton Screens](#loading-states--skeleton-screens)
3. [Drag & Drop File Upload](#drag--drop-file-upload)
4. [Progress Indicator](#progress-indicator)
5. [Error Handling](#error-handling)
6. [Dark Mode](#dark-mode)
7. [Empty States](#empty-states)
8. [Mobile Enhancements](#mobile-enhancements)

---

## Toast Notification System

**File:** `/assets/toast.js`

### Features
- SVG icons yang lebih bagus
- Action buttons support
- Pause on hover
- Queue management (max 5 toasts)
- 4 types: success, error, warning, info

### Usage

```javascript
// Basic toast
window.toast.success('Operation completed!');
window.toast.error('Something went wrong');
window.toast.warning('Please check your input');
window.toast.info('New update available');

// With custom options
window.toast.show({
    type: 'success',
    title: 'File Uploaded',
    message: 'Your file has been uploaded successfully',
    duration: 5000,
    closeable: true,
    actions: [
        {
            label: 'View',
            primary: true,
            onClick: () => console.log('View clicked'),
            closeOnClick: true
        },
        {
            label: 'Undo',
            onClick: () => console.log('Undo clicked')
        }
    ]
});

// Clear all toasts
window.toast.clearAll();
```

---

## Loading States & Skeleton Screens

**File:** `/assets/loading.css`

### Components Available

#### 1. Skeleton Text
```html
<div class="skeleton skeleton-text"></div>
<div class="skeleton skeleton-text lg"></div>
<div class="skeleton skeleton-text md"></div>
<div class="skeleton skeleton-text sm"></div>
```

#### 2. Skeleton Cards
```html
<div class="skeleton-card">
    <div class="skeleton skeleton-text lg"></div>
    <div class="skeleton skeleton-text"></div>
    <div class="skeleton skeleton-text sm"></div>
</div>
```

#### 3. Skeleton Stats
```html
<div class="skeleton-stat">
    <div class="skeleton skeleton-text sm"></div>
    <div class="skeleton skeleton-text lg"></div>
    <div class="skeleton skeleton-text"></div>
</div>
```

#### 4. Loading Overlay
```html
<div class="loading-overlay active">
    <div class="loading-spinner"></div>
</div>
```

#### 5. Loading Dots
```html
<div class="loading-dots">
    <div class="loading-dot"></div>
    <div class="loading-dot"></div>
    <div class="loading-dot"></div>
</div>
```

#### 6. Button Loading State
```javascript
button.classList.add('btn-loading');
// After operation
button.classList.remove('btn-loading');
```

---

## Drag & Drop File Upload

**File:** `/assets/dragdrop.js`

### Usage

```javascript
// Create drag & drop zone
const dragDrop = DragDrop.createZone({
    title: 'Upload CSV Files',
    subtitle: 'or <strong>click to browse</strong>',
    accept: '.csv,.xlsx',
    maxSize: 10 * 1024 * 1024, // 10MB
    multiple: true,
    showInfo: true,
    onDrop: (files) => {
        console.log('Files dropped:', files);
    },
    onError: (errors) => {
        console.error('Errors:', errors);
    },
    onChange: (files) => {
        dragDrop.renderFiles(files);
    }
});

// Append to container
document.getElementById('upload-container').appendChild(dragDrop.element);

// Get files
const files = dragDrop.getFiles();

// Clear files
dragDrop.clear();
```

### Example HTML Structure
```html
<div id="upload-container"></div>

<script>
    const dropzone = DragDrop.createZone({
        title: 'Drop CSV files here',
        accept: '.csv',
        maxSize: 5 * 1024 * 1024,
        onDrop: (files) => {
            // Handle files
            uploadFiles(files);
        }
    });

    document.getElementById('upload-container').appendChild(dropzone.element);
</script>
```

---

## Progress Indicator

**File:** `/assets/progress.js`

### Features
- Multi-operation support
- Pause/Resume functionality
- Real-time speed & ETA calculation
- Cancellable operations
- Live logs with timestamps

### Usage

```javascript
// Show progress
const progress = window.progress.show({
    id: 'csv-processing',
    title: 'Processing CSV Files',
    cancellable: true,
    pauseable: true,
    onCancel: () => {
        // Handle cancel
        console.log('Operation cancelled');
    },
    onPause: () => {
        // Handle pause
        console.log('Operation paused');
    },
    onResume: () => {
        // Handle resume
        console.log('Operation resumed');
    }
});

// Update progress
progress.update(50, 100); // 50 of 100 items

// Add log entries
progress.log('Processing file 1.csv', 'info');
progress.log('File processed successfully', 'success');
progress.log('Warning: Large file detected', 'warning');
progress.log('Error processing file', 'error');

// Check if paused
if (progress.isPaused) {
    // Handle paused state
}

// Manually pause/resume
progress.pause();
progress.resume();

// Close progress
progress.close();
```

---

## Error Handling

**File:** `/assets/errors.js`

### Error Types

```javascript
const errorTypes = [
    'NETWORK_ERROR',      // E001 - Retryable
    'VALIDATION_ERROR',   // E002 - Not retryable
    'AUTH_ERROR',         // E003 - Not retryable
    'PERMISSION_ERROR',   // E004 - Not retryable
    'NOT_FOUND',          // E005 - Not retryable
    'SERVER_ERROR',       // E006 - Retryable
    'TIMEOUT_ERROR',      // E007 - Retryable
    'RATE_LIMIT',         // E008 - Retryable
    'FILE_ERROR',         // E009 - Retryable
    'QUOTA_EXCEEDED'      // E010 - Not retryable
];
```

### Usage

#### Modal Error
```javascript
window.errorHandler.showModal({
    type: 'NETWORK_ERROR',
    message: 'Failed to connect to the server',
    details: 'Connection timeout after 30 seconds',
    suggestions: [
        'Check your internet connection',
        'Try refreshing the page',
        'Contact support if the problem persists'
    ],
    onRetry: () => {
        // Retry the operation
        retryOperation();
    },
    onClose: () => {
        // Handle close
        console.log('Error modal closed');
    }
});
```

#### Inline Error
```javascript
const container = document.getElementById('form-container');

window.errorHandler.showInline(container, {
    type: 'VALIDATION_ERROR',
    message: 'Please fill in all required fields',
    closeable: true
});
```

#### Handle Fetch Errors
```javascript
try {
    const response = await fetch('/api/endpoint');
    if (!response.ok) {
        throw new Error('Request failed');
    }
} catch (error) {
    window.errorHandler.handleFetchError(error, () => {
        // Retry function
        retryRequest();
    });
}
```

---

## Dark Mode

**File:** `/assets/darkmode.js`

### Features
- System preference detection
- Smooth theme transitions
- Keyboard shortcut (Ctrl+Shift+L)
- Accessibility (respects prefers-reduced-motion)
- Auto-sync with OS theme changes

### Usage

```javascript
// Toggle theme
window.darkMode.toggle();

// Get current theme
const currentTheme = window.darkMode.currentTheme; // 'dark' or 'light'

// Listen to theme changes
document.addEventListener('themechange', (e) => {
    console.log('Theme changed to:', e.detail.theme);
});
```

### CSS
Theme automatically applies CSS variables:
- `--bg`, `--bg2` - Background colors
- `--card`, `--card2` - Card backgrounds
- `--border`, `--border2` - Border colors
- `--text` - Text color
- `--muted` - Muted text color

---

## Empty States

**File:** `/assets/empty-states.css`

### Examples

#### Basic Empty State
```html
<div class="empty-state">
    <div class="empty-state-icon">
        <svg viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
    </div>
    <div class="empty-state-title">No Data Found</div>
    <div class="empty-state-description">
        You don't have any data yet. Get started by adding your first item.
    </div>
    <div class="empty-state-actions">
        <button class="empty-state-btn empty-state-btn-primary">
            <svg>...</svg>
            Add Item
        </button>
        <button class="empty-state-btn empty-state-btn-secondary">
            Learn More
        </button>
    </div>
</div>
```

#### Empty State with Features
```html
<div class="empty-state">
    <div class="empty-state-badge">
        <svg>...</svg>
        New Feature
    </div>
    <div class="empty-state-icon animation">...</div>
    <div class="empty-state-title">Welcome to Autopilot</div>
    <div class="empty-state-description">...</div>

    <div class="empty-state-features">
        <div class="empty-state-feature">
            <div class="empty-state-feature-icon">
                <svg>...</svg>
            </div>
            <div class="empty-state-feature-title">Fast Processing</div>
            <div class="empty-state-feature-text">Process files in seconds</div>
        </div>
        <!-- More features -->
    </div>

    <div class="empty-state-actions">...</div>
</div>
```

#### Empty State with Steps
```html
<div class="empty-state">
    <div class="empty-state-icon">...</div>
    <div class="empty-state-title">Get Started</div>
    <div class="empty-state-description">Follow these steps to begin</div>

    <div class="empty-state-steps">
        <div class="empty-state-step">
            <div class="empty-state-step-number">1</div>
            <div class="empty-state-step-content">
                <div class="empty-state-step-title">Upload File</div>
                <div class="empty-state-step-text">Select a CSV file to upload</div>
            </div>
        </div>
        <!-- More steps -->
    </div>
</div>
```

---

## Mobile Enhancements

**File:** `/assets/mobile-enhancements.css`

### Features

#### Touch-Friendly Controls
- Minimum 44px touch targets
- Touch feedback animations
- Larger tap areas for icons
- Optimized input field sizes (16px to prevent zoom)

#### Bottom Navigation
```html
<nav class="bottom-nav">
    <a href="#" class="bottom-nav-item active">
        <svg>...</svg>
        <span>Home</span>
    </a>
    <a href="#" class="bottom-nav-item">
        <svg>...</svg>
        <span>Dashboard</span>
    </a>
    <!-- More items -->
</nav>
```

#### Floating Action Button
```html
<button class="fab">
    <svg viewBox="0 0 24 24">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
</button>
```

#### Mobile Sheet Modal
```html
<div class="sheet active">
    <div class="sheet-handle"></div>
    <div class="sheet-content">
        <h3>Modal Title</h3>
        <p>Content here...</p>
    </div>
</div>
```

#### Horizontal Scroll Container
```html
<div class="scroll-container">
    <div class="scroll-item card">Item 1</div>
    <div class="scroll-item card">Item 2</div>
    <div class="scroll-item card">Item 3</div>
</div>
```

#### Mobile Menu Toggle
```html
<button class="mobile-menu-toggle">
    <svg viewBox="0 0 24 24">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<div class="mobile-overlay"></div>
```

---

## Integration Examples

### Complete Form with All Features

```html
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="/assets/main.css">
    <link rel="stylesheet" href="/assets/loading.css">
    <link rel="stylesheet" href="/assets/empty-states.css">
    <link rel="stylesheet" href="/assets/mobile-enhancements.css">
</head>
<body>
    <div class="container">
        <h2>Upload CSV Files</h2>

        <!-- Drag & Drop Zone -->
        <div id="upload-zone"></div>

        <!-- Progress will show here -->

        <!-- Results -->
        <div id="results"></div>
    </div>

    <script src="/assets/toast.js"></script>
    <script src="/assets/progress.js"></script>
    <script src="/assets/errors.js"></script>
    <script src="/assets/dragdrop.js"></script>
    <script src="/assets/darkmode.js"></script>

    <script>
        // Initialize drag & drop
        const dropzone = DragDrop.createZone({
            title: 'Drop CSV files here',
            accept: '.csv',
            maxSize: 10 * 1024 * 1024,
            onDrop: async (files) => {
                // Show progress
                const prog = window.progress.show({
                    id: 'csv-upload',
                    title: 'Uploading CSV Files',
                    cancellable: true,
                    pauseable: true
                });

                try {
                    // Process files
                    for (let i = 0; i < files.length; i++) {
                        prog.update(i + 1, files.length);
                        prog.log(`Processing ${files[i].name}`, 'info');

                        await processFile(files[i]);

                        prog.log(`${files[i].name} processed`, 'success');
                    }

                    prog.close();
                    window.toast.success('All files processed successfully!');

                } catch (error) {
                    prog.close();
                    window.errorHandler.handleFetchError(error, () => {
                        // Retry
                        dropzone.onDrop(files);
                    });
                }
            },
            onError: (errors) => {
                window.toast.error(errors.join(', '), 'Upload Error');
            }
        });

        document.getElementById('upload-zone').appendChild(dropzone.element);
    </script>
</body>
</html>
```

---

## Best Practices

### 1. Toast Notifications
- Use **success** for confirmations
- Use **error** for failures with clear messages
- Use **warning** for important notices
- Use **info** for general updates
- Keep messages concise (max 2 lines)
- Add action buttons when user can take action

### 2. Loading States
- Show skeleton screens for initial page load
- Use spinners for short operations (< 3s)
- Use progress indicators for long operations
- Always provide feedback for user actions

### 3. Error Handling
- Show inline errors for form validation
- Show modal errors for critical failures
- Always provide suggestions for resolution
- Make retryable errors clearly retryable
- Include error codes for support

### 4. Empty States
- Always provide context about why it's empty
- Include clear call-to-action buttons
- Use illustrations when appropriate
- Provide alternative actions or links

### 5. Mobile Design
- Ensure all touch targets are min 44px
- Use bottom navigation for primary actions
- Keep important actions within thumb reach
- Test on real devices, not just responsive mode
- Consider safe areas on iOS devices

---

## File Checklist

Make sure to include these files in your `<head>`:

```html
<!-- Core Styles -->
<link rel="stylesheet" href="/assets/main.css">
<link rel="stylesheet" href="/assets/loading.css">
<link rel="stylesheet" href="/assets/empty-states.css">
<link rel="stylesheet" href="/assets/mobile-enhancements.css">

<!-- Core Scripts -->
<script src="/assets/toast.js"></script>
<script src="/assets/progress.js"></script>
<script src="/assets/errors.js"></script>
<script src="/assets/dragdrop.js"></script>
<script src="/assets/darkmode.js"></script>
```

---

## Browser Support

- Chrome/Edge: 90+
- Firefox: 88+
- Safari: 14+
- iOS Safari: 14+
- Android Chrome: 90+

All components are tested and work on modern browsers with proper fallbacks.

---

## Performance Tips

1. **Lazy load components** - Only load what you need
2. **Use CSS animations** - Better performance than JS
3. **Debounce user inputs** - Prevent excessive updates
4. **Optimize images** - Use appropriate formats and sizes
5. **Minimize repaints** - Batch DOM updates when possible

---

## Accessibility

All components include:
- Proper ARIA labels
- Keyboard navigation support
- Screen reader compatibility
- High contrast mode support
- Reduced motion preferences
- Focus indicators

---

## Support

For issues or questions, check:
1. This documentation
2. Browser console for errors
3. Component source code comments
4. Test examples in the codebase
