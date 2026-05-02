<?php

/**
 * RuiNexus Market - 商品详情页
 *
 * 设计: 亮色主题，与 index.php 风格一致
 *
 * 开发者: RuiNexus / YeHuaiJing
 * 仓库: https://github.com/RuiNexus/RuiNexus-Market-Frontend
 */

use Market\Auth;

$listingId = max(1, intval($_GET['id'] ?? 0));

if ($listingId <= 0) {
    header('Location: /');
    exit;
}

$siteConfig = $api->getConfig()['data'] ?? [];
$siteName   = $siteConfig['site_name'] ?? 'RuiNexus Market';
$notice     = $siteConfig['notice_content'] ?? '';
$user       = Auth::getUser();

$frontendConfig = require __DIR__ . '/../config.php';
$apiBaseUrl = $frontendConfig['api_base_url'] ?? 'https://test.ruinexus.com';
$siteName = $frontendConfig['site_name'] ?: $siteName;

$detailData = $api->getDetail($listingId);
$listing    = ($detailData['status'] === 200 && isset($detailData['data'])) ? $detailData['data'] : null;
$loadError  = $listing ? null : ($detailData['msg'] ?? '商品不存在或已下架');
$is404      = ($detailData['status'] === 404);

if ($is404) {
    http_response_code(404);
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

function remainingClass($pct) {
    if ($pct > 65) return 'is-safe';
    if ($pct > 30) return 'is-warning';
    return 'is-danger';
}

function discountRate($sale, $orig) {
    if ($orig <= 0 || $sale >= $orig) return 0;
    $r = round($sale / $orig * 10, 1);
    return $r < 10 ? $r : 0;
}

function fmtPrice($p) {
    return ($p == intval($p)) ? number_format($p) : number_format($p, 2);
}
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $listing ? htmlspecialchars($listing['title'] ?? $listing['product_name']) . ' - ' . htmlspecialchars($siteName) : '商品详情 - ' . htmlspecialchars($siteName); ?></title>
    <meta name="description" content="<?php echo $listing ? htmlspecialchars(mb_substr($listing['description'] ?? '', 0, 160)) : '查看商品详情'; ?>">
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
        <li><a href="/">市场</a></li>
        <li><a href="/about">帮助</a></li>
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

<!-- ===== Notice ===== -->
<?php if ($notice): ?>
<div class="site-notice"><?php echo nl2br(htmlspecialchars($notice)); ?></div>
<?php endif; ?>

<!-- ===== Detail Content ===== -->
<section class="section">
    <?php if ($listing): ?>
    <?php
    $specData  = is_string($listing['spec_data']) ? json_decode($listing['spec_data'], true) : ($listing['spec_data'] ?? []);
    $specLabels = $listing['spec_labels'] ?? [];
    $remainingDays = $listing['remaining_days'];
    $regdate = $listing['regdate'] ?? 0;
    $nextduedate = $listing['nextduedate'] ?? 0;
    $durationDays = ($regdate > 0 && $nextduedate > $regdate) ? round(($nextduedate - $regdate) / 86400) : 0;
    $hasRemaining = $remainingDays !== null && $durationDays > 0;
    $remainingPct = $hasRemaining ? min(100, round($remainingDays / max($durationDays, 1) * 100)) : 0;
    $remClass = remainingClass($remainingPct);
    $billingTag = billingLabel($listing['billing_cycle'] ?? '');
    $discount = discountRate(floatval($listing['sale_price'] ?? 0), floatval($listing['original_amount'] ?? 0));
    $createTime = $listing['create_time'] ?? ($listing['list_time'] ?? 0);
    $createdDate = $createTime > 0 ? date('Y-m-d', $createTime) : '';
    $views = intval($listing['views'] ?? 0);
    $favId = 'fav-' . $listingId;
    ?>
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="/">首页</a>
        <span class="breadcrumb__sep">/</span>
        <span class="breadcrumb__current"><?php echo htmlspecialchars($listing['title'] ?? $listing['product_name']); ?></span>
    </nav>

    <div class="detail">
        <!-- Main -->
        <div class="detail__main">
            <h1 class="detail__title"><?php echo htmlspecialchars($listing['title'] ?? $listing['product_name']); ?></h1>

            <div class="detail__tags">
                <?php if ($billingTag): ?>
                <span class="card__tag"><?php echo htmlspecialchars($billingTag); ?></span>
                <?php endif; ?>
                <?php if ($remainingDays !== null): ?>
                <span class="card__tag card__tag--days <?php echo $remClass; ?>"><?php echo $remainingDays; ?> 天剩余</span>
                <?php endif; ?>
                <?php if (!empty($listing['host_domainstatus'])): ?>
                <span class="card__tag" style="color:var(--success);border-color:var(--success);"><?php echo htmlspecialchars($listing['host_domainstatus']); ?></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($listing['description'])): ?>
            <div class="detail__description">
                <h3 class="detail__section-title">商品描述</h3>
                <p><?php echo nl2br(htmlspecialchars($listing['description'])); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($specData)): ?>
            <div class="detail__specs">
                <h3 class="detail__section-title">配置信息</h3>
                <table class="detail__specs-table">
                    <tbody>
                    <?php foreach ($specData as $field => $value): ?>
                        <?php $label = $specLabels[$field] ?? $field; ?>
                        <?php $lowerVal = strtolower(strval($value)); ?>
                        <?php $isBool = in_array($lowerVal, ['是','否','yes','no','true','false','支持','不支持']); ?>
                        <?php $isYes = in_array($lowerVal, ['是','yes','true','支持']); ?>
                        <tr>
                            <td class="detail__specs-label"><?php echo htmlspecialchars($label); ?></td>
                            <td class="detail__specs-value">
                                <?php if ($isBool): ?>
                                <span class="card__spec-tag <?php echo $isYes ? 'is-yes' : 'is-no'; ?>"><?php echo $isYes ? '支持' : '不支持'; ?></span>
                                <?php else: ?>
                                <?php echo htmlspecialchars(strval($value)); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if ($hasRemaining): ?>
            <div class="detail__remaining">
                <h3 class="detail__section-title">剩余时间</h3>
                <div class="card__remaining-bar <?php echo $remClass; ?>">
                    <div class="card__remaining-fill" style="width:<?php echo $remainingPct; ?>%"></div>
                    <span class="card__remaining-text"><?php echo $remainingDays; ?> / <?php echo $durationDays; ?> 天 (<?php echo $remainingPct; ?>%)</span>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <aside class="detail__sidebar">
            <div class="detail__card">
                <div class="detail__price-block">
                    <div class="detail__price-main">
                        <span class="detail__price-symbol">¥</span>
                        <span class="detail__price-amount"><?php echo htmlspecialchars(fmtPrice(floatval($listing['sale_price'] ?? 0))); ?></span>
                        <span class="detail__price-unit">CNY</span>
                    </div>
                    <?php if (floatval($listing['original_amount'] ?? 0) > 0 && $listing['sale_price'] != $listing['original_amount']): ?>
                    <div class="detail__price-original-line">
                        <span class="detail__price-original">原价 ¥<?php echo htmlspecialchars(fmtPrice(floatval($listing['original_amount']))); ?></span>
                        <?php if ($discount > 0): ?>
                        <span class="card__discount">-<?php echo round((1 - $discount / 10) * 100); ?>%</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="detail__seller">
                    <div class="detail__seller-icon"><i class="fas fa-user-circle"></i></div>
                    <div class="detail__seller-info">
                        <span class="detail__seller-label">卖家</span>
                        <span class="detail__seller-name"><?php echo htmlspecialchars($listing['seller_username'] ?? '未知'); ?></span>
                    </div>
                </div>

                <div class="detail__actions">
                    <?php if ($user['loggedIn']): ?>
                    <button class="detail__btn detail__btn--buy" onclick="buyListing()">
                        <i class="fas fa-shopping-cart"></i> 立即购买
                    </button>
                    <?php else: ?>
                    <a href="<?php echo Auth::getLoginUrl($apiBaseUrl); ?>" class="detail__btn detail__btn--buy">
                        <i class="fas fa-sign-in-alt"></i> 登录后购买
                    </a>
                    <?php endif; ?>

                    <button class="detail__btn detail__btn--fav <?php echo !empty($listing['is_favorited']) ? 'is-active' : ''; ?>" id="favBtn" onclick="toggleFavorite()" <?php echo !$user['loggedIn'] ? 'disabled title="请先登录"' : ''; ?>>
                        <i class="<?php echo !empty($listing['is_favorited']) ? 'fas' : 'far'; ?> fa-heart"></i>
                        <span><?php echo !empty($listing['is_favorited']) ? '已收藏' : '收藏'; ?></span>
                    </button>
                </div>

                <div class="detail__meta">
                    <?php if ($createdDate): ?>
                    <div class="detail__meta-item"><i class="far fa-clock"></i> 发布于 <?php echo htmlspecialchars($createdDate); ?></div>
                    <?php endif; ?>
                    <div class="detail__meta-item"><i class="far fa-eye"></i> <?php echo $views; ?> 次浏览</div>
                </div>
            </div>

            <div class="detail__card detail__card--tips">
                <h4 class="detail__tips-title"><i class="fas fa-shield-alt"></i> 交易须知</h4>
                <ul class="detail__tips-list">
                    <li>请仔细阅读商品描述和配置信息</li>
                    <li>购买前可联系卖家确认细节</li>
                    <li>交易完成后产品将转移至您的账户</li>
                    <li>如有纠纷请联系平台客服处理</li>
                </ul>
            </div>
        </aside>
    </div>

    <?php else: ?>
    <!-- Error / 404 -->
    <div class="empty">
        <div class="empty__icon"><i class="fas <?php echo $is404 ? 'fa-search' : 'fa-exclamation-triangle'; ?>"></i></div>
        <p><?php echo $is404 ? '商品不存在或已下架' : htmlspecialchars($loadError); ?></p>
        <a href="/" class="detail__btn detail__btn--back" style="display:inline-flex;margin-top:20px;">
            <i class="fas fa-arrow-left"></i> 返回首页
        </a>
    </div>
    <?php endif; ?>
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

