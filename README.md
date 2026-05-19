# RuiNexus Market - Frontend

> 二手服务器转卖平台前端独立站点

---

## ⚠️ 开发阶段

**本项目当前处于开发阶段（v1.x），可能存在功能不完善或有 Bug 的情况。**

- 部分页面交互可能不完整
- API 接口可能存在变更
- 样式和布局可能在不同设备上有显示差异
- 线上部署前请充分测试
- 发现任何问题请 [提交 Issue](https://github.com/RuiNexus/RuiNexus-Market-Frontend/issues) 或联系开发者

---

## 简介

RuiNexus Market Frontend 是二手服务器转卖平台的独立前端站点。通过 HTTP 调用魔方财务系统的 Market API 接口，提供完整的浏览、购买、发布和管理功能。

### 技术栈

- 纯 PHP + HTML/CSS/JavaScript（无前端框架依赖）
- Font Awesome 6.5 图标
- Inter + GeistMono 双字体
- 暗色主题设计系统

### 页面

| 路由 | 页面 | 说明 |
|---|---|---|
| `/` | 首页 | 商品列表、搜索、排序、分页 |
| `/detail?id={id}` | 详情页 | 商品详情、卖家配置、购买、收藏 |
| `/publish` | 发布页 | 选择服务器→填写价格/配置→上架 |
| `/listings` | 我的发布 | 管理已发布商品、编辑、下架、重新上架 |
| `/orders` | 我的购买 | 查看购买订单 |
| `/favorites` | 我的收藏 | 查看收藏商品 |

---

## 部署

### 前置要求

- PHP 7.2+
- 魔方财务系统已安装 [RuiNexus Market 插件](https://github.com/RuiNexus/RuiNexus-Market)
- 前端站点须与魔方主站**同顶级域名**（以共享 Cookie 登录态）

### 部署步骤

1. 将站点文件部署到 Web 服务器（如 `trade.yourdomain.com`，Web 根目录指向 `public/`）
2. 复制 `config.example.php` 为 `config.php`
3. 修改 `config.php` 中的配置：

```php
return [
    // 魔方财务主站地址（必填）
    'api_base_url' => 'https://www.yourdomain.com',

    // 站点名称（留空则从插件配置读取）
    'site_name' => 'RuiNexus Market',

    // 缓存时间(秒)
    'cache_ttl' => 600,
];
```

4. 配置 Nginx 将请求转发到 `public/index.php`：

```nginx
server {
    listen 80;
    server_name trade.yourdomain.com;
    root /path/to/frontend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

5. 确保 `public/assets/` 目录可被浏览器访问（CSS/JS 静态资源）

---

## 文件结构

```
├── public/
│   ├── index.php              # 入口文件 + 简单路由
│   └── assets/
│       └── css/
│           └── style.css      # 样式文件
├── config.example.php         # 配置文件模板
├── config.php                 # 配置文件（部署时创建，不纳入版本控制）
├── pages/
│   ├── index.php              # 首页 / 商品列表
│   ├── detail.php             # 商品详情页
│   ├── publish.php            # 发布商品页
│   └── user/
│       ├── listings.php       # 我的发布
│       ├── orders.php         # 我的购买
│       └── favorites.php      # 我的收藏
├── lib/
│   ├── ApiClient.php          # API 调用封装
│   └── Auth.php               # JWT 检测 + 登录态管理
└── assets/
    └── css/
        └── style.css          # 样式（源文件）
```

---

## 配置说明

`config.php` 中唯一必填项是 `api_base_url`，指向魔方财务主站地址。前端所有 API 请求（商品列表/详情/购买/发布等）均通过此地址调用。

切换环境（测试/生产）只需修改此一处。

---

## 插件端

插件端仓库：[RuiNexus-Market](https://github.com/RuiNexus/RuiNexus-Market)

API 接口文档：见插件端的 `market/API.md`

---

## 开发者

RuiNexus / YeHuaiJing

## Issues

问题反馈：[https://github.com/RuiNexus/RuiNexus-Market-Frontend/issues](https://github.com/RuiNexus/RuiNexus-Market-Frontend/issues)