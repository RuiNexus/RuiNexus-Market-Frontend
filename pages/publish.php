<?php

use Market\Auth;

$siteConfig = $api->getConfig()['data'] ?? [];
$siteName   = $siteConfig['site_name'] ?? 'RuiNexus Market';
$notice     = $siteConfig['notice_content'] ?? '';
$user       = Auth::getUser();

$frontendConfig = require __DIR__ . '/../config.php';
$apiBaseUrl = $frontendConfig['api_base_url'] ?? 'https://test.ruinexus.com';
$siteName = $frontendConfig['site_name'] ?: $siteName;

$fieldsResult = $api->get('fields');
$specFields = $fieldsResult['data'] ?? [];

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
    <title>发布商品 - <?php echo htmlspecialchars($siteName); ?></title>
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
        <li><a href="/publish" class="active">发布</a></li>
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
    <div class="section-label">02 / PUBLISH</div>
    <h2 class="section-title">发布二手服务器</h2>

    <div class="publish">
        <div class="publish__step" id="step1">
            <h3 class="publish__step-title"><span class="publish__step-num">1</span> 选择要出售的服务器</h3>

            <div id="hostsLoading" class="empty">
                <div class="empty__icon"><i class="fas fa-spinner fa-pulse"></i></div>
                <p>正在加载服务器列表...</p>
            </div>

            <div id="hostsUnauth" class="empty" style="display:none;">
                <div class="empty__icon"><i class="fas fa-lock"></i></div>
                <p>请先登录以查看可出售的服务器</p>
                <a href="<?php echo Auth::getLoginUrl($apiBaseUrl); ?>" class="detail__btn detail__btn--buy" style="display:inline-flex;margin-top:20px;width:auto;padding:12px 28px;">
                    <i class="fas fa-sign-in-alt"></i> 登录账号
                </a>
            </div>

            <div id="hostsError" class="empty" style="display:none;">
                <div class="empty__icon"><i class="fas fa-exclamation-triangle"></i></div>
                <p>加载失败，请刷新重试</p>
            </div>

            <div id="hostsEmpty" class="empty" style="display:none;">
                <div class="empty__icon"><i class="fas fa-server"></i></div>
                <p>您没有可出售的服务器（需状态为 Active 且不在交易黑名单中）</p>
            </div>

            <div id="hostsList" class="publish__hosts" style="display:none;">
            </div>
        </div>

        <div id="publishForm" class="publish__step" style="display:none;">
            <h3 class="publish__step-title"><span class="publish__step-num">2</span> 填写商品信息</h3>

            <div class="publish__form">
                <input type="hidden" id="formHostId" value="">

                <div class="publish__field">
                    <label class="publish__label">产品名称</label>
                    <div class="publish__product-name" id="formProductName" style="padding:10px 0;font-size:15px;font-weight:600;color:var(--text-primary);">—</div>
                </div>

                <div class="publish__field">
                    <label class="publish__label">售价 (CNY)</label>
                    <div class="publish__price-row">
                        <span class="publish__price-symbol">¥</span>
                        <input type="number" id="formPrice" class="publish__input publish__input--price" step="0.01" min="0.01" placeholder="输入您期望的售价" required>
                        <span class="publish__price-hint" id="priceHint"></span>
                    </div>
                </div>

                <div class="publish__field">
                    <label class="publish__label">卖家备注 <span style="color:var(--muted);font-weight:normal;">（买家可见）</span></label>
                    <textarea id="formNotes" class="publish__textarea" rows="3" placeholder="给买家看的备注信息，如特殊说明、注意事项等"></textarea>
                </div>

                <?php if (!empty($specFields)): ?>
                <div class="publish__field">
                    <label class="publish__label">配置信息</label>
                    <div class="publish__specs" id="specFieldsContainer">
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
                                    <input type="radio" name="spec_<?php echo htmlspecialchars($field['field_name']); ?>" class="publish__spec-input" data-field="<?php echo htmlspecialchars($field['field_name']); ?>" value="<?php echo htmlspecialchars(is_array($opt) ? ($opt['value'] ?? $opt) : $opt); ?>">
                                    <span><?php echo htmlspecialchars(is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? '') : $opt); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <?php elseif ($field['field_type'] === 'number'): ?>
                            <input type="number" class="publish__input publish__spec-input" data-field="<?php echo htmlspecialchars($field['field_name']); ?>" placeholder="请输入<?php echo htmlspecialchars($field['field_label']); ?>" step="1">
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

                <div class="publish__actions">
                    <button type="button" class="detail__btn detail__btn--buy" onclick="submitPublish()">
                        <i class="fas fa-paper-plane"></i> 确认发布
                    </button>
                    <button type="button" class="detail__btn detail__btn--back" onclick="resetForm()">
                        <i class="fas fa-arrow-left"></i> 重新选择
                    </button>
                </div>
            </div>
        </div>
    </div>
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
var API_BASE = <?php echo json_encode($apiBaseUrl); ?>;
var LOGIN_URL = <?php echo json_encode(Auth::getLoginUrl($apiBaseUrl)); ?>;
var selectedHostId = 0;
var originalAmount = 0;
var selectedProductName = '';