<?php if ($listing): ?>
<script>
const API_BASE = '<?php echo htmlspecialchars($apiBaseUrl); ?>';
const LISTING_ID = <?php echo $listingId; ?>;
const IS_FAVORITED = <?php echo !empty($listing['is_favorited']) ? 'true' : 'false'; ?>;
const IS_LOGGED_IN = <?php echo $user['loggedIn'] ? 'true' : 'false'; ?>;

function toggleFavorite() {
    if (!IS_LOGGED_IN) {
        alert('请先登录');
        return;
    }

    const btn = document.getElementById('favBtn');
    const icon = btn.querySelector('i');
    const span = btn.querySelector('span');

    btn.disabled = true;

    fetch(`${API_BASE}/market_api.php?action=favorite&id=${LISTING_ID}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 200) {
            if (data.data && data.data.favorited) {
                btn.classList.add('is-active');
                icon.className = 'fas fa-heart';
                span.textContent = '已收藏';
            } else {
                btn.classList.remove('is-active');
                icon.className = 'far fa-heart';
                span.textContent = '收藏';
            }
        } else if (data.status === 401) {
            alert('请先登录');
        } else {
            alert(data.msg || '操作失败');
        }
    })
    .catch(err => {
        alert('网络错误: ' + err.message);
    })
    .finally(() => {
        btn.disabled = false;
    });
}

function buyListing() {
    if (!confirm('确认购买此商品？')) return;

    fetch(`${API_BASE}/market_api.php?action=buy`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `listing_id=${LISTING_ID}&pay_type=online`,
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 200 && data.data) {
            if (data.data.pay_type === 'online' && data.data.pay_url) {
                window.location.href = data.data.pay_url;
            } else {
                alert(data.msg || '下单成功');
            }
        } else if (data.status === 401) {
            alert('请先登录');
            window.location.href = '<?php echo Auth::getLoginUrl($apiBaseUrl); ?>';
        } else {
            alert(data.msg || '购买失败');
        }
    })
    .catch(err => {
        alert('网络错误: ' + err.message);
    });
}
</script>
<?php endif; ?>

</body>
</html>
