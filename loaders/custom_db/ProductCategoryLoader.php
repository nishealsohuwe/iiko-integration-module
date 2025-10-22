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

            // upsert в category
            $this->exec("
                INSERT INTO `categories`
                SET `iiko_group_id` = '$iikoId',
                    `name` = '$name',
                    `api_id` = '$iikoId',
                    `created_at` = NOW(),
                    `updated_at` = NOW()
                ON DUPLICATE KEY UPDATE
                    `name` = VALUES(`name`),
                    `updated_at` = NOW()
            ");
        }

        echo "✅ Категории загружены в БД\n";
    }
}
