<?php

$configPath = dirname(__DIR__, 1) . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

$imagesPath = dirname(__DIR__, 1) . '/images';
if (!file_exists($imagesPath)) {
    mkdir($imagesPath);
}

return [
    'iiko_token' => 'your_api_token',
    'iiko_organisation_id' => 'your_organization_id',

    'iiko_external_menu_id' => 'external_menu_id',

    'bulk_item_update' => 100,
    'images_path' =>  $imagesPath,
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'username',
        'pass' => 'user_password',
        'name' => 'db_name'
    ]
];
