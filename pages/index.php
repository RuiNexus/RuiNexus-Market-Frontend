<?php

/**
 * RuiNexus Market - 首页 (xAI Design System)
 *
 * 服务器/产品交易市场列表
 * 设计: xAI dark-first brutalist minimalism
 *
 * 开发者: RuiNexus / YeHuaiJing
 * 仓库: https://github.com/RuiNexus/RuiNexus-Market-Frontend
 */

use Market\Auth;

$page     = max(1, intval($_GET['page'] ?? 1));
$sort     = $_GET['sort'] ?? 'time_desc';
$keyword  = trim($_GET['keyword'] ?? '');
$size     = 15;

$siteConfig = $api->getConfig()['data'] ?? [];
$siteName   = $siteConfig['site_name'] ?? 'RuiNexus Market';
$notice     = $siteConfig['notice_content'] ?? '';
$user       = Auth::getUser();

$frontendConfig = require __DIR__ . '/../config.php';
$apiBaseUrl = $frontendConfig['api_base_url'] ?? 'https://test.ruinexus.com';
$siteName = $frontendConfig['site_name'] ?: $siteName;

$initialPage = $page;
$initialSort = $sort;
$initialKeyword = $keyword;

function remainingClass($pct) {
    if ($pct > 65) return 'is-safe';
    if ($pct > 30) return 'is-warning';
    return 'is-danger';
}

function billingLabel($cycle) {
    $map = [
        'monthly' => '月付', 'quarterly' => '季付',
        'semiannually' => '半年付', 'annually' => '年付',
        'biennially' => '两年付', 'triennially' => '三年付',
        'onetime' => '永久', 'free' => '免费',
    ];
    return $map[$cycle] ?? strtoupper($cycle);
}

function discountRate($sale, $orig) {
    if ($orig <= 0 || $sale >= $orig) return 0;
    $r = round($sale / $orig * 10, 1);
    return $r < 10 ? $r : 0;
}

function fmtPrice($p) {
    return ($p == intval($p)) ? number_format($p) : number_format($p, 2);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteName); ?> — 服务器交易市场</title>
    <meta name="description" content="<?php echo htmlspecialchars($siteConfig['seo_desc'] ?? '安全可靠的服务器与数字资产交易平台'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- ===== Nav ===== -->
<nav class="nav">
    <a href="https://test.ruinexus.com" class="nav-logo">
        <img src="https://test.ruinexus.com/themes/clientarea/default/assets/images/logo-inovice.png" alt="<?php echo htmlspecialchars($siteName); ?>">
    </a>
    <ul class="nav-links">
        <li><a href="/" class="active">市场</a></li>
        <li><a href="/about">帮助</a></li>
    </ul>
    <div class="nav-actions">
        <?php if ($user['loggedIn']): ?>
            <a href="/publish" class="nav-cta">发布</a>
            <a href="/user/listings" class="nav-cta--ghost">我的</a>
            <a href="/user/orders" class="nav-cta--ghost">订单</a>
        <?php else: ?>
            <a href="<?php echo Auth::getLoginUrl($apiBase); ?>" class="nav-cta--ghost">登录</a>
            <a href="<?php echo Auth::getRegisterUrl($apiBase); ?>" class="nav-cta">注册</a>
        <?php endif; ?>
    </div>
</nav>

<!-- ===== Notice ===== -->
<?php if ($notice): ?>
<div class="site-notice"><?php echo nl2br(htmlspecialchars($notice)); ?></div>
<?php endif; ?>

<!-- ===== Debug Info ===== -->
<div style="display:none;" id="api-debug" class="debug-panel">
    <div style="background:#1a1a2e;padding:12px;margin-bottom:16px;border-radius:4px;font-family:monospace;font-size:12px;color:#fff;">
        <div>API Status: <span style="color:<?php echo $apiStatus == 200 ? '#4ade80' : '#f87171'; ?>"><?php echo htmlspecialchars($apiStatus); ?></span></div>
        <div>API Message: <?php echo htmlspecialchars($apiMsg); ?></div>
        <div>API URL: <?php echo htmlspecialchars('https://test.ruinexus.com/market_api.php?action=list&page='.$page.'&size='.$size.'&sort='.$sort.($keyword ? '&keyword='.urlencode($keyword) : '')); ?></div>
        <div>Items Count: <?php echo count($list); ?></div>
    </div>
