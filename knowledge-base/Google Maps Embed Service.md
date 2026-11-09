# Google Maps Embed Service

Repo: `Osmium-Services/osmium-google-maps-embed`, service id `google-maps-embed`, category content. v2.0.0.

The name says "Embed" on purpose: this is the simple single-place iframe. Google's richer product, the Maps JavaScript API
(custom markers, styling, several pins; needs a key and is paid), would be a separate service. Renamed from `google-maps`
(v1.0.x) on 2026-10-05.

## What it does (v2.0.0)

- Many named maps, FAQs-style: admin Content > Google Maps Embed (list, edit page, soft delete, bin, recent changes). Each
  map has a name, a unique slug and an embed URL. Table `google_maps_embeds`.
- The URL must start with `https://www.google.com/maps/embed`, so the field can't put another site in an iframe. Paste only
  the `src` value from Google Maps > Share > Embed a map, not the whole iframe.
- Replaces v1's single config-file URL and the `serviceBlock('map')` theme block. Nothing is migrated: re-add maps by hand.

## Wiring it into a theme

Theme controller, then the view prints the iframe:

```php
$maps = new \Osmium\Services\GoogleMapsEmbed\Models\GoogleMapsEmbeds($this->osmium->dataSource);
$map = $maps->get('contact'); // null for an unknown/deleted slug, else ['name' => ..., 'url' => ...]
```

The theme owns the markup (v1 printed `#google_map > .map_container > #map > iframe`; escape `$map['url']` in `src`).

## Limits

- The iframe loads Google as soon as the page does. There is no consent step or click-to-load.
- It uses the keyless Share > Embed URL. Google's docs only describe the API-key form
  (`/maps/embed/v1/...?key=`), so this URL is undocumented and could change. The old field had the same risk.

## Tested (2026-10-05)

Installed locally, URL saved through the admin form, map loaded from Google on the base homepage. A non-Google URL was
rejected. Blank, non-Google, lookalike-host and `javascript:` values in the config file render nothing. A quote in the URL
comes out escaped.
