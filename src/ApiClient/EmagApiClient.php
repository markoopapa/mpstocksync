<?php
namespace MpStockSync\ApiClient;

/**
 * eMAG API client (stock-only)
 * Note: implement real endpoints and auth when you have eMAG credentials.
 * For now this provides a simple updateStock($sku, $quantity) method stub.
 */
class EmagApiClient
{
    private $apiUrl;
    private $clientId;
    private $clientSecret;
    private $username;
    private $password;

    public function __construct(string $apiUrl = '', string $clientId = '', string $clientSecret = '', string $username = '', string $password = '')
    {
        $this->apiUrl = $apiUrl;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Update stock on eMAG by SKU
     * Return array ['success'=>bool, 'message'=>string]
     */
    public function updateStock(string $sku, int $quantity): array
    {
        // TODO: Replace with real eMAG API call.
        // For now return a mock success for integration.
        return [
            'success' => true,
            'message' => 'Mock eMAG stock updated',
            'sku' => $sku,
            'quantity' => $quantity
        ];
    }
}
