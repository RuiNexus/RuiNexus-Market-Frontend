<?php

/**
 * RuiNexus Market - Frontend
 *
 * 服务器详情页
 *
 * 开发者: RuiNexus / YeHuaiJing
 */

use Market\Auth;

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /');
    exit;
}

$detailResult = $api->getDetail($id);
$item = $detailResult['data'] ?? null;
if (!$item) {
    header('Location: /');
    exit;
}

$siteConfig = $api->getConfig()['data'] ?? [];
$siteName   = $siteConfig['site_name'] ?? 'RuiNexus Market';
$user       = Auth::getUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($item['title'] . ' - ' . $siteName); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/main.js"></script>
</head>
<body>
<header class="header">
    <div class="container">
        <a href="/" class="logo"><?php echo htmlspecialchars($siteName); ?></a>
        <nav>
            <?php if ($user['loggedIn']): ?>
                <a href="/publish">发布商品</a>
                <span>UID: <?php echo $user['id']; ?></span>
            <?php else: ?>
                <a href="<?php echo Auth::getLoginUrl($apiBase); ?>">登录</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container detail-page">
    <h1><?php echo htmlspecialchars($item['title']); ?></h1>
    <div class="detail-meta">
        <span>卖家: <?php echo htmlspecialchars($item['seller_username'] ?? ''); ?></span>
        <span>浏览: <?php echo $item['views']; ?>次</span>
        <span>发布时间: <?php echo date('Y-m-d', $item['create_time']); ?></span>
    </div>

    <div class="detail-info">
        <table>
            <tr><td>产品类型</td><td><?php echo htmlspecialchars($item['product_name']); ?> (<?php echo htmlspecialchars($item['product_type']); ?>)</td></tr>
            <tr><td>主机名</td><td><?php echo htmlspecialchars($item['host_domain']); ?></td></tr>
            <tr><td>IP 地址</td><td><?php echo htmlspecialchars($item['host_ip']); ?></td></tr>
            <tr><td>端口</td><td><?php echo $item['host_port']; ?></td></tr>
            <tr><td>操作系统</td><td><?php echo htmlspecialchars($item['host_os']); ?></td></tr>
            <tr><td>开通时间</td><td><?php echo $item['regdate'] ? date('Y-m-d', $item['regdate']) : '-'; ?></td></tr>
            <tr><td>到期时间</td><td><?php echo $item['nextduedate'] ? date('Y-m-d', $item['nextduedate']) : '不到期'; ?></td></tr>
            <?php if ($item['remaining_days'] > 0): ?>
            <tr><td>剩余天数</td><td><?php echo $item['remaining_days']; ?> 天</td></tr>
            <?php endif; ?>
            <tr><td>原价</td><td>¥<?php echo number_format($item['original_amount'] ?? 0, 2); ?></td></tr>
            <tr><td>售价</td><td class="price">¥<?php echo number_format($item['sale_price'], 2); ?></td></tr>
        </table>
    </div>

    <?php if ($item['description']): ?>
    <div class="detail-desc">
        <h3>描述</h3>
        <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
    </div>
    <?php endif; ?>

    <div class="detail-actions">
        <?php if ($user['loggedIn']): ?>
            <?php if ($user['id'] == $item['uid']): ?>
                <p class="hint">这是您发布的商品</p>
            <?php else: ?>
                <button onclick="buyItem(<?php echo $item['id']; ?>)" class="btn primary">立即购买</button>
            <?php endif; ?>
            <button onclick="toggleFavorite(<?php echo $item['id']; ?>)" class="btn">
                <?php echo $item['is_favorited'] ? '取消收藏' : '收藏'; ?>
            </button>
        <?php else: ?>
            <a href="<?php echo Auth::getLoginUrl($apiBase); ?>" class="btn primary">登录后购买</a>
        <?php endif; ?>
    </div>
</main>

<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> RuiNexus Market</p>
</footer>

<script>
async function buyItem(id) {
    if (!confirm('确认购买此服务器？')) return;
    try {
        const resp = await fetch('<?php echo $apiBase; ?>/market_api.php?action=buy', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'listing_id=' + id + '&pay_type=online',
            credentials: 'include'
        });
        const data = await resp.json();
        if (data.status === 200) {
            alert('下单成功！');
            if (data.data.pay_url) {
                window.location.href = data.data.pay_url;
            }
        } else {
            alert(data.msg || '购买失败');
        }
    } catch(e) {
        alert('请求失败，请重试');
    }
}

async function toggleFavorite(id) {
    try {
        const resp = await fetch('<?php echo $apiBase; ?>/market_api.php?action=favorite&id=' + id, {
            method: 'POST',
            credentials: 'include'
        });
        const data = await resp.json();
        if (data.status === 200) {
            location.reload();
        }
    } catch(e) {
        alert('操作失败');
    }
}
</script>
</body>
</html>
