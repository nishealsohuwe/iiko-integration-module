<?php

namespace opencart;

use BaseDbLoader;

require_once dirname(__DIR__, 1).'/BaseDbLoader.php';

class OpencartProductLoader extends BaseDbLoader
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Загрузка продуктов в БД OpenCart
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
            $res = $this->db->query("SELECT category_id FROM `oc_category` WHERE iiko_id = '$categoryIikoId' LIMIT 1");
            $categoryId = $res && $res->num_rows > 0 ? (int)$res->fetch_assoc()['category_id'] : 0;

            // upsert в oc_product
            $this->exec("
                INSERT INTO `oc_product`
                SET `iiko_id` = '$iikoId',
                    `model` = '$iikoId',
                    `sku` = '',
                    `upc` = '',
                    `ean` = '',
                    `jan` = '',
                    `isbn` = '',
                    `mpn` = '',
                    `location` = '',
                    `quantity` = 9999,
                    `stock_status_id` = 7,
                    `image` = '$image',
                    `manufacturer_id` = 0,
                    `shipping` = 1,
                    `price` = $price,
                    `points` = 0,
                    `tax_class_id` = 0,
                    `date_available` = NOW(),
                    `weight` = 0,
                    `weight_class_id` = 1,
                    `length` = 0,
                    `width` = 0,
                    `height` = 0,
                    `length_class_id` = 1,
                    `subtract` = 0,
                    `minimum` = 1,
                    `sort_order` = 0,
                    `status` = 1,
                    `date_added` = NOW(),
                    `date_modified` = NOW()
                ON DUPLICATE KEY UPDATE
                    price = VALUES(price),
                    image = VALUES(image),
                    date_modified = NOW()
            ");

            // получаем product_id
            $res = $this->db->query("SELECT product_id FROM `oc_product` WHERE iiko_id = '$iikoId' LIMIT 1");
            $row = $res->fetch_assoc();
            $productId = (int)$row['product_id'];

            // upsert в oc_product_description
            $this->exec("
                INSERT INTO `oc_product_description`
                SET product_id = $productId,
                    language_id = 1,
                    name = '$name',
                    description = '$description'
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    description = VALUES(description)
            ");

            // связь с магазином
            $this->exec("
                INSERT IGNORE INTO `oc_product_to_store`
                SET product_id = $productId,
                    store_id = 0
            ");

            // связь с категорией
            if ($categoryId > 0) {
                $this->exec("
                    INSERT IGNORE INTO `oc_product_to_category`
                    SET product_id = $productId,
                        category_id = $categoryId
                ");
            }
        }

        echo "✅ Продукты загружены в OpenCart\n";
    }
}
