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
}
