# Laravel Disk Override

A small Laravel package that gracefully resolves filesystem route collisions (`"The [private] disk conflicts with the [local] disk at [/storage]."`).

When Laravel 13 merges base default configurations with user configurations, default disks automatically appear in the disk list. If your custom disk (e.g. `private`) maps to the same serve URI as a default disk (e.g. `local`), a collision exception is thrown. 

This package provides a drop-in replacement for the framework's `FilesystemServiceProvider` that safely deduplicates overlapping URIs by allowing your explicitly defined disks to "win" and override the framework defaults.

## Installation

You can install the package via Composer:

```bash
composer require comestro/laravel-disk-override
```

## Configuration

Since this overrides a core Laravel binding, it **does not** use normal Package Auto-Discovery. You must manually swap out the framework's `FilesystemServiceProvider`.

### Laravel 11 & 13 (bootstrap/providers.php)

In `bootstrap/providers.php`, add the replacement logic:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    Comestro\LaravelDiskOverride\FilesystemServiceProvider::class,
];
```

*Note: By default, Laravel implicitly loads its core framework providers. If you add our custom provider to `providers.php`, it will boot and apply the override strategy. If you explicitly have `Illuminate\Filesystem\FilesystemServiceProvider::class` defined there, simply replace it with `Comestro\LaravelDiskOverride\FilesystemServiceProvider::class`.*

### AppServiceProvider

Alternatively, you can just register it directly inside your `App\Providers\AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->register(\Comestro\LaravelDiskOverride\FilesystemServiceProvider::class);
}
```

Since the framework resolves providers by class name, Laravel's container handles the override.

## How it Works

Instead of immediately registering a route and throwing an exception when two disks share the same `/storage` URI:
1. It loops through all configured disks.
2. It groups serveable paths in an internal Map.
3. Because user-configured disks always come *after* framework defaults in the config array, the Map inherently overwrites early framework defaults with your custom disk configuration.
4. Only the winning disk gets bound to the storage route.

## License

The MIT License (MIT).
