<?php

namespace ConsentStudio\Laravel\Tests;

use ConsentStudio\Laravel\ConsentStudioServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            ConsentStudioServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default configuration
        $app['config']->set('consent-studio', [
            'google_consent_mode' => [
                'enabled' => true,
                'wait_for_update' => 500,
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
            'debug' => false,
        ]);
    }
}
