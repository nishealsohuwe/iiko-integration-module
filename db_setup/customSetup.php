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
            CREATE TABLE IF NOT EXISTS `categories` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `api_id` VARCHAR(255) DEFAULT NULL,
                `iiko_group_id` VARCHAR(255) UNIQUE NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // --- Товары в категориях ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `products_to_categories` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `category_id` INT(11) NOT NULL,
                `product_id` INT(11) NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // --- Товары ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `products` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT,
                `sku` VARCHAR(255) NOT NULL,
                `item_id` VARCHAR(255) NOT NULL UNIQUE,
                `measure_unit` ENUM('1','2','3'),
                `type` ENUM('1','2','3'),
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // --- Вариации товаров ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `product_variation` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `product_id` INT(11) NOT NULL,
                `size_name` VARCHAR(255) NOT NULL,
                `size_code` VARCHAR(255) NOT NULL,
                `sku` VARCHAR(255) NOT NULL UNIQUE,
                `weight` FLOAT(11),
                `measure_unit` ENUM('1','2','3'),
                `price` FLOAT(11),
                `nutritions` JSON,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // --- Изображения вариации товара ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `product_variation_images` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `image_path` VARCHAR(255) NOT NULL,
                `product_variation_id` INT(11) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // --- Группы модификаторов к вариациям товаров ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `modifier_groups_to_product_variations` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `modifier_group_id` INT(11) NOT NULL,
                `product_variation_id` INT(11) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // --- Группы модификаторов ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `modifier_groups` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT,
                `iiko_group_id` VARCHAR(11) UNIQUE,
                `sku` VARCHAR(255) NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // --- Модификаторы к группам модификаторов ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `modifiers_to_modifier_groups` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `modifier_id` INT(11) NOT NULL,
                `modifier_group_id` INT(11) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // --- Модификаторы ---
        $this->exec("
            CREATE TABLE IF NOT EXISTS `modifiers` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT,
                `sku` VARCHAR(255) NOT NULL,
                `iiko_item_id` INT(11) NOT NULL UNIQUE,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`, `iiko_item_id`)
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
