# RuiNexus Market - Frontend

二手服务器转卖平台前端独立站点。

## 开发者
RuiNexus / YeHuaiJing

## 仓库
https://github.com/RuiNexus/RuiNexus-Market-Frontend

## 部署
1. 将站点文件部署到 Web 服务器（如 `trade.yourdomain.com`）
2. 复制 `config.example.php` 为 `config.php`
3. 修改 `config.php` 中的 `api_base_url` 指向魔方财务主站地址
4. 配置 Nginx/Apache 将请求转发到 `public/index.php`

## 依赖
- PHP 7.2+
- 魔方财务系统（需安装 RuiNexus Market 插件）
- 前端站点须与魔方主站同顶级域名（共享 Cookie 登录态）

## 文件结构
```
├── public/
│   └── index.php              # 入口 + 简单路由
├── config.example.php         # 配置文件模板
├── pages/
│   ├── index.php              # 首页/服务器列表
│   ├── detail.php             # 服务器详情页
│   ├── publish.php            # 发布商品页
│   └── user/
│       ├── listings.php       # 我的发布
│       ├── orders.php         # 我的购买
│       └── favorites.php      # 我的收藏
├── lib/
│   ├── ApiClient.php          # API 调用封装
│   └── Auth.php               # 登录态管理
└── assets/
    ├── css/style.css          # 样式
    └── js/main.js             # 脚本
```
