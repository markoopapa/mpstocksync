<?php

namespace MpStockSync\Service;

use MpStockSync\ApiClient\SupplierApiClient;
use MpStockSync\Repository\MappingRepository;
use PrestaShopDatabaseException;
use PrestaShopException;

class SupplierSyncService
{
    /**
     * @var SupplierApiClient
     */
    private $supplierApiClient;

    /**
     * @var MappingRepository
     */
    private $mappingRepository;

    public function __construct()
    {
        $this->supplierApiClient = new SupplierApiClient();
        $this->mappingRepository = new MappingRepository();
    }

    /**
     * Fő sync folyamat a supplier → te boltod között
     *
     * @return array
     */
    public function syncFromSupplierToMainShop()
    {
        $log = [];

        // 1. Supplier API lekérés
        $supplierProducts = $this->supplierApiClient->getProducts();

        if (empty($supplierProducts)) {
            return ['error' => 'No supplier products fetched'];
        }

        foreach ($supplierProducts as $sp) {

            $supplierReference = $sp['reference'];
            $supplierQty = (int)$sp['quantity'];

            // Mapping keresése supplier reference alapján
            $mapping = $this->mappingRepository->findBySupplierReference($supplierReference);

            if (!$mapping) {
                $log[] = "NO MAP → " . $supplierReference;
                continue;
            }

            if (!(int)$mapping['active']) {
                $log[] = "INACTIVE MAP → " . $supplierReference;
                continue;
            }

            $yourReference = $mapping['your_reference'];

            // PrestaShop termék ID felkutatása
            $idProduct = $this->getProductIdByReference($yourReference);

            if (!$idProduct) {
                $log[] = "MISSING PRODUCT → YourRef: {$yourReference}";
                continue;
            }

            // Készlet frissítés
            $this->updateStock($idProduct, $supplierQty);

            $log[] = "UPDATED: {$yourReference} → Qty: {$supplierQty}";
        }

        return $log;
    }

    /**
     * Termék ID keresése referencia szerint
     *
     * @param string $reference
     * @return int|null
     */
    private function getProductIdByReference($reference)
    {
        $sql = 'SELECT id_product FROM ' . _DB_PREFIX_ . 'product WHERE reference = "' . pSQL($reference) . '"';
        return (int)\Db::getInstance()->getValue($sql) ?: null;
    }

    /**
     * PrestaShop készlet frissítése
     *
     * @param int $idProduct
     * @param int $qty
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function updateStock($idProduct, $qty)
    {
        \StockAvailable::setQuantity($idProduct, 0, $qty);
    }
}