function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function hostCardHtml(h) {
    var billingTag = h.billingcycle ? (
        {monthly:'月付',quarterly:'季付',semiannually:'半年付',annually:'年付',biennially:'两年付',triennially:'三年付',onetime:'永久',free:'免费'}[h.billingcycle] || h.billingcycle.toUpperCase()
    ) : '';
    var remainingDays = h.remaining_days;
    var isOnSale = h.is_on_sale;
    var daysTag = '';
    if (remainingDays !== null && remainingDays !== undefined && remainingDays > 0) {
        var cls = remainingDays > 65 ? 'is-safe' : (remainingDays > 30 ? 'is-warning' : 'is-danger');
        daysTag = '<span class="card__tag card__tag--days ' + cls + '">' + remainingDays + ' 天剩余</span>';
    } else if (remainingDays === null || remainingDays === undefined) {
        daysTag = '<span class="card__tag">永久有效</span>';
    }
    var price = parseFloat(h.original_amount || 0);
    var priceStr = price === Math.floor(price) ? price.toLocaleString() : price.toFixed(2);

    var onclick = isOnSale ? '' : ' onclick="selectHost(this)"';
    var disabledClass = isOnSale ? ' is-disabled' : '';
    var radioHtml = isOnSale
        ? '<span class="publish__host-onsale"><i class="fas fa-tag"></i> 在售</span>'
        : '<i class="far fa-circle publish__host-unchecked"></i><i class="fas fa-check-circle publish__host-checked" style="display:none;"></i>';

    return '<div class="publish__host-card' + disabledClass + '" data-host-id="' + h.id + '" data-product-name="' + escHtml(h.product_name || '') + '" data-original-amount="' + price.toFixed(2) + '" data-billing-cycle="' + escHtml(h.billingcycle || '') + '"' + onclick + '>' +
        '<div class="publish__host-radio">' + radioHtml + '</div>' +
        '<div class="publish__host-info">' +
            '<div class="publish__host-name">' + escHtml(h.product_name || '') + '</div>' +
            '<div class="publish__host-meta">' +
                (billingTag ? '<span class="card__tag">' + escHtml(billingTag) + '</span>' : '') +
                daysTag +
            '</div>' +
        '</div>' +
        '<div class="publish__host-price">' +
            '<span class="publish__host-price-label">原价</span>' +
            '<span class="publish__host-price-value">¥' + escHtml(priceStr) + '</span>' +
        '</div>' +
    '</div>';
}

