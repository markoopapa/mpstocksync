<?php
class SupplierSyncService
{
    private $supplierConfig;
    
    public function __construct($supplierId)
    {
        $this->loadSupplierConfig($supplierId);
    }
    
    private function loadSupplierConfig($supplierId)
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'mpstocksync_suppliers`
                WHERE id_supplier = '.(int)$supplierId;
        
        $this->supplierConfig = Db::getInstance()->getRow($sql);
    }
    
    public function syncSupplierToShops($supplierId)
    {
        // Mock implementation
        return [
            'success' => true,
            'total' => 10,
            'updated' => 8,
            'errors' => 2,
            'message' => 'Supplier sync completed (MOCK)'
        ];
    }
    
    public function testConnection()
    {
        return [
            'success' => true,
            'message' => 'Supplier connection test successful (MOCK)'
        ];
    }
}
