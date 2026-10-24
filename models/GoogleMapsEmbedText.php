<?php

declare(strict_types=1);

namespace Osmium\Services\GoogleMapsEmbed\Models;

/**
 * Cleaning and checking what an admin types for a map.
 */
class GoogleMapsEmbedText
{
    /** Lowercase letters, digits and single hyphens, for the slug of a map */
    public static function slug(string $text): string
    {
        $lower = \strtolower(\trim($text));
        $slug = \preg_replace('/[^a-z0-9]+/', '-', $lower) ?? '';

        return \trim($slug, '-');
    }

    /**
     * Only Google's own embed endpoint is accepted, so the field can't be
     * used to put an arbitrary page in an iframe on the site.
     */
    public static function isValidEmbedUrl(string $url): bool
    {
        return \preg_match(pattern: '#^https://www\.google\.com/maps/embed[/?]#', subject: $url) === 1;
    }
}
