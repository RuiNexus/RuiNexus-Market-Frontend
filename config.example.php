<?php

/**
 * RuiNexus Market - Frontend Config
 * 前端站点配置文件
 *
 * 开发者: RuiNexus / YeHuaiJing
 * 仓库: https://github.com/RuiNexus/RuiNexus-Market-Frontend
 */

return [
    // 魔方财务主站 API 地址
    'api_base_url' => 'https://test.ruinexus.com',

    // 站点名称 (留空则从插件配置读取)
    'site_name' => 'RuiNexus Market',

    // 魔方 JWT Cookie 名称 (留空则自动扫描 Cookie 检测)
    // 魔方 Cookie 命名规则: "ZJMF_" + MD5(system_license + 二级域名)[16:32]
    // 如果自动检测不准确，可手动指定，如: 'ZJMF_AB12CD34EF56GH78'
    'cookie_name' => '',

    // 缓存时间(秒)
    'cache_ttl' => 600,
];
