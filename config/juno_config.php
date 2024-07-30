<?php
return [
    'server' => [
        'host' => getenv('JUNO_HOST') ?: 'proxy',
        'port' => getenv('JUNO_PORT') ?: 8080,
    ],
    'host' => getenv('JUNO_HOST') ?: 'proxy',
    'port' => getenv('JUNO_PORT') ?: 8080,
    'max_connections' => 10,
    'connection_timeout' => 5,
    'read_timeout' => 10,
    'write_timeout' => 10,
    'retry' => [
        'max_attempts' => 3,
        'delay' => 100,
    ],
    'compression' => true,
];
