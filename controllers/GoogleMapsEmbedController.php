<?php

declare(strict_types=1);

namespace Osmium\Services\GoogleMapsEmbed\Controllers;

use Osmium\Modules\Admin\Core\AdminController;
use Osmium\Services\GoogleMapsEmbed\Models\GoogleMapsEmbed;
use Osmium\Services\GoogleMapsEmbed\Models\GoogleMapsEmbedText;

/**
 * Google Maps Embed admin - the maps, the editor for one map, and the AJAX actions behind them.
 *
 * Routes:
 *   - index()  → /admin/google-maps-embed/
 *   - edit()   → /admin/google-maps-embed/edit/?id=N   (no id: a new map)
 *   - action() → /admin/google-maps-embed/action/      (save, delete, restore, permanently_delete)
 */
class GoogleMapsEmbedController extends AdminController
{
    private const RECORD_TYPE = 'google_maps_embed';

    public function index(): void
    {
        $maps = new GoogleMapsEmbed($this->osmium->dataSource);

        $this->data['admin']['isAdmin'] = $this->isAdmin();
        $this->data['admin']['maps'] = \array_map($this->formatMap(...), $maps->listAll());
        $this->data['admin']['recentMaps'] = \array_map($this->formatRecentMap(...), $maps->listRecent());
        $this->data['admin']['deletedMaps'] = \array_map($this->formatMap(...), $maps->listDeleted());
        $this->data['admin']['lists'] = require __DIR__ . '/../lists/google-maps-embeds.php';

        $this->setView('google-maps-embed/index.phtml');
    }

    public function edit(): void
    {
        $id = (int) $this->osmium->getStashedParam(key: 'id', default: 0); // Core moves query params out of $_GET
        $this->osmium->clearStashedQuerystring(); // Or a later "New Map" would reuse this id
        $map = $id ? (new GoogleMapsEmbed($this->osmium->dataSource))->getById($id) : null;
        $isMissing = $id && (!$map || $map['deleted_at'] !== null);
        if ($isMissing) $this->redirect('google-maps-embed/');

        $this->data['admin']['map'] = [
            'id' => $id,
            'name' => $map['name'] ?? '',
            'slug' => $map['slug'] ?? '',
            'embedUrl' => $map['embed_url'] ?? '',
        ];
        $this->data['admin']['showSavedMessage'] = $this->consumeSavedFlag();

        $this->setView('google-maps-embed/edit.phtml');
    }

    private function consumeSavedFlag(): bool
    {
        $wasSaved = isset($_SESSION['google_maps_embed_saved']);
        unset($_SESSION['google_maps_embed_saved']);

        return $wasSaved;
    }

    public function action()
    {
        \header('Content-Type: application/json');

        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        if (!$isPost) $this->admin->jsonError('Method not allowed');

        $input = $this->admin->auth->getJsonInput();
        $csrfValid = $this->admin->auth->validateCsrfJson($input);
        if (!$csrfValid) $this->admin->jsonError('Invalid request token. Please refresh and try again.');

        $action = $input['action'] ?? '';
        $id = (int) ($input['id'] ?? 0);

        try {
            $result = match ($action) {
                'save' => $this->save(id: $id, input: $input),
                'delete' => $this->deleteMap($id),
                'restore' => $this->restoreMap($id),
                'permanently_delete' => $this->permanentlyDeleteMap($id),
                default => throw new \InvalidArgumentException('Unknown action'),
            };

            $this->admin->jsonSuccess($result);
        } catch (\Exception $e) {
            $this->admin->jsonError($e->getMessage());
        }
    }

    // =========================================================================
    // Action helpers
    // =========================================================================

