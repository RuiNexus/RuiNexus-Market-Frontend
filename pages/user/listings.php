<?php

use Market\Auth;

$page    = max(1, intval($_GET['page'] ?? 1));
$size    = 20;

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

    <div id="listingsStats" class="listings__stats" style="display:none;">
        <div class="listings__stat">
            <span class="listings__stat-num" id="statAll">0</span>
            <span class="listings__stat-label">全部</span>
        </div>
        <div class="listings__stat">
            <span class="listings__stat-num" id="statActive">0</span>
            <span class="listings__stat-label">上架中</span>
        </div>
        <div class="listings__stat">
            <span class="listings__stat-num" id="statPending">0</span>
            <span class="listings__stat-label">待审核</span>
        </div>
        <div class="listings__stat">
            <span class="listings__stat-num" id="statDelist">0</span>
            <span class="listings__stat-label">已下架</span>
        </div>
    </div>

    <div id="listingsLoading" class="empty">
        <div class="empty__icon"><i class="fas fa-spinner fa-pulse"></i></div>
        <p>正在加载...</p>
    </div>

    <div id="listingsUnauth" class="empty" style="display:none;">
        <div class="empty__icon"><i class="fas fa-lock"></i></div>
        <p>请先登录以查看您的发布</p>
        <a href="<?php echo Auth::getLoginUrl($apiBaseUrl); ?>" class="detail__btn detail__btn--buy" style="display:inline-flex;margin-top:20px;width:auto;padding:12px 28px;">
            <i class="fas fa-sign-in-alt"></i> 登录账号
        </a>
    </div>

    <div id="listingsError" class="empty" style="display:none;">
        <div class="empty__icon"><i class="fas fa-exclamation-triangle"></i></div>
        <p>加载失败，请刷新重试</p>
    </div>

    <div id="listingsEmpty" class="empty" style="display:none;">
        <div class="empty__icon"><i class="fas fa-inbox"></i></div>
        <p>暂无发布</p>
        <a href="/publish" class="detail__btn detail__btn--buy" style="display:inline-flex;margin-top:20px;width:auto;padding:12px 28px;">
            <i class="fas fa-plus"></i> 发布第一个商品
        </a>
    </div>

    <div id="listingsList" class="listings__list" style="display:none;">
    </div>

    <div id="listingsPagination" class="pagination" style="display:none;">
    </div>
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
var API_BASE = <?php echo json_encode($apiBaseUrl); ?>;
var LOGIN_URL = <?php echo json_encode(Auth::getLoginUrl($apiBaseUrl)); ?>;
var CURRENT_PAGE = <?php echo $page; ?>;
var PAGE_SIZE = <?php echo $size; ?>;
var currentEditData = null;

var STATUS_MAP = <?php echo json_encode($statusMap); ?>;

function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function billingLabelJS(cycle) {
    var map = {monthly:'月付',quarterly:'季付',semiannually:'半年付',annually:'年付',biennially:'两年付',triennially:'三年付',onetime:'永久',free:'免费'};
    return map[cycle] || (cycle ? cycle.toUpperCase() : '');
}

function fmtPriceJS(p) {
    return p === Math.floor(p) ? p.toLocaleString() : p.toFixed(2);
}

function showById(id) { var el = document.getElementById(id); if (el) el.style.display = ''; }
function hideById(id) { var el = document.getElementById(id); if (el) el.style.display = 'none'; }

