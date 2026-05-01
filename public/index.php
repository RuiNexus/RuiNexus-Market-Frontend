<?php

/**
 * RuiNexus Market - Frontend Index / Entry
 *
 * 简单的前端路由入口
 *
 * 开发者: RuiNexus / YeHuaiJing
 * 仓库: https://github.com/RuiNexus/RuiNexus-Market-Frontend
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/ApiClient.php';
require_once __DIR__ . '/lib/Auth.php';

use Market\ApiClient;
use Market\Auth;

$config   = require __DIR__ . '/config.php';
$apiBase  = $config['api_base_url'];

$api = new ApiClient($apiBase);

$uri = $_SERVER['REQUEST_URI'];
$uri = parse_url($uri, PHP_URL_PATH);
$uri = trim($uri, '/');

switch ($uri) {
    case '':
    case 'index':
        require __DIR__ . '/pages/index.php';
        break;

    case 'detail':
        require __DIR__ . '/pages/detail.php';
        break;

    case 'publish':
        require __DIR__ . '/pages/publish.php';
        break;

    case 'user/listings':
        require __DIR__ . '/pages/user/listings.php';
        break;

    case 'user/orders':
        require __DIR__ . '/pages/user/orders.php';
        break;

    case 'user/sales':
        require __DIR__ . '/pages/user/sales.php';
        break;

    case 'user/favorites':
        require __DIR__ . '/pages/user/favorites.php';
        break;

    case 'about':
        include __DIR__ . '/pages/notice.php';
        break;

    default:
        http_response_code(404);
        echo '<h1>404 Not Found</h1>';
        break;
}
