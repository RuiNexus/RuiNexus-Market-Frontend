<?php

use Market\Auth;

$uid = Auth::getUid();
if (!$uid) { header('Location: ' . Auth::getLoginUrl($apiBase)); exit; }

$page    = max(1, intval($_GET['page'] ?? 1));
$size    = 20;
$result  = $api->getMyListings(['page' => $page, 'size' => $size]);
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

$fieldsResult = $api->get('fields');
$specFields = $fieldsResult['data'] ?? [];

$statusMap = [
    0 => ['label' => '待审核', 'class' => 'is-pending'],
    1 => ['label' => '上架中', 'class' => 'is-active'],
    2 => ['label' => '已售出', 'class' => 'is-sold'],
    3 => ['label' => '已下架', 'class' => 'is-delist'],
    4 => ['label' => '已删除', 'class' => 'is-deleted'],
    5 => ['label' => '锁定中', 'class' => 'is-locked'],
];

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
    <title>我的发布 - <?php echo htmlspecialchars($siteName); ?></title>
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
        <li><a href="/user/listings" class="active">我的</a></li>
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
    <div class="section-label">03 / MY LISTINGS</div>
    <div class="listings__header">
        <h2 class="section-title">我的发布</h2>
        <a href="/publish" class="nav-cta"><i class="fas fa-plus"></i> 发布新商品</a>
    </div>

    <div class="listings__stats">
        <div class="listings__stat">
            <span class="listings__stat-num"><?php echo $total; ?></span>
            <span class="listings__stat-label">全部</span>
        </div>
        <div class="listings__stat">
            <span class="listings__stat-num"><?php echo count(array_filter($list, function($v) { return $v['status'] == 1; })); ?></span>
            <span class="listings__stat-label">上架中</span>
        </div>
        <div class="listings__stat">
            <span class="listings__stat-num"><?php echo count(array_filter($list, function($v) { return $v['status'] == 0; })); ?></span>
            <span class="listings__stat-label">待审核</span>
        </div>
        <div class="listings__stat">
            <span class="listings__stat-num"><?php echo count(array_filter($list, function($v) { return $v['status'] == 3; })); ?></span>
            <span class="listings__stat-label">已下架</span>
        </div>
    </div>

    <?php if (empty($list)): ?>
    <div class="empty">
        <div class="empty__icon"><i class="fas fa-inbox"></i></div>
        <p>暂无发布</p>
        <a href="/publish" class="detail__btn detail__btn--buy" style="display:inline-flex;margin-top:20px;width:auto;padding:12px 28px;">
            <i class="fas fa-plus"></i> 发布第一个商品
        </a>
    </div>
    <?php else: ?>
    <div class="listings__list">
        <?php foreach ($list as $v): ?>
        <?php
        $status = $statusMap[$v['status']] ?? ['label' => '未知', 'class' => ''];
        $specData = is_string($v['spec_data']) ? json_decode($v['spec_data'], true) : ($v['spec_data'] ?? []);
        $billingTag = billingLabel($v['billing_cycle'] ?? '');
        $remainingDays = $v['remaining_days'] ?? null;
        $regdate = $v['regdate'] ?? 0;
        $nextduedate = $v['nextduedate'] ?? 0;
        $durationDays = ($regdate > 0 && $nextduedate > $regdate) ? round(($nextduedate - $regdate) / 86400) : 0;
        $hasRemaining = $remainingDays !== null && $durationDays > 0;
        $remainingPct = $hasRemaining ? min(100, round($remainingDays / max($durationDays, 1) * 100)) : 0;
        $discount = 0;
        if (($v['original_amount'] ?? 0) > 0 && ($v['sale_price'] ?? 0) < $v['original_amount']) {
            $r = round($v['sale_price'] / $v['original_amount'] * 10, 1);
            if ($r < 10) $discount = $r;
        }
        ?>
        <div class="listings__item" data-listing-id="<?php echo $v['id']; ?>" data-status="<?php echo $v['status']; ?>">
            <div class="listings__item-main">
                <div class="listings__item-header">
                    <a href="/detail?id=<?php echo $v['id']; ?>" class="listings__item-title"><?php echo htmlspecialchars($v['title'] ?? $v['product_name']); ?></a>
                    <span class="listings__status <?php echo $status['class']; ?>"><?php echo $status['label']; ?></span>
                </div>

                <div class="listings__item-tags">
                    <?php if ($billingTag): ?><span class="card__tag"><?php echo htmlspecialchars($billingTag); ?></span><?php endif; ?>
                    <?php if ($remainingDays !== null && $remainingDays > 0): ?>
                    <span class="card__tag card__tag--days <?php echo $remainingDays > 65 ? 'is-safe' : ($remainingDays > 30 ? 'is-warning' : 'is-danger'); ?>"><?php echo $remainingDays; ?> 天剩余</span>
                    <?php elseif ($remainingDays === null): ?>
                    <span class="card__tag">永久有效</span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($specData)): ?>
                <div class="listings__item-specs">
                    <?php foreach ($specData as $field => $value): ?>
                    <?php $label = $specLabels[$field] ?? $field; ?>
                    <span class="listings__spec-tag"><?php echo htmlspecialchars($label); ?>: <?php echo htmlspecialchars(is_array($value) ? implode(',', $value) : strval($value)); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($hasRemaining): ?>
                <div class="card__remaining-bar <?php echo $remainingPct > 65 ? 'is-safe' : ($remainingPct > 30 ? 'is-warning' : 'is-danger'); ?>">
                    <div class="card__remaining-fill" style="width:<?php echo $remainingPct; ?>%"></div>
                    <span class="card__remaining-text"><?php echo $remainingDays; ?> / <?php echo $durationDays; ?> 天</span>
                </div>
                <?php endif; ?>

                <div class="listings__item-meta">
                    <span><i class="far fa-clock"></i> <?php echo date('Y-m-d', $v['create_time']); ?></span>
                    <span><i class="far fa-eye"></i> <?php echo intval($v['views'] ?? 0); ?></span>
                </div>
            </div>

            <div class="listings__item-side">
                <div class="listings__item-price">
                    <?php if ($discount > 0): ?><span class="card__discount">-<?php echo round((1 - $discount / 10) * 100); ?>%</span><?php endif; ?>
                    <span class="card__price-symbol">¥</span>
                    <span class="card__price-amount"><?php echo htmlspecialchars(fmtPrice(floatval($v['sale_price'] ?? 0))); ?></span>
                </div>
                <?php if (($v['original_amount'] ?? 0) > 0 && $v['sale_price'] != $v['original_amount']): ?>
                <div class="listings__item-original">原价 ¥<?php echo htmlspecialchars(fmtPrice(floatval($v['original_amount']))); ?></div>
                <?php endif; ?>

                <div class="listings__item-actions">
                    <?php if (in_array($v['status'], [0, 1])): ?>
                    <button class="listings__btn listings__btn--delist" onclick="delistItem(<?php echo $v['id']; ?>)" title="下架">
                        <i class="fas fa-arrow-down"></i> 下架
                    </button>
                    <?php endif; ?>
                    <?php if ($v['status'] == 3): ?>
                    <button class="listings__btn listings__btn--relist" onclick="relistItem(<?php echo $v['id']; ?>)" title="重新上架">
                        <i class="fas fa-arrow-up"></i> 重新上架
                    </button>
                    <?php endif; ?>
                    <?php if (in_array($v['status'], [1, 3])): ?>
                    <button class="listings__btn listings__btn--edit" onclick="editItem(<?php echo $v['id']; ?>, this)" title="编辑">
                        <i class="fas fa-pen"></i> 编辑
                    </button>
                    <?php endif; ?>
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

