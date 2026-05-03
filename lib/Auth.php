<?php

/**
 * RuiNexus Market - 前端认证助手
 *
 * 魔方财务 JWT 机制:
 *   Cookie 名 = ZJMF_ + MD5(system_license + 二级域名)[16:32] (动态)
 *   Cookie 值 = 完整 JWT 字符串 (header.payload.signature)
 *   Payload  = {userinfo:{id,username}, ip, iat, nbf, exp}
 *
 * 本类通过扫描 Cookie 自动发现 JWT，
 * 解析 payload 获取 uid/username（不解码签名，签名验证由 API 端完成）。
 *
 * 开发者: RuiNexus / YeHuaiJing
 */

namespace Market;

class Auth
{
    private static $uid = null;
    private static $username = null;
    private static $jwt = null;

    /**
     * 检查当前用户是否已登录
     */
    public static function check()
    {
        return self::getUid() > 0;
    }

    /**
     * 获取当前用户 ID
     *
     * 优先级:
     *   1. 内存缓存
     *   2. 从 Cookie 自动发现 JWT → 解析 payload → 提取 uid
     *   3. Session fallback
     */
    public static function getUid()
    {
        if (self::$uid !== null) {
            return self::$uid;
        }

        $jwt = self::getJwt();
        if ($jwt) {
            $payload = self::parsePayload($jwt);
            if ($payload && !empty($payload['userinfo']['id'])) {
                self::$uid = intval($payload['userinfo']['id']);
                self::$username = $payload['userinfo']['username'] ?? '';
                return self::$uid;
            }
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (isset($_SESSION['user']['id'])) {
            self::$uid = intval($_SESSION['user']['id']);
            self::$username = $_SESSION['user']['username'] ?? '';
            return self::$uid;
        }

        self::$uid = 0;
        return self::$uid;
    }

    /**
     * 获取当前用户信息
     */
    public static function getUser()
    {
        $uid = self::getUid();
        return [
            'id'       => $uid,
            'username' => self::$username ?? '',
            'loggedIn' => $uid > 0,
        ];
    }

    /**
     * 获取当前 JWT 字符串
     */
    public static function getJwt()
    {
        if (self::$jwt !== null) {
            return self::$jwt ?: null;
        }

        $frontendConfig = [];
        $configFile = dirname(__DIR__) . '/config.php';
        if (file_exists($configFile)) {
            $frontendConfig = require $configFile;
        }
        $cookieName = $frontendConfig['cookie_name'] ?? '';

        if ($cookieName && !empty($_COOKIE[$cookieName])) {
            $value = $_COOKIE[$cookieName];
            if (self::looksLikeJwt($value)) {
                self::$jwt = $value;
                return self::$jwt;
            }
        }

        foreach ($_COOKIE as $name => $value) {
            if (!is_string($value) || strlen($value) < 20 || strlen($value) > 2000) {
                continue;
            }
            if (self::looksLikeJwt($value)) {
                self::$jwt = $value;
                return self::$jwt;
            }
        }

        self::$jwt = '';
        return null;
    }

    /**
     * 判断字符串是否像 JWT（3段 base64url + 2个点）
     */
    private static function looksLikeJwt($str)
    {
        $parts = explode('.', $str);
        if (count($parts) !== 3) return false;
        foreach ($parts as $part) {
            if (empty($part)) return false;
        }
        return true;
    }

    /**
     * 解析 JWT payload（不解码签名，仅提取用户信息）
     *
     * @return array|null
     */
    private static function parsePayload($jwt)
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;

        $payload = $parts[1];
        $payload = str_replace(['-', '_'], ['+', '/'], $payload);
        $payload = base64_decode($payload);
        if (!$payload) return null;

        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) return null;

        return $data;
    }

    /**
     * 浏览器端认证 + UI 更新 JS 代码
     *
     * fetch /me 端点 → 设置 window.__marketUser → 更新页面导航栏和按钮
     *
     * @param string $apiBase 魔方 API 基础 URL
     *
     * 用法: <?php echo Auth::jsSnippet($apiBaseUrl); ?>
     */
    public static function jsSnippet($apiBase)
    {
        $url = rtrim($apiBase, '/') . '/market_api.php?action=me';
        $loginUrl = self::getLoginUrl($apiBase);
        $regUrl = self::getRegisterUrl($apiBase);
        return "(function(){var api='" . $url . "';var loginUrl='" . $loginUrl . "';var regUrl='" . $regUrl . "';function updateNav(user){var na=document.querySelector('.nav-actions');if(!na)return;if(user.loggedIn){na.innerHTML='<a href=\"/publish\" class=\"nav-cta\">发布</a><a href=\"/user/listings\" class=\"nav-cta--ghost\">我的</a><a href=\"/user/orders\" class=\"nav-cta--ghost\">订单</a>'}else{na.innerHTML='<a href=\"'+loginUrl+'\" class=\"nav-cta--ghost\">登录</a><a href=\"'+regUrl+'\" class=\"nav-cta\">注册</a>'}}function updateDetail(user){var da=document.querySelector('.detail__actions');if(!da)return;if(user.loggedIn){var bb=da.querySelector('.detail__btn--buy');if(bb&&bb.tagName==='A'){var btn=document.createElement('button');btn.className='detail__btn detail__btn--buy';btn.setAttribute('onclick','buyListing()');btn.innerHTML='<i class=\"fas fa-shopping-cart\"></i> 立即购买';bb.parentNode.replaceChild(btn,bb)}var fb=document.getElementById('favBtn');if(fb){fb.disabled=false;fb.removeAttribute('title')}}else{var bb2=da.querySelector('.detail__btn--buy');if(bb2&&bb2.tagName==='BUTTON'){var a=document.createElement('a');a.className='detail__btn detail__btn--buy';a.href=loginUrl;a.innerHTML='<i class=\"fas fa-sign-in-alt\"></i> 登录后购买';bb2.parentNode.replaceChild(a,bb2)}var fb2=document.getElementById('favBtn');if(fb2){fb2.disabled=true;fb2.setAttribute('title','请先登录')}}}fetch(api,{credentials:'include'}).then(function(r){return r.json()}).then(function(d){var user=d.status===200&&d.data?d.data:{id:0,username:'',loggedIn:false};window.__marketUser=user;updateNav(user);updateDetail(user)}).catch(function(){window.__marketUser={id:0,username:'',loggedIn:false}})})();";
    }

    /**
     * 获取登录跳转 URL
     */
    public static function getLoginUrl($apiBaseUrl)
    {
        return rtrim($apiBaseUrl, '/') . '/login';
    }

    /**
     * 获取注册跳转 URL
     */
    public static function getRegisterUrl($apiBaseUrl)
    {
        return rtrim($apiBaseUrl, '/') . '/register';
    }
}
