<?php

use Market\Auth;

$uid = Auth::getUid();
if (!$uid) { header('Location: ' . Auth::getLoginUrl($apiBase)); exit; }

$page    = max(1, intval($_GET['page'] ?? 1));
$size    = 20;
$result  = $api->getFavorites(['page' => $page, 'size' => $size]);
$list    = $result['data']['list'] ?? [];
$total   = $result['data']['total'] ?? 0;
$specLabels = $result['data']['spec_labels'] ?? [];

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

    <?php if (empty($list)): ?>
    <div class="empty">
        <div class="empty__icon"><i class="far fa-heart"></i></div>
        <p>暂无收藏</p>
        <a href="/" class="detail__btn detail__btn--buy" style="display:inline-flex;margin-top:20px;width:auto;padding:12px 28px;">
            <i class="fas fa-search"></i> 浏览市场
        </a>
    </div>
    <?php else: ?>
    <div class="card-grid">
        <?php foreach ($list as $v): ?>
        <?php
        $specData = is_string($v['spec_data']) ? json_decode($v['spec_data'], true) : ($v['spec_data'] ?? []);
        $billingTag = billingLabel($v['billing_cycle'] ?? '');
        $remainingDays = $v['remaining_days'] ?? null;
        $discount = 0;
        if (($v['original_amount'] ?? 0) > 0 && ($v['sale_price'] ?? 0) < $v['original_amount']) {
            $r = round($v['sale_price'] / $v['original_amount'] * 10, 1);
            if ($r < 10) $discount = $r;
        }
        ?>
        <article class="card" onclick="location.href='/detail?id=<?php echo $v['id']; ?>'">
            <div class="card__header">
                <h3 class="card__title" title="<?php echo htmlspecialchars($v['title'] ?? $v['product_name']); ?>">
                    <?php echo htmlspecialchars($v['title'] ?? $v['product_name']); ?>
                </h3>
                <svg class="card__arrow" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="butt">
                    <path d="m16 39.513 15.556-15.557L16 8.4"></path>
                </svg>
            </div>

            <div class="card__tags">
                <?php if ($billingTag): ?><span class="card__tag"><?php echo htmlspecialchars($billingTag); ?></span><?php endif; ?>
                <?php if ($remainingDays !== null && $remainingDays > 0): ?>
                <span class="card__tag card__tag--days <?php echo $remainingDays > 65 ? 'is-safe' : ($remainingDays > 30 ? 'is-warning' : 'is-danger'); ?>"><?php echo $remainingDays; ?> 天剩余</span>
                <?php elseif ($remainingDays === null): ?>
                <span class="card__tag">永久有效</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($specData)): ?>
            <div class="card__specs">
                <?php foreach ($specData as $field => $value): ?>
                <?php $label = $specLabels[$field] ?? $field; ?>
                <div class="card__spec-row">
                    <span class="card__spec-label"><?php echo htmlspecialchars($label); ?></span>
                    <span class="card__spec-value"><?php echo htmlspecialchars(is_array($value) ? implode(',', $value) : strval($value)); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="card__pricing">
                <?php if ($discount > 0): ?><span class="card__discount">-<?php echo round((1 - $discount / 10) * 100); ?>%</span><?php endif; ?>
                <span class="card__price-symbol">¥</span>
                <span class="card__price-amount"><?php echo htmlspecialchars(fmtPrice(floatval($v['sale_price'] ?? 0))); ?></span>
                <span class="card__price-unit">CNY</span>
                <?php if (($v['original_amount'] ?? 0) > 0 && $v['sale_price'] != $v['original_amount']): ?>
                <span class="card__price-original">¥<?php echo htmlspecialchars(fmtPrice(floatval($v['original_amount']))); ?></span>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <?php
    $totalPages = max(1, ceil($total / $size));
    if ($totalPages > 1):
    ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>" class="pagination__item"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        if ($start > 1) {
            echo '<a href="?page=1" class="pagination__item">1</a>';
            if ($start > 2) echo '<span class="pagination__item disabled">...</span>';
        }
        for ($i = $start; $i <= $end; $i++) {
            echo '<a href="?page=' . $i . '" class="pagination__item ' . ($i == $page ? 'active' : '') . '">' . $i . '</a>';
        }
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) echo '<span class="pagination__item disabled">...</span>';
            echo '<a href="?page=' . $totalPages . '" class="pagination__item">' . $totalPages . '</a>';
        }
        ?>
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>" class="pagination__item"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
        <span class="pagination__info">第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页</span>
    </div>
    <?php endif; ?>
    <?php endif; ?>
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
<?php echo \Market\Auth::jsSnippet($apiBaseUrl); ?>
</script>
</body>
</html>
