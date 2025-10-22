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
            $image = $prod['image'];
            $categoryIikoId = $this->db->real_escape_string($prod['category_id']);

            // находим id категории в OpenCart
            $res = $this->db->query("SELECT category_id FROM `category` WHERE iiko_id = '$categoryIikoId' LIMIT 1");
            $categoryId = $res && $res->num_rows > 0 ? (int)$res->fetch_assoc()['category_id'] : 0;

            // upsert в product
            $this->exec("
                INSERT INTO `product`
                SET `iiko_id` = '$iikoId',
                    `model` = '$iikoId',
                    `name` = '$name',
                    `description` = '$description',
                    `price` = $price,
                    `quantity` = 9999,
                    `sort_order` = 0,
                    `status` = 1,
                    `date_added` = NOW(),
                    `date_modified` = NOW()
                ON DUPLICATE KEY UPDATE
                    price = VALUES(price),
                    name = VALUES(name),
                    description = VALUES(description),
                    date_modified = NOW()
            ");

            // получаем product_id
            $res = $this->db->query("SELECT product_id FROM `product` WHERE iiko_id = '$iikoId' LIMIT 1");
            $row = $res->fetch_assoc();
            $productId = (int)$row['product_id'];

            // связь с категорией
            if ($categoryId > 0) {
                $this->exec("
                    INSERT IGNORE INTO `product_to_category`
                    SET product_id = $productId,
                        category_id = $categoryId
                ");
            }
        }

        echo "✅ Продукты загружены в OpenCart\n";
    }
}
