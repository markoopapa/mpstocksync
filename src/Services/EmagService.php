<?php
namespace MpStockSync\Services;  // ← EZT TARTSUK MEG!

class EmagService
{
    private $apiUrl;
    private $clientId;
    private $clientSecret;
    private $username;
    private $password;
    private $accessToken;
    
    public function __construct($apiUrl, $clientId, $clientSecret, $username, $password)
    {
        $this->apiUrl = $apiUrl;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->username = $username;
        $this->password = $password;
    }
    
    public function updateStock($sku, $quantity)
    {
        // Mock implementation
        return [
            'success' => true,
            'message' => 'Stock updated successfully (MOCK)',
            'data' => [
                'sku' => $sku,
                'quantity' => $quantity
            ]
        ];
    }
    
    public function testConnection()
    {
        return [
            'success' => true,
            'message' => 'eMAG connection test successful (MOCK)'
        ];
    }
}
