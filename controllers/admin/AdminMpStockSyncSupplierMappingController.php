<?php
// controllers/admin/AdminMpStockSyncSupplierMappingController.php
class AdminMpStockSyncSupplierMappingController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mpstocksync_supplier_mapping';
        $this->identifier = 'id_mapping';
        
        parent::__construct();
        
        $this->fields_list = [
            'id_mapping' => ['title' => 'ID', 'width' => 50],
            'supplier_reference' => ['title' => 'Supplier SKU', 'width' => 150],
            'local_reference' => ['title' => 'Local SKU', 'width' => 150],
            'local_product_name' => ['title' => 'Product Name', 'width' => 200],
            'last_sync' => ['title' => 'Last Sync', 'width' => 120, 'type' => 'datetime'],
            'active' => [
                'title' => 'Active',
                'width' => 80,
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool'
            ]
        ];
        
        $this->_select = 'p.reference as local_reference, pl.name as local_product_name';
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'product` p ON p.id_product = a.id_product
                       LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON pl.id_product = p.id_product
                           AND pl.id_lang = '.(int)$this->context->language->id;
    }
    
    public function initToolbar()
    {
        parent::initToolbar();
        
        $this->page_header_toolbar_btn['import_mapping'] = [
            'href' => self::$currentIndex . '&import_mapping&token=' . $this->token,
            'desc' => 'Import Mapping',
            'icon' => 'process-icon-import'
        ];
        
        $this->page_header_toolbar_btn['auto_match'] = [
            'href' => self::$currentIndex . '&auto_match&token=' . $this->token,
            'desc' => 'Auto Match Products',
            'icon' => 'process-icon-refresh'
        ];
    }
    
    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        
        // Add supplier filter
        $suppliers = $this->getSuppliers();
        $this->fields_list['id_supplier'] = [
            'title' => 'Supplier',
            'width' => 150,
            'type' => 'select',
            'list' => array_column($suppliers, 'name', 'id_supplier'),
            'filter_key' => 'a!id_supplier'
        ];
        
        return parent::renderList();
    }
    
    public function renderForm()
    {
        $suppliers = $this->getSuppliers();
        
        $this->fields_form = [
            'legend' => [
                'title' => 'Supplier Product Mapping',
                'icon' => 'icon-link'
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => 'Supplier',
                    'name' => 'id_supplier',
                    'required' => true,
                    'options' => [
                        'query' => $suppliers,
                        'id' => 'id_supplier',
                        'name' => 'name'
                    ]
                ],
                [
                    'type' => 'text',
                    'label' => 'Supplier Product Reference',
                    'name' => 'supplier_reference',
                    'required' => true,
                    'desc' => 'SKU from supplier'
                ],
                [
                    'type' => 'select',
                    'label' => 'Local Product',
                    'name' => 'id_product',
                    'required' => true,
                    'options' => [
                        'query' => $this->getLocalProducts(),
                        'id' => 'id_product',
                        'name' => 'name'
                    ]
                ],
                [
                    'type' => 'switch',
                    'label' => 'Sync Stock',
                    'name' => 'sync_stock',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1],
                        ['id' => 'active_off', 'value' => 0]
                    ]
                ],
                [
                    'type' => 'switch',
                    'label' => 'Sync Price',
                    'name' => 'sync_price',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1],
                        ['id' => 'active_off', 'value' => 0]
                    ]
                ],
                [
                    'type' => 'switch',
                    'label' => 'Active',
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1],
                        ['id' => 'active_off', 'value' => 0]
                    ]
                ]
            ],
            'submit' => [
                'title' => 'Save',
                'class' => 'btn btn-default pull-right'
            ]
        ];
        
        return parent::renderForm();
    }
    
    private function getSuppliers()
    {
        return Db::getInstance()->executeS('
            SELECT id_supplier, name 
            FROM `'._DB_PREFIX_.'mpstocksync_suppliers`
            WHERE active = 1
            ORDER BY name ASC
        ');
    }
    
    private function getLocalProducts()
    {
        $sql = 'SELECT p.id_product, pl.name, p.reference, p.ean13
                FROM `'._DB_PREFIX_.'product` p
                INNER JOIN `'._DB_PREFIX_.'product_lang` pl ON pl.id_product = p.id_product
                    AND pl.id_lang = '.(int)$this->context->language->id.'
                WHERE p.active = 1
                ORDER BY pl.name ASC';
        
        $products = Db::getInstance()->executeS($sql);
        
        $list = [];
        foreach ($products as $product) {
            $name = $product['name'] . ' (SKU: ' . $product['reference'] . 
                   ($product['ean13'] ? ', EAN: ' . $product['ean13'] : '') . ')';
            $list[] = [
                'id_product' => $product['id_product'],
                'name' => $name
            ];
        }
        
        return $list;
    }
    
    public function processAutoMatch()
    {
        $suppliers = $this->getSuppliers();
        
        $matched = 0;
        $failed = 0;
        
        foreach ($suppliers as $supplier) {
            $service = new SupplierSyncService($supplier['id_supplier']);
            $supplierProducts = $service->getProductsFromSupplier();
            
            foreach ($supplierProducts as $supplierProduct) {
                // Try to auto-match
                $localProductId = $this->findLocalProductForSupplier(
                    $supplierProduct,
                    $supplier['id_supplier']
                );
                
                if ($localProductId) {
                    // Save mapping
                    $this->saveMapping(
                        $supplier['id_supplier'],
                        $supplierProduct['supplier_reference'],
                        $localProductId
                    );
                    $matched++;
                } else {
                    $failed++;
                }
            }
        }
        
        $this->confirmations[] = sprintf(
            'Auto-match completed: %d matched, %d failed',
            $matched,
            $failed
        );
    }
}
