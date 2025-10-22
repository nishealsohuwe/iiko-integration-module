<?php

namespace customDb;

use BaseDbLoader;

require_once dirname(__DIR__, 1).'/BaseDbLoader.php';

class CustomProductCategoryLoader extends BaseDbLoader
{
    /**
     * Загрузка категорий в кастомную БД
     */
    public function load(array $categories): void
    {
        foreach ($categories as $cat) {
            $iikoId = $this->db->real_escape_string($cat['iiko_id']);
            $name = $this->db->real_escape_string($cat['name']);
            $description = $this->db->real_escape_string($cat['description']);
            $parentId = (int)$cat['parent_id'];
            $image = $cat['image'];

            // upsert в category
            $this->exec("
                INSERT INTO `category`
                SET `iiko_id` = '$iikoId',
                    `name` = '$name',
                    `description` = '$description',
                    `image` = '$image',
                    `parent_id` = $parentId,
                    `top` = 1,
                    `column` = 1,
                    `sort_order` = 10,
                    `status` = 1,
                    `date_added` = NOW(),
                    `date_modified` = NOW()
                ON DUPLICATE KEY UPDATE
                    `parent_id` = VALUES(`parent_id`),
                    `name` = VALUES(`name`),
                    `description` = VALUES(`description`),
                    `image` = VALUES(`image`),
                    `date_modified` = NOW()
            ");
        }

        echo "✅ Категории загружены в OpenCart\n";
    }
}
