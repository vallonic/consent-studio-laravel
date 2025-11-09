<?php

namespace ConsentStudio\Laravel\Tests;

use ConsentStudio\Laravel\Facades\ConsentStudio;
use Illuminate\Http\Request;

class ConsentStudioTest extends TestCase
{
    /** @test */
    public function it_returns_false_for_seen_banner_when_cookie_is_null()
    {
        $this->assertFalse(ConsentStudio::seenBanner());
    }

    /** @test */
    public function it_returns_false_for_seen_banner_when_cookie_is_empty()
    {
        $this->setCookie('consent-studio__seen', '');

        $this->assertFalse(ConsentStudio::seenBanner());
    }

    /** @test */
    public function it_returns_true_for_seen_banner_when_cookie_is_true()
    {
        $this->setCookie('consent-studio__seen', 'true');

        $this->assertTrue(ConsentStudio::seenBanner());
    }

    /** @test */
    public function it_returns_true_for_seen_banner_when_cookie_is_1()
    {
        $this->setCookie('consent-studio__seen', '1');

        $this->assertTrue(ConsentStudio::seenBanner());
    }

    /** @test */
    public function it_returns_null_for_consent_id_when_cookie_is_not_set()
    {
        $this->assertNull(ConsentStudio::id());
    }

    /** @test */
    public function it_returns_consent_id_from_cookie()
    {
        $this->setCookie('consent-studio__consent-id', 'abc-123-def-456');

        $this->assertEquals('abc-123-def-456', ConsentStudio::id());
    }

    /** @test */
    public function it_returns_empty_array_when_no_consents_are_stored()
    {
        $this->assertEquals([], ConsentStudio::all());
    }

    /** @test */
    public function it_decodes_granted_consents_from_storage_cookie()
    {
        // Simulate the URL-encoded JSON cookie value
        $encodedValue = urlencode('["functional","analytics","marketing"]');
        $this->setCookie('consent-studio__storage', $encodedValue);

        $this->assertEquals(['functional', 'analytics', 'marketing'], ConsentStudio::all());
    }

    /** @test */
    public function it_checks_if_a_specific_category_is_granted()
    {
        $encodedValue = urlencode('["functional","analytics"]');
        $this->setCookie('consent-studio__storage', $encodedValue);

        $this->assertTrue(ConsentStudio::granted('functional'));
        $this->assertTrue(ConsentStudio::granted('analytics'));
        $this->assertFalse(ConsentStudio::granted('marketing'));
    }

    /** @test */
    public function it_has_is_an_alias_for_granted()
    {
        $encodedValue = urlencode('["functional"]');
        $this->setCookie('consent-studio__storage', $encodedValue);

        $this->assertTrue(ConsentStudio::has('functional'));
        $this->assertFalse(ConsentStudio::has('marketing'));
    }

    /** @test */
    public function it_checks_if_any_of_multiple_categories_are_granted()
    {
        $encodedValue = urlencode('["functional","analytics"]');
        $this->setCookie('consent-studio__storage', $encodedValue);

        $this->assertTrue(ConsentStudio::any(['functional', 'marketing']));
        $this->assertTrue(ConsentStudio::any(['analytics', 'neutral']));
        $this->assertFalse(ConsentStudio::any(['marketing', 'neutral']));
    }

    /** @test */
    public function it_checks_if_all_of_multiple_categories_are_granted()
    {
        $encodedValue = urlencode('["functional","analytics","marketing"]');
        $this->setCookie('consent-studio__storage', $encodedValue);

        $this->assertTrue(ConsentStudio::all(['functional', 'analytics']));
        $this->assertTrue(ConsentStudio::all(['functional', 'marketing']));
        $this->assertFalse(ConsentStudio::all(['functional', 'neutral']));
        $this->assertFalse(ConsentStudio::all(['marketing', 'neutral']));
    }

    /** @test */
    public function it_returns_complete_state()
    {
        $this->setCookie('consent-studio__seen', 'true');
        $this->setCookie('consent-studio__consent-id', 'test-id-123');
        $encodedValue = urlencode('["functional","analytics"]');
        $this->setCookie('consent-studio__storage', $encodedValue);

        $state = ConsentStudio::state();

        $this->assertEquals([
            'id' => 'test-id-123',
            'seen' => true,
            'consents' => ['functional', 'analytics'],
        ], $state);
    }

    /** @test */
    public function helper_function_returns_manager_when_no_arguments()
    {
        $this->assertInstanceOf(\ConsentStudio\Laravel\ConsentStudioManager::class, consent());
    }

    /** @test */
    public function helper_function_checks_single_category_with_string_argument()
    {
        $encodedValue = urlencode('["functional","analytics"]');
        $this->setCookie('consent-studio__storage', $encodedValue);

        $this->assertTrue(consent('functional'));
        $this->assertTrue(consent('analytics'));
        $this->assertFalse(consent('marketing'));
    }

    /** @test */
    public function helper_function_checks_any_category_with_array_argument()
    {
        $encodedValue = urlencode('["functional"]');
        $this->setCookie('consent-studio__storage', $encodedValue);

        $this->assertTrue(consent(['functional', 'marketing']));
        $this->assertFalse(consent(['marketing', 'analytics']));
    }

    /** @test */
    public function helper_function_supports_method_chaining()
    {
        $this->setCookie('consent-studio__consent-id', 'chained-id-789');

        $this->assertEquals('chained-id-789', consent()->id());
    }

    /** @test */
    public function it_handles_malformed_json_in_storage_cookie_gracefully()
    {
        $this->setCookie('consent-studio__storage', 'not-valid-json');

        $this->assertEquals([], ConsentStudio::all());
        $this->assertFalse(ConsentStudio::granted('functional'));
    }

    /** @test */
    public function it_handles_non_array_json_in_storage_cookie_gracefully()
    {
        $encodedValue = urlencode('"just-a-string"');
        $this->setCookie('consent-studio__storage', $encodedValue);

        $this->assertEquals([], ConsentStudio::all());
        $this->assertFalse(ConsentStudio::granted('functional'));
    }

    /** @test */
    public function it_handles_missing_request_gracefully()
    {
        // Create manager without a request (simulates CLI/queue context)
        $manager = new \ConsentStudio\Laravel\ConsentStudioManager(null);

        // All methods should return safe defaults without errors
        $this->assertFalse($manager->seenBanner());
        $this->assertNull($manager->id());
        $this->assertFalse($manager->granted('marketing'));
        $this->assertFalse($manager->has('analytics'));
        $this->assertFalse($manager->any(['marketing', 'analytics']));
        $this->assertEquals([], $manager->all());
        $this->assertFalse($manager->all(['functional']));

        $state = $manager->state();
        $this->assertEquals([
            'id' => null,
            'seen' => false,
            'consents' => [],
        ], $state);
    }

    /**
     * Helper method to set cookies on the request.
     *
     * @param string $key
     * @param string $value
     */
    protected function setCookie(string $key, string $value): void
    {
        $request = Request::create('/', 'GET');
        $request->cookies->set($key, $value);

        // Re-bind the request in the container
        $this->app->instance('request', $request);

        // Re-bind the consent-studio manager with the new request
        $this->app->singleton('consent-studio', function ($app) {
            return new \ConsentStudio\Laravel\ConsentStudioManager($app->make('request'));
        });
    }
}
