<?php

namespace opencart;

use BaseDbLoader;

require_once dirname(__DIR__, 1).'/BaseDbLoader.php';

class ProductCategoryLoader extends BaseDbLoader
{
    private int $store_id = 0;
    private int $language_id = 1;

    public function load(array $categories): void
    {
        foreach ($categories as $cat) {
            $iikoId = $this->db->real_escape_string($cat['iiko_id']);
            $name   = $this->db->real_escape_string($cat['name']);
            $desc   = $this->db->real_escape_string($cat['description']);
            $image  = $this->db->real_escape_string($cat['image'] ?? '');
            $parent = (int)$cat['parent_id'];

            // ищем категорию по iiko_id
            $res = $this->db->query("SELECT category_id FROM oc_category WHERE iiko_id = '{$iikoId}'");
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $category_id = (int)$row['category_id'];

                // обновляем категорию
                $this->db->query("
                    UPDATE oc_category 
                    SET parent_id = {$parent}, image = '{$image}', status = ".($cat['is_deleted'] ? 0 : 1)." 
                    WHERE category_id = {$category_id}
                ");
                $this->db->query("
                    UPDATE oc_category_description 
                    SET name = '{$name}', description = '{$desc}' 
                    WHERE category_id = {$category_id} AND language_id = {$this->language_id}
                ");
            } else {
                // создаем категорию
                $this->db->query("
                    INSERT INTO oc_category SET 
                        parent_id = {$parent}, 
                        top = 1,
                        `column` = 1,
                        sort_order = 0,
                        status = ".($cat['is_deleted'] ? 0 : 1).",
                        date_added = NOW(),
                        date_modified = NOW(),
                        iiko_id = '{$iikoId}',
                        image = '{$image}'
                ");
                $category_id = $this->db->insert_id;

                $this->db->query("
                    INSERT INTO oc_category_description SET 
                        category_id = {$category_id},
                        language_id = {$this->language_id},
                        name = '{$name}',
                        description = '{$desc}'
                ");

                $this->db->query("
                    INSERT INTO oc_category_to_store SET 
                        category_id = {$category_id}, 
                        store_id = {$this->store_id}
                ");
            }
        }

        echo "✅ Категории загружены в OpenCart\n";
    }
}
