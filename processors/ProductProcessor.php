<?php

require_once __DIR__ . '/../workers/IikoApiWorker.php';

class ProductProcessor
{
    private $iiko;

    public function __construct()
    {
        $this->iiko = IikoApiWorker::make();
    }

    /**
     * Получить продукты из внешнего меню
     */
    public function getProductsFromExternalMenu(): array
    {
        $menu = $this->iiko->getExternalMenu();

        $processed = [];

        foreach ($menu['itemCategories'] ?? [] as $category) {
            foreach ($category['items'] ?? [] as $item) {
                $processed[] = [
                    'iiko_id'     => $item['itemId'] ?? null,
                    'name'        => $item['name'] ?? '',
                    'description' => $item['description'] ?? '',
                    'category_id' => $category['id'] ?? null,
                    'price'       => $this->extractPrice($item),
                    'image'       => $item['buttonImageUrl'] ?? null,
                    'is_deleted'  => $item['isHidden'] ?? false,
                    'attributes'  => $this->extractAttributes($item),
                    'options'     => $this->extractOptions($item),
                ];
            }
        }

        return $processed;
    }

    /**
     * Вытаскиваем цену (если есть itemSizes или другой источник)
     */
    private function extractPrice(array $item): float
    {
        if (!empty($item['itemSizes'][0]['price'])) {
            return (float)$item['itemSizes'][0]['price'];
        }
        return 0.0;
    }

    /**
     * Преобразуем теги/аллергены в атрибуты
     */
    private function extractAttributes(array $item): array
    {
        $attributes = [];

        if (!empty($item['allergens'])) {
            $attributes[] = [
                'name'  => 'Аллергены',
                'value' => implode(', ', $item['allergens'])
            ];
        }

        if (!empty($item['tags'])) {
            $attributes[] = [
                'name'  => 'Теги',
                'value' => implode(', ', $item['tags'])
            ];
        }

        return $attributes;
    }

    /**
     * Формируем опции (если у товара есть варианты)
     */
    private function extractOptions(array $item): array
    {
        $options = [];

        if (!empty($item['itemSizes']) && count($item['itemSizes']) > 0) {
            $opt = [
                'name'   => 'Опции',
                'values' => []
            ];
            foreach ($item['itemSizes'] as $size) {
                $opt['values'][] = [
                    'sku'  => $size['sku'] ?? '',
                    'price' => $size['prices'][0]['price'] ?? 0,
                    'weight' => $size['portionWeightGrams'] ?? 0,
                    'image' => $size['buttonImageUrl'] ?? null,
                ];
            }
            $options[] = $opt;
        }

        return $options;
    }

    public function getProductsFromMenu(): array
    {
        $menu = $this->iiko->getMenu();

        return $this->processProducts($menu['products'] ?? []);
    }

    private function processProducts(array $products): array
    {
        $processed = [];
        foreach ($products as $product) {
            $processed[] = [
                'iiko_id'     => $product['id'] ?? null,
                'name'        => $product['name'] ?? '',
                'description' => $product['description'] ?? '',
                'category_id' => $product['parentGroup'] ?? null,
                'price'       => $product['sizePrices'][0]['price']['currentPrice'] ?? 0,
                'image'       => $product['imageLinks'][0] ?? null,
                'is_deleted'  => $product['isDeleted'] ?? false,
                'attributes'  => $this->extractAttributes($product),
                'options'     => $product['modifiers'] ?? [],
            ];
        }
        return $processed;
    }
}
