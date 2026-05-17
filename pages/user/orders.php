<?php

use Market\Auth;

$uid = Auth::getUid();
if (!$uid) { header('Location: ' . Auth::getLoginUrl($apiBase)); exit; }

$page    = max(1, intval($_GET['page'] ?? 1));
$size    = 20;
$result  = $api->getMyOrders(['page' => $page, 'size' => $size]);
$list    = $result['data']['list'] ?? [];
$total   = $result['data']['total'] ?? 0;

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

    <?php if (empty($list)): ?>
    <div class="empty">
        <div class="empty__icon"><i class="fas fa-shopping-bag"></i></div>
        <p>暂无购买记录</p>
        <a href="/" class="detail__btn detail__btn--buy" style="display:inline-flex;margin-top:20px;width:auto;padding:12px 28px;">
            <i class="fas fa-search"></i> 浏览市场
        </a>
    </div>
    <?php else: ?>
    <div class="listings__list">
        <?php foreach ($list as $v): ?>
        <div class="listings__item">
            <div class="listings__item-main">
                <div class="listings__item-header">
                    <span class="listings__item-title"><?php echo htmlspecialchars($v['title'] ?? ''); ?></span>
                    <span class="listings__status <?php echo $statusClassMap[$v['status']] ?? ''; ?>"><?php echo $statusMap[$v['status']] ?? ''; ?></span>
                </div>
                <div class="listings__item-meta">
                    <span><i class="fas fa-credit-card"></i> <?php echo $payTypeMap[$v['pay_type']] ?? ''; ?></span>
                    <span><i class="far fa-clock"></i> <?php echo date('Y-m-d H:i', $v['create_time']); ?></span>
                </div>
            </div>
            <div class="listings__item-side">
                <div class="listings__item-price">
                    <span class="card__price-symbol">¥</span>
                    <span class="card__price-amount"><?php echo number_format($v['amount'], 2); ?></span>
                </div>
            </div>
        </div>
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
