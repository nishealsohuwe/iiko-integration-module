<?php

namespace opencart;

use BaseDbLoader;

require_once dirname(__DIR__, 1).'/BaseDbLoader.php';

class OpencartProductCategoryLoader extends BaseDbLoader
{
    /**
     * Загрузка категорий в БД OpenCart
     */
    public function load(array $categories): void
    {
        foreach ($categories as $cat) {
            $iikoId = $this->db->real_escape_string($cat['iiko_id']);
            $name = $this->db->real_escape_string($cat['name']);
            $description = $this->db->real_escape_string($cat['description']);
            $parentId = (int)$cat['parent_id'];
            $image = $cat['image'];

            // upsert в oc_category
            $this->exec("
                INSERT INTO `oc_category`
                SET `iiko_id` = '$iikoId',
                    `parent_id` = $parentId,
                    `image` = '$image',
                    `top` = 1,
                    `column` = 1,
                    `sort_order` = 10,
                    `status` = 1,
                    `date_added` = NOW(),
                    `date_modified` = NOW()
                ON DUPLICATE KEY UPDATE
                    `parent_id` = VALUES(`parent_id`),
                    `image` = VALUES(`image`),
                    `date_modified` = NOW()
            ");

            // Получаем ID категории в OpenCart
            $res = $this->db->query("SELECT category_id FROM `oc_category` WHERE iiko_id = '$iikoId' LIMIT 1");
            $row = $res->fetch_assoc();
            $categoryId = (int)$row['category_id'];

            // upsert в oc_category_description
            $this->exec("
                INSERT INTO `oc_category_description`
                SET category_id = $categoryId,
                    language_id = 1,
                    name = '$name',
                    description = '$description'
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    description = VALUES(description)
            ");

            // upsert в oc_category_to_store
            $this->exec("
                INSERT IGNORE INTO `oc_category_to_store`
                SET category_id = $categoryId,
                    store_id = 0
            ");


            // upsert в oc_category_path
            $this->exec("
                INSERT IGNORE INTO `oc_category_path`
                SET category_id = $categoryId,
                    path_id = $categoryId,
                    level = 0
            ");
        }

        echo "✅ Категории загружены в OpenCart\n";
    }
}
