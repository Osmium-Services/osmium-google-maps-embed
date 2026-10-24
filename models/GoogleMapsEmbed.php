<?php

declare(strict_types=1);

namespace Osmium\Services\GoogleMapsEmbed\Models;

use Osmium\Core\Library\OsmiumPDO;
use Osmium\Core\Models\Model;

/**
 * The named map embeds an admin manages. A map is soft-deleted first and only removed for good from the bin.
 */
class GoogleMapsEmbed extends Model
{
    protected ?string $sqlDir = __DIR__ . '/sql';

    public function __construct(OsmiumPDO $database)
    {
        parent::__construct(database: $database, tableName: 'google_maps_embeds');
    }

    /** Every live map, by name */
    public function listAll(): array
    {
        $this->database->query($this->loadSqlFile('map-list.sql'));

        return $this->database->resultset();
    }

    /** Maps created or changed in the last 24 hours, newest change first, capped at 10 */
    public function listRecent(): array
    {
        $this->database->query($this->loadSqlFile('map-list-recent.sql'));

        return $this->database->resultset();
    }

    /** Every map in the bin, most recently deleted first */
    public function listDeleted(): array
    {
        $this->database->query($this->loadSqlFile('map-list-deleted.sql'));

        return $this->database->resultset();
    }

    /** A map whether or not it is deleted (check deleted_at), or null when there is no such map */
    public function getById(int $id): ?array
    {
        $this->database->query($this->loadSqlFile('map-get.sql'));
        $this->database->bind(param: ':id', value: $id);
        $map = $this->database->single();

        return $map === false ? null : $map;
    }

    /** The live map with this slug, or null */
    public function findBySlug(string $slug): ?array
    {
        $this->database->query($this->loadSqlFile('map-get-by-slug.sql'));
        $this->database->bind(param: ':slug', value: $slug);
        $map = $this->database->single();

        return $map === false ? null : $map;
    }

    /** Whether another map, deleted or not, already uses the slug (the column is unique) */
    public function slugTaken(string $slug, int $excludeId = 0): bool
    {
        $this->database->query($this->loadSqlFile('map-slug-taken.sql'));
        $this->database->bind(param: ':slug', value: $slug);
        $this->database->bind(param: ':exclude_id', value: $excludeId);

        return $this->database->single() !== false;
    }

    public function create(string $name, string $slug, string $embedUrl): int
    {
        $this->database->query($this->loadSqlFile('map-insert.sql'));
        $this->database->bind(param: ':name', value: $name);
        $this->database->bind(param: ':slug', value: $slug);
        $this->database->bind(param: ':embed_url', value: $embedUrl);
        $this->database->execute();

        return (int) $this->database->lastInsertId();
    }

    public function update(int $id, string $name, string $slug, string $embedUrl): void
    {
        $this->database->query($this->loadSqlFile('map-update.sql'));
        $this->database->bind(param: ':id', value: $id);
        $this->database->bind(param: ':name', value: $name);
        $this->database->bind(param: ':slug', value: $slug);
        $this->database->bind(param: ':embed_url', value: $embedUrl);
        $this->database->execute();
    }

    public function softDelete(int $id): void
    {
        $this->database->query($this->loadSqlFile('map-soft-delete.sql'));
        $this->database->bind(param: ':id', value: $id);
        $this->database->execute();
    }

    public function restore(int $id): void
    {
        $this->database->query($this->loadSqlFile('map-restore.sql'));
        $this->database->bind(param: ':id', value: $id);
        $this->database->execute();
    }

    public function delete(int $id): void
    {
        $this->database->query($this->loadSqlFile('map-delete.sql'));
        $this->database->bind(param: ':id', value: $id);
        $this->database->execute();
    }
}
