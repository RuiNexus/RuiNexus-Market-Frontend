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
     * 浏览器端 JWT 扫描 JS 代码（前端 fallback）
     *
     * PHP 端 $_COOKIE 在跨子域（Cookie 域未设为 .xxx.com）时可能读不到，
     * 此时浏览器端 document.cookie 仍可访问。调用此方法输出 JS 代码后，
     * 可通过 window.__marketUser 获取用户状态。
     *
     * 用法: 在页面 <script> 中 <?php echo Auth::jsSnippet(); ?>
     */
    public static function jsSnippet()
    {
        return "(function(){var c=document.cookie.split(';');for(var i=0;i<c.length;i++){var v=c[i].trim();var e=v.indexOf('=');if(e===-1)continue;v=v.substring(e+1);if(v.length<20||v.length>2000)continue;var p=v.split('.');if(p.length!==3||!p[0]||!p[1]||!p[2])continue;try{var d=JSON.parse(atob(p[1].replace(/-/g,'+').replace(/_/g,'/')));if(d.userinfo&&d.userinfo.id){window.__marketUser={id:d.userinfo.id,username:d.userinfo.username||'',loggedIn:true};return;}}catch(ex){}}window.__marketUser={id:0,username:'',loggedIn:false};})();";
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
