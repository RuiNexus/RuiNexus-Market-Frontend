<?php

/**
 * RuiNexus Market - Frontend Auth Helper
 *
 * 同域通过 Cookie 共享登录态
 * 如果 cookie 中已有魔方的登录 session，直接复用
 *
 * 开发者: RuiNexus / YeHuaiJing
 */

namespace Market;

class Auth
{
    private static $uid = null;

    /**
     * 检查当前用户是否已登录
     */
    public static function check()
    {
        return self::getUid() > 0;
    }

    /**
     * 获取当前用户ID（通过Cookie中魔方系统的session判断）
     */
    public static function getUid()
    {
        if (self::$uid !== null) {
            return self::$uid;
        }

        $cookieName = 'user_login';
        if (isset($_COOKIE[$cookieName])) {
            // 从魔方的 cookie 中读取 uid
            $cookieData = $_COOKIE[$cookieName];
            // 如果魔方的 user cookie 是加密的，需要通过魔方接口验证
        }

        // 备用：检查 PHP session
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (isset($_SESSION['user']['id'])) {
            self::$uid = intval($_SESSION['user']['id']);
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
        return [
            'id'       => self::getUid(),
            'loggedIn' => self::getUid() > 0,
        ];
    }

    /**
     * 获取登录跳转URL（跳转到魔方主站登录页）
     */
    public static function getLoginUrl($apiBaseUrl)
    {
        return rtrim($apiBaseUrl, '/') . '/login';
    }

    /**
     * 获取注册跳转URL
     */
    public static function getRegisterUrl($apiBaseUrl)
    {
        return rtrim($apiBaseUrl, '/') . '/register';
    }
}
