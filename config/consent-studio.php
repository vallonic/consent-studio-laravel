<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Consent Mode
    |--------------------------------------------------------------------------
    |
    | Configure Google Consent Mode v2 integration settings.
    | Learn more: https://learn.consent.studio
    |
    */

    'google_consent_mode' => [

        /*
        | Enable or disable Google Consent Mode integration
        */
        'enabled' => env('CONSENT_STUDIO_GCM_ENABLED', true),

        /*
        | Milliseconds to wait before updating consent state
        */
        'wait_for_update' => env('CONSENT_STUDIO_GCM_WAIT', 500),

        /*
        | Redact ads data when consent is not granted
        */
        'ads_data_redaction' => env('CONSENT_STUDIO_GCM_ADS_REDACTION', true),

        /*
        | Pass click information in URL parameters
        */
        'url_passthrough' => env('CONSENT_STUDIO_GCM_URL_PASSTHROUGH', false),

        /*
        | Default consent states
        | You can specify a single default or an array of region-specific defaults
        |
        | Single default example:
        | 'defaults' => [
        |     'ad_storage' => 'denied',
        |     'analytics_storage' => 'denied',
        |     ...
        | ]
        |
        | Region-specific example:
        | 'defaults' => [
        |     [
        |         'ad_storage' => 'denied',
        |         'analytics_storage' => 'denied',
        |         'region' => ['US', 'CA'],
        |     ],
        |     [
        |         'ad_storage' => 'granted',
        |         'analytics_storage' => 'granted',
        |         'region' => ['GB'],
        |     ],
        | ]
        */
        'defaults' => [
            [
                'ad_storage' => 'denied',
                'ad_user_data' => 'denied',
                'ad_personalization' => 'denied',
                'analytics_storage' => 'denied',
                'functionality_storage' => 'granted',
                'personalization_storage' => 'granted',
                'security_storage' => 'granted',
                // Optional: uncomment and specify regions
                // 'region' => ['US', 'CA'],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable debug mode for development and testing.
    |
    */

    'debug' => env('CONSENT_STUDIO_DEBUG', false),

];
