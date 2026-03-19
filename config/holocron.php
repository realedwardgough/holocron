<?php

declare(strict_types=1);

return [
    'table_name' => 'holocron_entries',

    'auto_record' => [
        'created' => true,
        'updated' => true,
        'deleted' => true,
        'restored' => true,
    ],

    'exclude' => [
        'created_at',
        'updated_at',
    ],

    'actor_resolver' => static fn () => auth()->user(),
];
