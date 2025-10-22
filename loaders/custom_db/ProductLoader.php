<?php

namespace customDb;

use BaseDbLoader;

require_once dirname(__DIR__, 1).'/BaseDbLoader.php';

class CustomProductLoader extends BaseDbLoader
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Загрузка продуктов в кастомную БД
     */
    public function load(array $products): void
    {
        foreach ($products as $prod) {
            $iikoId = $this->db->real_escape_string($prod['iiko_id']);
            $name = $this->db->real_escape_string($prod['name']);
            $description = $this->db->real_escape_string($prod['description']);
            $price = (float)$prod['price'];
            $categoryIikoId = $this->db->real_escape_string($prod['category_id']);
            $sku = $this->db->real_escape_string($prod['sku']);
            $measureUnit = $this->db->real_escape_string($prod['measure_unit']);
            $variations = $prod['variations'];

            // находим id категории в OpenCart
            $res = $this->db->query("SELECT id FROM `categories` WHERE iiko_group_id = '$categoryIikoId' LIMIT 1");
            $categoryId = $res && $res->num_rows > 0 ? (int)$res->fetch_assoc()['id'] : 0;

            // upsert в product
            $this->exec("
                INSERT INTO `products`
                SET `item_id` = '$iikoId',
                    `name` = '$name',
                    `description` = '$description',
                    `sku` = '$sku',
                    `measure_unit` = '1',
                    `type` = '1',
                    `created_at` = NOW(),
                    `updated_at` = NOW()
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    description = VALUES(description),
                    sku = VALUES(sku),
                    updated_at = NOW()
            ");

            // получаем product_id
            $res = $this->db->query("SELECT id FROM `products` WHERE item_id = '$iikoId' LIMIT 1");
            $row = $res->fetch_assoc();
            $productId = (int)$row['id'];

            // связь с категорией
            if ($categoryId > 0) {
                $this->exec("
                    INSERT IGNORE INTO `products_to_categories`
                    SET product_id = $productId,
                        category_id = $categoryId
                ");
            }

            // вариация продукта
            foreach ($variations as $variation) {
                $sizeName = $this->db->real_escape_string($variation['size_name']);
                $sizeCode = $this->db->real_escape_string($variation['size_code']);
                $sku = $this->db->real_escape_string($variation['sku']);
                $measureUnit = $this->db->real_escape_string($variation['measureUnitType']) ?? '1';
                $weight = $this->db->real_escape_string($variation['portionWeightGrams']);
                $price = $this->db->real_escape_string($variation['prices'][0]['price']);
                $nutritions = json_encode($variation['nutritions']);
                if (!empty($variation)) {
                    $this->exec("
                        INSERT IGNORE INTO `product_variation`
                        SET product_id = '$productId',
                            size_name = '$sizeName',
                            size_code = '$sizeCode',
                            sku = '$sku',
                            weight = '$weight',
                            measure_unit = '$measureUnit',
                            price = '$price',
                            nutritions = '$nutritions',
                            created_at = NOW(),
                            updated_at = NOW()
                        ON DUPLICATE KEY UPDATE
                            size_name = VALUES(size_name),
                            size_code = VALUES(size_code),
                            sku = VALUES(sku),
                            weight = VALUES(weight),
                            measure_unit = VALUES(measure_unit),
                            price = VALUES(price),
                            nutritions = VALUES(nutritions),
                            updated_at = NOW()
                    ");
                }
            }

            // изображения товаров
            // placeholder
        }

        echo "✅ Продукты загружены в БД\n";
    }
}
