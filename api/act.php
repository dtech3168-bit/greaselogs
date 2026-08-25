<?php
/**
 * AcctShop API Service (Fixed Version)
 */

require_once __DIR__ . '/../includes/config.php';

class AcctShopAPI {

    private $api_key = "149ec1e0d3929e27dd0a64144862e7b5KNYLivl0rgF1b4awSDqEH2XTCdneZxc5";
    private $base_url = "https://acctshop.com/api/";


    public function __construct() {

        /**
         * Future-proof design:
         * - If admin API key exists in DB → use it
         * - fallback → config constant (optional)
         */
        $this->api_key = defined('ACCTSHOP_API_KEY') && ACCTSHOP_API_KEY !== ''
            ? ACCTSHOP_API_KEY
            : null;
    }

    
    /**
     * Core request handler
     */
    private function request($endpoint, $params = [], $method = 'GET') {

        $url = $this->base_url . $endpoint;

        if ($method === 'GET') {
            $params['api_key'] = $this->api_key;
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            $params['api_key'] = $this->api_key;
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return [
                'status' => 'error',
                'msg' => curl_error($ch)
            ];
        }

        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($decoded === null) {
            return [
                'status' => 'error',
                'msg' => 'Invalid JSON response',
                'raw' => $response
            ];
        }

        return $decoded;
    }

    public function getCategories() {
        return $this->request("products.php");
    }

    public function getProduct($product_id) {
        return $this->request("product.php", [
            'product' => $product_id
        ]);
    }

    public function buyProduct($product_id, $qty = 1) {
        return $this->request("buy_product", [
            'action' => 'buyProduct',
            'id' => $product_id,
            'amount' => $qty
        ], 'POST');
    }

    public function getProfile() {
        return $this->request("profile.php");
    }
}