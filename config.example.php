<?php

$configPath = dirname(__DIR__, 1) . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

return [
    'iiko_token' => 'your_api_token',
    'iiko_organisation_id' => 'your_organization_id',

    'iiko_external_menu_id' => 'external_menu_id',

    'bulk_item_update' => 100,
    'images_path' =>  DIR_IMAGE . 'catalog/products/',
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'username',
        'pass' => 'user_password',
        'name' => 'db_name'
    ]
];