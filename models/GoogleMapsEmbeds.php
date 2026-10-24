<?php

declare(strict_types=1);

namespace Osmium\Services\GoogleMapsEmbed\Models;

use Osmium\Core\Library\OsmiumPDO;

/**
 * What a theme calls: a map by its slug.
 *
 *     $maps = new \Osmium\Services\GoogleMapsEmbed\Models\GoogleMapsEmbeds($this->osmium->dataSource);
 *     $map = $maps->get('contact');
 *
 * The map is an array with name and url (the iframe src), or null for an unknown or deleted slug or a stored URL
 * that is not a Google embed, so a theme prints the iframe only when it gets one.
 */
class GoogleMapsEmbeds
{
    public function __construct(private OsmiumPDO $database)
    {
    }

    /**
     * @return array{name: string, url: string}|null
     */
    public function get(string $slug): ?array
    {
        $map = (new GoogleMapsEmbed($this->database))->findBySlug($slug);
        if (!$map) return null;

        $url = (string) $map['embed_url'];
        $urlInvalid = !GoogleMapsEmbedText::isValidEmbedUrl($url);
        if ($urlInvalid) return null;

        return ['name' => (string) $map['name'], 'url' => $url];
    }
}
