<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

return [
    'paths' => [
        'migrations' => __DIR__ . '/db/migrations',
        'seeds'      => __DIR__ . '/db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinx_migrations',
        'default_environment'     => 'default',
        'default' => [
            'adapter' => 'mysql',
            'host'    => $_ENV['MYSQL_HOST'] ?? 'localhost',
            'name'    => $_ENV['MYSQL_DATABASE'] ?? 'sintoniza',
            'user'    => $_ENV['MYSQL_USER'] ?? 'root',
            'pass'    => $_ENV['MYSQL_PASSWORD'] ?? '',
            'port'    => $_ENV['MYSQL_PORT'] ?? '3306',
            'charset' => 'utf8mb4',
        ],
    ],
    'version_order' => 'creation',
];
