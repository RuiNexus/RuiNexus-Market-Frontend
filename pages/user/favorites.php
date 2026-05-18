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

function billingLabel($cycle) {
    $map = [
        'monthly' => '月付', 'quarterly' => '季付',
        'semiannually' => '半年付', 'annually' => '年付',
        'biennially' => '两年付', 'triennially' => '三年付',
        'onetime' => '永久', 'free' => '免费',
    ];
    return $map[$cycle] ?? strtoupper($cycle);
}

function fmtPrice($p) {
    return ($p == intval($p)) ? number_format($p) : number_format($p, 2);
}
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的收藏 - <?php echo htmlspecialchars($siteName); ?></title>
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
        <li><a href="/user/favorites" class="active">收藏</a></li>
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
    <div class="section-label">05 / FAVORITES</div>
    <h2 class="section-title">我的收藏</h2>

    <div id="favoritesLoading" class="empty">
        <div class="empty__icon"><i class="fas fa-spinner fa-pulse"></i></div>
        <p>正在加载...</p>
    </div>

    <div id="favoritesUnauth" class="empty" style="display:none;">
        <div class="empty__icon"><i class="fas fa-lock"></i></div>
        <p>请先登录以查看您的收藏</p>
        <a href="<?php echo Auth::getLoginUrl($apiBaseUrl); ?>" class="detail__btn detail__btn--buy" style="display:inline-flex;margin-top:20px;width:auto;padding:12px 28px;">
            <i class="fas fa-sign-in-alt"></i> 登录账号
        </a>
    </div>

    <div id="favoritesError" class="empty" style="display:none;">
        <div class="empty__icon"><i class="fas fa-exclamation-triangle"></i></div>
        <p>加载失败，请刷新重试</p>
    </div>

    <div id="favoritesEmpty" class="empty" style="display:none;">
        <div class="empty__icon"><i class="far fa-heart"></i></div>
        <p>暂无收藏</p>
        <a href="/" class="detail__btn detail__btn--buy" style="display:inline-flex;margin-top:20px;width:auto;padding:12px 28px;">
            <i class="fas fa-search"></i> 浏览市场
        </a>
    </div>

    <div id="favoritesList" class="card-grid" style="display:none;">
    </div>

    <div id="favoritesPagination" class="pagination" style="display:none;">
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

function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function billingLabelJS(cycle) {
    var map = {monthly:'月付',quarterly:'季付',semiannually:'半年付',annually:'年付',biennially:'两年付',triennially:'三年付',onetime:'永久',free:'免费'};
    return map[cycle] || (cycle ? cycle.toUpperCase() : '');
}

function fmtPriceJS(p) {
    return p === Math.floor(p) ? p.toLocaleString() : p.toFixed(2);
}

function showById(id) { var el = document.getElementById(id); if (el) el.style.display = ''; }
function hideById(id) { var el = document.getElementById(id); if (el) el.style.display = 'none'; }

