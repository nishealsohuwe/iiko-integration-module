<?php
/**
 * setup.php
 * Скрипт подготовки кастомной БД под синхронизацию с iiko
 */

require_once __DIR__ . '/../loaders/BaseDbLoader.php';

class CustomSetup extends BaseDbLoader
{
    /**
     * @throws Exception
     */
    public function run(): void
    {
        // --- Категории ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `category` (
                `category_id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT NOT NULL,
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

        $this->addColumnIfNotExists('category', 'iiko_id', "VARCHAR(64) DEFAULT NULL");
        $this->addUniqueIfNotExists('category', 'iiko_id');

        // --- Продукты ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `product` (
                `product_id` INT(11) NOT NULL AUTO_INCREMENT,
                `model` VARCHAR(64) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT NOT NULL,
                `price` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                `quantity` INT(4) NOT NULL DEFAULT 9999,
                `sort_order` INT(4) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `date_added` DATETIME NOT NULL,
                `date_modified` DATETIME NOT NULL,
                PRIMARY KEY (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addColumnIfNotExists('product', 'iiko_id', "VARCHAR(64) DEFAULT NULL");
        $this->addUniqueIfNotExists('product', 'iiko_id');

        // --- Связка товара с категорией ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `product_to_category` (
                `product_id` INT(11) NOT NULL,
                `category_id` INT(11) NOT NULL,
                PRIMARY KEY (`product_id`, `category_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // --- Атрибуты ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `attribute` (
                `attribute_id` INT(11) NOT NULL AUTO_INCREMENT,
                `attribute_group_id` INT(11) NOT NULL,
                `name` VARCHAR(64) NOT NULL,
                `sort_order` INT(3) NOT NULL DEFAULT 0,
                PRIMARY KEY (`attribute_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->exec("
            CREATE TABLE IF NOT EXISTS `product_attribute` (
                `product_id` INT(11) NOT NULL,
                `attribute_id` INT(11) NOT NULL,
                `text` TEXT NOT NULL,
                PRIMARY KEY (`product_id`, `attribute_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // --- Опции ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `option` (
                `option_id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(128) NOT NULL,
                `type` VARCHAR(32) NOT NULL,
                `sort_order` INT(3) NOT NULL DEFAULT 0,
                PRIMARY KEY (`option_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->exec("
            CREATE TABLE IF NOT EXISTS `product_option` (
                `product_option_id` INT(11) NOT NULL AUTO_INCREMENT,
                `product_id` INT(11) NOT NULL,
                `option_id` INT(11) NOT NULL,
                `required` BOOL NOT NULL DEFAULT false,
                PRIMARY KEY (`product_option_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->exec("
            CREATE TABLE IF NOT EXISTS `product_option_value` (
                `product_option_value_id` INT(11) NOT NULL AUTO_INCREMENT,
                `product_option_id` INT(11) NOT NULL,
                `product_id` INT(11) NOT NULL,
                `option_id` INT(11) NOT NULL,
                `option_value_id` INT(11) NOT NULL,
                `price` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                `price_prefix` CHAR(1) NOT NULL DEFAULT '+',
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
    (new CustomSetup())->run();
} catch (Exception $e) {
    echo "❌ Ошибка setup: " . $e->getMessage() . "\n";
}
