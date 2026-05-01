<?php

use Market\Auth;

$uid = Auth::getUid();
if (!$uid) { header('Location: ' . Auth::getLoginUrl($apiBase)); exit; }

$page    = intval($_GET['page'] ?? 1);
$result  = $api->getFavorites(['page' => $page, 'size' => 20]);
$list    = $result['data']['list'] ?? [];
$total   = $result['data']['total'] ?? 0;
$siteConfig = $api->getConfig()['data'] ?? [];
$siteName   = $siteConfig['site_name'] ?? 'RuiNexus Market';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8"><title>我的收藏 - <?php echo htmlspecialchars($siteName); ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="header"><div class="container">
<a href="/" class="logo"><?php echo htmlspecialchars($siteName); ?></a>
<nav><a href="/">首页</a><a href="/user/listings">发布</a><a href="/user/orders">购买</a></nav>
</div></header>

<main class="container">
<h1 style="margin:20px 0">我的收藏</h1>
<?php if (empty($list)): ?><p class="empty">暂无收藏</p><?php else: ?>
<div class="listing-grid">
<?php foreach ($list as $v): ?>
<div class="listing-card">
<h3><a href="/detail?id=<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['title']); ?></a></h3>
<div class="info"><p>IP: <?php echo htmlspecialchars($v['host_ip']); ?></p></div>
<div class="price">¥<?php echo number_format($v['sale_price'], 2); ?></div>
<a href="/detail?id=<?php echo $v['id']; ?>" class="btn">查看详情</a>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</main>
<footer class="footer"><p>&copy; <?php echo date('Y'); ?> RuiNexus Market</p></footer>
</body>
</html>
