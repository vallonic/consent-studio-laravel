<?php

namespace ConsentStudio\Laravel\Tests;

class ConfigTest extends TestCase
{
    /** @test */
    public function it_has_default_config_values()
    {
        $config = config('consent-studio');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('google_consent_mode', $config);
        $this->assertArrayHasKey('debug', $config);
    }

    /** @test */
    public function it_has_google_consent_mode_config()
    {
        $gcm = config('consent-studio.google_consent_mode');

        $this->assertTrue($gcm['enabled']);
        $this->assertEquals(500, $gcm['wait_for_update']);
        $this->assertTrue($gcm['ads_data_redaction']);
        $this->assertFalse($gcm['url_passthrough']);
        $this->assertIsArray($gcm['defaults']);
    }

    /** @test */
    public function it_has_correct_default_consent_states()
    {
        $defaults = config('consent-studio.google_consent_mode.defaults');

        $this->assertIsArray($defaults);
        $this->assertNotEmpty($defaults);

        $firstDefault = $defaults[0];

        $this->assertEquals('denied', $firstDefault['ad_storage']);
        $this->assertEquals('denied', $firstDefault['ad_user_data']);
        $this->assertEquals('denied', $firstDefault['ad_personalization']);
        $this->assertEquals('denied', $firstDefault['analytics_storage']);
        $this->assertEquals('granted', $firstDefault['functionality_storage']);
        $this->assertEquals('granted', $firstDefault['personalization_storage']);
        $this->assertEquals('granted', $firstDefault['security_storage']);
    }

    /** @test */
    public function it_can_override_config_values()
    {
        config(['consent-studio.debug' => true]);

        $this->assertTrue(config('consent-studio.debug'));
    }

    /** @test */
    public function it_can_override_google_consent_mode_settings()
    {
        config(['consent-studio.google_consent_mode.enabled' => false]);
        config(['consent-studio.google_consent_mode.wait_for_update' => 1000]);

        $this->assertFalse(config('consent-studio.google_consent_mode.enabled'));
        $this->assertEquals(1000, config('consent-studio.google_consent_mode.wait_for_update'));
    }
}
