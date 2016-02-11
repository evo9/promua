<?php

if (!isset($argv[1])) {
    exit('Остутствует команда! ');
}

$command = $argv[1];


define('ROOT_DIR', __DIR__);

require_once ROOT_DIR . '/config.php';
require_once ROOT_DIR . '/safemysql.class.php';
require_once ROOT_DIR . '/Snoopy.class.php';
require_once ROOT_DIR . '/promua.class.php';

$dbOpt = [
    'user' => DB_USER,
    'pass' => DB_PASS,
    'db' => DB_NAME
];

$promua = new Promua($dbOpt);

switch ($command) {
    case 'company-categories-list-content':

        $promua->getCompanyCategories();
        echo 'Страница со списком категорий компаний сохранена ';

        break;
    case 'company-categories-list-parse':
        $promua->parseCompanyCategories();
        echo 'Категории компаний сохранены ';

        break;
    case 'companies-list-content':
        $promua->getCompaniesListContent();
        echo 'Страницы со спискамим компаий загружены ';

        break;
    default:
        echo 'Команда не найдена! ';
        break;
}
