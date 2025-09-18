<?php

require_once __DIR__ . '/../workers/IikoApiWorker.php';

class ProductCategoryProcessor
{
    private $iiko;

    public function __construct()
    {
        $this->iiko = IikoApiWorker::make();
    }

    /**
     * Получить категории из стандартного меню
     */
    public function getCategoriesFromMenu(): array
    {
        $menu = $this->iiko->getMenu();

        $processed = [];

        foreach ($menu['groups'] ?? [] as $cat) {
            $processed[] = [
                'iiko_id' => $cat['id'] ?? null,
                'parent_id' => $cat['parentGroup'] ?? 0, // у productCategories родителя нет
                'name' => $cat['name'] ?? '',
                'description' => $cat['description'] ?? '',
                'image' => $cat['imageLinks'][0] ?? null,
                'is_deleted' => $cat['isDeleted'] ?? false,
            ];
        }
        return $processed;
    }

    /**
     * Получить категории из внешнего меню
     */
    public function getCategoriesFromExternalMenu(): array
    {
        $menu = $this->iiko->getExternalMenu();

        $processed = [];

        // productCategories — глобальные категории меню
        foreach ($menu['productCategories'] ?? [] as $cat) {
            $processed[] = [
                'iiko_id'     => $cat['id'] ?? null,
                'parent_id'   => 0, // у productCategories родителя нет
                'name'        => $cat['name'] ?? '',
                'description' => $cat['description'] ?? '',
                'image'       => $cat['buttonImageUrl'] ?? null,
                'is_deleted'  => $cat['isDeleted'] ?? false,
            ];
        }

        // itemCategories — категории с вложенными товарами
        foreach ($menu['itemCategories'] ?? [] as $cat) {
            $processed[] = [
                'iiko_id'     => $cat['id'] ?? null,
                'parent_id'   => $cat['iikoGroupId'] ?? 0,
                'name'        => $cat['name'] ?? '',
                'description' => $cat['description'] ?? '',
                'image'       => $cat['buttonImageUrl'] ?? null,
                'is_deleted'  => $cat['isDeleted'] ?? false,
            ];

        }


        return $processed;
    }

    /**
     * Обработка категорий: добавляем связку parent/child
     */
    private function processCategories(array $groups): array
    {
        $processed = [];
        foreach ($groups as $group) {
            $processed[] = [
                'id'          => $group['id'] ?? null,
                'name'        => $group['name'] ?? '',
                'parentId'    => $group['parentGroup'] ?? null,
                'isDeleted'   => $group['isDeleted'] ?? false,
                'raw'         => $group, // сохраняем все поля «как есть» для гибкости
            ];
        }
        return $processed;
    }
}
