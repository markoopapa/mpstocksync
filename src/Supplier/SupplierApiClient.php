<?php

namespace MpStockSync\Supplier;

use Exception;

class SupplierApiClient
{
    private $apiUrl;
    private $apiKey;

    public function __construct($apiUrl, $apiKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
    }

    /**
     * Fetch products from supplier PrestaShop API
     * Only: name, reference, quantity
     */
    public function getProducts()
    {
        $endpoint = $this->apiUrl . '/products?display=[name,reference,quantity]';

        $response = $this->call($endpoint);

        if (!isset($response['products'])) {
            return [];
        }

        return $response['products'];
    }

    /**
     * Generic API caller
     */
    private function call(string $url)
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($this->apiKey . ':'),
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $result = curl_exec($ch);

        if ($result === false) {
            throw new Exception('Supplier API connection failed: ' . curl_error($ch));
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($code !== 200) {
            throw new Exception('Supplier API error: HTTP ' . $code);
        }

        $json = json_decode($result, true);

        if (!is_array($json)) {
            throw new Exception('Invalid JSON from supplier API');
        }

        return $json;
    }
}
