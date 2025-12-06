<?php

namespace Markoopapa\MpStockSync\Service;

use Db;
use Configuration;
use PrestaShopLogger;

class SupplierSyncService
{
    private $apiUrl;
    private $apiKey;

    public function __construct()
    {
        // Később ezeket a Config oldalon állítod majd be
        $this->apiUrl = Configuration::get('MP_SUPPLIER_URL');
        $this->apiKey = Configuration::get('MP_SUPPLIER_KEY');
    }

    /**
     * Ez a fő függvény, amit a CRON hív meg
     */
    public function syncAllProducts()
    {
        // 1. Lekérjük azokat a termékeket, ahol a SYNC be van kapcsolva
        $productsToSync = $this->getSyncableProducts();

        if (empty($productsToSync)) {
            $this->log("No products marked for sync.", "INFO");
            return;
        }

        // 2. Lekérjük a Supplier készletét (API hívás)
        $supplierStockData = $this->fetchSupplierStock();
        
        if (!$supplierStockData) {
            $this->log("Failed to fetch supplier data.", "ERROR");
            return;
        }

        // 3. Összefésülés
        foreach ($productsToSync as $product) {
            $mySku = $product['supplier_sku']; // A mapping táblánkból
            
            if (isset($supplierStockData[$mySku])) {
                $newQty = (int)$supplierStockData[$mySku];
                $this->updateLocalStock($product['id_product'], $newQty);
            }
        }
    }

    private function getSyncableProducts()
    {
        return Db::getInstance()->executeS('
            SELECT id_product, supplier_sku 
            FROM `'._DB_PREFIX_.'mp_supplier_map` 
            WHERE sync_enabled = 1
        ');
    }
    
    /**
     * Placeholder: Ide jön majd a tényleges API hívás
     */
    private function fetchSupplierStock()
    {
        // TODO: Megírni az API klienst
        // Return formátum: ['SKU123' => 50, 'SKU999' => 0]
        return []; 
    }

    private function updateLocalStock($id_product, $quantity)
    {
        // PrestaShop beépített készletkezelőjét használjuk (StockAvailable)
        \StockAvailable::setQuantity($id_product, 0, $quantity);
        
        // Frissítjük a last_synced dátumot
        Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'mp_supplier_map` 
            SET last_synced = NOW() 
            WHERE id_product = ' . (int)$id_product
        );
        
        $this->log("Updated Product ID $id_product to QTY: $quantity", "SUCCESS");
    }

    private function log($message, $severity)
    {
        Db::getInstance()->insert('mp_stock_logs', [
            'severity' => $severity,
            'message' => pSQL($message),
            'date_add' => date('Y-m-d H:i:s')
        ]);
    }
}
