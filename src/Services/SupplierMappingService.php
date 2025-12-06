<?php
namespace MpStockSync\Service;

use Db;

/**
 * SupplierMappingService
 * - manages mapping table: supplier_reference -> local product id
 */
class SupplierMappingService
{
    private $db;
    private $table;

    public function __construct()
    {
        $this->db = Db::getInstance();
        $this->table = _DB_PREFIX_ . 'mpstocksync_supplier_map';
    }

    public function install()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id_map` INT AUTO_INCREMENT PRIMARY KEY,
            `id_supplier` INT NOT NULL,
            `supplier_reference` VARCHAR(255) NOT NULL,
            `local_id_product` INT DEFAULT NULL,
            `local_id_product_attribute` INT DEFAULT NULL,
            `sync_enabled` TINYINT(1) DEFAULT 1,
            INDEX (`id_supplier`),
            INDEX (`supplier_reference`),
            INDEX (`local_id_product`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;
        ";
        return $this->db->execute($sql);
    }

    public function getMapping(int $supplierId, string $supplierRef)
    {
        $sql = "
            SELECT *
            FROM `{$this->table}`
            WHERE id_supplier = " . (int)$supplierId . "
              AND supplier_reference = '" . pSQL($supplierRef) . "'
            LIMIT 1
        ";
        return $this->db->getRow($sql);
    }

    public function autoGenerateMappings(int $supplierId, array $supplierProducts)
    {
        foreach ($supplierProducts as $p) {
            if (empty($p['reference'])) {
                continue;
            }
            $ref = pSQL($p['reference']);
            $exists = $this->db->getValue("
                SELECT COUNT(*) FROM `{$this->table}`
                WHERE id_supplier = " . (int)$supplierId . " AND supplier_reference = '{$ref}'
            ");
            if (!(int)$exists) {
                $this->db->insert('mpstocksync_supplier_map', [
                    'id_supplier' => (int)$supplierId,
                    'supplier_reference' => $ref,
                    'local_id_product' => null,
                    'local_id_product_attribute' => null,
                    'sync_enabled' => 0
                ]);
            }
        }
    }

    public function saveMapping(int $supplierId, string $supplierRef, ?int $localProductId, ?int $localProductAttr, int $syncEnabled = 1)
    {
        $existing = $this->getMapping($supplierId, $supplierRef);
        if ($existing) {
            return $this->db->update('mpstocksync_supplier_map', [
                'local_id_product' => (int)$localProductId,
                'local_id_product_attribute' => (int)$localProductAttr,
                'sync_enabled' => (int)$syncEnabled
            ], 'id_map = ' . (int)$existing['id_map']);
        } else {
            return $this->db->insert('mpstocksync_supplier_map', [
                'id_supplier' => (int)$supplierId,
                'supplier_reference' => pSQL($supplierRef),
                'local_id_product' => (int)$localProductId,
                'local_id_product_attribute' => (int)$localProductAttr,
                'sync_enabled' => (int)$syncEnabled
            ]);
        }
    }

    public function getMappingsBySupplier(int $supplierId): array
    {
        return $this->db->executeS("
            SELECT * FROM `{$this->table}` WHERE id_supplier = " . (int)$supplierId . " ORDER BY supplier_reference ASC
        ") ?: [];
    }

    public function setSyncEnabled(int $idMap, int $enabled)
    {
        return $this->db->update('mpstocksync_supplier_map', ['sync_enabled' => (int)$enabled], 'id_map = ' . (int)$idMap);
    }

    public function deleteMapping(int $idMap)
    {
        return $this->db->delete('mpstocksync_supplier_map', 'id_map = ' . (int)$idMap);
    }
}
