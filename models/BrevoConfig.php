<?php

declare(strict_types=1);

namespace Osmium\Services\Brevo\Models;

/**
 * Brevo configuration.
 *
 * File-based config (app/config/services/brevo.json.php), matching the
 * Xero/Stripe/PayPal/Turnstile convention. The From address and name are not
 * kept here: they stay on the core Email settings page, shared by every mail
 * provider.
 */
class BrevoConfig
{
    private static ?object $config = null;
    private static string $configPath = 'app/config/services/brevo.json.php';

    public static function get(): object
    {
        $configLoaded = self::$config !== null;
        if ($configLoaded) return self::$config;

        $configFile = self::$configPath;

        $configExists = \file_exists($configFile);
        if (!$configExists) {
            self::$config = self::defaults();
            return self::$config;
        }

        $content = \file_get_contents($configFile);
        $jsonStart = \strpos(haystack: $content, needle: '{');

        $noJsonFound = $jsonStart === false;
        if ($noJsonFound) {
            self::$config = self::defaults();
            return self::$config;
        }

        $json = \substr(string: $content, offset: $jsonStart);
        $decoded = \json_decode($json);

        self::$config = (object) \array_merge((array) self::defaults(), (array) ($decoded->brevo ?? []));

        return self::$config;
    }

    public static function clearCache(): void
    {
        self::$config = null;
    }

    /**
     * Can mail be sent right now - an API key is set.
     */
    public static function isReady(): bool
    {
        return (self::get()->apiKey ?? '') !== '';
    }

    private static function defaults(): object
    {
        return (object) [
            'apiKey' => '',
        ];
    }
}
