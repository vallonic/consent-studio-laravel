<?php

namespace ConsentStudio\Laravel;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ConsentStudioServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/consent-studio.php',
            'consent-studio'
        );

        // Register the ConsentStudioManager as a singleton
        $this->app->singleton('consent-studio', function ($app) {
            // Check if we're in a web request context
            try {
                $request = $app->make('request');
            } catch (\Exception $e) {
                // If no request is available (CLI, queue jobs, etc.), pass null
                $request = null;
            }

            return new ConsentStudioManager($request);
        });

        // Load helper functions
        require_once __DIR__.'/helpers.php';
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/consent-studio.php' => config_path('consent-studio.php'),
        ], 'consent-studio-config');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'consent-studio');

        // Publish views (optional)
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/consent-studio'),
        ], 'consent-studio-views');

        // Register Blade directives
        $this->registerBladeDirectives();
    }

    /**
     * Register Blade directives for Consent Studio.
     */
    protected function registerBladeDirectives(): void
    {
        // @consentstudio - Injects the main Consent Studio script
        Blade::directive('consentstudio', function () {
            return "<?php echo view('consent-studio::script', ['config' => config('consent-studio')])->render(); ?>";
        });

        // @consent('category') - Start consent blocking wrapper
        Blade::directive('consent', function ($expression) {
            return "<?php ob_start(); \$__consentCategory = {$expression}; ?>";
        });

        // @endconsent - End consent blocking wrapper and transform HTML
        Blade::directive('endconsent', function () {
            return <<<'PHP'
                <?php
                    $__consentHtml = ob_get_clean();
                    $__consentCategory = $__consentCategory ?? 'functional';

                    // Transform the HTML based on element type
                    $__consentHtml = \ConsentStudio\Laravel\ConsentStudioServiceProvider::transformConsentHtml($__consentHtml, $__consentCategory);

                    echo $__consentHtml;
                ?>
            PHP;
        });
    }

    /**
     * Transform HTML content to add consent blocking attributes.
     *
     * Handles edge cases:
     * - Elements with data-* attributes before src
     * - Self-closing tags (img, embed, source, track)
     * - Single and double quotes
     * - Attributes without whitespace between them
     *
     * @param string $html
     * @param string $category
     * @return string
     */
    public static function transformConsentHtml(string $html, string $category): string
    {
        // Trim whitespace
        $html = trim($html);

        // Check for inline script (no src attribute, including data-src)
        if (preg_match('/<script(?![^>]*(?:data-)?src\s*=)/i', $html)) {
            // Inline script: change type to text/plain and add cs-require
            $html = preg_replace(
                '/<script(?![^>]*\stype\s*=)([^>]*)>/i',
                '<script type="text/plain" cs-require="' . $category . '"$1>',
                $html
            );
            // If type already exists, replace it
            $html = preg_replace(
                '/<script([^>]*)\stype\s*=\s*["\']([^"\']*)["\']([^>]*)>/i',
                '<script$1 type="text/plain" cs-require="' . $category . '"$3>',
                $html
            );
            // Add cs-require if not already added
            if (!str_contains($html, 'cs-require')) {
                $html = preg_replace(
                    '/<script([^>]*)>/i',
                    '<script$1 cs-require="' . $category . '">',
                    $html
                );
            }
        }
        // Check for external script (has src attribute)
        elseif (preg_match('/<script[^>]+src\s*=/i', $html)) {
            // External script: move src to data-src, set src="", add cs-require
            // Pattern handles: data-* attributes before src, optional whitespace around =
            $html = preg_replace_callback(
                '/<script([^>]*?)src\s*=\s*["\']([^"\']+)["\']([^>]*?)(\/?)\s*>/i',
                function ($matches) use ($category) {
                    $before = $matches[1];
                    $srcValue = $matches[2];
                    $after = $matches[3];
                    $selfClosing = $matches[4];

                    // Remove existing cs-require and data-src if present
                    $before = preg_replace('/\s*(?:cs-require|data-src)\s*=\s*["\'][^"\']*["\']/', '', $before);
                    $after = preg_replace('/\s*(?:cs-require|data-src)\s*=\s*["\'][^"\']*["\']/', '', $after);

                    return '<script' . $before . ' src="" data-src="' . $srcValue . '" cs-require="' . $category . '"' . $after . $selfClosing . '>';
                },
                $html
            );
        }
        // Check for iframe (with optional self-closing)
        elseif (preg_match('/<iframe[^>]+src\s*=/i', $html)) {
            $html = preg_replace_callback(
                '/<iframe([^>]*?)src\s*=\s*["\']([^"\']+)["\']([^>]*?)(\/?)\s*>/i',
                function ($matches) use ($category) {
                    $before = $matches[1];
                    $srcValue = $matches[2];
                    $after = $matches[3];
                    $selfClosing = $matches[4];

                    // Remove existing cs-require and data-src if present
                    $before = preg_replace('/\s*(?:cs-require|data-src)\s*=\s*["\'][^"\']*["\']/', '', $before);
                    $after = preg_replace('/\s*(?:cs-require|data-src)\s*=\s*["\'][^"\']*["\']/', '', $after);

                    return '<iframe' . $before . ' data-src="' . $srcValue . '" cs-require="' . $category . '"' . $after . $selfClosing . '>';
                },
                $html
            );
        }
        // Check for img, video, audio, embed, source, track (with self-closing support)
        elseif (preg_match('/<(img|video|audio|embed|source|track)[^>]+src\s*=/i', $html, $tagMatch)) {
            $tag = $tagMatch[1];
            $html = preg_replace_callback(
                '/<' . $tag . '([^>]*?)src\s*=\s*["\']([^"\']+)["\']([^>]*?)(\/?)\s*>/i',
                function ($matches) use ($category, $tag) {
                    $before = $matches[1];
                    $srcValue = $matches[2];
                    $after = $matches[3];
                    $selfClosing = $matches[4];

                    // Remove existing cs-require and data-src if present
                    $before = preg_replace('/\s*(?:cs-require|data-src)\s*=\s*["\'][^"\']*["\']/', '', $before);
                    $after = preg_replace('/\s*(?:cs-require|data-src)\s*=\s*["\'][^"\']*["\']/', '', $after);

                    return '<' . $tag . $before . ' data-src="' . $srcValue . '" cs-require="' . $category . '"' . $after . $selfClosing . '>';
                },
                $html
            );
        }

        return $html;
    }
}
