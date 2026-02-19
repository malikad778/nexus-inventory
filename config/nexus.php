<?php

// config for Adnan/LaravelNexus
return [
    'default' => env('NEXUS_DRIVER', 'shopify'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware to apply to the dashboard routes.
    |
    */
    'dashboard_middleware' => ['web'],

    'drivers' => [
        'shopify' => [
            'shop_url' => env('SHOPIFY_SHOP_URL'),
            'access_token' => env('SHOPIFY_ACCESS_TOKEN'),
            'api_version' => '2024-01',
            'location_id' => env('SHOPIFY_LOCATION_ID'),
        ],

        'woocommerce' => [
            'store_url' => env('WOOCOMMERCE_STORE_URL'),
            'consumer_key' => env('WOOCOMMERCE_CONSUMER_KEY'),
            'consumer_secret' => env('WOOCOMMERCE_CONSUMER_SECRET'),
        ],

        'amazon' => [
            'refresh_token' => env('AMAZON_REFRESH_TOKEN'),
            'client_id' => env('AMAZON_CLIENT_ID'),
            'client_secret' => env('AMAZON_CLIENT_SECRET'),
            'access_key_id' => env('AMAZON_ACCESS_KEY_ID'),
            'secret_access_key' => env('AMAZON_SECRET_ACCESS_KEY'),
            'region' => env('AMAZON_REGION', 'us-east-1'),
            'seller_id' => env('AMAZON_SELLER_ID'),
        ],

        'etsy' => [
            'client_id' => env('ETSY_CLIENT_ID'), // Keystring / x-api-key
            'refresh_token' => env('ETSY_REFRESH_TOKEN'),
            'access_token' => env('ETSY_ACCESS_TOKEN'),
            'shop_id' => env('ETSY_SHOP_ID'),
        ],
    ],

    'rate_limits' => [
        'shopify' => ['capacity' => 10, 'rate' => 2.0], // 2 requests per second
        'woocommerce' => ['capacity' => 20, 'rate' => 5.0],
        'amazon' => ['capacity' => 5, 'rate' => 0.5], // Strict limits
        'etsy' => ['capacity' => 10, 'rate' => 10.0],
    ],
];
