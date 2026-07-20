<?php

declare(strict_types=1);

namespace MailChannels\Tests\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\MaintenanceMode;

/**
 * Minimal Illuminate\Contracts\Foundation\Application implementation backed
 * by a plain container. Laravel service providers and managers are typed
 * against the Application contract; this stands in for a full
 * Illuminate\Foundation\Application in tests that don't need one.
 */
final class MockLaravelApplication extends Container implements Application
{
    public function version()
    {
        return 'test';
    }

    public function basePath($path = '')
    {
        return $path;
    }

    public function bootstrapPath($path = '')
    {
        return $path;
    }

    public function configPath($path = '')
    {
        return $path;
    }

    public function databasePath($path = '')
    {
        return $path;
    }

    public function langPath($path = '')
    {
        return $path;
    }

    public function publicPath($path = '')
    {
        return $path;
    }

    public function resourcePath($path = '')
    {
        return $path;
    }

    public function storagePath($path = '')
    {
        return $path;
    }

    /** @param string ...$environments */
    public function environment(...$environments): string
    {
        return 'testing';
    }

    public function runningInConsole()
    {
        return false;
    }

    public function runningUnitTests()
    {
        return true;
    }

    public function hasDebugModeEnabled()
    {
        return false;
    }

    public function maintenanceMode()
    {
        throw new \BadMethodCallException(__METHOD__.' is not supported by '.self::class);
    }

    public function isDownForMaintenance()
    {
        return false;
    }

    public function registerConfiguredProviders()
    {
    }

    public function register($provider, $force = false)
    {
        throw new \BadMethodCallException(__METHOD__.' is not supported by '.self::class);
    }

    public function registerDeferredProvider($provider, $service = null)
    {
    }

    public function resolveProvider($provider)
    {
        throw new \BadMethodCallException(__METHOD__.' is not supported by '.self::class);
    }

    public function boot()
    {
    }

    public function booting($callback)
    {
    }

    public function booted($callback)
    {
    }

    /** @param array<int, string> $bootstrappers */
    public function bootstrapWith(array $bootstrappers)
    {
    }

    public function getLocale()
    {
        return 'en';
    }

    public function getNamespace()
    {
        return 'MailChannels\\Tests\\';
    }

    /** @return array<int, \Illuminate\Support\ServiceProvider> */
    public function getProviders($provider)
    {
        return [];
    }

    public function hasBeenBootstrapped()
    {
        return true;
    }

    public function loadDeferredProviders()
    {
    }

    public function setLocale($locale)
    {
    }

    public function shouldSkipMiddleware()
    {
        return false;
    }

    public function terminating($callback)
    {
        return $this;
    }

    public function terminate()
    {
    }
}
