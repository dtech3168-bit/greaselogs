<?php
/**
 * NOWPayments API wrapper (hosted invoice flow)
 * Docs: https://documenter.getpostman.com/view/7907941/2s93JusNJt
 */

class NowPayments {

    private $apiKey;
    private $baseUrl = "https://api.nowpayments.io/v1";

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    private function request($method, $endpoint, $data = []) {

        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);

        $headers = [
            "Content-Type: application/json",
            "x-api-key: " . $this->apiKey
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['status' => false, 'message' => $error];
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Create a hosted invoice. NOWPayments prices in fiat (e.g. usd)
     * and lets the customer pick the crypto currency on their page.
     * $data: price_amount, price_currency, order_id, order_description,
     *        ipn_callback_url, success_url, cancel_url
     */
    public function createInvoice($data) {
        return $this->request('POST', '/invoice', [
            "price_amount"       => $data['price_amount'],
            "price_currency"     => $data['price_currency'] ?? 'usd',
            "order_id"           => $data['order_id'],
            "order_description"  => $data['order_description'] ?? 'Wallet funding',
            "ipn_callback_url"   => $data['ipn_callback_url'] ?? null,
            "success_url"        => $data['success_url'] ?? null,
            "cancel_url"         => $data['cancel_url'] ?? null,
        ]);
    }

    /**
     * Look up payment status by NOWPayments payment id.
     */
    public function getPaymentStatus($paymentId) {
        return $this->request('GET', '/payment/' . rawurlencode($paymentId));
    }

    /**
     * Verify an IPN callback's signature.
     * NOWPayments signs the sorted, JSON-encoded body with HMAC-SHA512
     * using your IPN secret key.
     */
    public static function verifyIpnSignature(string $rawBody, string $receivedSignature, string $ipnSecret): bool {
        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            return false;
        }

        ksort($data);
        $sortedJson = json_encode($data, JSON_UNESCAPED_SLASHES);

        $expected = hash_hmac('sha512', $sortedJson, $ipnSecret);

        return hash_equals($expected, $receivedSignature);
    }
}
