<?php

namespace MailChannels\Plugins\Laravel;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use MailChannels\Plugins\Symfony\Transport\MailChannelsApiTransport;
use Psr\Log\LoggerInterface;

class MailChannelsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mail::extend('mailchannels', function (array $config = []) {
            $apiKey = $config['api_key'] ?? Config::get('services.mailchannels.api_key');
            $baseUrl = $config['base_url'] ?? Config::get('services.mailchannels.base_url');

            if (!is_string($apiKey) || trim($apiKey) === '') {
                throw new InvalidArgumentException('MAILCHANNELS_API_KEY is required for the mailchannels mail transport.');
            }

            if ($baseUrl != null && !is_string($baseUrl)) {
                throw new InvalidArgumentException('Base Url expects type string, '.gettype($baseUrl).' given.');
            }

            $logger = $this->app->bound(LoggerInterface::class) ? $this->app->make(LoggerInterface::class) : null;

            return new MailChannelsApiTransport(
                apiKey: $apiKey,
                baseUrl: is_string($baseUrl) ? $baseUrl : null,
                logger: $logger instanceof LoggerInterface ? $logger : null,
            );
        });
    }
}
