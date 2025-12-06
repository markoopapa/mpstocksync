<?php
namespace MpStockSync\Service;

use MpStockSync\ApiClient\SupplierApiClient;
use MpStockSync\Service\SupplierMappingService;
use MpStockSync\Service\LocalStockService;
use Db;
use Exception;

/**
 * SupplierSyncService
 * - orchestrates: fetch from supplier API, map, update local shop(s)
 */
class SupplierSyncService
{
    private $supplierApiClient;
    private $mappingService;
    private $localStockService;
    private $supplierConfig;

    /**
     * $supplierConfig array expected keys:
     *  - api_url
     *  - api_key
     *  - id_supplier
     *  - target_shops (json encoded array of shop ids) optional
     */
    public function __construct(array $supplierConfig)
    {
        $this->supplierConfig = $supplierConfig;
        $this->supplierApiClient = new SupplierApiClient($supplierConfig['api_url'], $supplierConfig['api_key']);
        $this->mappingService = new SupplierMappingService();
        $this->localStockService = new LocalStockService();
    }

    /**
     * Sync supplier -> target shops (only stock)
     * Returns summary array
     */
    public function sync(): array
    {
        $start = microtime(true);
        $summary = [
            'success' => false,
            'fetched' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'duration_ms' => 0
        ];

        $resp = $this->supplierApiClient->getProducts();

        if (!$resp['success']) {
            $summary['errors'][] = 'Fetch error: ' . ($resp['error'] ?? 'unknown');
            $summary['duration_ms'] = round((microtime(true) - $start) * 1000);
            return $summary;
        }

        $supplierProducts = $resp['products'];
        $summary['fetched'] = count($supplierProducts);

        // Ensure mapping table exists (safe to call)
        $this->mappingService->install();

        // Auto-generate empty mappings for quick review (non-active)
        $this->mappingService->autoGenerateMappings((int)$this->supplierConfig['id_supplier'], $supplierProducts);

        // For each supplier product, find mapping and update stock in selected shops
        foreach ($supplierProducts as $p) {
            $ref = $p['reference'] ?? '';
            $qty = (int)($p['quantity'] ?? 0);

            if ($ref === '') {
                $summary['skipped']++;
                continue;
            }

            $map = $this->mappingService->getMapping((int)$this->supplierConfig['id_supplier'], $ref);

            if (!$map) {
                // no mapping yet — skip (admin can activate mapping later)
                $summary['skipped']++;
                continue;
            }

            if ((int)$map['sync_enabled'] !== 1) {
                $summary['skipped']++;
                continue;
            }

            // if local_id_product is set, update its stock (and optionally product_attribute)
            $localProductId = (int)$map['local_id_product'];
            $localAttr = isset($map['local_id_product_attribute']) ? (int)$map['local_id_product_attribute'] : 0;

            if ($localProductId <= 0) {
                $summary['skipped']++;
                continue;
            }

            try {
                // update for primary shop (your shop)
                $this->localStockService->updateStockInShop($localProductId, $localAttr, $qty);

                // update for additional target shops if configured
                if (!empty($this->supplierConfig['target_shops'])) {
                    $targets = json_decode($this->supplierConfig['target_shops'], true);
                    if (is_array($targets)) {
                        foreach ($targets as $shopId) {
                            $this->localStockService->updateStockInShop($localProductId, $localAttr, $qty, (int)$shopId);
                        }
                    }
                }

                $summary['updated']++;
            } catch (Exception $e) {
                $summary['errors'][] = 'Error updating ' . $ref . ': ' . $e->getMessage();
            }
        }

        $summary['success'] = true;
        $summary['duration_ms'] = round((microtime(true) - $start) * 1000);

        return $summary;
    }
}
