<?php


class BaseDbLoader
{
    protected mysqli $db;

    public function __construct()
    {
        $config = require __DIR__ . '/../config.php';
        $dbConf = $config['db'];

        $this->db = new mysqli(
            $dbConf['host'],
            $dbConf['user'],
            $dbConf['pass'],
            null, // пока без имени БД
            $dbConf['port']
        );

        if ($this->db->connect_error) {
            throw new Exception("Ошибка подключения к MySQL: " . $this->db->connect_error);
        }

        $dbName = $this->db->real_escape_string($dbConf['name']);
        // Создаём БД, если её нет
        $this->db->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Выбираем её
        $this->db->select_db($dbName);
    }

    /**
     * Выполнение SQL с проверкой ошибок
     */
    protected function exec(string $sql): void
    {
        if (!$this->db->query($sql)) {
            throw new Exception("Ошибка MySQL: " . $this->db->error);
        }
    }

    /**
     * Подготовка значения для SQL (обработка типов)
     */
    protected function prepareValue(mixed $value): string
    {
        if (is_null($value)) {
            return "NULL";
        } elseif (is_bool($value)) {
            return $value ? "1" : "0";
        } elseif (is_array($value) || is_object($value)) {
            return "'" . $this->db->real_escape_string(json_encode($value, JSON_UNESCAPED_UNICODE)) . "'";
        } else { // строки и числа
            return "'" . $this->db->real_escape_string((string)$value) . "'";
        }
    }

    /**
     * Upsert (добавление или обновление по ключу)
     */
    protected function upsert(string $table, array $data, string $primaryKey = 'id'): void
    {
        $columns = array_map(fn($key) => $this->db->real_escape_string($key), array_keys($data));
        $values = array_map(fn($value) => $this->prepareValue($value), array_values($data));

        $updates = [];
        foreach ($columns as $col) {
            if ($col !== $primaryKey) {
                $updates[] = "`$col` = VALUES(`$col`)";
            }
        }

        $sql = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', $values) . ")
                ON DUPLICATE KEY UPDATE " . implode(',', $updates);

        $this->exec($sql);
    }
}
