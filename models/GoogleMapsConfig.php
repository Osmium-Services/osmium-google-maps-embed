<?php

declare(strict_types=1);

namespace Osmium\Services\GoogleMaps\Models;

/**
 * Google Maps configuration helper.
 *
 * Loads this service's own settings file, matching the file-based config
 * convention used by the other services (app/config/services/{id}.json.php).
 */
class GoogleMapsConfig
{
    private static ?object $config = null;
    private static string $configPath = 'app/config/services/google-maps.json.php';

    /**
     * Falls back to defaults (no embed URL) if the config file is missing, so
     * installing this service renders nothing until a URL is saved.
     */
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

        self::$config = $decoded->googleMaps ?? self::defaults();

        return self::$config;
    }

    public static function clearCache(): void
    {
        self::$config = null;
    }

    /**
     * Only Google's own embed endpoint is accepted, so the field can't be
     * used to put an arbitrary page in an iframe on the site.
     */
    public static function isValidEmbedUrl(string $url): bool
    {
        return \preg_match(pattern: '#^https://www\.google\.com/maps/embed[/?]#', subject: $url) === 1;
    }

    private static function defaults(): object
    {
        return (object) ['embedUrl' => ''];
    }
}
