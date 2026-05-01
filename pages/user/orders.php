<?php

use Market\Auth;

$uid = Auth::getUid();
if (!$uid) { header('Location: ' . Auth::getLoginUrl($apiBase)); exit; }

$page    = intval($_GET['page'] ?? 1);
$result  = $api->getMyOrders(['page' => $page, 'size' => 20]);
$list    = $result['data']['list'] ?? [];
$total   = $result['data']['total'] ?? 0;
$siteConfig = $api->getConfig()['data'] ?? [];
$siteName   = $siteConfig['site_name'] ?? 'RuiNexus Market';

$statusMap = [0 => '待付款', 1 => '已付款', 2 => '已转移', 3 => '已完成', 4 => '已取消', 5 => '退款中', 6 => '已退款'];
$payTypeMap = ['online' => '线上', 'offline' => '线下'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8"><title>我的购买 - <?php echo htmlspecialchars($siteName); ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="header"><div class="container">
<a href="/" class="logo"><?php echo htmlspecialchars($siteName); ?></a>
<nav><a href="/">首页</a><a href="/user/listings">发布</a><a href="/user/favorites">收藏</a></nav>
</div></header>

<main class="container">
<h1 style="margin:20px 0">我的购买</h1>
<?php if (empty($list)): ?><p class="empty">暂无购买记录</p><?php else: ?>
<table>
<thead><tr><th>商品</th><th>金额</th><th>支付方式</th><th>状态</th><th>时间</th></tr></thead>
<tbody>
<?php foreach ($list as $v): ?>
<tr>
<td><?php echo htmlspecialchars($v['title'] ?? ''); ?></td>
<td>¥<?php echo number_format($v['amount'], 2); ?></td>
<td><?php echo $payTypeMap[$v['pay_type']] ?? ''; ?></td>
<td><?php echo $statusMap[$v['status']] ?? ''; ?></td>
<td><?php echo date('Y-m-d', $v['create_time']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</main>
<footer class="footer"><p>&copy; <?php echo date('Y'); ?> RuiNexus Market</p></footer>
</body>
</html>
