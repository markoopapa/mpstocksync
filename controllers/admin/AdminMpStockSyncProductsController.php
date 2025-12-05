<?php
class AdminMpStockSyncProductsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mpstocksync_mapping';
        $this->identifier = 'id_mapping';
        $this->className = 'MpStockSyncMapping';
        $this->lang = false;
        
        parent::__construct();
        
        $this->fields_list = [
            'id_mapping' => [
                'title' => 'ID',
                'width' => 50,
                'align' => 'center'
            ],
            'api_name' => [
                'title' => 'Marketplace',
                'width' => 100,
                'callback' => 'renderMarketplace'
            ],
            'external_id' => [
                'title' => 'External ID',
                'width' => 150
            ],
            'product_name' => [
                'title' => 'Product',
                'width' => 200,
                'callback' => 'renderProductName'
            ],
            'last_sync' => [
                'title' => 'Last Sync',
                'width' => 120,
                'type' => 'datetime'
            ],
            'active' => [
                'title' => 'Status',
                'width' => 80,
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool'
            ]
        ];
        
        $this->bulk_actions = [
            'enable' => [
                'text' => 'Enable',
                'icon' => 'icon-check'
            ],
            'disable' => [
                'text' => 'Disable',
                'icon' => 'icon-remove'
            ],
            'delete' => [
                'text' => 'Delete',
                'icon' => 'icon-trash',
                'confirm' => 'Delete selected mappings?'
            ]
        ];
    }
    
    public function initContent()
    {
        parent::initContent();
        
        // Handle AJAX requests
        if (Tools::isSubmit('ajax') && Tools::getValue('action') == 'search_products') {
            $this->ajaxProcessSearchProducts();
        }
        
        if (Tools::isSubmit('save_mapping')) {
            $this->ajaxProcessSaveMapping();
        }
        
        // Get existing mappings for template
        $mappings = $this->getMappings();
        
        $this->context->smarty->assign([
            'mappings' => $mappings,
            'token' => $this->token
        ]);
        
        $this->setTemplate('products.tpl');
    }
    
    public function renderMarketplace($value, $row)
    {
        if ($value == 'emag') {
            return '<span class="label label-primary">eMAG</span>';
        } elseif ($value == 'trendyol') {
            return '<span class="label" style="background:#ff6b00">Trendyol</span>';
        }
        return $value;
    }
    
    public function renderProductName($value, $row)
    {
        // Try to get product name
        $product = new Product($row['id_product'], false, $this->context->language->id);
        if (Validate::isLoadedObject($product)) {
            return $product->name;
        }
        return 'Product ID: ' . $row['id_product'];
    }
    
    private function getMappings()
    {
        $sql = 'SELECT m.*, p.reference, pl.name as product_name
                FROM `'._DB_PREFIX_.'mpstocksync_mapping` m
                LEFT JOIN `'._DB_PREFIX_.'product` p ON p.id_product = m.id_product
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON pl.id_product = p.id_product
                    AND pl.id_lang = '.(int)$this->context->language->id.'
                ORDER BY m.date_upd DESC
                LIMIT 50';
        
        return Db::getInstance()->executeS($sql);
    }
    
    private function ajaxProcessSearchProducts()
    {
        $query = Tools::getValue('query');
        
        $sql = 'SELECT p.id_product, pl.name, p.reference, p.ean13
                FROM `'._DB_PREFIX_.'product` p
                INNER JOIN `'._DB_PREFIX_.'product_lang` pl ON pl.id_product = p.id_product
                    AND pl.id_lang = '.(int)$this->context->language->id.'
                WHERE p.active = 1
                AND (pl.name LIKE "%'.pSQL($query).'%" 
                     OR p.reference LIKE "%'.pSQL($query).'%"
                     OR p.ean13 LIKE "%'.pSQL($query).'%")
                ORDER BY pl.name ASC
                LIMIT 20';
        
        $products = Db::getInstance()->executeS($sql);
        
        die(json_encode($products));
    }
    
    private function ajaxProcessSaveMapping()
    {
        $id_mapping = (int)Tools::getValue('id_mapping');
        $id_product = (int)Tools::getValue('id_product');
        $api_name = Tools::getValue('api_name');
        $external_id = Tools::getValue('external_id');
        $active = (int)Tools::getValue('active');
        
        $data = [
            'id_product' => $id_product,
            'id_product_attribute' => 0,
            'api_name' => pSQL($api_name),
            'external_id' => pSQL($external_id),
            'active' => $active,
            'date_upd' => date('Y-m-d H:i:s')
        ];
        
        if ($id_mapping > 0) {
            // Update existing
            $result = Db::getInstance()->update('mpstocksync_mapping', $data, 'id_mapping = ' . $id_mapping);
        } else {
            // Insert new
            $data['date_add'] = date('Y-m-d H:i:s');
            $result = Db::getInstance()->insert('mpstocksync_mapping', $data);
        }
        
        die(json_encode([
            'success' => $result,
            'message' => $result ? 'Mapping saved successfully' : 'Error saving mapping'
        ]));
    }
    
    public function initToolbar()
    {
        parent::initToolbar();
        
        // Remove default new button
        unset($this->toolbar_btn['new']);
        
        // Add custom buttons
        $this->page_header_toolbar_btn['add_mapping'] = [
            'href' => '#',
            'desc' => 'Add Mapping',
            'icon' => 'process-icon-new',
            'class' => 'btn-primary'
        ];
        
        $this->page_header_toolbar_btn['import_csv'] = [
            'href' => self::$currentIndex . '&importcsv&token=' . $this->token,
            'desc' => 'Import CSV',
            'icon' => 'process-icon-upload'
        ];
        
        $this->page_header_toolbar_btn['export_csv'] = [
            'href' => self::$currentIndex . '&exportcsv&token=' . $this->token,
            'desc' => 'Export CSV',
            'icon' => 'process-icon-download'
        ];
    }
}
