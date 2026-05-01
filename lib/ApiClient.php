<?php

/**
 * RuiNexus Market - Frontend API Client
 * 
 * 封装对魔方财务 Market 插件的 API 调用
 *
 * 开发者: RuiNexus / YeHuaiJing
 */

namespace Market;

class ApiClient
{
    private $baseUrl;
    private $token;

    public function __construct($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function setAuthCookie($cookieData)
    {
        $this->authCookie = $cookieData;
    }

    /**
     * 获取站点配置
     */
    public function getConfig()
    {
        return $this->get('/api/market/config');
    }

    /**
     * 获取服务器列表
     */
    public function getList($params = [])
    {
        return $this->get('/api/market/list', $params);
    }

    /**
     * 获取详情
     */
    public function getDetail($id)
    {
        return $this->get("/api/market/detail/{$id}");
    }

    /**
     * 获取我可上架的host
     */
    public function getMyHosts()
    {
        return $this->get('/api/market/my_hosts');
    }

    /**
     * 发布
     */
    public function create($data)
    {
        return $this->post('/api/market/create', $data);
    }

    /**
     * 购买
     */
    public function buy($data)
    {
        return $this->post('/api/market/buy', $data);
    }

    /**
     * 我的发布
     */
    public function getMyListings($params = [])
    {
        return $this->get('/api/market/my_listings', $params);
    }

    /**
     * 我的购买
     */
    public function getMyOrders($params = [])
    {
        return $this->get('/api/market/my_orders', $params);
    }

    /**
     * 我的销售
     */
    public function getMySales($params = [])
    {
        return $this->get('/api/market/my_sales', $params);
    }

    /**
     * 收藏/取消收藏
     */
    public function favorite($id)
    {
        return $this->post("/api/market/favorite/{$id}");
    }

    /**
     * 我的收藏
     */
    public function getFavorites($params = [])
    {
        return $this->get('/api/market/favorites', $params);
    }

    /**
     * 下架
     */
    public function delist($id)
    {
        return $this->post("/api/market/delist/{$id}");
    }

    private function get($path, $params = [])
    {
        return $this->request('GET', $path, $params);
    }

    private function post($path, $data = [])
    {
        return $this->request('POST', $path, $data);
    }

    private function request($method, $path, $data = [])
    {
        $url = $this->baseUrl . $path;

        if ($method === 'GET' && $data) {
            $url .= '?' . http_build_query($data);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $headers = ['Accept: application/json'];

        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        if (isset($this->authCookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->authCookie);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($result, true);
        }

        return [
            'status' => 400,
            'msg'    => 'API请求失败, HTTP状态码: ' . $httpCode,
        ];
    }
}
