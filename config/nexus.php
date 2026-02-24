<?php

return [
    'default' => env('NEXUS_DRIVER', 'shopify'),

    

    'dashboard_middleware' => ['web'],

    'drivers' => [
        'shopify' => [
            'shop_url' => env('SHOPIFY_SHOP_URL'),
            'access_token' => env('SHOPIFY_ACCESS_TOKEN'),
            'api_version' => '2024-01',
            'location_id' => env('SHOPIFY_LOCATION_ID'),
            'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET'),
        ],

        'woocommerce' => [
            'store_url' => env('WOOCOMMERCE_STORE_URL'),
            'consumer_key' => env('WOOCOMMERCE_CONSUMER_KEY'),
            'consumer_secret' => env('WOOCOMMERCE_CONSUMER_SECRET'),
            'webhook_secret' => env('WOOCOMMERCE_WEBHOOK_SECRET'),
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
            'client_id' => env('ETSY_CLIENT_ID'), 
            'refresh_token' => env('ETSY_REFRESH_TOKEN'),
            'access_token' => env('ETSY_ACCESS_TOKEN'),
            'shop_id' => env('ETSY_SHOP_ID'),
            'webhook_secret' => env('ETSY_WEBHOOK_SECRET'),
        ],
    ],

    'rate_limits' => [
        'shopify'     => ['capacity' => 40,  'rate' => 2.0],
        'woocommerce' => ['capacity' => 100, 'rate' => 25.0],
        'amazon'      => ['capacity' => 10,  'rate' => 1.0],
        'etsy'        => ['capacity' => 50,  'rate' => 10.0],
    ],
];
