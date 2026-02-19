# Laravel Nexus

[![Latest Version on Packagist](https://img.shields.io/packagist/v/adnan/laravel-nexus.svg?style=flat-square)](https://packagist.org/packages/adnan/laravel-nexus)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/adnan/laravel-nexus/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/adnan/laravel-nexus/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/adnan/laravel-nexus.svg?style=flat-square)](https://packagist.org/packages/adnan/laravel-nexus)

**Laravel Nexus** is a robust, multi-channel inventory management package for Laravel. It provides a unified interface to sync products and inventory across Shopify, WooCommerce, Amazon, and Etsy.

## Features

-   **Multi-Channel Support**: unified `InventoryDriver` interface for all platforms.
-   **Robust Queue System**: Job batches, rate limiting (Token Bucket), and meaningful retries.
-   **Dead Letter Queue (DLQ)**: Capture and manage failed jobs with a built-in UI.
-   **Webhooks**: Verified, secure webhook handling for real-time updates.
-   **Dashboard**: A focused administrative interface to monitor channel status and sync health.

## Installation

You can install the package via composer:

```bash
composer require adnan/laravel-nexus
```

Publish the config file and migrations:

```bash
php artisan vendor:publish --tag="nexus-config"
php artisan vendor:publish --tag="nexus-migrations"
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

Add your channel credentials to your `.env` file:

```dotenv
# Shopify
SHOPIFY_SHOP_URL=your-shop.myshopify.com
SHOPIFY_ACCESS_TOKEN=shpat_...
SHOPIFY_WEBHOOK_SECRET=...

# WooCommerce
WOOCOMMERCE_URL=https://your-store.com
WOOCOMMERCE_CONSUMER_KEY=ck_...
WOOCOMMERCE_CONSUMER_SECRET=cs_...

# Amazon (SP-API)
AMAZON_CLIENT_ID=...
AMAZON_CLIENT_SECRET=...
AMAZON_REFRESH_TOKEN=...
AMAZON_SELLER_ID=...
AMAZON_REGION=us-east-1

# Etsy
ETSY_KEYSTRING=...
ETSY_SHARED_SECRET=...
ETSY_SHOP_ID=...
```

See `config/nexus.php` for advanced configuration, including rate limits and middleware settings.

## Usage

### Drivers

Access any driver using the `Nexus` facade:

```php
use Adnan\LaravelNexus\Facades\Nexus;

// Get products from Shopify
$products = Nexus::driver('shopify')->getProducts();

// Update inventory on Amazon
Nexus::driver('amazon')->updateInventory($sku, 50);
```

### Queue System

Nexus uses Laravel's queue system extensively. Ensure your queue worker is running:

```bash
php artisan queue:work
```

### Webhooks

Nexus automatically registers a route for incoming webhooks at `/nexus/webhooks/{channel}`.
The `VerifyNexusWebhookSignature` middleware ensures all requests are authentic.

### Importing Products

When running `ChannelSyncBatchJob` (via the scheduler or manually), Nexus fetches recent products from the configured channels.
To handle these imported products (e.g., to create local models), listen for the `Adnan\LaravelNexus\Events\ProductImported` event:

```php
// In EventServiceProvider
use Adnan\LaravelNexus\Events\ProductImported;

protected $listen = [
    ProductImported::class => [
        CreateLocalProductListener::class,
    ],
];
```

### Dashboard

Visit `/nexus` to access the Nexus Dashboard.
*Note: Make sure to configure the `dashboard_middleware` in `config/nexus.php` to secure this route (e.g., `['web', 'auth']`).*

## Testing

Run the test suite:

```bash
composer test
```

## Credits

-   [Adnan](https://github.com/adnan)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
