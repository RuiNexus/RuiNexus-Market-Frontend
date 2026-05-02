<?php

/**
 * RuiNexus Market - 首页
 *
 * 服务器/产品交易市场列表
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

function remainingClass($percentage) {
    if ($percentage > 65) return 'is-safe';
    if ($percentage > 30) return 'is-warning';
    return 'is-danger';
}

function billingCycleLabel($cycle) {
    $map = [
        'monthly'       => '月',
        'quarterly'     => '季',
        'semiannually'  => '半年',
        'annually'      => '年',
        'biennially'    => '两年',
        'triennially'   => '三年',
        'onetime'       => '永久',
        'free'          => '免费',
    ];
    return $map[$cycle] ?? $cycle;
}

function discountRate($salePrice, $originalPrice) {
    if ($originalPrice <= 0 || $salePrice >= $originalPrice) return 0;
    $rate = round($salePrice / $originalPrice * 10, 1);
    return $rate < 10 ? $rate : 0;
}

function formatPrice($price) {
    if ($price == intval($price)) return number_format($price);
    return number_format($price, 2);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteName); ?> - 安全可靠的数字资产与产品交易平台</title>
    <meta name="keywords" content="交易市场,数字资产交易,担保交易,安全转让,挂单交易">
    <meta name="description" content="<?php echo htmlspecialchars($siteConfig['seo_desc'] ?? '专业的数字资产与产品交易平台，提供担保交易、安全转让、在售挂单。'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- ===== Header ===== -->
<header class="top-header">
    <div class="container">
        <div class="top-header__left">
            <a href="/" class="top-header__logo">
                <span class="top-header__logo-icon"><i class="fas fa-store"></i></span>
                <?php echo htmlspecialchars($siteName); ?>
            </a>
            <nav class="top-header__nav">
                <a href="/" class="active">交易市场</a>
                <a href="/about">帮助中心</a>
            </nav>
        </div>
        <div class="top-header__right">
            <?php if ($user['loggedIn']): ?>
                <a href="/publish" class="top-header__publish-btn">
                    <i class="fas fa-plus"></i> 发布商品
                </a>
                <a href="/user/listings" class="top-header__user-btn">
                    <i class="fas fa-user"></i> 我的
                </a>
                <a href="/user/orders" class="top-header__user-btn">
                    <i class="fas fa-shopping-cart"></i> 订单
                </a>
            <?php else: ?>
                <a href="<?php echo Auth::getLoginUrl($apiBase); ?>" class="top-header__user-btn">
                    <i class="fas fa-sign-in-alt"></i> 登录
                </a>
                <a href="<?php echo Auth::getRegisterUrl($apiBase); ?>" class="top-header__publish-btn">
                    <i class="fas fa-user-plus"></i> 注册
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- ===== Notice Bar ===== -->
<?php if ($notice): ?>
<div class="site-notice"><?php echo nl2br(htmlspecialchars($notice)); ?></div>
<?php endif; ?>

<!-- ===== Main Content ===== -->
<main class="main-content">
    <div class="container">
        <!-- Toolbar -->
        <form class="market-toolbar" method="get" action="/">
            <div class="market-toolbar__search">
                <span class="market-toolbar__search-icon"><i class="fas fa-search"></i></span>
                <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="搜索产品名称...">
            </div>
            <div class="market-toolbar__sort">
                <span class="market-toolbar__sort-label">排序:</span>
                <select name="sort" onchange="this.form.submit()">
                    <option value="time_desc" <?php echo $sort==='time_desc'?'selected':''; ?>>最新发布</option>
                    <option value="price_asc" <?php echo $sort==='price_asc'?'selected':''; ?>>价格从低到高</option>
                    <option value="price_desc" <?php echo $sort==='price_desc'?'selected':''; ?>>价格从高到低</option>
                    <option value="remaining_asc" <?php echo $sort==='remaining_asc'?'selected':''; ?>>到期时间从近到远</option>
                    <option value="views_desc" <?php echo $sort==='views_desc'?'selected':''; ?>>浏览量最多</option>
                </select>
            </div>
            <span class="market-toolbar__total">共 <?php echo $total; ?> 条</span>
        </form>

        <!-- Product Grid -->
        <div class="market-product-grid">
            <?php if (empty($list)): ?>
            <div class="market-empty">
                <div class="market-empty__icon"><i class="fas fa-inbox"></i></div>
                <p class="market-empty__text">暂无在售商品</p>
            </div>
            <?php else: ?>
                <?php foreach ($list as $item): ?>
                <?php
                    $specData = is_array($item['spec_data'] ?? null) ? $item['spec_data'] : (is_string($item['spec_data'] ?? null) ? json_decode($item['spec_data'], true) : []);
                    if (!is_array($specData)) $specData = [];
                    $itemSpecLabels = $item['spec_labels'] ?? $specLabels;

                    $remainingDays = $item['remaining_days'] ?? null;
                    $regdate = $item['regdate'] ?? 0;
                    $nextduedate = $item['nextduedate'] ?? 0;
                    $durationDays = ($regdate > 0 && $nextduedate > $regdate) ? round(($nextduedate - $regdate) / 86400) : 0;
                    $hasRemaining = $remainingDays !== null && $durationDays > 0;
                    $remainingPct = $hasRemaining ? min(100, round($remainingDays / max($durationDays, 1) * 100)) : 0;
                    $remClass = remainingClass($remainingPct);

                    $billingLabel = billingCycleLabel($item['billing_cycle'] ?? '');
                    $discount = discountRate($item['sale_price'] ?? 0, $item['original_amount'] ?? 0);

                    $createTime = $item['create_time'] ?? 0;
                    $createdDate = $createTime > 0 ? date('Y-m-d', $createTime) : '';
                    $views = intval($item['views'] ?? 0);
                ?>
                <article class="market-product-card" onclick="location.href='/detail?id=<?php echo $item['id']; ?>'">
                    <!-- Header: Title + Arrow -->
                    <div class="market-product-card__header">
                        <h3 class="market-product-card__title" title="<?php echo htmlspecialchars($item['title'] ?? $item['product_name']); ?>">
                            <?php echo htmlspecialchars($item['title'] ?? $item['product_name']); ?>
                        </h3>
                        <svg class="market-product-card__arrow" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="butt" stroke-linejoin="miter">
                            <path d="m16 39.513 15.556-15.557L16 8.4"></path>
                        </svg>
                    </div>

                    <!-- Description & Tags -->
                    <div class="market-product-card__desc">
                        <?php if ($billingLabel): ?>
                        <span class="market-product-card__tag"><?php echo htmlspecialchars($billingLabel); ?></span>
                        <?php endif; ?>
                        <?php if ($remainingDays !== null): ?>
                        <span class="market-product-card__tag market-product-card__tag--days <?php echo $remClass; ?>">
                            剩余<?php echo $remainingDays; ?>天
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Specs -->
                    <?php if (!empty($specData)): ?>
                    <div class="market-product-card__specs">
                        <?php foreach ($specData as $field => $value):
                            $label = $itemSpecLabels[$field] ?? $field;
                            $isBool = in_array(strtolower((string)$value), ['是','否','yes','no','true','false','支持','不支持'], true);
                            $isYes = in_array(strtolower((string)$value), ['是','yes','true','支持'], true);
                        ?>
                        <div class="market-product-card__spec-row">
                            <span class="market-product-card__spec-label"><?php echo htmlspecialchars($label); ?></span>
                            <?php if ($isBool): ?>
                            <span class="market-product-card__spec-value">
                                <span class="market-product-card__spec-tag <?php echo $isYes ? 'is-yes' : 'is-no'; ?>">
                                    <?php echo $isYes ? '支持' : '不支持'; ?>
                                </span>
                            </span>
                            <?php else: ?>
                            <span class="market-product-card__spec-value"><?php echo htmlspecialchars((string)$value); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Meta -->
                    <div class="market-product-card__meta">
                        <?php if ($createdDate): ?>
                        <span class="market-product-card__meta-item">上架: <?php echo $createdDate; ?></span>
                        <?php endif; ?>
                        <span class="market-product-card__meta-sep"></span>
                        <span class="market-product-card__meta-item"><i class="fas fa-eye"></i> <?php echo $views; ?></span>
                    </div>

                    <!-- Remaining Bar -->
                    <?php if ($hasRemaining): ?>
                    <div class="market-product-card__remaining">
                        <div class="market-product-card__remaining-bar <?php echo $remClass; ?>">
                            <div class="market-product-card__remaining-fill" style="width:<?php echo $remainingPct; ?>%"></div>
                            <span class="market-product-card__remaining-text">
                                <?php echo $durationDays; ?>天 / <?php echo $remainingDays; ?>天 剩余<?php echo $remainingPct; ?>%
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Pricing -->
                    <div class="market-product-card__pricing">
                        <?php if ($discount > 0): ?>
                        <span class="market-product-card__discount-badge"><?php echo $discount; ?>折</span>
                        <?php endif; ?>
                        <span class="market-product-card__price-symbol">¥</span>
                        <span class="market-product-card__price-amount"><?php echo formatPrice($item['sale_price'] ?? 0); ?></span>
                        <span class="market-product-card__price-unit">元</span>
                        <?php if (($item['original_amount'] ?? 0) > 0 && $item['sale_price'] != $item['original_amount']): ?>
                        <span class="market-product-card__original-price">¥<?php echo formatPrice($item['original_amount'] ?? 0); ?></span>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="market-pagination">
            <?php
            $queryParams = function($p) use ($sort, $keyword) {
                return '?page=' . $p . '&sort=' . urlencode($sort) . ($keyword ? '&keyword=' . urlencode($keyword) : '');
            };
            ?>

            <!-- Previous -->
            <a href="<?php echo $queryParams($page - 1); ?>" class="market-pagination__item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <i class="fas fa-chevron-left"></i>
            </a>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);

            if ($start > 1): ?>
            <a href="<?php echo $queryParams(1); ?>" class="market-pagination__item">1</a>
            <?php if ($start > 2): ?>
            <span class="market-pagination__item disabled">...</span>
            <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="<?php echo $queryParams($i); ?>" class="market-pagination__item <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
            <span class="market-pagination__item disabled">...</span>
            <?php endif; ?>
            <a href="<?php echo $queryParams($totalPages); ?>" class="market-pagination__item"><?php echo $totalPages; ?></a>
            <?php endif; ?>

            <!-- Next -->
            <a href="<?php echo $queryParams($page + 1); ?>" class="market-pagination__item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                <i class="fas fa-chevron-right"></i>
            </a>

            <span class="market-pagination__info">第 <?php echo $page; ?>/<?php echo $totalPages; ?> 页</span>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- ===== Footer ===== -->
<footer class="site-footer">
    <div class="container">
        <div class="site-footer__brand">
            <div class="site-footer__brand-name"><?php echo htmlspecialchars($siteName); ?></div>
            <p class="site-footer__brand-desc">
                安全可靠的数字资产与产品交易平台，提供担保交易、安全转让、挂单交易等服务。
            </p>
        </div>
        <div class="site-footer__links">
            <div class="site-footer__links-group">
                <h4>快速链接</h4>
                <ul>
                    <li><a href="/">首页</a></li>
                    <li><a href="/about">帮助中心</a></li>
                </ul>
            </div>
            <div class="site-footer__links-group">
                <h4>关于我们</h4>
                <ul>
                    <li><a href="/about">隐私政策</a></li>
                    <li><a href="/about">服务声明</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div style="max-width:var(--max-width);margin:0 auto;padding:0 20px">
        <div class="site-footer__divider"></div>
    </div>
    <div class="site-footer__bottom">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. 保留所有权利.</p>
        <p>Powered by RuiNexus</p>
    </div>
</footer>

</body>
</html>
