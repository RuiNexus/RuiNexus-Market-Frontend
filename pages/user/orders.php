<?php

use Market\Auth;

$page    = max(1, intval($_GET['page'] ?? 1));
$size    = 20;

$siteConfig = $api->getConfig()['data'] ?? [];
$siteName   = $siteConfig['site_name'] ?? 'RuiNexus Market';
$notice     = $siteConfig['notice_content'] ?? '';
$user       = Auth::getUser();

$frontendConfig = require __DIR__ . '/../../config.php';
$apiBaseUrl = $frontendConfig['api_base_url'] ?? 'https://test.ruinexus.com';
$siteName = $frontendConfig['site_name'] ?: $siteName;

$statusMap = [0 => '待付款', 1 => '已付款', 2 => '已转移', 3 => '已完成', 4 => '已取消', 5 => '退款中', 6 => '已退款'];
$payTypeMap = ['online' => '线上', 'offline' => '线下'];

$statusClassMap = [
    0 => 'is-pending', 1 => 'is-active', 2 => 'is-active',
    3 => 'is-sold', 4 => 'is-delist', 5 => 'is-locked', 6 => 'is-delist'
];
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的购买 - <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<nav class="nav">
    <a href="<?php echo htmlspecialchars($apiBaseUrl); ?>" class="nav-logo">
        <img src="<?php echo htmlspecialchars($apiBaseUrl); ?>/themes/clientarea/default/assets/images/logo-inovice.png" alt="<?php echo htmlspecialchars($siteName); ?>">
    </a>
    <ul class="nav-links">
        <li><a href="/">市场</a></li>
        <li><a href="/user/orders" class="active">订单</a></li>
    </ul>
    <div class="nav-actions">
        <?php if ($user['loggedIn']): ?>
            <a href="/publish" class="nav-cta">发布</a>
            <a href="/user/listings" class="nav-cta--ghost">我的</a>
            <a href="/user/orders" class="nav-cta--ghost">订单</a>
        <?php else: ?>
            <a href="<?php echo Auth::getLoginUrl($apiBaseUrl); ?>" class="nav-cta--ghost">登录</a>
            <a href="<?php echo Auth::getRegisterUrl($apiBaseUrl); ?>" class="nav-cta">注册</a>
        <?php endif; ?>
    </div>
</nav>

<?php if ($notice): ?>
<div class="site-notice"><?php echo nl2br(htmlspecialchars($notice)); ?></div>
<?php endif; ?>

<section class="section">
    <div class="section-label">04 / MY ORDERS</div>
    <h2 class="section-title">我的购买</h2>

    <div id="ordersLoading" class="empty">
        <div class="empty__icon"><i class="fas fa-spinner fa-pulse"></i></div>
        <p>正在加载...</p>
    </div>

    <div id="ordersUnauth" class="empty" style="display:none;">
        <div class="empty__icon"><i class="fas fa-lock"></i></div>
        <p>请先登录以查看您的订单</p>
        <a href="<?php echo Auth::getLoginUrl($apiBaseUrl); ?>" class="detail__btn detail__btn--buy" style="display:inline-flex;margin-top:20px;width:auto;padding:12px 28px;">
            <i class="fas fa-sign-in-alt"></i> 登录账号
        </a>
    </div>

    <div id="ordersError" class="empty" style="display:none;">
        <div class="empty__icon"><i class="fas fa-exclamation-triangle"></i></div>
        <p>加载失败，请刷新重试</p>
    </div>

    <div id="ordersEmpty" class="empty" style="display:none;">
        <div class="empty__icon"><i class="fas fa-shopping-bag"></i></div>
        <p>暂无购买记录</p>
        <a href="/" class="detail__btn detail__btn--buy" style="display:inline-flex;margin-top:20px;width:auto;padding:12px 28px;">
            <i class="fas fa-search"></i> 浏览市场
        </a>
    </div>

    <div id="ordersList" class="listings__list" style="display:none;">
    </div>

    <div id="ordersPagination" class="pagination" style="display:none;">
    </div>
</section>

<footer class="footer">
    <div class="footer-inner">
        <div class="footer__brand">
            <div class="footer__brand-name"><?php echo htmlspecialchars($siteName); ?></div>
            <p class="footer__brand-desc">安全可靠的服务器与数字资产交易平台。值得信赖，透明交易。</p>
        </div>
        <div class="footer__links-group">
            <h4>快速链接</h4>
            <ul>
                <li><a href="/">市场</a></li>
                <li><a href="/publish">发布</a></li>
            </ul>
        </div>
    </div>
    <div class="footer__bottom">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?> — 版权所有。</p>
    </div>
</footer>

<script>
var API_BASE = <?php echo json_encode($apiBaseUrl); ?>;
var LOGIN_URL = <?php echo json_encode(Auth::getLoginUrl($apiBaseUrl)); ?>;
var CURRENT_PAGE = <?php echo $page; ?>;
var PAGE_SIZE = <?php echo $size; ?>;

