<?php

$configPath = dirname(__DIR__, 1) . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

return [
//    'iiko_token' => 'c8e705c908a14219af3eedf3a30b4e3c',        // Сказка Востока
    'iiko_token' => '147fd5b664144629a0f0cc8a64ee6634',          // Японский Экспресс
//    'iiko_organisation_id' => 'e3468212-58f5-4d41-a276-29d9facf15cf',       // Сказка Востока
    'iiko_organisation_id' => 'd674227e-5f62-400f-bcd0-6974ace8b814',       // Японский Экспресс

    'iiko_external_menu_id' => '58523',

    'bulk_item_update' => 100,
    'images_path' =>  DIR_IMAGE . 'catalog/products/',
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'japan_expres',
        'pass' => 'N8RQH7HjyWfMDQuX',
        'name' => 'japan_expres'
    ]
];