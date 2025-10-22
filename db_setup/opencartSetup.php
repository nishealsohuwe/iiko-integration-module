<?php
/**
 * setup.php
 * Скрипт подготовки БД OpenCart под синхронизацию с iiko
 */

require_once dirname(__DIR__, 1) . '/loaders/BaseDbLoader.php';

class OpencartSetup extends BaseDbLoader
{
    /**
     * @throws Exception
     */
    public function run(): void
    {
        // --- Категории ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_category` (
                `category_id` INT(11) NOT NULL AUTO_INCREMENT,
                `image` VARCHAR(255) DEFAULT NULL,
                `parent_id` INT(11) NOT NULL DEFAULT 0,
                `top` TINYINT(1) NOT NULL DEFAULT 0,
                `column` INT(3) NOT NULL DEFAULT 1,
                `sort_order` INT(3) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `date_added` DATETIME NOT NULL,
                `date_modified` DATETIME NOT NULL,
                PRIMARY KEY (`category_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_category_description` (
                `category_id` INT(11) NOT NULL,
                `language_id` INT(11) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT NOT NULL,
                PRIMARY KEY (`category_id`, `language_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_category_to_store` (
                `category_id` INT(11) NOT NULL,
                `store_id` INT(11) NOT NULL,
                PRIMARY KEY (`category_id`, `store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addColumnIfNotExists('oc_category', 'iiko_id', "VARCHAR(64) DEFAULT NULL");
        $this->addUniqueIfNotExists('oc_category', 'iiko_id');

        // --- Продукты ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_product` (
                `product_id` INT(11) NOT NULL AUTO_INCREMENT,
                `model` VARCHAR(64) NOT NULL,
                `sku` VARCHAR(64) DEFAULT '',
                `upc` VARCHAR(64) DEFAULT '',
                `ean` VARCHAR(64) DEFAULT '',
                `jan` VARCHAR(64) DEFAULT '',
                `isbn` VARCHAR(64) DEFAULT '',
                `mpn` VARCHAR(64) DEFAULT '',
                `location` VARCHAR(64) DEFAULT '',
                `stock_status_id` INT(4) DEFAULT 0,
                `manufacturer_id` INT(4) DEFAULT 0,
                `shipping` INT(4) DEFAULT 0,
                `points` INT(4) DEFAULT 0,
                `image` VARCHAR(255) DEFAULT NULL,
                `price` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                `quantity` INT(4) NOT NULL DEFAULT 9999,
                `tax_class_id` INT(4) NOT NULL DEFAULT 0,
                `date_available` DATETIME NOT NULL,
                `weight` INT(4) NOT NULL DEFAULT 0,
                `weight_class_id` INT(4) NOT NULL DEFAULT 1,
                `length` INT(4) NOT NULL DEFAULT 0,
                `width` INT(4) NOT NULL DEFAULT 0,
                `height` INT(4) NOT NULL DEFAULT 0,
                `length_class_id` INT(4) NOT NULL DEFAULT 1,
                `subtract` INT(4) NOT NULL DEFAULT 0,
                `minimum` INT(4) NOT NULL DEFAULT 1,
                `sort_order` INT(4) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `date_added` DATETIME NOT NULL,
                `date_modified` DATETIME NOT NULL,
                PRIMARY KEY (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_product_description` (
                `product_id` INT(11) NOT NULL,
                `language_id` INT(11) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT NOT NULL,
                PRIMARY KEY (`product_id`, `language_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_product_to_store` (
                `product_id` INT(11) NOT NULL,
                `store_id` INT(11) NOT NULL,
                PRIMARY KEY (`product_id`, `store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addColumnIfNotExists('oc_product', 'iiko_id', "VARCHAR(64) DEFAULT NULL");
        $this->addUniqueIfNotExists('oc_product', 'iiko_id');

        // --- Связка товара с категорией ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_product_to_category` (
                `product_id` INT(11) NOT NULL,
                `category_id` INT(11) NOT NULL,
                PRIMARY KEY (`product_id`, `category_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // --- Атрибуты ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_attribute` (
                `attribute_id` INT(11) NOT NULL AUTO_INCREMENT,
                `attribute_group_id` INT(11) NOT NULL,
                `sort_order` INT(3) NOT NULL DEFAULT 0,
                PRIMARY KEY (`attribute_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_attribute_description` (
                `attribute_id` INT(11) NOT NULL,
                `language_id` INT(11) NOT NULL,
                `name` VARCHAR(64) NOT NULL,
                PRIMARY KEY (`attribute_id`, `language_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_product_attribute` (
                `product_id` INT(11) NOT NULL,
                `attribute_id` INT(11) NOT NULL,
                `language_id` INT(11) NOT NULL,
                `text` TEXT NOT NULL,
                PRIMARY KEY (`product_id`, `attribute_id`, `language_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // --- Опции ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_option` (
                `option_id` INT(11) NOT NULL AUTO_INCREMENT,
                `type` VARCHAR(32) NOT NULL,
                `sort_order` INT(3) NOT NULL DEFAULT 0,
                PRIMARY KEY (`option_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_option_description` (
                `option_id` INT(11) NOT NULL,
                `language_id` INT(11) NOT NULL,
                `name` VARCHAR(128) NOT NULL,
                PRIMARY KEY (`option_id`, `language_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_product_option` (
                `product_option_id` INT(11) NOT NULL AUTO_INCREMENT,
                `product_id` INT(11) NOT NULL,
                `option_id` INT(11) NOT NULL,
                `required` TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`product_option_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->exec("
            CREATE TABLE IF NOT EXISTS `oc_product_option_value` (
                `product_option_value_id` INT(11) NOT NULL AUTO_INCREMENT,
                `product_option_id` INT(11) NOT NULL,
                `product_id` INT(11) NOT NULL,
                `option_id` INT(11) NOT NULL,
                `option_value_id` INT(11) NOT NULL,
                `quantity` INT(3) NOT NULL DEFAULT 9999,
                `subtract` TINYINT(1) NOT NULL DEFAULT 0,
                `price` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                `price_prefix` CHAR(1) NOT NULL DEFAULT '+',
                `weight` DECIMAL(15,8) NOT NULL DEFAULT 0.00000000,
                `weight_prefix` CHAR(1) NOT NULL DEFAULT '+',
                PRIMARY KEY (`product_option_value_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        echo "✅ База данных подготовлена для синхронизации с iiko\n";
    }

    private function addColumnIfNotExists(string $table, string $column, string $definition): void
    {
        $res = $this->db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($res->num_rows === 0) {
            $this->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    }

    private function addUniqueIfNotExists(string $table, string $column): void
    {
        $res = $this->db->query("SHOW INDEX FROM `$table` WHERE Column_name = '$column' AND Non_unique = 0");
        if ($res->num_rows === 0) {
            $this->exec("ALTER TABLE `$table` ADD UNIQUE (`$column`)");
        }
    }
}

try {
    (new OpencartSetup())->run();
} catch (Exception $e) {
    echo "❌ Ошибка setup: " . $e->getMessage() . "\n";
}