var STATUS_MAP = <?php echo json_encode($statusMap); ?>;
var PAY_TYPE_MAP = <?php echo json_encode($payTypeMap); ?>;
var STATUS_CLASS_MAP = <?php echo json_encode($statusClassMap); ?>;

function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function showById(id) { var el = document.getElementById(id); if (el) el.style.display = ''; }
function hideById(id) { var el = document.getElementById(id); if (el) el.style.display = 'none'; }

function renderOrderItem(v) {
    return '<div class="listings__item">' +
        '<div class="listings__item-main">' +
            '<div class="listings__item-header">' +
                '<span class="listings__item-title">' + escHtml(v.title || '') + '</span>' +
                '<span class="listings__status ' + (STATUS_CLASS_MAP[v.status] || '') + '">' + (STATUS_MAP[v.status] || '') + '</span>' +
            '</div>' +
            '<div class="listings__item-meta">' +
                '<span><i class="fas fa-credit-card"></i> ' + escHtml(PAY_TYPE_MAP[v.pay_type] || '') + '</span>' +
                '<span><i class="far fa-clock"></i> ' + new Date(v.create_time * 1000).toISOString().slice(0, 16).replace('T', ' ') + '</span>' +
            '</div>' +
        '</div>' +
        '<div class="listings__item-side">' +
            '<div class="listings__item-price">' +
                '<span class="card__price-symbol">¥</span>' +
                '<span class="card__price-amount">' + parseFloat(v.amount || 0).toFixed(2) + '</span>' +
            '</div>' +
        '</div>' +
    '</div>';
}

function renderPagination(total, page, size) {
    var totalPages = Math.max(1, Math.ceil(total / size));
    if (totalPages <= 1) return '';

    var html = '';
    if (page > 1) {
        html += '<a href="?page=' + (page - 1) + '" class="pagination__item"><i class="fas fa-chevron-left"></i></a>';
    }
    var start = Math.max(1, page - 2);
    var end = Math.min(totalPages, page + 2);
    if (start > 1) {
        html += '<a href="?page=1" class="pagination__item">1</a>';
        if (start > 2) html += '<span class="pagination__item disabled">...</span>';
    }
    for (var i = start; i <= end; i++) {
        html += '<a href="?page=' + i + '" class="pagination__item ' + (i === page ? 'active' : '') + '">' + i + '</a>';
    }
    if (end < totalPages) {
        if (end < totalPages - 1) html += '<span class="pagination__item disabled">...</span>';
        html += '<a href="?page=' + totalPages + '" class="pagination__item">' + totalPages + '</a>';
    }
    if (page < totalPages) {
        html += '<a href="?page=' + (page + 1) + '" class="pagination__item"><i class="fas fa-chevron-right"></i></a>';
    }
    html += '<span class="pagination__info">第 ' + page + ' / ' + totalPages + ' 页</span>';
    return html;
}

async function loadOrders() {
    hideById('ordersLoading');
    hideById('ordersUnauth');
    hideById('ordersError');
    hideById('ordersEmpty');
    hideById('ordersList');
    hideById('ordersPagination');

    if (!window.__marketUser || !window.__marketUser.loggedIn) {
        showById('ordersUnauth');
        return;
    }

    try {
        var resp = await fetch(API_BASE + '/market_api.php?action=my_orders&page=' + CURRENT_PAGE + '&size=' + PAGE_SIZE, { credentials: 'include' });
        var data = await resp.json();
        if (data.status !== 200) {
            showById('ordersError');
            return;
        }
        var list = data.data.list || [];
        var total = data.data.total || 0;

        if (list.length === 0) {
            showById('ordersEmpty');
            return;
        }

        var html = '';
        for (var i = 0; i < list.length; i++) {
            html += renderOrderItem(list[i]);
        }
        document.getElementById('ordersList').innerHTML = html;
        showById('ordersList');

        var pagHtml = renderPagination(total, CURRENT_PAGE, PAGE_SIZE);
        if (pagHtml) {
            document.getElementById('ordersPagination').innerHTML = pagHtml;
            showById('ordersPagination');
        }
    } catch (e) {
        showById('ordersError');
    }
}

(function initOrders() {
    if (window.__marketUser) {
        loadOrders();
        return;
    }
    var checkCount = 0;
    var timer = setInterval(function() {
        checkCount++;
        if (window.__marketUser) {
            clearInterval(timer);
            loadOrders();
        } else if (checkCount > 50) {
            clearInterval(timer);
            hideById('ordersLoading');
            showById('ordersUnauth');
        }
    }, 100);
})();

<?php echo \Market\Auth::jsSnippet($apiBaseUrl); ?>
</script>
</body>
</html>