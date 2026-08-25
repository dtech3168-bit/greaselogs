<?php
/**
* AcctShop API Service (Clean Stable Version)
*/

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

class AcctShopAPI {

    private $api_key;
    private $base_url;

    public function __construct() {

        // use global db helper
        $pdo = db();

        // fetch settings row
        $stmt = $pdo->prepare("SELECT * FROM settings WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch();

        $this->api_key = $settings['api_key'] ?? '';
        $this->base_url = $settings['api_base_url'] ?? ACCTSHOP_API_BASE;
    } 


    /**
     * Core request handler
     */
    private function request($endpoint, $params = [], $method = 'GET') {

        // Attach API key if not provided
        if (!isset($params['api_key'])) {
            $params['api_key'] = $this->api_key;
        }

        $url = $this->base_url . $endpoint;

        $ch = curl_init();

        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

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

    /**
     * Get categories + products
     */
    public function getCategories() {
        return $this->request("products.php");
    }

    /**
     * Get single product
     */
    public function getProduct($product_id) {
        return $this->request("product.php", [
            'product' => $product_id
        ]);
    }

    /**
     * Buy product
     */
    public function buyProduct($product_id, $qty = 1) {
        return $this->request("buy_product.php", [
            'action' => 'buyProduct',
            'id' => $product_id,
            'amount' => $qty,
        ], 'POST');
    }

    /**
     * Get profile
     */
    public function getProfile() {
        return $this->request("profile.php");
    }
} 
