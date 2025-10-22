<?php

namespace customDb;

use BaseDbLoader;

require_once dirname(__DIR__, 1).'/BaseDbLoader.php';


class ModifiersLoader extends BaseDbLoader
{
    /**
     * Загрузка модификаторов в кастомную БД
     */
    public function load(array $modifiers): void
    {

    }
}
