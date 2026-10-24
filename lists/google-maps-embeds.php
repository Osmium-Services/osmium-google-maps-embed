<?php

declare(strict_types=1);

use Osmium\Modules\Admin\Core\ListView;

/**
 * Google Maps Embed lists, rendered by Osmium\Modules\Admin\Core\ListView. Rows are shaped by GoogleMapsEmbedController::formatMap().
 */
require_once 'app/core/admin/services/ChangedByCell.php';

$changedBy = ['key' => 'changed_by', 'label' => '', 'type' => 'html', 'sortable' => false, 'render' => fn(array $row, array $context): string => \Osmium\Modules\Admin\Services\ChangedByCell::html($row, (string)($context['avatarUrl'] ?? ''))];
$nameColumn = ['key' => 'name', 'label' => 'Map', 'type' => 'text', 'strong' => true, 'fill' => true, 'sub' => ['key' => 'slug', 'style' => 'plain']];

return [
    'recent' => [
        'id' => 'google-maps-embeds-recent',
        'title' => 'Recent Changes',
        'noun' => ['map', 'maps'],
        'icon' => 'bx-time-five',
        'rowClass' => static fn(array $row): string => empty($row['deleted_at']) ? '' : 'table-danger',
        'endpoint' => 'google-maps-embed/action/',
        'datatable' => false,
        'collapsible' => ['label' => 'Recent Changes ({count})'],
        'hideWhenEmpty' => false,
        'columns' => [
            $changedBy,
            $nameColumn,
            ['key' => 'updated_label', 'label' => 'Updated', 'type' => 'text', 'small' => true, 'nowrap' => true],
        ],
    ],

    'maps' => [
        'id' => 'google-maps-embeds',
        'title' => 'Maps',
        'noun' => ['map', 'maps'],
        'endpoint' => 'google-maps-embed/action/',
        'emptyText' => 'No maps yet. Use New Map to add the first.',
        'order' => null, // keep the server's name order until a header is clicked
        'columns' => [
            $nameColumn,
            ['key' => 'date_label', 'label' => 'Updated', 'type' => 'text', 'nowrap' => true, 'sortKey' => 'date_sort'],
        ],
        'actions' => [
            ['label' => 'Edit', 'icon' => 'bx-edit-alt', 'url' => '{edit_url}'],
            [
                'label' => 'Delete',
                'icon' => 'bx-trash',
                'action' => 'delete',
                'confirm' => 'Delete this map? It can be restored from Deleted Maps.',
                'danger' => true,
            ],
        ],
    ],

    'deleted' => ListView::deletedPreset(
        definition: [
            'id' => 'google-maps-embeds-deleted',
            'title' => 'Deleted Maps',
            'noun' => ['map', 'maps'],
            'endpoint' => 'google-maps-embed/action/',
            'hideWhenEmpty' => false,
            'columns' => [$changedBy, $nameColumn],
        ],
        permanentConfirm: 'Permanently delete this map? This cannot be undone!',
    ),
];