</div>

<!-- ===== Products Section ===== -->
<section class="section">
    <div class="section-label">01 / 商品</div>
    <h2 class="section-title">可用服务器</h2>

    <!-- Toolbar -->
    <form class="toolbar" id="searchForm" method="get" action="/">
        <div class="toolbar__search">
            <span class="toolbar__search-icon"><i class="fas fa-search"></i></span>
            <input type="text" name="keyword" id="searchKeyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="搜索商品...">
        </div>
        <div class="toolbar__sort">
            <span class="toolbar__sort-label">排序:</span>
            <select name="sort" id="sortSelect" onchange="loadProducts()">
                <option value="time_desc" <?php echo $sort==='time_desc'?'selected':''; ?>>最新</option>
                <option value="price_asc" <?php echo $sort==='price_asc'?'selected':''; ?>>价格从低到高</option>
                <option value="price_desc" <?php echo $sort==='price_desc'?'selected':''; ?>>价格从高到低</option>
                <option value="remaining_asc" <?php echo $sort==='remaining_asc'?'selected':''; ?>>即将到期</option>
                <option value="views_desc" <?php echo $sort==='views_desc'?'selected':''; ?>>最多浏览</option>
            </select>
        </div>
        <span class="toolbar__total" id="totalCount">0 件商品</span>
    </form>

    <!-- Loading -->
    <div class="loading" id="loading" style="display:none;">
        <i class="fas fa-spinner fa-spin"></i>
        <span>加载中...</span>
    </div>

    <!-- Card Grid -->
    <div class="card-grid" id="cardGrid">
        <div class="empty">
            <div class="empty__icon"><i class="fas fa-inbox"></i></div>
            <p>加载商品...</p>
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination" id="pagination" style="display:none;">
    </div>
</section>

<!-- ===== Footer ===== -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer__brand">
            <div class="footer__brand-name"><?php echo htmlspecialchars($siteName); ?></div>
            <p class="footer__brand-desc">
                安全可靠的服务器与数字资产交易平台。值得信赖，透明交易。
            </p>
        </div>
        <div class="footer__links-group">
            <h4>快速链接</h4>
            <ul>
                <li><a href="/">市场</a></li>
                <li><a href="/about">帮助中心</a></li>
                <li><a href="/publish">发布</a></li>
            </ul>
        </div>
        <div class="footer__links-group">
            <h4>关于</h4>
            <ul>
                <li><a href="/about">隐私政策</a></li>
                <li><a href="/about">服务条款</a></li>
            </ul>
        </div>
    </div>
    <div class="footer__bottom">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?> — 版权所有。</p>
        <p>由 <?php echo htmlspecialchars($siteName); ?> 驱动</p>
    </div>
</footer>

<script>
const API_BASE = '<?php echo htmlspecialchars($apiBaseUrl); ?>';
const INITIAL_PAGE = <?php echo $initialPage; ?>;
const INITIAL_SORT = '<?php echo htmlspecialchars($initialSort); ?>';
const INITIAL_KEYWORD = '<?php echo htmlspecialchars($initialKeyword); ?>';

let currentPage = INITIAL_PAGE;
let currentSort = INITIAL_SORT;
let currentKeyword = INITIAL_KEYWORD;
let specLabels = {};

function billingLabel(cycle) {
    const map = {
        'monthly': '月付', 'quarterly': '季付',
        'semiannually': '半年付', 'annually': '年付',
        'biennially': '两年付', 'triennially': '三年付',
        'onetime': '永久', 'free': '免费',
    };
    return map[cycle] || (cycle || '').toUpperCase();
}

function remainingClass(pct) {
    if (pct > 65) return 'is-safe';
    if (pct > 30) return 'is-warning';
    return 'is-danger';
}

