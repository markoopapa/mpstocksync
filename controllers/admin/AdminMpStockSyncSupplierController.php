<?php
class AdminMpStockSyncSuppliersController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mpstocksync_suppliers';
        $this->identifier = 'id_supplier';
        $this->className = 'MpStockSyncSupplier';
        $this->lang = false;
        
        parent::__construct();
        
        $this->fields_list = [
            'id_supplier' => [
                'title' => 'ID',
                'width' => 50,
                'align' => 'center'
            ],
            'name' => [
                'title' => 'Supplier Name',
                'width' => 150
            ],
            'connection_type' => [
                'title' => 'Type',
                'width' => 100,
                'callback' => 'renderConnectionType'
            ],
            'target_shops_display' => [
                'title' => 'Target Shops',
                'width' => 150,
                'callback' => 'renderTargetShops'
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
                'confirm' => 'Delete selected suppliers?'
            ]
        ];
    }
    
    public function initContent()
    {
        parent::initContent();
        
        // Handle form submissions
        if (Tools::isSubmit('save_supplier')) {
            $this->processSaveSupplier();
        }
        
        if (Tools::isSubmit('delete_supplier')) {
            $this->processDeleteSupplier();
        }
        
        if (Tools::isSubmit('sync_supplier')) {
            $this->processSyncSupplier();
        }
        
        // Get suppliers for template
        $suppliers = $this->getSuppliers();
        
        $this->context->smarty->assign([
            'suppliers' => $suppliers,
            'shops' => Shop::getShops(true, null, true),
            'form_action' => self::$currentIndex . '&token=' . $this->token,
            'module_url' => $this->context->link->getAdminLink('AdminMpStockSync')
        ]);
        
        $this->setTemplate('suppliers.tpl');
    }
    
    public function renderConnectionType($value, $row)
    {
        if ($value == 'database') {
            return '<span class="label label-info">Database</span>';
        } elseif ($value == 'api') {
            return '<span class="label label-primary">API</span>';
        }
        return $value;
    }
    
    public function renderTargetShops($value, $row)
    {
        if (empty($row['target_shops'])) {
            return '<span class="text-muted">No shops</span>';
        }
        
        $shop_ids = json_decode($row['target_shops'], true);
        if (!is_array($shop_ids) || empty($shop_ids)) {
            return '<span class="text-muted">No shops</span>';
        }
        
        $shop_names = [];
        foreach ($shop_ids as $shop_id) {
            $shop = new Shop($shop_id);
            if (Validate::isLoadedObject($shop)) {
                $shop_names[] = $shop->name;
            }
        }
        
        if (count($shop_names) > 2) {
            return $shop_names[0] . ', ' . $shop_names[1] . ' +' . (count($shop_names) - 2) . ' more';
        }
        
        return implode(', ', $shop_names);
    }
    
    private function getSuppliers()
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'mpstocksync_suppliers` 
                ORDER BY active DESC, name ASC';
        
        $suppliers = Db::getInstance()->executeS($sql);
        
        // Add shop names for display
        foreach ($suppliers as &$supplier) {
            $supplier['target_shops_display'] = $this->renderTargetShops('', $supplier);
        }
        
        return $suppliers;
    }
    
    private function processSaveSupplier()
    {
        $id_supplier = (int)Tools::getValue('id_supplier');
        $name = Tools::getValue('name');
        $connection_type = Tools::getValue('connection_type');
        
        $data = [
            'name' => pSQL($name),
            'connection_type' => pSQL($connection_type),
            'db_host' => Tools::getValue('db_host'),
            'db_name' => Tools::getValue('db_name'),
            'db_user' => Tools::getValue('db_user'),
            'db_password' => Tools::getValue('db_password'),
            'db_prefix' => Tools::getValue('db_prefix', 'ps_'),
            'api_url' => Tools::getValue('api_url'),
            'api_key' => Tools::getValue('api_key'),
            'target_shops' => json_encode(Tools::getValue('target_shops', [])),
            'auto_sync' => (int)Tools::getValue('auto_sync'),
            'sync_interval' => (int)Tools::getValue('sync_interval', 15),
            'active' => (int)Tools::getValue('active'),
            'date_upd' => date('Y-m-d H:i:s')
        ];
        
        if ($id_supplier > 0) {
            // Update existing
            $result = Db::getInstance()->update('mpstocksync_suppliers', $data, 'id_supplier = ' . $id_supplier);
            $message = 'Supplier updated successfully';
        } else {
            // Insert new
            $data['date_add'] = date('Y-m-d H:i:s');
            $result = Db::getInstance()->insert('mpstocksync_suppliers', $data);
            $message = 'Supplier added successfully';
        }
        
        if ($result) {
            $this->confirmations[] = $message;
        } else {
            $this->errors[] = 'Error saving supplier: ' . Db::getInstance()->getMsgError();
        }
    }
    
    private function processDeleteSupplier()
    {
        $id_supplier = (int)Tools::getValue('delete_supplier');
        
        if ($id_supplier > 0) {
            $result = Db::getInstance()->delete('mpstocksync_suppliers', 'id_supplier = ' . $id_supplier);
            
            if ($result) {
                $this->confirmations[] = 'Supplier deleted successfully';
            } else {
                $this->errors[] = 'Error deleting supplier';
            }
        }
    }
    
    private function processSyncSupplier()
    {
        $id_supplier = (int)Tools::getValue('sync_supplier');
        
        if ($id_supplier > 0) {
            $module = Module::getInstanceByName('mpstocksync');
            if ($module && method_exists($module, 'syncSupplier')) {
                $result = $module->syncSupplier($id_supplier);
                
                if ($result['success']) {
                    $this->confirmations[] = 'Supplier sync completed: ' . 
                        $result['updated'] . '/' . $result['total'] . ' products updated';
                } else {
                    $this->errors[] = 'Supplier sync failed: ' . $result['message'];
                }
            } else {
                $this->errors[] = 'Module not found or sync method not available';
            }
        }
    }
    
    public function initToolbar()
    {
        parent::initToolbar();
        
        // Remove default new button
        unset($this->toolbar_btn['new']);
        
        // Add custom buttons
        $this->page_header_toolbar_btn['add_supplier'] = [
            'href' => self::$currentIndex . '&addsupplier&token=' . $this->token,
            'desc' => 'Add Supplier',
            'icon' => 'process-icon-new',
            'class' => 'btn-primary'
        ];
        
        $this->page_header_toolbar_btn['test_all'] = [
            'href' => self::$currentIndex . '&testall&token=' . $this->token,
            'desc' => 'Test All Connections',
            'icon' => 'process-icon-refresh'
        ];
        
        $this->page_header_toolbar_btn['sync_all'] = [
            'href' => self::$currentIndex . '&syncall&token=' . $this->token,
            'desc' => 'Sync All Suppliers',
            'icon' => 'process-icon-cogs'
        ];
    }
    
    public function renderForm()
    {
        $id_supplier = (int)Tools::getValue('id_supplier');
        $supplier = null;
        
        if ($id_supplier > 0) {
            $sql = 'SELECT * FROM `'._DB_PREFIX_.'mpstocksync_suppliers` 
                    WHERE id_supplier = ' . $id_supplier;
            $supplier = Db::getInstance()->getRow($sql);
        }
        
        $this->fields_form = [
            'legend' => [
                'title' => $id_supplier ? 'Edit Supplier' : 'Add New Supplier',
                'icon' => 'icon-truck'
            ],
            'input' => [
                [
                    'type' => 'hidden',
                    'name' => 'id_supplier'
                ],
                [
                    'type' => 'text',
                    'label' => 'Supplier Name',
                    'name' => 'name',
                    'required' => true,
                    'col' => 6
                ],
                [
                    'type' => 'select',
                    'label' => 'Connection Type',
                    'name' => 'connection_type',
                    'required' => true,
                    'options' => [
                        'query' => [
                            ['id' => 'database', 'name' => 'Database Connection'],
                            ['id' => 'api', 'name' => 'API Connection']
                        ],
                        'id' => 'id',
                        'name' => 'name'
                    ],
                    'col' => 6
                ],
                // Database fields
                [
                    'type' => 'text',
                    'label' => 'Database Host',
                    'name' => 'db_host',
                    'col' => 4,
                    'form_group_class' => 'database-field' . ($supplier && $supplier['connection_type'] == 'database' ? '' : ' hidden')
                ],
                [
                    'type' => 'text',
                    'label' => 'Database Name',
                    'name' => 'db_name',
                    'col' => 4,
                    'form_group_class' => 'database-field' . ($supplier && $supplier['connection_type'] == 'database' ? '' : ' hidden')
                ],
                [
                    'type' => 'text',
                    'label' => 'Database User',
                    'name' => 'db_user',
                    'col' => 4,
                    'form_group_class' => 'database-field' . ($supplier && $supplier['connection_type'] == 'database' ? '' : ' hidden')
                ],
                [
                    'type' => 'password',
                    'label' => 'Database Password',
                    'name' => 'db_password',
                    'col' => 4,
                    'form_group_class' => 'database-field' . ($supplier && $supplier['connection_type'] == 'database' ? '' : ' hidden')
                ],
                [
                    'type' => 'text',
                    'label' => 'Table Prefix',
                    'name' => 'db_prefix',
                    'col' => 4,
                    'value' => 'ps_',
                    'form_group_class' => 'database-field' . ($supplier && $supplier['connection_type'] == 'database' ? '' : ' hidden')
                ],
                // API fields
                [
                    'type' => 'text',
                    'label' => 'API URL',
                    'name' => 'api_url',
                    'col' => 6,
                    'form_group_class' => 'api-field' . ($supplier && $supplier['connection_type'] == 'api' ? '' : ' hidden')
                ],
                [
                    'type' => 'text',
                    'label' => 'API Key',
                    'name' => 'api_key',
                    'col' => 6,
                    'form_group_class' => 'api-field' . ($supplier && $supplier['connection_type'] == 'api' ? '' : ' hidden')
                ],
                // Sync settings
                [
                    'type' => 'select',
                    'label' => 'Target Shops',
                    'name' => 'target_shops[]',
                    'multiple' => true,
                    'options' => [
                        'query' => Shop::getShops(true, null, true),
                        'id' => 'id_shop',
                        'name' => 'name'
                    ],
                    'col' => 6,
                    'hint' => 'Select shops to sync products to'
                ],
                [
                    'type' => 'switch',
                    'label' => 'Auto Sync',
                    'name' => 'auto_sync',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1],
                        ['id' => 'active_off', 'value' => 0]
                    ],
                    'col' => 6
                ],
                [
                    'type' => 'text',
                    'label' => 'Sync Interval (minutes)',
                    'name' => 'sync_interval',
                    'col' => 3,
                    'suffix' => 'minutes',
                    'value' => 15
                ],
                [
                    'type' => 'switch',
                    'label' => 'Active',
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1],
                        ['id' => 'active_off', 'value' => 0]
                    ],
                    'col' => 3
                ]
            ],
            'submit' => [
                'title' => 'Save',
                'class' => 'btn btn-default pull-right'
            ],
            'buttons' => [
                'testButton' => [
                    'title' => 'Test Connection',
                    'name' => 'test_connection',
                    'type' => 'button',
                    'class' => 'btn btn-info',
                    'icon' => 'process-icon-refresh'
                ]
            ]
        ];
        
        if ($supplier) {
            // Decode JSON fields
            if ($supplier['target_shops']) {
                $supplier['target_shops'] = json_decode($supplier['target_shops'], true);
            }
            
            $this->fields_value = $supplier;
        }
        
        return parent::renderForm();
    }
}
