<?php

class Cryptomus {

    private $apiKey;
    private $baseUrl;

    public function __construct($apiKey, $baseUrl) {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
    }

    private function request($endpoint, $data = []) {

        $url = $this->baseUrl . $endpoint;

        $payload = json_encode($data);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->apiKey
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return [
                'status' => 'error',
                'message' => curl_error($ch)
            ];
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    /* =========================
       CREATE PAYMENT
    ========================= */
    public function createInvoice($data) {
        return $this->request("/v1/payment", $data);
    }

    /* =========================
       CHECK PAYMENT
    ========================= */
    public function verifyPayment($uuid) {
        return $this->request("/v1/payment/info", [
            "uuid" => $uuid
        ]);
    }
} 
