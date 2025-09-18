<?php

$config_path = dirname(__DIR__, 1) . '/config.php';
if (!file_exists($config_path)) {
    $config_example = dirname(__DIR__, 1) . '/config.example.php';
    if (file_exists($config_example)) {
        $config_data = file_get_contents($config_example);
        file_put_contents($config_path, $config_data);
    }
}

$config = require $config_path;

class IikoApiWorker
{
    private static $instance = null;
    private $api_base_url = 'https://api-ru.iiko.services';
    private $accessToken = null;
    private $tokenExpiresAt = null;

    private function __construct() {}

    public static function make()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Получение access token
     * @throws Exception
     */
    public function getAccessToken(): string
    {
        // Проверяем, не истёк ли токен
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        $url = $this->api_base_url . '/api/1/access_token';
        $data = [
            'apiLogin' => $GLOBALS['config']['iiko_token'],
        ];

        $response = $this->fetch($url, $data, 'POST', ['Content-Type' => 'application/json']);
        $result = json_decode($response, true);

        if (!isset($result['token'])) {
            throw new Exception('Failed to get iiko access token: ' . $response);
        }

        $this->accessToken = $result['token'];
        $this->tokenExpiresAt = time() + 60 * 60; // токен живет 1 час

        return $this->accessToken;
    }

    public function getOrganisations(): array
    {
        $url = $this->api_base_url . '/api/1/organizations';
        $token = $this->getAccessToken();

        $headers = [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ];

        $response = $this->fetch($url, [], 'GET', $headers);
        return json_decode($response, true);
    }

    public function getMenu(): array
    {
        $url = $this->api_base_url . '/api/1/nomenclature';
        $token = $this->getAccessToken();

        $data = [
            'organizationId' => $GLOBALS['config'][''],
        ];

        $headers = [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ];

        $response = $this->fetch($url, $data, 'POST', $headers);
        return json_decode($response, true);
    }

    public function getCities(): array
    {
        $url = $this->api_base_url . '/api/1/reference/cities';
        $token = $this->getAccessToken();

        $headers = [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ];

        $response = $this->fetch($url, [], 'POST', $headers);
        return json_decode($response, true);
    }

    public function getExternalMenus(): array
    {
        $url = $this->api_base_url . '/api/2/menu';
        $token = $this->getAccessToken();

        $headers = [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ];

        $response = $this->fetch($url, [], 'POST', $headers);
        return json_decode($response, true);
    }

    /**
     * Получение конкретного внешнего меню
     */
    public function getExternalMenu(): array
    {
        $url = $this->api_base_url . '/api/2/menu/by_id';
        $token = $this->getAccessToken();

        $organizations = $this->getOrganisations();
        $organizationIds = array_column($organizations['organizations'], 'id');

        $data = [
            'externalMenuId' => $GLOBALS['config']['iiko_external_menu_id'],
            'organizationIds' => $organizationIds
        ];

        $headers = [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ];

        $response = $this->fetch($url, $data, 'POST', $headers);
        return json_decode($response, true);
    }

    /**
     * Метод отправки HTTP-запроса
     * @throws Exception
     */
    private function fetch($url, $data = [], $method = 'GET', $headers = [])
    {
        $method = strtoupper($method);
        $payload = null;

        // Определяем Content-Type из заголовков
        $contentType = null;
        foreach ($headers as $key => $value) {
            if (is_int($key)) {
                // Заголовок в виде строки 'Content-Type: application/json'
                if (stripos($value, 'Content-Type:') === 0) {
                    $contentType = trim(substr($value, 13));
                }
            } else {
                // Заголовок в виде ключ => значение
                if (strtolower($key) === 'content-type') {
                    $contentType = $value;
                }
            }
        }

        // Формируем тело запроса
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            if ($contentType === 'application/json') {
                $payload = json_encode($data);
            } else {
                $payload = http_build_query($data);
            }
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        $defaultHeaders = [
            'Accept: application/json',
        ];

        // Формируем заголовки
        $finalHeaders = [];
        foreach (array_merge($defaultHeaders, $headers) as $key => $value) {
            if (is_int($key)) {
                $finalHeaders[] = $value;
            } else {
                $finalHeaders[] = "$key: $value";
            }
        }

        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $finalHeaders),
                'content' => $payload,
                'ignore_errors' => true, // чтобы получить тело при ошибке
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            throw new Exception("HTTP request failed: " . $error['message']);
        }

        return $response;
    }
}
