<?php

use opencart\ProductCategoryLoader;
use opencart\ProductLoader;

require_once __DIR__ . '/workers/IikoApiWorker.php';
require_once __DIR__ . '/processors/ProductCategoryProcessor.php';
require_once __DIR__ . '/processors/ProductProcessor.php';
require_once __DIR__ . '/loaders/opencart/ProductCategoryLoader.php';
require_once __DIR__ . '/loaders/opencart/ProductLoader.php';
require_once __DIR__ . '/core/IntegrationPipeline.php';

try {
    // 1. Создаём воркер для API
    $iiko = IikoApiWorker::make();

    // 2. Получаем список организаций
    $orgs = $iiko->getOrganisations();

    if (!empty($orgs['organizations'][0]['id'])) {
        $orgId = $orgs['organizations'][0]['id'];
        echo "▶ Используем организацию: {$orgId}\n";

        // 3. Создаём процессоры
        $catProcessor  = new ProductCategoryProcessor();
        $prodProcessor = new ProductProcessor();

        // 4. Создаём лоадеры (MySQL)
        $catLoader = new ProductCategoryLoader();
        $prodLoader = new ProductLoader();

        // 5. Настраиваем pipeline
        $pipeline = new IntegrationPipeline();

        // Добавляем процессоры
        $pipeline
            ->addProcessor('categories', fn() => $catProcessor->getCategoriesFromMenu())
            ->addProcessor('products', fn() => $prodProcessor->getProductsFromMenu());

        // Добавляем лоадеры
        $pipeline
            ->addLoader('categoryLoader', fn($results) => $catLoader->load($results['categories']))
            ->addLoader('productLoader', fn($results) => $prodLoader->load($results['products']));

        // 6. Запускаем интеграцию
        $pipeline->run();
    } else {
        echo "❌ Не удалось получить organizationId\n";
    }

} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
}
