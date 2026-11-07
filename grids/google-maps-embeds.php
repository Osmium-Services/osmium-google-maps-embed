<?php

declare(strict_types=1);

/**
 * Grid (card) layout for the Maps list, rendered by Osmium\Modules\Admin\Core\ListViewGrid. Columns use the
 * same vocabulary as lists/google-maps-embeds.php but nothing is shared with it. The actions are a copy of the list's for now.
 */
return [
    'columns' => 4,
    'title' => [
        'key' => 'name',
        'type' => 'text',
        'strong' => true,
        'sub' => ['key' => 'slug', 'style' => 'plain'],
    ],
    'meta' => [
        ['key' => 'date_label', 'type' => 'text', 'small' => true],
    ],
    'actionsAt' => 'bottom-end',
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
];
