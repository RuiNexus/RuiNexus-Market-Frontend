<?php

/**
 * RuiNexus Market - Frontend Index / Entry
 *
 * 简单的前端路由入口
 * 注意: public/ 是 Web 根目录，项目文件在其上级目录
 *
 * 开发者: RuiNexus / YeHuaiJing
 * 仓库: https://github.com/RuiNexus/RuiNexus-Market-Frontend
 */

define('ROOT', dirname(__DIR__));

require_once ROOT . '/config.php';
require_once ROOT . '/lib/ApiClient.php';
require_once ROOT . '/lib/Auth.php';

use Market\ApiClient;
use Market\Auth;

$config   = require ROOT . '/config.php';
$apiBase  = $config['api_base_url'];

$api = new ApiClient($apiBase);

$uri = $_SERVER['REQUEST_URI'];
$uri = parse_url($uri, PHP_URL_PATH);
$uri = trim($uri, '/');

switch ($uri) {
    case '':
    case 'index':
        require ROOT . '/pages/index.php';
        break;

    case 'detail':
        require ROOT . '/pages/detail.php';
        break;

    case 'publish':
        require ROOT . '/pages/publish.php';
        break;

    case 'user/listings':
        require ROOT . '/pages/user/listings.php';
        break;

    case 'user/orders':
        require ROOT . '/pages/user/orders.php';
        break;

    case 'user/sales':
        require ROOT . '/pages/user/sales.php';
        break;

    case 'user/favorites':
        require ROOT . '/pages/user/favorites.php';
        break;

    case 'about':
        include ROOT . '/pages/notice.php';
        break;

    default:
        http_response_code(404);
        echo '<h1>404 Not Found</h1>';
        break;
}
