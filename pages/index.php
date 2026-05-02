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
$siteName   = $siteConfig['site_name'] ?? 'RUI NEXUS MARKET';
$notice     = $siteConfig['notice_content'] ?? '';

$listResult = $api->getList([
    'page'    => $page,
    'size'    => $size,
    'sort'    => $sort,
    'keyword' => $keyword,
]);

$list  = $listResult['data']['list'] ?? [];
$total = $listResult['data']['total'] ?? 0;
$totalPages = max(1, ceil($total / $size));
$specLabels = $listResult['data']['spec_labels'] ?? [];
$user  = Auth::getUser();

function remainingClass($pct) {
    if ($pct > 65) return 'is-safe';
    if ($pct > 30) return 'is-warning';
    return 'is-danger';
}

function billingLabel($cycle) {
    $map = [
        'monthly' => 'MONTHLY', 'quarterly' => 'QUARTERLY',
        'semiannually' => 'SEMI-ANNUAL', 'annually' => 'ANNUAL',
        'biennially' => 'BIENNIAL', 'triennially' => 'TRIENNIAL',
        'onetime' => 'PERMANENT', 'free' => 'FREE',
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@300;400;500&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- ===== Nav ===== -->
<nav class="nav">
    <a href="/" class="nav-brand"><?php echo htmlspecialchars($siteName); ?></a>
    <ul class="nav-links">
        <li><a href="/" class="active">MARKET</a></li>
        <li><a href="/about">HELP</a></li>
    </ul>
    <div class="nav-actions">
        <?php if ($user['loggedIn']): ?>
            <a href="/publish" class="nav-cta">PUBLISH</a>
            <a href="/user/listings" class="nav-cta--ghost">MY</a>
            <a href="/user/orders" class="nav-cta--ghost">ORDERS</a>
        <?php else: ?>
            <a href="<?php echo Auth::getLoginUrl($apiBase); ?>" class="nav-cta--ghost">SIGN IN</a>
            <a href="<?php echo Auth::getRegisterUrl($apiBase); ?>" class="nav-cta">REGISTER</a>
        <?php endif; ?>
    </div>
</nav>

<!-- ===== Notice ===== -->
<?php if ($notice): ?>
<div class="site-notice"><?php echo nl2br(htmlspecialchars($notice)); ?></div>
<?php endif; ?>

<!-- ===== Hero ===== -->
<section class="hero">
    <div class="hero-label">RUI NEXUS MARKET</div>
    <h1>Server Trading Market</h1>
    <p>安全可靠的服务器与数字资产交易平台。担保交易 · 安全转让 · 实时挂单。</p>
    <div class="hero-buttons">
        <?php if ($user['loggedIn']): ?>
            <a href="/publish" class="btn-primary">PUBLISH NOW</a>
            <a href="/user/listings" class="btn-ghost">MY LISTINGS</a>
        <?php else: ?>
            <a href="<?php echo Auth::getRegisterUrl($apiBase); ?>" class="btn-primary">GET STARTED</a>
            <a href="/about" class="btn-ghost">LEARN MORE</a>
        <?php endif; ?>
    </div>
</section>

<!-- ===== Products Section ===== -->
<section class="section">
    <div class="section-label">01 / PRODUCTS</div>
    <h2 class="section-title">Available Servers</h2>

    <!-- Toolbar -->
    <form class="toolbar" method="get" action="/">
        <div class="toolbar__search">
            <span class="toolbar__search-icon"><i class="fas fa-search"></i></span>
            <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Search products...">
        </div>
        <div class="toolbar__sort">
            <span class="toolbar__sort-label">Sort:</span>
            <select name="sort" onchange="this.form.submit()">
                <option value="time_desc" <?php echo $sort==='time_desc'?'selected':''; ?>>Newest</option>
                <option value="price_asc" <?php echo $sort==='price_asc'?'selected':''; ?>>Price: Low → High</option>
                <option value="price_desc" <?php echo $sort==='price_desc'?'selected':''; ?>>Price: High → Low</option>
                <option value="remaining_asc" <?php echo $sort==='remaining_asc'?'selected':''; ?>>Expiring Soon</option>
                <option value="views_desc" <?php echo $sort==='views_desc'?'selected':''; ?>>Most Viewed</option>
            </select>
        </div>
        <span class="toolbar__total"><?php echo $total; ?> items</span>
    </form>

    <!-- Card Grid -->
    <div class="card-grid">
        <?php if (empty($list)): ?>
        <div class="empty">
            <div class="empty__icon"><i class="fas fa-inbox"></i></div>
            <p>No products available</p>
        </div>
        <?php else: ?>
            <?php foreach ($list as $item): ?>
            <?php
                $specData = is_array($item['spec_data'] ?? null) ? $item['spec_data']
                    : (is_string($item['spec_data'] ?? null) ? json_decode($item['spec_data'], true) : []);
                if (!is_array($specData)) $specData = [];
                $itemSpecLabels = $item['spec_labels'] ?? $specLabels;

                $remainingDays = $item['remaining_days'] ?? null;
                $regdate = $item['regdate'] ?? 0;
                $nextduedate = $item['nextduedate'] ?? 0;
                $durationDays = ($regdate > 0 && $nextduedate > $regdate)
                    ? round(($nextduedate - $regdate) / 86400) : 0;
                $hasRemaining = $remainingDays !== null && $durationDays > 0;
                $remainingPct = $hasRemaining
                    ? min(100, round($remainingDays / max($durationDays, 1) * 100)) : 0;
                $remClass = remainingClass($remainingPct);

                $billingTag = billingLabel($item['billing_cycle'] ?? '');
                $discount = discountRate($item['sale_price'] ?? 0, $item['original_amount'] ?? 0);

                $createTime = $item['create_time'] ?? 0;
                $createdDate = $createTime > 0 ? date('Y-m-d', $createTime) : '';
                $views = intval($item['views'] ?? 0);
            ?>
            <article class="card" onclick="location.href='/detail?id=<?php echo $item['id']; ?>'">

                <!-- Header -->
                <div class="card__header">
                    <h3 class="card__title" title="<?php echo htmlspecialchars($item['title'] ?? $item['product_name']); ?>">
                        <?php echo htmlspecialchars($item['title'] ?? $item['product_name']); ?>
                    </h3>
                    <svg class="card__arrow" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="butt">
                        <path d="m16 39.513 15.556-15.557L16 8.4"></path>
                    </svg>
                </div>

                <!-- Tags -->
                <div class="card__tags">
                    <?php if ($billingTag): ?>
                    <span class="card__tag"><?php echo htmlspecialchars($billingTag); ?></span>
                    <?php endif; ?>
                    <?php if ($remainingDays !== null): ?>
                    <span class="card__tag card__tag--days <?php echo $remClass; ?>">
                        <?php echo $remainingDays; ?> DAYS LEFT
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Specs -->
                <?php if (!empty($specData)): ?>
                <div class="card__specs">
                    <?php foreach ($specData as $field => $value):
                        $label = $itemSpecLabels[$field] ?? $field;
                        $isBool = in_array(strtolower((string)$value), ['是','否','yes','no','true','false','支持','不支持'], true);
                        $isYes = in_array(strtolower((string)$value), ['是','yes','true','支持'], true);
                    ?>
                    <div class="card__spec-row">
                        <span class="card__spec-label"><?php echo htmlspecialchars($label); ?></span>
                        <?php if ($isBool): ?>
                        <span class="card__spec-value">
                            <span class="card__spec-tag <?php echo $isYes ? 'is-yes' : 'is-no'; ?>">
                                <?php echo $isYes ? 'YES' : 'NO'; ?>
                            </span>
                        </span>
                        <?php else: ?>
                        <span class="card__spec-value"><?php echo htmlspecialchars((string)$value); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Meta -->
                <div class="card__meta">
                    <?php if ($createdDate): ?>
                    <span class="card__meta-item"><?php echo $createdDate; ?></span>
                    <?php endif; ?>
                    <span class="card__meta-sep"></span>
                    <span class="card__meta-item"><i class="fas fa-eye"></i> <?php echo $views; ?></span>
                </div>

                <!-- Remaining Bar -->
                <?php if ($hasRemaining): ?>
                <div class="card__remaining-bar <?php echo $remClass; ?>">
                    <div class="card__remaining-fill" style="width:<?php echo $remainingPct; ?>%"></div>
                    <span class="card__remaining-text">
                        <?php echo $remainingDays; ?> / <?php echo $durationDays; ?> DAYS (<?php echo $remainingPct; ?>%)
                    </span>
                </div>
                <?php endif; ?>

                <!-- Pricing -->
                <div class="card__pricing">
                    <?php if ($discount > 0): ?>
                    <span class="card__discount">-<?php echo round((1 - $discount/10) * 100); ?>%</span>
                    <?php endif; ?>
                    <span class="card__price-symbol">¥</span>
                    <span class="card__price-amount"><?php echo fmtPrice($item['sale_price'] ?? 0); ?></span>
                    <span class="card__price-unit">CNY</span>
                    <?php if (($item['original_amount'] ?? 0) > 0 && $item['sale_price'] != $item['original_amount']): ?>
                    <span class="card__price-original">¥<?php echo fmtPrice($item['original_amount'] ?? 0); ?></span>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php
        $qp = function($p) use ($sort, $keyword) {
            return '?page=' . $p . '&sort=' . urlencode($sort) . ($keyword ? '&keyword=' . urlencode($keyword) : '');
        };
        ?>
        <a href="<?php echo $qp($page - 1); ?>" class="pagination__item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <i class="fas fa-chevron-left"></i>
        </a>

        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        if ($start > 1): ?>
        <a href="<?php echo $qp(1); ?>" class="pagination__item">1</a>
        <?php if ($start > 2): ?><span class="pagination__item disabled">...</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
        <a href="<?php echo $qp($i); ?>" class="pagination__item <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?><span class="pagination__item disabled">...</span><?php endif; ?>
        <a href="<?php echo $qp($totalPages); ?>" class="pagination__item"><?php echo $totalPages; ?></a>
        <?php endif; ?>

        <a href="<?php echo $qp($page + 1); ?>" class="pagination__item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
            <i class="fas fa-chevron-right"></i>
        </a>
        <span class="pagination__info">PAGE <?php echo $page; ?> / <?php echo $totalPages; ?></span>
    </div>
    <?php endif; ?>
</section>

<hr class="section-divider">

<!-- ===== Dark Showcase ===== -->
<section class="dark-showcase">
    <div class="dark-showcase-inner">
        <div style="font-family:var(--font-display);font-size:12px;font-weight:400;color:var(--text-muted);text-transform:uppercase;letter-spacing:1.4px;margin-bottom:16px;">HOW IT WORKS</div>
        <h2 style="font-family:var(--font-display);font-size:48px;font-weight:300;color:#fff;margin-bottom:16px;line-height:1.2;">Simple & Secure</h2>
        <p style="font-size:16px;color:rgba(255,255,255,0.7);line-height:1.6;margin-bottom:24px;max-width:600px;">
            List your server, set your price, and connect with buyers. Our escrow system ensures safe transactions for both parties.
        </p>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="/about" class="btn-primary">LEARN MORE</a>
            <a href="/publish" class="btn-ghost">START SELLING</a>
        </div>
    </div>
</section>

<!-- ===== Footer ===== -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer__brand">
            <div class="footer__brand-name"><?php echo htmlspecialchars($siteName); ?></div>
            <p class="footer__brand-desc">
                A secure platform for server and digital asset trading. Built with trust and transparency.
            </p>
        </div>
        <div class="footer__links-group">
            <h4>QUICK LINKS</h4>
            <ul>
                <li><a href="/">Market</a></li>
                <li><a href="/about">Help Center</a></li>
                <li><a href="/publish">Publish</a></li>
            </ul>
        </div>
        <div class="footer__links-group">
            <h4>ABOUT</h4>
            <ul>
                <li><a href="/about">Privacy Policy</a></li>
                <li><a href="/about">Terms of Service</a></li>
            </ul>
        </div>
    </div>
    <div class="footer__bottom">
        <p>&copy; <?php echo date('Y'); ?> RuiNexus Market — All rights reserved.</p>
        <p>Powered by RuiNexus</p>
    </div>
</footer>

</body>
</html>