<div id="editModal" class="modal" style="display:none;">
    <div class="modal__overlay" onclick="closeEditModal()"></div>
    <div class="modal__content">
        <div class="modal__header">
            <h3>编辑商品</h3>
            <button class="modal__close" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal__body">
            <input type="hidden" id="editListingId" value="">

            <div class="publish__field">
                <label class="publish__label">标题</label>
                <input type="text" id="editTitle" class="publish__input" required>
            </div>

            <div class="publish__field">
                <label class="publish__label">售价 (CNY)</label>
                <div class="publish__price-row">
                    <span class="publish__price-symbol">¥</span>
                    <input type="number" id="editPrice" class="publish__input publish__input--price" step="0.01" min="0.01" required>
                </div>
            </div>

            <div class="publish__field">
                <label class="publish__label">描述</label>
                <textarea id="editDesc" class="publish__textarea" rows="4"></textarea>
            </div>

            <?php if (!empty($specFields)): ?>
            <div class="publish__field">
                <label class="publish__label">配置信息</label>
                <div class="publish__specs" id="editSpecFieldsContainer">
                    <?php foreach ($specFields as $field): ?>
                    <div class="publish__spec-field" data-field-name="<?php echo htmlspecialchars($field['field_name']); ?>" data-field-type="<?php echo htmlspecialchars($field['field_type']); ?>" data-is-required="<?php echo intval($field['is_required']); ?>">
                        <label class="publish__spec-label">
                            <?php echo htmlspecialchars($field['field_label']); ?>
                            <?php if ($field['is_required']): ?><span class="publish__required">*</span><?php endif; ?>
                        </label>
                        <?php if ($field['field_type'] === 'input'): ?>
                        <input type="text" class="publish__input publish__spec-input" data-field="<?php echo htmlspecialchars($field['field_name']); ?>" placeholder="请输入<?php echo htmlspecialchars($field['field_label']); ?>">
                        <?php elseif ($field['field_type'] === 'dropdown'): ?>
                        <?php $options = is_string($field['field_options']) ? json_decode($field['field_options'], true) : ($field['field_options'] ?? []); ?>
                        <select class="publish__input publish__spec-input" data-field="<?php echo htmlspecialchars($field['field_name']); ?>">
                            <option value="">请选择</option>
                            <?php foreach (($options ?? []) as $opt): ?>
                            <option value="<?php echo htmlspecialchars(is_array($opt) ? ($opt['value'] ?? $opt) : $opt); ?>"><?php echo htmlspecialchars(is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? '') : $opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php elseif ($field['field_type'] === 'radio'): ?>
                        <?php $options = is_string($field['field_options']) ? json_decode($field['field_options'], true) : ($field['field_options'] ?? []); ?>
                        <div class="publish__radio-group">
                            <?php foreach (($options ?? []) as $i => $opt): ?>
                            <label class="publish__radio-item">
                                <input type="radio" name="edit_spec_<?php echo htmlspecialchars($field['field_name']); ?>" class="publish__spec-input" data-field="<?php echo htmlspecialchars($field['field_name']); ?>" value="<?php echo htmlspecialchars(is_array($opt) ? ($opt['value'] ?? $opt) : $opt); ?>">
                                <span><?php echo htmlspecialchars(is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? '') : $opt); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php elseif ($field['field_type'] === 'checkbox'): ?>
                        <?php $options = is_string($field['field_options']) ? json_decode($field['field_options'], true) : ($field['field_options'] ?? []); ?>
                        <div class="publish__checkbox-group">
                            <?php foreach (($options ?? []) as $i => $opt): ?>
                            <label class="publish__checkbox-item">
                                <input type="checkbox" class="publish__spec-input" data-field="<?php echo htmlspecialchars($field['field_name']); ?>" value="<?php echo htmlspecialchars(is_array($opt) ? ($opt['value'] ?? $opt) : $opt); ?>">
                                <span><?php echo htmlspecialchars(is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? '') : $opt); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="modal__footer">
            <button class="detail__btn detail__btn--buy" onclick="saveEdit()"><i class="fas fa-check"></i> 保存</button>
            <button class="detail__btn detail__btn--back" onclick="closeEditModal()"><i class="fas fa-times"></i> 取消</button>
        </div>
    </div>
