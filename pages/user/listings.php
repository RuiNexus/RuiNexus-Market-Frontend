<?php

use Market\Auth;

$uid = Auth::getUid();
if (!$uid) { header('Location: ' . Auth::getLoginUrl($apiBase)); exit; }

$page    = intval($_GET['page'] ?? 1);
$result  = $api->getMyListings(['page' => $page, 'size' => 20]);
$list    = $result['data']['list'] ?? [];
$total   = $result['data']['total'] ?? 0;
$siteConfig = $api->getConfig()['data'] ?? [];
$siteName   = $siteConfig['site_name'] ?? 'RuiNexus Market';

$statusMap = [0 => '待审核', 1 => '上架中', 2 => '已售出', 3 => '已下架'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8"><title>我的发布 - <?php echo htmlspecialchars($siteName); ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="header"><div class="container">
<a href="/" class="logo"><?php echo htmlspecialchars($siteName); ?></a>
<nav><a href="/">首页</a><a href="/publish">发布</a><a href="/user/orders">购买</a><a href="/user/favorites">收藏</a></nav>
</div></header>

<main class="container">
<h1 style="margin:20px 0">我的发布</h1>
<?php if (empty($list)): ?><p class="empty">暂无发布</p><?php else: ?>
<table>
<thead><tr><th>标题</th><th>售价</th><th>状态</th><th>时间</th><th>操作</th></tr></thead>
<tbody>
<?php foreach ($list as $v): ?>
<tr>
<td><a href="/detail?id=<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['title']); ?></a></td>
<td>¥<?php echo number_format($v['sale_price'], 2); ?></td>
<td><?php echo $statusMap[$v['status']] ?? ''; ?></td>
<td><?php echo date('Y-m-d', $v['create_time']); ?></td>
<td>
<?php if (in_array($v['status'], [0,1,3])): ?>
<button onclick="delistItem(<?php echo $v['id']; ?>)" class="btn">下架</button>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</main>
<script>
async function delistItem(id) { if(!confirm('确认下架？')) return; try { const r = await fetch('<?php echo $apiBase; ?>/api/market/delist/'+id, {method:'POST',credentials:'include'}); const d = await r.json(); alert(d.msg||'操作完成'); location.reload(); } catch(e) { alert('请求失败'); } }
</script>
<footer class="footer"><p>&copy; <?php echo date('Y'); ?> RuiNexus Market</p></footer>
</body>
</html>
