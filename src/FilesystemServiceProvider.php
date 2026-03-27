<?php

namespace Comestro\LaravelDiskOverride;

use Illuminate\Filesystem\FilesystemServiceProvider as BaseServiceProvider;

class FilesystemServiceProvider extends BaseServiceProvider
{
    /**
     * Register the route that serves the disks configured to be served in the local environment.
     *
     * @return void
     */
    protected function serveFiles()
    {
        $this->app->booted(function () {
            // Only serve disks if not in production and no asset URL is defined...
            if ($this->app->environment('production') ||
                config('app.asset_url') !== null) {
                return;
            }

            $disks = config('filesystems.disks', []);

            // Pass 1: Collect serveable disks and resolve URI collisions by letting later items win.
            // When user configurations are array_merge()'d with framework defaults, user items
            // come after framework defaults. Thus, a user-defined disk will override a default
            // framework disk at the same URI if both are set to serve => true.
            $serveable = collect();

            foreach ($disks as $name => $config) {
                $driver = $config['driver'] ?? null;

                if (! in_array($driver, ['local', 'scoped'])) {
                    continue;
                }

                if (! ($config['serve'] ?? false)) {
                    continue;
                }

                $path = $config['path'] ?? '/storage';
                $path = str_starts_with($path, '/') ? $path : '/'.$path;

                // Overrides any previously registered disk for this exact path
                $serveable->put($path, [
                    'name' => $name,
                    'config' => $config,
                    'path' => $path,
                ]);
            }

            // Pass 2: Register routes for the winning disks.
            foreach ($serveable as $serve) {
                app('router')->get($serve['path'].'/{path}', [
                    'uses' => '\Illuminate\Filesystem\ServeFileController@show',
                    'as' => 'storage.'.$serve['name'],
                ])->where('path', '.*');
            }
        });
    }
}
