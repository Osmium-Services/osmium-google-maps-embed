<?php

declare(strict_types=1);

namespace Osmium\Services\GoogleMapsEmbed\Models;

/**
 * What the maps service tells the front-end staff bar: that the page showed one of its maps (adminbar.context) and
 * the admin link that goes with it (adminbar.links). A page has no map data of its own, since themes fetch a map by
 * slug, so GoogleMapsEmbeds::get() notes the first map it handed out and this reads that. Core asks after the page is
 * built, so the answer is current. A supporting link (order 80): after the page's own and any main service link.
 */
class MapsAdminBar
{
    /**
     * @param array<string, mixed> $payload the finished page's view data (unused: get() notes the map)
     * @return string|null "google-map:3" when the page showed a map, null otherwise
     */
    public static function context(array $payload): ?string
    {
        $mapId = GoogleMapsEmbeds::firstShownMapId();
        $hasMap = $mapId !== null && $mapId > 0;

        return $hasMap ? "google-map:{$mapId}" : null;
    }

    /**
     * @param array{context?: string, role?: string} $payload
     * @return array<int, array<string, mixed>>|null
     */
    public static function links(array $payload): ?array
    {
        $context = (string)($payload['context'] ?? '');

        $isMap = \preg_match('/^google-map:(\d+)$/', $context, $match) === 1;
        if (!$isMap) return null;

        return [['label' => 'Edit map', 'icon' => 'bx-map', 'path' => 'google-maps-embed/edit/?id=' . (int)$match[1], 'primary' => true, 'order' => 80]];
    }
}
