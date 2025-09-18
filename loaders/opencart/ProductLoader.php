<?php

namespace opencart;

use BaseDbLoader;

require_once dirname(__DIR__, 1).'/BaseDbLoader.php';

class ProductLoader extends BaseDbLoader
{
    private int $store_id = 0;
    private int $language_id = 1;

    public function load(array $products): void
    {
        foreach ($products as $prod) {
            $iikoId = $this->db->real_escape_string($prod['iiko_id']);
            $name   = $this->db->real_escape_string($prod['name']);
            $desc   = $this->db->real_escape_string($prod['description']);
            $image  = $this->db->real_escape_string($prod['image'] ?? '');
            $price  = (float)$prod['price'];
            $status = $prod['is_deleted'] ? 0 : 1;

            // проверяем категорию
            $category_id = null;
            if (!empty($prod['category_id'])) {
                $res = $this->db->query("SELECT category_id FROM oc_category WHERE iiko_id = '".$this->db->real_escape_string($prod['category_id'])."'");
                if ($res && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $category_id = (int)$row['category_id'];
                }
            }

            // ищем продукт по iiko_id
            $res = $this->db->query("SELECT product_id FROM oc_product WHERE iiko_id = '{$iikoId}'");
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $product_id = (int)$row['product_id'];

                // обновляем
                $this->db->query("
                    UPDATE oc_product 
                    SET price = {$price}, status = {$status}, image = '{$image}', date_modified = NOW() 
                    WHERE product_id = {$product_id}
                ");
                $this->db->query("
                    UPDATE oc_product_description 
                    SET name = '{$name}', description = '{$desc}' 
                    WHERE product_id = {$product_id} AND language_id = {$this->language_id}
                ");
            } else {
                // создаем продукт
                $this->db->query("
                    INSERT INTO oc_product SET 
                        model = '{$iikoId}',
                        sku = '{$iikoId}',
                        price = {$price},
                        quantity = 9999,
                        stock_status_id = 7,
                        shipping = 1,
                        status = {$status},
                        date_added = NOW(),
                        date_modified = NOW(),
                        iiko_id = '{$iikoId}',
                        image = '{$image}'
                ");
                $product_id = $this->db->insert_id;

                $this->db->query("
                    INSERT INTO oc_product_description SET 
                        product_id = {$product_id},
                        language_id = {$this->language_id},
                        name = '{$name}',
                        description = '{$desc}'
                ");

                $this->db->query("
                    INSERT INTO oc_product_to_store SET 
                        product_id = {$product_id}, 
                        store_id = {$this->store_id}
                ");
            }

            // связываем с категорией
            if ($category_id) {
                $this->db->query("
                    REPLACE INTO oc_product_to_category SET 
                        product_id = {$product_id}, 
                        category_id = {$category_id}
                ");
            }

            // атрибуты
            foreach ($prod['attributes'] as $attr) {
                $attrName  = $this->db->real_escape_string($attr['name']);
                $attrValue = $this->db->real_escape_string($attr['value']);

                // ищем attribute_id
                $resAttr = $this->db->query("SELECT attribute_id FROM oc_attribute_description WHERE name = '{$attrName}' AND language_id = {$this->language_id}");
                if ($resAttr && $resAttr->num_rows > 0) {
                    $rowA = $resAttr->fetch_assoc();
                    $attribute_id = (int)$rowA['attribute_id'];
                } else {
                    // создаем новый атрибут
                    $this->db->query("INSERT INTO oc_attribute SET attribute_group_id = 1, sort_order = 0");
                    $attribute_id = $this->db->insert_id;
                    $this->db->query("INSERT INTO oc_attribute_description SET attribute_id = {$attribute_id}, language_id = {$this->language_id}, name = '{$attrName}'");
                }

                // добавляем к товару
                $this->db->query("
                    REPLACE INTO oc_product_attribute SET 
                        product_id = {$product_id}, 
                        attribute_id = {$attribute_id}, 
                        language_id = {$this->language_id}, 
                        text = '{$attrValue}'
                ");
            }

            // опции
            foreach ($prod['options'] as $opt) {
                $optName = $this->db->real_escape_string($opt['name']);

                // ищем option_id
                $resOpt = $this->db->query("SELECT option_id FROM oc_option_description WHERE name = '{$optName}' AND language_id = {$this->language_id}");
                if ($resOpt && $resOpt->num_rows > 0) {
                    $rowO = $resOpt->fetch_assoc();
                    $option_id = (int)$rowO['option_id'];
                } else {
                    $this->db->query("INSERT INTO oc_option SET type = 'select', sort_order = 0");
                    $option_id = $this->db->insert_id;
                    $this->db->query("INSERT INTO oc_option_description SET option_id = {$option_id}, language_id = {$this->language_id}, name = '{$optName}'");
                }

                // связываем с товаром
                $this->db->query("REPLACE INTO oc_product_option SET product_id = {$product_id}, option_id = {$option_id}, required = 0, product_option_id = {$option_id}");

                foreach ($opt['values'] as $val) {
                    $valName  = $this->db->real_escape_string($val['name']);
                    $valPrice = (float)$val['price'];

                    $this->db->query("INSERT IGNORE INTO oc_option_value SET option_id = {$option_id}, image = '', sort_order = 0");
                    $option_value_id = $this->db->insert_id ?: $this->db->insert_id;

                    $this->db->query("INSERT IGNORE INTO oc_option_value_description SET option_value_id = {$option_value_id}, language_id = {$this->language_id}, option_id = {$option_id}, name = '{$valName}'");

                    $this->db->query("
                        REPLACE INTO oc_product_option_value SET 
                            product_option_id = {$option_id}, 
                            product_id = {$product_id}, 
                            option_id = {$option_id}, 
                            option_value_id = {$option_value_id}, 
                            quantity = 9999, 
                            subtract = 0, 
                            price = {$valPrice}, 
                            price_prefix = '+'
                    ");
                }
            }
        }

        echo "✅ Продукты загружены в OpenCart\n";
    }
}