function renderListingItem(v, specLabels) {
    var status = STATUS_MAP[v.status] || {label:'未知',class:''};
    var specData = typeof v.spec_data === 'string' ? JSON.parse(v.spec_data || '{}') : (v.spec_data || {});
    var billingTag = billingLabelJS(v.billing_cycle);
    var remainingDays = v.remaining_days;
    var regdate = v.regdate || 0;
    var nextduedate = v.nextduedate || 0;
    var durationDays = (regdate > 0 && nextduedate > regdate) ? Math.round((nextduedate - regdate) / 86400) : 0;
    var hasRemaining = remainingDays !== null && remainingDays !== undefined && durationDays > 0;
    var remainingPct = hasRemaining ? Math.min(100, Math.round(remainingDays / Math.max(durationDays, 1) * 100)) : 0;
    var discount = 0;
    if ((v.original_amount || 0) > 0 && (v.sale_price || 0) < (v.original_amount || 0)) {
        var r = Math.round(v.sale_price / v.original_amount * 100) / 10;
        if (r < 10) discount = r;
    }

    var tagsHtml = '';
    if (billingTag) tagsHtml += '<span class="card__tag">' + escHtml(billingTag) + '</span>';
    if (remainingDays !== null && remainingDays !== undefined && remainingDays > 0) {
        var cls = remainingDays > 65 ? 'is-safe' : (remainingDays > 30 ? 'is-warning' : 'is-danger');
        tagsHtml += '<span class="card__tag card__tag--days ' + cls + '">' + remainingDays + ' 天剩余</span>';
    } else if (remainingDays === null || remainingDays === undefined) {
        tagsHtml += '<span class="card__tag">永久有效</span>';
    }

    var specsHtml = '';
    if (Object.keys(specData).length > 0) {
        specsHtml += '<div class="listings__item-specs">';
        for (var field in specData) {
            var label = specLabels[field] || field;
            var val = Array.isArray(specData[field]) ? specData[field].join(',') : String(specData[field]);
            specsHtml += '<span class="listings__spec-tag">' + escHtml(label) + ': ' + escHtml(val) + '</span>';
        }
        specsHtml += '</div>';
    }

    var barHtml = '';
    if (hasRemaining) {
        var barCls = remainingPct > 65 ? 'is-safe' : (remainingPct > 30 ? 'is-warning' : 'is-danger');
        barHtml = '<div class="card__remaining-bar ' + barCls + '">' +
            '<div class="card__remaining-fill" style="width:' + remainingPct + '%"></div>' +
            '<span class="card__remaining-text">' + remainingDays + ' / ' + durationDays + ' 天</span></div>';
    }

    var actionsHtml = '';
    if (v.status === 0 || v.status === 1) {
        actionsHtml += '<button class="listings__btn listings__btn--delist" onclick="delistItem(' + v.id + ')" title="下架"><i class="fas fa-arrow-down"></i> 下架</button>';
    }
    if (v.status === 3) {
        actionsHtml += '<button class="listings__btn listings__btn--relist" onclick="relistItem(' + v.id + ')" title="重新上架"><i class="fas fa-arrow-up"></i> 重新上架</button>';
    }
    if (v.status === 1 || v.status === 3) {
        actionsHtml += '<button class="listings__btn listings__btn--edit" onclick="editItem(' + v.id + ', this)" title="编辑"><i class="fas fa-pen"></i> 编辑</button>';
    }

    var discountHtml = discount > 0 ? '<span class="card__discount">-' + Math.round((1 - discount / 10) * 100) + '%</span>' : '';
    var price = parseFloat(v.sale_price || 0);
    var originalPrice = parseFloat(v.original_amount || 0);

    return '<div class="listings__item" data-listing-id="' + v.id + '" data-status="' + v.status + '">' +
        '<div class="listings__item-main">' +
            '<div class="listings__item-header">' +
                '<a href="/detail?id=' + v.id + '" class="listings__item-title">' + escHtml(v.title || v.product_name) + '</a>' +
            '</div>' +
            '<div class="listings__item-tags">' + tagsHtml + '</div>' +
            specsHtml +
            barHtml +
            '<div class="listings__item-meta">' +
                '<span><i class="far fa-clock"></i> ' + new Date(v.create_time * 1000).toISOString().slice(0, 10) + '</span>' +
                '<span><i class="far fa-eye"></i> ' + (v.views || 0) + '</span>' +
            '</div>' +
        '</div>' +
        '<div class="listings__item-side">' +
            '<span class="listings__status ' + status['class'] + '">' + status['label'] + '</span>' +
            '<div class="listings__item-price">' +
                discountHtml +
                '<span class="card__price-symbol">¥</span>' +
                '<span class="card__price-amount">' + escHtml(fmtPriceJS(price)) + '</span>' +
            '</div>' +
            (originalPrice > 0 && price !== originalPrice ? '<div class="listings__item-original">原价 ¥' + escHtml(fmtPriceJS(originalPrice)) + '</div>' : '') +
            '<div class="listings__item-actions">' + actionsHtml + '</div>' +
        '</div>' +
    '</div>';
}

