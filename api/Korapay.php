<?php
/**
 * Korapay API wrapper
 * Docs: https://docs.korapay.com
 */

class Korapay {

    private $secretKey;
    private $baseUrl = "https://api.korapay.com/merchant/api/v1";

    public function __construct($secretKey) {
        $this->secretKey = $secretKey;
    }

    private function request($method, $endpoint, $data = []) {

        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->secretKey
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
     * Initialize a checkout charge.
     * $data: amount, currency, reference, customer_name, customer_email, redirect_url
     */
    public function initializeCharge($data) {
        return $this->request('POST', '/charges/initialize', [
            "amount"       => $data['amount'],
            "currency"     => $data['currency'] ?? 'NGN',
            "reference"    => $data['reference'],
            "narration"    => $data['narration'] ?? 'Wallet funding',
            "notification_url" => $data['notification_url'] ?? null,
            "redirect_url" => $data['redirect_url'] ?? null,
            "customer"     => [
                "name"  => $data['customer_name'] ?? '',
                "email" => $data['customer_email'] ?? ''
            ]
        ]);
    }

    /**
     * Verify/check the status of a charge by its reference.
     */
    public function verifyCharge($reference) {
        return $this->request('GET', '/charges/' . rawurlencode($reference));
    }
}
