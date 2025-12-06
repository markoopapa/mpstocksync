<?php
namespace MpStockSync\ApiClient;

/**
 * Trendyol API client (stock-only)
 * - provide Basic/Auth headers as needed
 * - updateStock($sellerStockItemId, $quantity) or by barcode depending on your integration
 */
class TrendyolApiClient
{
    private $apiUrl;
    private $apiKey;
    private $apiSecret;
    private $sellerId;

    public function __construct(string $apiUrl = '', string $apiKey = '', string $apiSecret = '', string $sellerId = '')
    {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->sellerId = $sellerId;
    }

    /**
     * Update stock on Trendyol (mock)
     */
    public function updateStock(string $externalId, int $quantity): array
    {
        // TODO: implement Trendyol stock update endpoint
        return [
            'success' => true,
            'message' => 'Mock Trendyol stock updated',
            'external_id' => $externalId,
            'quantity' => $quantity
        ];
    }
}