function renderFavoriteCard(v, specLabels) {
    var specData = typeof v.spec_data === 'string' ? JSON.parse(v.spec_data || '{}') : (v.spec_data || {});
    var billingTag = billingLabelJS(v.billing_cycle);
    var remainingDays = v.remaining_days;
    var discount = 0;
    if ((v.original_amount || 0) > 0 && (v.sale_price || 0) < (v.original_amount || 0)) {
        var r = Math.round(v.sale_price / v.original_amount * 100) / 10;
        if (r < 10) discount = r;
    }

    var tagsHtml = '';
    if (billingTag) tagsHtml += '<span class="card__tag">' + escHtml(billingTag) + '</span>';
    if (remainingDays !== null && remainingDays !== undefined && remainingDays > 0) {
        var cls = remainingDays > 65 ? 'is-safe' : (remainingDays > 30 ? 'is-warning' : 'is-danger');
        tagsHtml += '<span class="card__tag card__tag--days ' + cls + '">' + remainingDays + ' 天剩余</span>';
    } else if (remainingDays === null || remainingDays === undefined) {
        tagsHtml += '<span class="card__tag">永久有效</span>';
    }

    var specsHtml = '';
    if (Object.keys(specData).length > 0) {
        specsHtml += '<div class="card__specs">';
        for (var field in specData) {
            var label = specLabels[field] || field;
            var val = Array.isArray(specData[field]) ? specData[field].join(',') : String(specData[field]);
            specsHtml += '<div class="card__spec-row"><span class="card__spec-label">' + escHtml(label) + '</span><span class="card__spec-value">' + escHtml(val) + '</span></div>';
        }
        specsHtml += '</div>';
    }

    var discountHtml = discount > 0 ? '<span class="card__discount">-' + Math.round((1 - discount / 10) * 100) + '%</span>' : '';
    var price = parseFloat(v.sale_price || 0);

    return '<article class="card" onclick="location.href=\'/detail?id=' + v.id + '\'">' +
        '<div class="card__header">' +
            '<h3 class="card__title" title="' + escHtml(v.title || v.product_name) + '">' + escHtml(v.title || v.product_name) + '</h3>' +
            '<svg class="card__arrow" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="butt"><path d="m16 39.513 15.556-15.557L16 8.4"></path></svg>' +
        '</div>' +
        '<div class="card__tags">' + tagsHtml + '</div>' +
        specsHtml +
        '<div class="card__pricing">' +
            discountHtml +
            '<span class="card__price-symbol">¥</span>' +
            '<span class="card__price-amount">' + escHtml(fmtPriceJS(price)) + '</span>' +
            '<span class="card__price-unit">CNY</span>' +
            ((v.original_amount || 0) > 0 && price !== parseFloat(v.original_amount) ? '<span class="card__price-original">¥' + escHtml(fmtPriceJS(parseFloat(v.original_amount))) + '</span>' : '') +
        '</div>' +
    '</article>';
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

async function loadFavorites() {
    hideById('favoritesLoading');
    hideById('favoritesUnauth');
    hideById('favoritesError');
    hideById('favoritesEmpty');
    hideById('favoritesList');
    hideById('favoritesPagination');

    if (!window.__marketUser || !window.__marketUser.loggedIn) {
        showById('favoritesUnauth');
        return;
    }

    try {
        var resp = await fetch(API_BASE + '/market_api.php?action=favorites&page=' + CURRENT_PAGE + '&size=' + PAGE_SIZE, { credentials: 'include' });
        var data = await resp.json();
        if (data.status !== 200) {
            showById('favoritesError');
            return;
        }
        var list = data.data.list || [];
        var total = data.data.total || 0;
        var specLabels = data.data.spec_labels || {};

        if (list.length === 0) {
            showById('favoritesEmpty');
            return;
        }

        var html = '';
        for (var i = 0; i < list.length; i++) {
            html += renderFavoriteCard(list[i], specLabels);
        }
        document.getElementById('favoritesList').innerHTML = html;
        showById('favoritesList');

        var pagHtml = renderPagination(total, CURRENT_PAGE, PAGE_SIZE);
        if (pagHtml) {
            document.getElementById('favoritesPagination').innerHTML = pagHtml;
            showById('favoritesPagination');
        }
    } catch (e) {
        showById('favoritesError');
    }
}

(function initFavorites() {
    if (window.__marketUser) {
        loadFavorites();
        return;
    }
    var checkCount = 0;
    var timer = setInterval(function() {
        checkCount++;
        if (window.__marketUser) {
            clearInterval(timer);
            loadFavorites();
        } else if (checkCount > 50) {
            clearInterval(timer);
            hideById('favoritesLoading');
            showById('favoritesUnauth');
        }
    }, 100);
})();

<?php echo \Market\Auth::jsSnippet($apiBaseUrl); ?>
</script>
</body>
</html>