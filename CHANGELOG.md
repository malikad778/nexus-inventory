# Changelog

All notable changes to `adnan/laravel-nexus` will be documented in this file.

## v1.1.0 - 2026-02-19

### Added
- **Fluent Facade API**: Added `context()`, `channel()`, `catalog()`, and `batch()` to `Nexus` facade for refined operation chaining.
- **Job Builders**: Introduced `CatalogSyncBuilder` and `BatchBuilder` as part of the new fluent API.
- **Lifecycle Events**: Added `BeforeInventorySync`, `AfterInventorySync`, `InventorySyncFailed`, `InventoryUpdated`, and `ChannelThrottled`.
- **Modular Webhooks**: Re-architected webhook verification to use platform-specific `WebhookVerifier` classes.
- **Enhanced DTOs**: Added support for barcodes, variants, and metadata in `NexusProduct` and `NexusInventoryUpdate`.
- **Operational Logs**: Introduced `nexus_sync_jobs` and `nexus_rate_limit_logs` for better observability.
- **Amazon Driver**: Fully implemented functional Catalog Items search and product fetching.

### Fixed
- Webhook payload parsing for WooCommerce and Etsy.
- Dead Letter Queue (DLQ) tracking and schema reliability.
- Internal test suite stability by resolving duplicated `TestCase` usage.

### Security
- Mandatory signature verification for all drivers via dedicated verifier classes.

**Full Changelog**: https://github.com/malikad778/laravel-nexus/compare/v1.0.0...v1.1.0

## v1.0.0 - 2026-02-19

**Full Changelog**: https://github.com/malikad778/laravel-nexus/commits/v1.0.0
