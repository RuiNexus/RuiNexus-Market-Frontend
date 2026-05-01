<?php

/**
 * RuiNexus Market - 发布商品页
 *
 * 开发者: RuiNexus / YeHuaiJing
 */

use Market\Auth;

$uid = Auth::getUid();
if (!$uid) {
    header('Location: ' . Auth::getLoginUrl($apiBase));
    exit;
}

$hostsResult = $api->getMyHosts();
$hosts = $hostsResult['data'] ?? [];
$siteConfig = $api->getConfig()['data'] ?? [];
$siteName   = $siteConfig['site_name'] ?? 'RuiNexus Market';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>发布商品 - <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="header">
    <div class="container">
        <a href="/" class="logo"><?php echo htmlspecialchars($siteName); ?></a>
        <nav>
            <a href="/">首页</a>
        </nav>
    </div>
</header>

<main class="container publish-page">
    <h1>发布二手服务器</h1>

    <div class="host-select">
        <h3>选择要出售的服务器</h3>
        <?php if (empty($hosts)): ?>
            <p>您没有可出售的服务器（需状态为 Active 且不在交易黑名单中）</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>选择</th>
                    <th>产品</th>
                    <th>IP</th>
                    <th>操作系统</th>
                    <th>到期时间</th>
                    <th>原始价格</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hosts as $h): ?>
                <tr>
                    <td>
                        <?php if ($h['is_on_sale']): ?>
                            <span class="hint">已在售</span>
                        <?php else: ?>
                            <input type="radio" name="host_id" value="<?php echo $h['id']; ?>" onclick="selectHost(<?php echo $h['id']; ?>, '<?php echo htmlspecialchars($h['product_name'] ?? ''); ?>', <?php echo number_format($h['original_amount'], 2, '.', ''); ?>)">
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($h['product_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($h['dedicatedip'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($h['os'] ?? ''); ?></td>
                    <td><?php echo $h['nextduedate'] ? date('Y-m-d', $h['nextduedate']) : '不到期'; ?></td>
                    <td>¥<?php echo number_format($h['original_amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <form id="publish-form" onsubmit="publishItem(event)" style="display:none;">
        <input type="hidden" name="host_id" id="form-host-id">
        <div class="form-group">
            <label>标题</label>
            <input type="text" name="title" id="form-title" required placeholder="如: 日本BGP线路 全系统可win">
        </div>
        <div class="form-group">
            <label>售价 (元)</label>
            <input type="number" name="sale_price" id="form-price" required step="0.01" placeholder="输入您期望的售价">
        </div>
        <div class="form-group">
            <label>描述</label>
            <textarea name="description" rows="4" placeholder="可填写服务器线路、性能等补充信息"></textarea>
        </div>
        <button type="submit" class="btn primary">发布</button>
    </form>
</main>

<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> RuiNexus Market</p>
</footer>

<script>
function selectHost(id, productName, originalAmount) {
    document.getElementById('publish-form').style.display = 'block';
    document.getElementById('form-host-id').value = id;
    document.getElementById('form-title').value = productName;
    document.getElementById('form-price').value = originalAmount;
}

async function publishItem(e) {
    e.preventDefault();
    const form = document.getElementById('publish-form');
    const formData = new FormData(form);
    const data = new URLSearchParams(formData).toString();

    try {
        const resp = await fetch('<?php echo $apiBase; ?>/market_api.php?action=create', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: data,
            credentials: 'include'
        });
        const result = await resp.json();
        if (result.status === 200) {
            alert(result.msg || '发布成功');
            window.location.href = '/user/listings';
        } else {
            alert(result.msg || '发布失败');
        }
    } catch(e) {
        alert('请求失败，请重试');
    }
}
</script>
</body>
</html>