function discountRate(sale, orig) {
    if (orig <= 0 || sale >= orig) return 0;
    const r = Math.round(sale / orig * 10 * 10) / 10;
    return r < 10 ? r : 0;
}

function fmtPrice(p) {
    return (p == parseInt(p)) ? p.toLocaleString() : p.toFixed(2);
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function renderCard(item) {
    const specData = typeof item.spec_data === 'string' ? JSON.parse(item.spec_data) : (item.spec_data || {});
    const itemSpecLabels = item.spec_labels || specLabels;
    
    const remainingDays = item.remaining_days;
    const regdate = item.regdate || 0;
    const nextduedate = item.nextduedate || 0;
    const durationDays = (regdate > 0 && nextduedate > regdate) ? Math.round((nextduedate - regdate) / 86400) : 0;
    const hasRemaining = remainingDays !== null && remainingDays !== undefined && durationDays > 0;
    const remainingPct = hasRemaining ? Math.min(100, Math.round(remainingDays / Math.max(durationDays, 1) * 100)) : 0;
    const remClass = remainingClass(remainingPct);
    
    const billingTag = billingLabel(item.billing_cycle);
    const discount = discountRate(item.sale_price || 0, item.original_amount || 0);
    
    const createTime = item.create_time || 0;
    const createdDate = createTime > 0 ? new Date(createTime * 1000).toISOString().split('T')[0] : '';
    const views = parseInt(item.views || 0);

    let specsHtml = '';
    if (Object.keys(specData).length > 0) {
        specsHtml = '<div class="card__specs">';
        for (const [field, value] of Object.entries(specData)) {
            const label = itemSpecLabels[field] || field;
            const lowerValue = String(value).toLowerCase();
            const isBool = ['是','否','yes','no','true','false','支持','不支持'].includes(lowerValue);
            const isYes = ['是','yes','true','支持'].includes(lowerValue);
            
            specsHtml += `<div class="card__spec-row">
                <span class="card__spec-label">${escapeHtml(label)}</span>
                <span class="card__spec-value">${isBool ? `<span class="card__spec-tag ${isYes ? 'is-yes' : 'is-no'}">${isYes ? '支持' : '不支持'}</span>` : escapeHtml(String(value))}</span>
            </div>`;
        }
        specsHtml += '</div>';
    }

    return `<article class="card" onclick="location.href='/detail?id=${item.id}'">
        <div class="card__header">
            <h3 class="card__title" title="${escapeHtml(item.title || item.product_name)}">
                ${escapeHtml(item.title || item.product_name)}
            </h3>
            <svg class="card__arrow" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="butt">
                <path d="m16 39.513 15.556-15.557L16 8.4"></path>
            </svg>
        </div>
        
        <div class="card__tags">
            ${billingTag ? `<span class="card__tag">${escapeHtml(billingTag)}</span>` : ''}
            ${remainingDays !== null && remainingDays !== undefined ? `<span class="card__tag card__tag--days ${remClass}">${remainingDays} 天剩余</span>` : ''}
        </div>
        
        ${specsHtml}
        
        <div class="card__meta">
            ${createdDate ? `<span class="card__meta-item">${createdDate}</span>` : ''}
            <span class="card__meta-sep"></span>
            <span class="card__meta-item"><i class="fas fa-eye"></i> ${views}</span>
        </div>
        
        ${hasRemaining ? `<div class="card__remaining-bar ${remClass}">
            <div class="card__remaining-fill" style="width:${remainingPct}%"></div>
            <span class="card__remaining-text">${remainingDays} / ${durationDays} 天 (${remainingPct}%)</span>
        </div>` : ''}
        
        <div class="card__pricing">
            ${discount > 0 ? `<span class="card__discount">-${Math.round((1 - discount/10) * 100)}%</span>` : ''}
            <span class="card__price-symbol">¥</span>
            <span class="card__price-amount">${fmtPrice(item.sale_price || 0)}</span>
            <span class="card__price-unit">CNY</span>
            ${(item.original_amount || 0) > 0 && item.sale_price != item.original_amount ? `<span class="card__price-original">¥${fmtPrice(item.original_amount)}</span>` : ''}
        </div>
    </article>`;
}

function renderPagination(total, pageSize) {
    const totalPages = Math.max(1, Math.ceil(total / pageSize));
    if (totalPages <= 1) {
        document.getElementById('pagination').style.display = 'none';
        return;
    }

    let html = `<a href="#" class="pagination__item ${currentPage <= 1 ? 'disabled' : ''}" onclick="goPage(${currentPage - 1})">
        <i class="fas fa-chevron-left"></i>
    </a>`;

    const start = Math.max(1, currentPage - 2);
    const end = Math.min(totalPages, currentPage + 2);

    if (start > 1) {
        html += `<a href="#" class="pagination__item" onclick="goPage(1)">1</a>`;
        if (start > 2) html += `<span class="pagination__item disabled">...</span>`;
    }

    for (let i = start; i <= end; i++) {
        html += `<a href="#" class="pagination__item ${i === currentPage ? 'active' : ''}" onclick="goPage(${i})">${i}</a>`;
    }

    if (end < totalPages) {
        if (end < totalPages - 1) html += `<span class="pagination__item disabled">...</span>`;
        html += `<a href="#" class="pagination__item" onclick="goPage(${totalPages})">${totalPages}</a>`;
    }

    html += `<a href="#" class="pagination__item ${currentPage >= totalPages ? 'disabled' : ''}" onclick="goPage(${currentPage + 1})">
        <i class="fas fa-chevron-right"></i>
    </a>`;
    html += `<span class="pagination__info">第 ${currentPage} / ${totalPages} 页</span>`;

    document.getElementById('pagination').innerHTML = html;
    document.getElementById('pagination').style.display = 'flex';
}

function goPage(page) {
    if (page < 1) return;
    currentPage = page;
    loadProducts();
}

function loadProducts() {
    currentSort = document.getElementById('sortSelect').value;
    currentKeyword = document.getElementById('searchKeyword').value.trim();

    const url = `${API_BASE}/market_api.php?action=list&page=${currentPage}&size=15&sort=${encodeURIComponent(currentSort)}${currentKeyword ? '&keyword=' + encodeURIComponent(currentKeyword) : ''}`;
    
    document.getElementById('loading').style.display = 'flex';
    document.getElementById('cardGrid').style.opacity = '0.5';

    fetch(url)
        .then(response => response.json())
        .then(data => {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('cardGrid').style.opacity = '1';

            if (data.status === 200 && data.data) {
                specLabels = data.data.spec_labels || {};
                const list = data.data.list || [];
                const total = data.data.total || 0;

                document.getElementById('totalCount').textContent = `${total} 件商品`;

                if (list.length === 0) {
                    document.getElementById('cardGrid').innerHTML = `<div class="empty">
                        <div class="empty__icon"><i class="fas fa-inbox"></i></div>
                        <p>暂无商品</p>
                    </div>`;
                } else {
                    document.getElementById('cardGrid').innerHTML = list.map(renderCard).join('');
                }

                renderPagination(total, 15);
            } else {
                document.getElementById('cardGrid').innerHTML = `<div class="empty">
                    <div class="empty__icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <p>${data.msg || '加载商品失败'}</p>
                </div>`;
            }
        })
        .catch(error => {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('cardGrid').style.opacity = '1';
            document.getElementById('cardGrid').innerHTML = `<div class="empty">
                <div class="empty__icon"><i class="fas fa-exclamation-triangle"></i></div>
                <p>网络错误: ${error.message}</p>
            </div>`;
        });
}

document.getElementById('searchKeyword').addEventListener('keyup', (e) => {
    if (e.key === 'Enter') {
        currentPage = 1;
        loadProducts();
    }
});

document.getElementById('searchForm').addEventListener('submit', (e) => {
    e.preventDefault();
    currentPage = 1;
    loadProducts();
});

document.addEventListener('DOMContentLoaded', loadProducts);
<?php echo \Market\Auth::jsSnippet($apiBaseUrl); ?>
</script>

</body>
</html>
