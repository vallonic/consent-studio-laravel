<?php

namespace ConsentStudio\Laravel\Tests;

use Illuminate\Support\Facades\Blade;

class BladeDirectivesTest extends TestCase
{
    /** @test */
    public function it_renders_consent_studio_script()
    {
        $output = Blade::render('@consentstudio');

        $this->assertStringContainsString('window.bakery', $output);
        $this->assertStringContainsString('Consent Studio - European CMP', $output);
        $this->assertStringContainsString('consent.studio', $output);
        $this->assertStringContainsString('googleConsentMode', $output);
    }

    /** @test */
    public function it_renders_consent_studio_script_with_config()
    {
        config(['consent-studio.google_consent_mode.enabled' => true]);
        config(['consent-studio.debug' => true]);

        $output = Blade::render('@consentstudio');

        $this->assertStringContainsString('enabled: true', $output);
        $this->assertStringContainsString('debug: true', $output);
    }

    /** @test */
    public function it_transforms_inline_script()
    {
        $blade = <<<'BLADE'
@consent('marketing')
<script>
    fbq('track', 'PageView');
</script>
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('type="text/plain"', $output);
        $this->assertStringContainsString('cs-require="marketing"', $output);
        $this->assertStringContainsString("fbq('track', 'PageView');", $output);
    }

    /** @test */
    public function it_transforms_external_script()
    {
        $blade = <<<'BLADE'
@consent('analytics')
<script src="https://www.google-analytics.com/analytics.js"></script>
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('src=""', $output);
        $this->assertStringContainsString('data-src="https://www.google-analytics.com/analytics.js"', $output);
        $this->assertStringContainsString('cs-require="analytics"', $output);
    }

    /** @test */
    public function it_transforms_iframe()
    {
        $blade = <<<'BLADE'
@consent('marketing')
<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-src="https://www.youtube.com/embed/dQw4w9WgXcQ"', $output);
        $this->assertStringContainsString('cs-require="marketing"', $output);
        $this->assertStringNotContainsString('src="https://www.youtube.com/embed/', $output);
    }

    /** @test */
    public function it_transforms_image()
    {
        $blade = <<<'BLADE'
@consent('analytics')
<img src="https://tracking-pixel.example.com/pixel.gif" alt="Tracking">
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-src="https://tracking-pixel.example.com/pixel.gif"', $output);
        $this->assertStringContainsString('cs-require="analytics"', $output);
        $this->assertStringNotContainsString('src="https://tracking-pixel.example.com/', $output);
    }

    /** @test */
    public function it_transforms_video()
    {
        $blade = <<<'BLADE'
@consent('marketing')
<video src="https://example.com/video.mp4" controls></video>
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-src="https://example.com/video.mp4"', $output);
        $this->assertStringContainsString('cs-require="marketing"', $output);
        $this->assertStringNotContainsString('src="https://example.com/video.mp4"', $output);
    }

    /** @test */
    public function it_handles_different_consent_categories()
    {
        $categories = ['functional', 'analytics', 'marketing', 'neutral'];

        foreach ($categories as $category) {
            $blade = "@consent('{$category}')\n<script>console.log('test');</script>\n@endconsent";
            $output = Blade::render($blade);

            $this->assertStringContainsString("cs-require=\"{$category}\"", $output);
        }
    }

    /** @test */
    public function it_handles_script_with_single_quotes_for_src()
    {
        $blade = <<<'BLADE'
@consent('analytics')
<script src='https://example.com/script.js'></script>
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-src="https://example.com/script.js"', $output);
        $this->assertStringContainsString('cs-require="analytics"', $output);
    }

    /** @test */
    public function it_handles_audio_element()
    {
        $blade = <<<'BLADE'
@consent('marketing')
<audio src="https://example.com/audio.mp3" controls></audio>
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-src="https://example.com/audio.mp3"', $output);
        $this->assertStringContainsString('cs-require="marketing"', $output);
    }

    /** @test */
    public function it_handles_embed_element()
    {
        $blade = <<<'BLADE'
@consent('marketing')
<embed src="https://example.com/content.swf" type="application/x-shockwave-flash">
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-src="https://example.com/content.swf"', $output);
        $this->assertStringContainsString('cs-require="marketing"', $output);
    }

    /** @test */
    public function it_handles_source_element()
    {
        $blade = <<<'BLADE'
@consent('marketing')
<source src="https://example.com/video.mp4" type="video/mp4">
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-src="https://example.com/video.mp4"', $output);
        $this->assertStringContainsString('cs-require="marketing"', $output);
    }

    /** @test */
    public function it_handles_track_element()
    {
        $blade = <<<'BLADE'
@consent('functional')
<track src="https://example.com/subtitles.vtt" kind="subtitles" srclang="en">
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-src="https://example.com/subtitles.vtt"', $output);
        $this->assertStringContainsString('cs-require="functional"', $output);
    }

    /** @test */
    public function it_handles_elements_with_data_attributes_before_src()
    {
        $blade = <<<'BLADE'
@consent('analytics')
<img data-lazy="true" data-id="123" src="https://example.com/image.jpg" alt="Test">
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-lazy="true"', $output);
        $this->assertStringContainsString('data-id="123"', $output);
        $this->assertStringContainsString('data-src="https://example.com/image.jpg"', $output);
        $this->assertStringContainsString('cs-require="analytics"', $output);
        $this->assertStringNotContainsString('src="https://example.com/image.jpg"', $output);
    }

    /** @test */
    public function it_handles_self_closing_img_tag()
    {
        $blade = <<<'BLADE'
@consent('marketing')
<img src="https://example.com/pixel.gif" alt="Tracking" />
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-src="https://example.com/pixel.gif"', $output);
        $this->assertStringContainsString('cs-require="marketing"', $output);
        $this->assertStringContainsString('/>', $output);
    }

    /** @test */
    public function it_handles_self_closing_embed_tag()
    {
        $blade = <<<'BLADE'
@consent('marketing')
<embed src="https://example.com/content.swf" type="application/x-shockwave-flash" />
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-src="https://example.com/content.swf"', $output);
        $this->assertStringContainsString('cs-require="marketing"', $output);
        $this->assertStringContainsString('/>', $output);
    }

    /** @test */
    public function it_handles_attributes_without_whitespace()
    {
        $blade = <<<'BLADE'
@consent('analytics')
<script src="https://example.com/script.js"async defer></script>
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-src="https://example.com/script.js"', $output);
        $this->assertStringContainsString('cs-require="analytics"', $output);
        $this->assertStringContainsString('async', $output);
        $this->assertStringContainsString('defer', $output);
    }

    /** @test */
    public function it_handles_src_with_whitespace_around_equals()
    {
        $blade = <<<'BLADE'
@consent('marketing')
<iframe src = "https://youtube.com/embed/xxx" width="560"></iframe>
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-src="https://youtube.com/embed/xxx"', $output);
        $this->assertStringContainsString('cs-require="marketing"', $output);
    }

    /** @test */
    public function it_preserves_data_attributes_in_order()
    {
        $blade = <<<'BLADE'
@consent('analytics')
<img data-width="100" data-height="200" src="https://example.com/image.jpg" data-caption="Test">
@endconsent
BLADE;

        $output = Blade::render($blade);

        $this->assertStringContainsString('data-width="100"', $output);
        $this->assertStringContainsString('data-height="200"', $output);
        $this->assertStringContainsString('data-src="https://example.com/image.jpg"', $output);
        $this->assertStringContainsString('data-caption="Test"', $output);
        $this->assertStringContainsString('cs-require="analytics"', $output);
    }
}