    private function save(int $id, array $input): array
    {
        $name = \trim((string) ($input['name'] ?? ''));
        $nameMissing = $name === '';
        if ($nameMissing) throw new \InvalidArgumentException('Name is required');

        $typedSlug = (string) ($input['slug'] ?? '');
        $slug = GoogleMapsEmbedText::slug($typedSlug !== '' ? $typedSlug : $name);
        $slugMissing = $slug === '';
        if ($slugMissing) throw new \InvalidArgumentException('Slug is required');

        $embedUrl = \trim((string) ($input['embed_url'] ?? ''));
        $urlInvalid = !GoogleMapsEmbedText::isValidEmbedUrl($embedUrl);
        if ($urlInvalid) throw new \InvalidArgumentException('The URL must start with https://www.google.com/maps/embed - copy only the src value from the Google Maps embed code.');

        $maps = new GoogleMapsEmbed($this->osmium->dataSource);
        $slugTaken = $maps->slugTaken(slug: $slug, excludeId: $id);
        if ($slugTaken) throw new \InvalidArgumentException('Another map (possibly a deleted one) already uses that slug');

        $existing = $id ? $maps->getById($id) : null;
        $isGone = $id && (!$existing || $existing['deleted_at'] !== null);
        if ($isGone) throw new \InvalidArgumentException('Map not found');

        if ($existing) {
            $maps->update(id: $id, name: $name, slug: $slug, embedUrl: $embedUrl);
        } else {
            $id = $maps->create(name: $name, slug: $slug, embedUrl: $embedUrl);
        }

        $this->logChange(action: $existing ? 'Updated' : 'Created', id: $id, name: $name);
        $_SESSION['google_maps_embed_saved'] = true;

        return ['success' => true, 'id' => $id];
    }

    /** Soft delete: a restore brings the map back */
    private function deleteMap(int $id): array
    {
        $maps = new GoogleMapsEmbed($this->osmium->dataSource);
        $map = $maps->getById($id);
        $isLive = $map && $map['deleted_at'] === null;
        if (!$isLive) throw new \InvalidArgumentException('Map not found');

        $maps->softDelete($id);
        $this->logChange(action: 'Deleted', id: $id, name: (string) $map['name']);

        return ['success' => true];
    }

    private function restoreMap(int $id): array
    {
        $maps = new GoogleMapsEmbed($this->osmium->dataSource);
        $map = $maps->getById($id);
        $isDeleted = $map && $map['deleted_at'] !== null;
        if (!$isDeleted) throw new \InvalidArgumentException('Deleted map not found');

        $maps->restore($id);
        $this->logChange(action: 'Restored', id: $id, name: (string) $map['name']);

        return ['success' => true];
    }

    /** Admins only, and only from the bin */
    private function permanentlyDeleteMap(int $id): array
    {
        $notAdmin = !$this->isAdmin();
        if ($notAdmin) throw new \RuntimeException('Permission denied');

        $maps = new GoogleMapsEmbed($this->osmium->dataSource);
        $map = $maps->getById($id);
        $isDeleted = $map && $map['deleted_at'] !== null;
        if (!$isDeleted) throw new \InvalidArgumentException('Deleted map not found');

        $maps->delete($id);
        $this->logChange(action: 'Permanently deleted', id: $id, name: (string) $map['name']);

        return ['success' => true];
    }

    // =========================================================================
    // Formatting
    // =========================================================================

    private function formatMap(array $map): array
    {
        $updated = $map['updated_at'] ?? $map['deleted_at'] ?? null;

        return $map + [
            'edit_url' => $this->getBasePath() . 'google-maps-embed/edit/?id=' . (int) $map['id'],
            'date_label' => $updated ? \date('j M Y', \strtotime($updated)) : '',
            'date_sort' => (string) $updated,
        ];
    }

    private function formatRecentMap(array $map): array
    {
        $changed = \max($map['updated_at'] ?? '', $map['deleted_at'] ?? '', $map['created_at']);

        return $this->formatMap($map) + ['updated_label' => \date('j M Y, g:ia', \strtotime((string) $changed))];
    }

    private function logChange(string $action, int $id, string $name): void
    {
        $this->admin->model->changelog->log(
            description: $action . ': Google Maps Embed ' . $name,
            recordType: self::RECORD_TYPE,
            recordId: $id,
        );
    }
}