function selectHost(el) {
    document.querySelectorAll('.publish__host-card').forEach(function(c) {
        c.classList.remove('is-selected');
        var uc = c.querySelector('.publish__host-unchecked');
        var cc = c.querySelector('.publish__host-checked');
        if (uc) uc.style.display = '';
        if (cc) cc.style.display = 'none';
    });

    el.classList.add('is-selected');
    var uc = el.querySelector('.publish__host-unchecked');
    var cc = el.querySelector('.publish__host-checked');
    if (uc) uc.style.display = 'none';
    if (cc) cc.style.display = '';

    selectedHostId = parseInt(el.dataset.hostId);
    originalAmount = parseFloat(el.dataset.originalAmount) || 0;
    selectedProductName = el.dataset.productName || '';

    document.getElementById('formHostId').value = selectedHostId;
    document.getElementById('formPrice').value = el.dataset.originalAmount || '';
    document.getElementById('priceHint').textContent = originalAmount > 0 ? '原价 ¥' + originalAmount.toFixed(2) : '';
    document.getElementById('formProductName').textContent = selectedProductName;

    document.getElementById('publishForm').style.display = '';
    document.getElementById('publishForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetForm() {
    selectedHostId = 0;
    originalAmount = 0;
    document.getElementById('publishForm').style.display = 'none';
    document.querySelectorAll('.publish__host-card').forEach(function(c) {
        c.classList.remove('is-selected');
        var uc = c.querySelector('.publish__host-unchecked');
        var cc = c.querySelector('.publish__host-checked');
        if (uc) uc.style.display = '';
        if (cc) cc.style.display = 'none';
    });
}

function collectSpecData() {
    var data = {};

    document.querySelectorAll('.publish__spec-field').forEach(function(f) {
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

function validateSpecFields() {
    var valid = true;
    document.querySelectorAll('.publish__spec-field').forEach(function(f) {
        var isRequired = f.dataset.isRequired === '1';
        if (!isRequired) return;

        var fieldType = f.dataset.fieldType;
        var hasValue = false;

        if (fieldType === 'checkbox') {
            hasValue = f.querySelectorAll('input[type="checkbox"]:checked').length > 0;
        } else if (fieldType === 'radio') {
            hasValue = !!f.querySelector('input[type="radio"]:checked');
        } else {
            var input = f.querySelector('.publish__spec-input');
            hasValue = input && input.value.trim() !== '';
        }

        if (!hasValue) {
            f.classList.add('is-error');
            valid = false;
        } else {
            f.classList.remove('is-error');
        }
    });
    return valid;
}

async function submitPublish() {
    if (selectedHostId <= 0) {
        alert('请先选择要出售的服务器');
        return;
    }

    var title = selectedProductName;
    var salePrice = parseFloat(document.getElementById('formPrice').value);
    if (!salePrice || salePrice <= 0) {
        alert('请输入有效的售价');
        return;
    }

    if (!validateSpecFields()) {
        alert('请填写所有必填配置字段');
        return;
    }

    var specData = collectSpecData();
    var params = new URLSearchParams();
    params.append('host_id', selectedHostId);
    params.append('title', title);
    params.append('sale_price', salePrice);
    params.append('notes', document.getElementById('formNotes').value.trim());
    if (Object.keys(specData).length > 0) {
        params.append('spec_data', JSON.stringify(specData));
    }

    try {
        var resp = await fetch(API_BASE + '/market_api.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString(),
            credentials: 'include'
        });
        var result = await resp.json();
        if (result.status === 200) {
            alert(result.msg || '发布成功');
            window.location.href = '/user/listings';
        } else if (result.status === 401) {
            alert('请先登录');
        } else {
            alert(result.msg || '发布失败');
        }
    } catch (e) {
        alert('请求失败，请重试');
    }
}

function showById(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = '';
}
function hideById(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

async function loadHosts() {
    hideById('hostsLoading');
    hideById('hostsUnauth');
    hideById('hostsError');
    hideById('hostsEmpty');
    hideById('hostsList');

    if (!window.__marketUser || !window.__marketUser.loggedIn) {
        showById('hostsUnauth');
        return;
    }

    try {
        var resp = await fetch(API_BASE + '/market_api.php?action=my_hosts', { credentials: 'include' });
        var data = await resp.json();
        if (data.status !== 200) {
            showById('hostsError');
            return;
        }
        var hosts = data.data || [];
        if (hosts.length === 0) {
            showById('hostsEmpty');
            return;
        }
        var html = '';
        for (var i = 0; i < hosts.length; i++) {
            html += hostCardHtml(hosts[i]);
        }
        document.getElementById('hostsList').innerHTML = html;
        showById('hostsList');
    } catch (e) {
        showById('hostsError');
    }
}

(function initPublish() {
    if (window.__marketUser) {
        loadHosts();
        return;
    }
    var checkCount = 0;
    var timer = setInterval(function() {
        checkCount++;
        if (window.__marketUser) {
            clearInterval(timer);
            loadHosts();
        } else if (checkCount > 50) {
            clearInterval(timer);
            hideById('hostsLoading');
            showById('hostsUnauth');
        }
    }, 100);
})();

<?php echo \Market\Auth::jsSnippet($apiBaseUrl); ?>
</script>
</body>
</html>