<?php


class BaseProcessor
{
    /**
     * @param  string  $url
     * @param  string  $imageName
     * @return string|null
     */
    protected function downloadImage(string $url, string $imageName): ?string
    {
        $savingPath = $GLOBALS['config']['images_path'];
        if (!is_dir($savingPath)) {
            mkdir($savingPath, 0755, true);  // Создаём директорию, если нет
        }

        $image = file_get_contents($url);
        $fullPath = $savingPath . $imageName;

        if(is_file($fullPath)) {
            return $fullPath;
        }

        $result = file_put_contents($fullPath, $image);
        if($result) {
            return $fullPath;
        }
        return null;
    }
}