<?php

/**
 * RuiNexus Market - Frontend
 *
 * 首页/服务器列表页面
 *
 * 开发者: RuiNexus / YeHuaiJing
 */

use Market\Auth;

$page     = intval($_GET['page'] ?? 1);
$sort     = $_GET['sort'] ?? 'time_desc';
$keyword  = $_GET['keyword'] ?? '';
$priceMin = $_GET['price_min'] ?? '';
$priceMax = $_GET['price_max'] ?? '';
$region   = $_GET['region'] ?? '';

$siteConfig = $api->getConfig()['data'] ?? [];
$siteName   = $siteConfig['site_name'] ?? 'RuiNexus Market';
$notice     = $siteConfig['notice_content'] ?? '';

$listResult = $api->getList([
    'page'       => $page,
    'size'       => 20,
    'sort'       => $sort,
    'keyword'    => $keyword,
    'price_min'  => $priceMin,
    'price_max'  => $priceMax,
    'region'     => $region,
]);

$list  = $listResult['data']['list'] ?? [];
$total = $listResult['data']['total'] ?? 0;
$user  = Auth::getUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($siteConfig['seo_desc'] ?? '二手服务器转卖交易市场'); ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="header">
    <div class="container">
        <a href="/" class="logo"><?php echo htmlspecialchars($siteName); ?></a>
        <nav>
            <?php if ($user['loggedIn']): ?>
                <a href="/publish">发布商品</a>
                <a href="/user/listings">我的发布</a>
                <a href="/user/orders">我的购买</a>
                <a href="/user/favorites">收藏</a>
                <span>UID: <?php echo $user['id']; ?></span>
            <?php else: ?>
                <a href="<?php echo Auth::getLoginUrl($apiBase); ?>">登录</a>
                <a href="<?php echo Auth::getRegisterUrl($apiBase); ?>">注册</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php if ($notice): ?>
<div class="notice"><?php echo nl2br(htmlspecialchars($notice)); ?></div>
<?php endif; ?>

<main class="container">
    <div class="search-box">
        <form method="get" action="/">
            <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="搜索服务器...">
            <input type="number" name="price_min" value="<?php echo htmlspecialchars($priceMin); ?>" placeholder="最低价">
            <input type="number" name="price_max" value="<?php echo htmlspecialchars($priceMax); ?>" placeholder="最高价">
            <select name="sort">
                <option value="time_desc" <?php echo $sort==='time_desc'?'selected':''; ?>>最新发布</option>
                <option value="price_asc" <?php echo $sort==='price_asc'?'selected':''; ?>>价格从低到高</option>
                <option value="price_desc" <?php echo $sort==='price_desc'?'selected':''; ?>>价格从高到低</option>
                <option value="remaining_asc" <?php echo $sort==='remaining_asc'?'selected':''; ?>>剩余时间</option>
            </select>
            <button type="submit">搜索</button>
        </form>
    </div>

    <div class="listing-grid">
        <?php if (empty($list)): ?>
            <p class="empty">暂无在售服务器</p>
        <?php else: ?>
            <?php foreach ($list as $item): ?>
            <div class="listing-card <?php echo $item['is_featured'] ? 'featured' : ''; ?>">
                <?php if ($item['is_featured']): ?><span class="badge">推荐</span><?php endif; ?>
                <h3><a href="/detail?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['title']); ?></a></h3>
                <div class="info">
                    <p>产品: <?php echo htmlspecialchars($item['product_name']); ?></p>
                    <p>IP: <?php echo htmlspecialchars($item['host_ip']); ?></p>
                    <p>OS: <?php echo htmlspecialchars($item['host_os']); ?></p>
                    <?php if ($item['remaining_days'] > 0): ?>
                    <p>剩余: <?php echo $item['remaining_days']; ?>天</p>
                    <?php endif; ?>
                </div>
                <div class="price">¥<?php echo number_format($item['sale_price'], 2); ?></div>
                <a href="/detail?id=<?php echo $item['id']; ?>" class="btn">查看详情</a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($total > 20): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= ceil($total / 20); $i++): ?>
            <a href="?page=<?php echo $i; ?>&sort=<?php echo $sort; ?>&keyword=<?php echo urlencode($keyword); ?>" <?php echo $i===$page?'class="active"':''; ?>><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</main>

<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> RuiNexus Market - Powered by RuiNexus</p>
</footer>
</body>
</html>
