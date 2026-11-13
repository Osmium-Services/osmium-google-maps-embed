<?php

declare(strict_types=1);

namespace Tests\Services\GoogleMapsEmbed;

use Osmium\Services\GoogleMapsEmbed\Models\GoogleMapsEmbeds;
use Osmium\Services\GoogleMapsEmbed\Models\MapsAdminBar;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/GoogleMapsEmbeds.php';
require_once __DIR__ . '/../models/MapsAdminBar.php';

/**
 * The staff bar's map answers: a page that showed a map announces it by id and gets an "Edit map" link.
 */
final class MapsAdminBarTest extends TestCase
{
    protected function setUp(): void
    {
        GoogleMapsEmbeds::forgetShown();
    }

    public function test_a_page_that_showed_no_map_announces_nothing(): void
    {
        $this->assertNull(MapsAdminBar::context(['data' => []]));
    }

    public function test_a_page_that_showed_a_map_announces_it_by_id(): void
    {
        $this->setShown(3);

        $this->assertSame('google-map:3', MapsAdminBar::context(['data' => []]));
    }

    public function test_the_link_opens_that_maps_editor_with_the_services_own_icon(): void
    {
        $links = MapsAdminBar::links(['context' => 'google-map:3']);

        $this->assertSame('google-maps-embed/edit/?id=3', $links[0]['path']);
        $this->assertSame('Edit map', $links[0]['label']);
        $this->assertSame('bx-map', $links[0]['icon']);
        $this->assertSame(80, $links[0]['order']);
    }

    public function test_other_or_malformed_contexts_get_no_links(): void
    {
        $this->assertNull(MapsAdminBar::links(['context' => 'product:5']));
        $this->assertNull(MapsAdminBar::links(['context' => 'google-map:abc']));
        $this->assertNull(MapsAdminBar::links([]));
    }

    private function setShown(int $id): void
    {
        $property = new \ReflectionProperty(GoogleMapsEmbeds::class, 'firstShownMapId');
        $property->setValue(null, $id);
    }
}