function renderPagination(total, page, size) {
    var totalPages = Math.max(1, Math.ceil(total / size));
    if (totalPages <= 1) return '';

    var html = '';
    if (page > 1) {
        html += '<a href="?page=' + (page - 1) + '" class="pagination__item"><i class="fas fa-chevron-left"></i></a>';
    }
    var start = Math.max(1, page - 2);
    var end = Math.min(totalPages, page + 2);
    if (start > 1) {
        html += '<a href="?page=1" class="pagination__item">1</a>';
        if (start > 2) html += '<span class="pagination__item disabled">...</span>';
    }
    for (var i = start; i <= end; i++) {
        html += '<a href="?page=' + i + '" class="pagination__item ' + (i === page ? 'active' : '') + '">' + i + '</a>';
    }
    if (end < totalPages) {
        if (end < totalPages - 1) html += '<span class="pagination__item disabled">...</span>';
        html += '<a href="?page=' + totalPages + '" class="pagination__item">' + totalPages + '</a>';
    }
    if (page < totalPages) {
        html += '<a href="?page=' + (page + 1) + '" class="pagination__item"><i class="fas fa-chevron-right"></i></a>';
    }
    html += '<span class="pagination__info">第 ' + page + ' / ' + totalPages + ' 页</span>';
    return html;
}

async function loadListings() {
    hideById('listingsLoading');
    hideById('listingsUnauth');
    hideById('listingsError');
    hideById('listingsEmpty');
    hideById('listingsList');
    hideById('listingsStats');
    hideById('listingsPagination');

    if (!window.__marketUser || !window.__marketUser.loggedIn) {
        showById('listingsUnauth');
        return;
    }

    try {
        var resp = await fetch(API_BASE + '/market_api.php?action=my_listings&page=' + CURRENT_PAGE + '&size=' + PAGE_SIZE, { credentials: 'include' });
        var data = await resp.json();
        if (data.status !== 200) {
            showById('listingsError');
            return;
        }
        var list = data.data.list || [];
        var total = data.data.total || 0;
        var specLabels = data.data.spec_labels || {};

        if (list.length === 0) {
            showById('listingsEmpty');
            return;
        }

        var activeCount = 0, pendingCount = 0, delistCount = 0;
        list.forEach(function(v) {
            if (v.status === 1) activeCount++;
            if (v.status === 0) pendingCount++;
            if (v.status === 3) delistCount++;
        });
        document.getElementById('statAll').textContent = total;
        document.getElementById('statActive').textContent = activeCount;
        document.getElementById('statPending').textContent = pendingCount;
        document.getElementById('statDelist').textContent = delistCount;
        showById('listingsStats');

        var html = '';
        for (var i = 0; i < list.length; i++) {
            html += renderListingItem(list[i], specLabels);
        }
        document.getElementById('listingsList').innerHTML = html;
        showById('listingsList');

        var pagHtml = renderPagination(total, CURRENT_PAGE, PAGE_SIZE);
        if (pagHtml) {
            document.getElementById('listingsPagination').innerHTML = pagHtml;
            showById('listingsPagination');
        }
    } catch (e) {
        showById('listingsError');
    }
}

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

(function initListings() {
    if (window.__marketUser) {
        loadListings();
        return;
    }
    var checkCount = 0;
    var timer = setInterval(function() {
        checkCount++;
        if (window.__marketUser) {
            clearInterval(timer);
            loadListings();
        } else if (checkCount > 50) {
            clearInterval(timer);
            hideById('listingsLoading');
            showById('listingsUnauth');
        }
    }, 100);
})();

<?php echo \Market\Auth::jsSnippet($apiBaseUrl); ?>
</script>
</body>
</html>