</div>

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
const API_BASE = '<?php echo htmlspecialchars($apiBaseUrl); ?>';
let currentEditData = null;

async function delistItem(id) {
    if (!confirm('确认下架此商品？下架后商品将不再展示。')) return;
    try {
        var r = await fetch(API_BASE + '/market_api.php?action=delist&id=' + id, {
            method: 'POST',
            credentials: 'include'
        });
        var d = await r.json();
        if (d.status === 200) {
            alert(d.msg || '下架成功');
            location.reload();
        } else {
            alert(d.msg || '操作失败');
        }
    } catch (e) {
        alert('请求失败');
    }
}

async function relistItem(id) {
    if (!confirm('确认重新上架此商品？')) return;
    try {
        var r = await fetch(API_BASE + '/market_api.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id + '&status=1',
            credentials: 'include'
        });
        var d = await r.json();
        if (d.status === 200) {
            alert(d.msg || '重新上架成功');
            location.reload();
        } else {
            alert(d.msg || '操作失败');
        }
    } catch (e) {
        alert('请求失败');
    }
}

function editItem(id, btn) {
    var item = document.querySelector('[data-listing-id="' + id + '"]');
    if (!item) return;

    var title = item.querySelector('.listings__item-title');
    var titleText = title ? title.textContent.trim() : '';

    document.getElementById('editListingId').value = id;
    document.getElementById('editTitle').value = titleText;

    var priceEl = item.querySelector('.card__price-amount');
    var priceText = priceEl ? priceEl.textContent.replace(/,/g, '') : '';
    document.getElementById('editPrice').value = parseFloat(priceText) || 0;

    document.getElementById('editModal').style.display = '';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function collectEditSpecData() {
    var data = {};
    document.querySelectorAll('#editSpecFieldsContainer .publish__spec-field').forEach(function(f) {
        var fieldName = f.dataset.fieldName;
        var fieldType = f.dataset.fieldType;
        var values = [];

        if (fieldType === 'checkbox') {
            f.querySelectorAll('input[type="checkbox"]:checked').forEach(function(cb) {
                values.push(cb.value);
            });
            if (values.length > 0) data[fieldName] = values.join(',');
        } else if (fieldType === 'radio') {
            var checked = f.querySelector('input[type="radio"]:checked');
            if (checked) data[fieldName] = checked.value;
        } else {
            var input = f.querySelector('.publish__spec-input');
            if (input && input.value.trim()) data[fieldName] = input.value.trim();
        }
    });
    return data;
}

async function saveEdit() {
    var id = document.getElementById('editListingId').value;
    if (!id) return;

    var title = document.getElementById('editTitle').value.trim();
    var salePrice = parseFloat(document.getElementById('editPrice').value);
    var desc = document.getElementById('editDesc').value.trim();

    if (!title) { alert('请输入标题'); return; }
    if (!salePrice || salePrice <= 0) { alert('请输入有效的售价'); return; }

    var params = new URLSearchParams();
    params.append('id', id);
    params.append('title', title);
    params.append('sale_price', salePrice);
    if (desc) params.append('description', desc);

    var specData = collectEditSpecData();
    if (Object.keys(specData).length > 0) {
        params.append('spec_data', JSON.stringify(specData));
    }

    try {
        var r = await fetch(API_BASE + '/market_api.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString(),
            credentials: 'include'
        });
        var d = await r.json();
        if (d.status === 200) {
            alert(d.msg || '修改成功');
            location.reload();
        } else {
            alert(d.msg || '修改失败');
        }
    } catch (e) {
        alert('请求失败');
    }
}
<?php echo \Market\Auth::jsSnippet($apiBaseUrl); ?>
</script>
</body>
</html>
