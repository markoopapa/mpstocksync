<?php
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
        // Mock implementation - cseréld ki valós API hívással
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
            'message' => 'Connection test successful (MOCK)'
        ];
    }
}
