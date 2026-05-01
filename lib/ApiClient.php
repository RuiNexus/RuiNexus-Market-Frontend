<?php

/**
 * RuiNexus Market - Frontend API Client
 * 
 * 封装对魔方财务 Market 插件的 API 调用
 * 请求独立入口文件 market_api.php，不依赖加密的路由系统
 *
 * 开发者: RuiNexus / YeHuaiJing
 */

namespace Market;

class ApiClient
{
    private $baseUrl;
    private $token;
    private $apiEntry = 'market_api.php';

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

    private function apiUrl($action, $params = [])
    {
        $url = $this->baseUrl . '/' . $this->apiEntry . '?action=' . $action;
        if ($params) {
            $url .= '&' . http_build_query($params);
        }
        return $url;
    }

    public function getConfig()
    {
        return $this->get('config');
    }

    public function getList($params = [])
    {
        return $this->get('list', $params);
    }

    public function getDetail($id)
    {
        return $this->get('detail', ['id' => $id]);
    }

    public function getMyHosts()
    {
        return $this->get('my_hosts');
    }

    public function create($data)
    {
        return $this->post('create', $data);
    }

    public function buy($data)
    {
        return $this->post('buy', $data);
    }

    public function getMyListings($params = [])
    {
        return $this->get('my_listings', $params);
    }

    public function getMyOrders($params = [])
    {
        return $this->get('my_orders', $params);
    }

    public function getMySales($params = [])
    {
        return $this->get('my_sales', $params);
    }

    public function favorite($id)
    {
        return $this->post('favorite', ['id' => $id]);
    }

    public function getFavorites($params = [])
    {
        return $this->get('favorites', $params);
    }

    public function delist($id)
    {
        return $this->post('delist', ['id' => $id]);
    }

    private function get($action, $params = [])
    {
        return $this->request('GET', $action, $params);
    }

    private function post($action, $data = [])
    {
        return $this->request('POST', $action, $data);
    }

    private function request($method, $action, $data = [])
    {
        $url = $this->apiUrl($action, $method === 'GET' ? [] : []);

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

        if ($method === 'GET' && $data) {
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl($action, $data));
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
