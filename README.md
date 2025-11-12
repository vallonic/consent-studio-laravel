# Consent Studio Laravel Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/consent-studio/laravel.svg?style=flat-square)](https://packagist.org/packages/consent-studio/laravel)

Laravel package for [Consent Studio](https://consent.studio) CMP (Consent Management Platform) integration. Easily integrate GDPR-compliant cookie consent management into your Laravel applications.

## Features

- 🇪🇺 **European CMP** - Built in the Netherlands with 100% European-owned infrastructure
- 🚀 **Simple Integration** - Add consent management with just a few lines of code
- ⚙️ **Google Consent Mode v2** - Full support for Google's consent framework
- 🎨 **Smart Blade Directives** - Automatically block content based on consent categories
- 🔧 **Highly Configurable** - Customize all settings via Laravel config

## Installation

Install the package via Composer:

```bash
composer require consent-studio/laravel
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=consent-studio-config
```

This will create a `config/consent-studio.php` file where you can customize your settings.

## Basic Usage

### 1. Add the Consent Studio Script

Add the `@consentstudio` directive to your layout file, typically in the `<head>` section before any other tracking scripts:

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Laravel App</title>

    @consentstudio

    <!-- Your other scripts here -->
</head>
<body>
    @yield('content')
</body>
</html>
```

### 2. Wrap Content That Requires Consent

Use the `@consent` directive to automatically block content until proper consent is given:

```blade
{{-- Marketing scripts --}}
@consent('marketing')
<script>
    fbq('track', 'PageView');
</script>
@endconsent

{{-- Analytics scripts --}}
@consent('analytics')
<script src="https://www.google-analytics.com/analytics.js"></script>
@endconsent

{{-- YouTube embeds --}}
@consent('marketing')
<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>
@endconsent
```

## Consent Categories

The `@consent` directive supports the following categories:

- `functional` - Essential functionality (usually not blocked)
- `analytics` - Analytics and statistics tracking
- `marketing` - Marketing, advertising, and social media
- `neutral` - Neutral category (not assigned to any specific consent type)

**Note:** The directive accepts only a single category string, not an array.

## Reading Consent State in Controllers

You can read the user's consent state in your controllers, middleware, or anywhere in your Laravel application using the `ConsentStudio` facade or the `consent()` helper function.

### Facade Usage

```php
use ConsentStudio\Laravel\Facades\ConsentStudio;

// Check if user has seen the banner
if (ConsentStudio::seenBanner()) {
    // Banner was shown to the user
}

// Get the consent ID
$consentId = ConsentStudio::id(); // Returns string|null

// Check if a specific consent category is granted
if (ConsentStudio::granted('marketing')) {
    // Marketing consent is granted
}

// Alternative method (alias)
if (ConsentStudio::has('analytics')) {
    // Analytics consent is granted
}

// Check if any of multiple categories are granted
if (ConsentStudio::any(['marketing', 'analytics'])) {
    // At least one is granted
}

// Check if all of multiple categories are granted
if (ConsentStudio::all(['functional', 'analytics'])) {
    // Both are granted
}

// Get all granted consent categories
$consents = ConsentStudio::all(); // Returns array: ['functional', 'analytics', 'marketing']

// Get complete consent state
$state = ConsentStudio::state();
// Returns: ['id' => 'xxx', 'seen' => true, 'consents' => ['functional', 'analytics']]
```

### Helper Function Usage

```php
// No arguments: returns the manager instance for chaining
consent()->id(); // 'abc-123-def'
consent()->seenBanner(); // true

// String argument: checks if the category is granted
consent('marketing'); // true or false

// Array argument: checks if any of the categories are granted
consent(['marketing', 'analytics']); // true or false
```

### Practical Examples

**Conditional Logic in Controllers:**

```php
public function index()
{
    // Show different content based on consent
    if (ConsentStudio::granted('marketing')) {
        // Load personalized recommendations
        $recommendations = $this->getPersonalizedRecommendations();
    } else {
        // Load generic content
        $recommendations = $this->getGenericRecommendations();
    }

    return view('home', compact('recommendations'));
}
```

**Middleware Example:**

```php
public function handle($request, Closure $next)
{
    if (!consent('analytics')) {
        // Skip analytics tracking
        config(['analytics.enabled' => false]);
    }

    return $next($request);
}
```

**Conditional View Rendering:**

```php
// In your controller
return view('dashboard', [
    'canShowAds' => ConsentStudio::granted('marketing'),
    'consentId' => ConsentStudio::id(),
]);
```

```blade
{{-- In your Blade view --}}
@if(consent('marketing'))
    <div class="advertisement">
        <!-- Show ads -->
    </div>
@endif
```

### Cookie Details

The consent state is stored in the following cookies:

- `consent-studio__seen` - Whether the user has seen the banner (true/false)
- `consent-studio__consent-id` - The unique consent ID for this user
- `consent-studio__storage` - URL-encoded JSON array of granted consents (e.g., `["functional","analytics","marketing"]`)

All methods automatically handle cookie decoding and edge cases (missing cookies, malformed data, etc.).

### CLI and Queue Jobs

When used in CLI contexts (artisan commands, queue jobs, etc.) where no HTTP request is available, all methods return safe defaults:

- `seenBanner()` returns `false`
- `id()` returns `null`
- `granted()`, `has()`, `any()`, `all()` return `false` or `[]`
- No errors or exceptions are thrown

This ensures your code works reliably in all Laravel contexts without requiring conditional checks.

## Blade Directive Examples

### Inline Scripts

```blade
@consent('marketing')
<script>
    console.log('This will be blocked until marketing consent is granted');
    gtag('event', 'page_view');
</script>
@endconsent
```

**Output:**
```html
<script type="text/plain" cs-require="marketing">
    console.log('This will be blocked until marketing consent is granted');
    gtag('event', 'page_view');
</script>
```

### External Scripts

```blade
@consent('analytics')
<script src="https://www.google-analytics.com/analytics.js"></script>
@endconsent
```

**Output:**
```html
<script src="" data-src="https://www.google-analytics.com/analytics.js" cs-require="analytics"></script>
```

### Iframes (YouTube, Vimeo, etc.)

```blade
@consent('marketing')
<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"
        width="560"
        height="315"
        frameborder="0"
        allowfullscreen>
</iframe>
@endconsent
```

**Output:**
```html
<iframe data-src="https://www.youtube.com/embed/dQw4w9WgXcQ"
        width="560"
        height="315"
        frameborder="0"
        allowfullscreen
        cs-require="marketing">
</iframe>
```

### Images

```blade
@consent('marketing')
<img src="https://tracking-pixel.example.com/pixel.gif" alt="Tracking">
@endconsent
```

**Output:**
```html
<img data-src="https://tracking-pixel.example.com/pixel.gif" alt="Tracking" cs-require="marketing">
```

### Video Elements

```blade
@consent('marketing')
<video src="https://example.com/video.mp4" controls></video>
@endconsent
```

**Output:**
```html
<video data-src="https://example.com/video.mp4" controls cs-require="marketing"></video>
```

## Manual HTML Usage

If you prefer not to use the Blade directives, you can manually add the consent blocking attributes:

### External Scripts
```html
<script src="" data-src="https://analytics.com/script.js" cs-require="analytics"></script>
```

### Inline Scripts
```html
<script type="text/plain" cs-require="marketing">
    fbq('track', 'PageView');
</script>
```

### Iframes
```html
<iframe data-src="https://youtube.com/embed/xxx" cs-require="marketing"></iframe>
```

### Images
```html
<img data-src="https://example.com/image.jpg" cs-require="analytics">
```

## Configuration

The `config/consent-studio.php` file provides full control over Consent Studio's behavior:

### Google Consent Mode

```php
'google_consent_mode' => [
    'enabled' => true,
    'wait_for_update' => 500,  // Milliseconds to wait for consent update
    'ads_data_redaction' => true,
    'url_passthrough' => false,

    'defaults' => [
        [
            'ad_storage' => 'denied',
            'ad_user_data' => 'denied',
            'ad_personalization' => 'denied',
            'analytics_storage' => 'denied',
            'functionality_storage' => 'granted',
            'personalization_storage' => 'granted',
            'security_storage' => 'granted',
        ],
    ],
],
```

### Region-Specific Consent Defaults

You can configure different default consent states for different regions:

```php
'defaults' => [
    [
        'ad_storage' => 'denied',
        'analytics_storage' => 'denied',
        'region' => ['US', 'CA'],  // North America
    ],
    [
        'ad_storage' => 'granted',
        'analytics_storage' => 'granted',
        'region' => ['GB'],  // United Kingdom
    ],
],
```

### Environment Variables

You can also configure settings via environment variables in your `.env` file:

```env
CONSENT_STUDIO_GCM_ENABLED=true
CONSENT_STUDIO_GCM_WAIT=500
CONSENT_STUDIO_GCM_ADS_REDACTION=true
CONSENT_STUDIO_GCM_URL_PASSTHROUGH=false
CONSENT_STUDIO_DEBUG=false
```

### Debug Mode

Enable debug mode during development:

```php
'debug' => env('CONSENT_STUDIO_DEBUG', false),
```

Or in `.env`:
```env
CONSENT_STUDIO_DEBUG=true
```

## Styling Blocked Content

Consent Studio automatically adds the `.insufficient-consent` CSS class to blocked elements. You can style these elements to provide visual feedback:

```css
[cs-require].insufficient-consent {
    filter: blur(10px);
    opacity: 0.5;
    pointer-events: none;
}

iframe[cs-require].insufficient-consent {
    background: #f0f0f0;
    position: relative;
}

iframe[cs-require].insufficient-consent::after {
    content: 'Please accept cookies to view this content';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 16px;
    color: #666;
}
```

## Google Tag Manager Integration

If you're using Google Tag Manager, make sure to load it **after** the Consent Studio script:

```blade
@consentstudio

<!-- Google Tag Manager -->
<script>
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-XXXXXXXX');
</script>
```

## How It Works

### The `@consent` Directive

The `@consent` directive intelligently detects the type of HTML element and applies the appropriate transformation:

1. **External resources** (iframe, script, img, video, audio, embed, source, track):
   - Moves `src` attribute to `data-src`
   - Sets `src=""` (empty)
   - Adds `cs-require="category"` attribute

2. **Inline scripts**:
   - Changes `type` to `type="text/plain"`
   - Adds `cs-require="category"` attribute

3. **Consent Studio activation**:
   - When user grants consent, Consent Studio automatically restores the original `src` values
   - Elements are activated and loaded
   - The `.insufficient-consent` class is removed

## Testing

Run the tests with:

```bash
composer test
```

## Documentation

For more information about Consent Studio CMP:

- [Official Documentation](https://learn.consent.studio)
- [Support](mailto:support@consent.studio)

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or 11.0

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

If you discover any security vulnerabilities or bugs, please email [support@consent.studio](mailto:support@consent.studio).

## Credits

- Built by [Consent Studio](https://consent.studio)
- 🇳🇱 Made in the Netherlands
- 🇪🇺 100% European-owned infrastructure
