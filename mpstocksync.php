<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

// Manually load required classes (composer autoload not available yet)
require_once __DIR__ . '/src/Services/EmagService.php';
require_once __DIR__ . '/src/Services/TrendyolService.php';
require_once __DIR__ . '/src/Services/SupplierSyncService.php';
require_once __DIR__ . '/src/Models/ProductMapping.php';

class MpStockSync extends Module
{
    private $emagService = null;
    private $trendyolService = null;
    
    public function __construct()
    {
        $this->name = 'mpstocksync';
        $this->tab = 'administration';
        $this->version = '2.0.0';
        $this->author = 'MP Team';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '9.9.9'
        ];
        $this->bootstrap = true;
        $this->module_key = 'mpstocksync2024';
        
        parent::__construct();
        
        $this->displayName = $this->l('MP Stock Sync Pro');
        $this->description = $this->l('Professional stock synchronization for eMAG, Trendyol and suppliers');
        $this->confirmUninstall = $this->l('Are you sure? All data will be lost!');
    }
    
    public function install()
    {
        if (!parent::install()) {
            return false;
        }
        
        // Install database tables
        if (!$this->installDatabase()) {
            $this->_errors[] = 'Database installation failed: ' . Db::getInstance()->getMsgError();
            return false;
        }
        
        // Install admin tabs - JAVÍTVA
        if (!$this->installTabs()) {
            $this->_errors[] = 'Tab installation failed';
            return false;
        }
        
        // Register hooks
        $hooks = [
            'actionUpdateQuantity',
            'actionProductSave',
            'actionProductUpdate',
            'actionAdminControllerSetMedia',
            'actionObjectStockAddAfter',
            'actionObjectStockUpdateAfter',
            'actionObjectStockDeleteAfter'
        ];
        
        foreach ($hooks as $hook) {
            if (!$this->registerHook($hook)) {
                $this->_errors[] = "Failed to register hook: $hook";
                return false;
            }
        }
        
        // Set default configurations
        $defaults = [
            'MP_LOG_ENABLED' => 1,
            'MP_NOTIFY_ERRORS' => 1,
            'MP_AUTO_RETRY' => 1,
            'MP_RETRY_ATTEMPTS' => 3,
            'MP_RETRY_DELAY' => 60,
            'MP_EMAG_AUTO_SYNC' => 0,
            'MP_TRENDYOL_AUTO_SYNC' => 0,
            'MP_SUPPLIER_AUTO_SYNC' => 0
        ];
        
        foreach ($defaults as $key => $value) {
            Configuration::updateValue($key, $value);
        }
        
        return true;
    }
    
    public function uninstall()
    {
        // Delete configurations
        $configs = [
            'MP_LOG_ENABLED', 'MP_NOTIFY_ERRORS', 'MP_AUTO_RETRY',
            'MP_RETRY_ATTEMPTS', 'MP_RETRY_DELAY',
            'MP_EMAG_API_URL', 'MP_EMAG_CLIENT_ID', 'MP_EMAG_CLIENT_SECRET',
            'MP_EMAG_USERNAME', 'MP_EMAG_PASSWORD', 'MP_EMAG_AUTO_SYNC',
            'MP_TRENDYOL_API_URL', 'MP_TRENDYOL_API_KEY', 'MP_TRENDYOL_API_SECRET',
            'MP_TRENDYOL_SUPPLIER_ID', 'MP_TRENDYOL_AUTO_SYNC'
        ];
        
        foreach ($configs as $config) {
            Configuration::deleteByName($config);
        }
        
        // Uninstall tabs
        $this->uninstallTabs();
        
        // Drop database tables (opcionális)
        // $this->uninstallDatabase();
        
        return parent::uninstall();
    }
    
    private function installDatabase()
    {
        $sql = [];
        
        // Main sync log table
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mpstocksync_log` (
            `id_log` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `api_name` VARCHAR(20) NOT NULL,
            `id_product` INT(11) NOT NULL,
            `id_product_attribute` INT(11) DEFAULT 0,
            `action` VARCHAR(50) NOT NULL,
            `old_value` TEXT,
            `new_value` TEXT,
            `status` TINYINT(1) DEFAULT 0,
            `error_message` TEXT,
            `response_data` TEXT,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_log`),
            INDEX `api_product` (`api_name`, `id_product`),
            INDEX `date_status` (`date_add`, `status`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
        
        // Product mapping table
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mpstocksync_mapping` (
            `id_mapping` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_product` INT(11) NOT NULL,
            `id_product_attribute` INT(11) DEFAULT 0,
            `api_name` VARCHAR(20) NOT NULL,
            `external_id` VARCHAR(100) NOT NULL,
            `external_reference` VARCHAR(100),
            `sync_stock` TINYINT(1) DEFAULT 1,
            `sync_price` TINYINT(1) DEFAULT 1,
            `last_sync` DATETIME,
            `sync_count` INT(11) DEFAULT 0,
            `active` TINYINT(1) DEFAULT 1,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_mapping`),
            UNIQUE KEY `unique_mapping` (`id_product`, `id_product_attribute`, `api_name`),
            INDEX `external_lookup` (`api_name`, `external_id`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
        
        // Suppliers table
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mpstocksync_suppliers` (
            `id_supplier` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `connection_type` VARCHAR(50) NOT NULL,
            `db_host` VARCHAR(255),
            `db_name` VARCHAR(100),
            `db_user` VARCHAR(100),
            `db_password` VARCHAR(255),
            `db_prefix` VARCHAR(50) DEFAULT "ps_",
            `api_url` VARCHAR(255),
            `api_key` VARCHAR(255),
            `target_shops` TEXT,
            `auto_sync` TINYINT(1) DEFAULT 0,
            `sync_interval` INT(11) DEFAULT 15,
            `last_sync` DATETIME,
            `active` TINYINT(1) DEFAULT 1,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_supplier`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
        
        // Supplier product mapping
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mpstocksync_supplier_mapping` (
            `id_mapping` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_supplier` INT(11) UNSIGNED NOT NULL,
            `supplier_reference` VARCHAR(100) NOT NULL,
            `id_product` INT(11) NOT NULL,
            `id_product_attribute` INT(11) DEFAULT 0,
            `sync_stock` TINYINT(1) DEFAULT 1,
            `sync_price` TINYINT(1) DEFAULT 0,
            `price_multiplier` DECIMAL(10,4) DEFAULT 1.0000,
            `stock_buffer` INT(11) DEFAULT 0,
            `last_sync` DATETIME,
            `sync_count` INT(11) DEFAULT 0,
            `active` TINYINT(1) DEFAULT 1,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_mapping`),
            UNIQUE KEY `unique_mapping` (`id_supplier`, `supplier_reference`),
            INDEX `supplier_product` (`id_supplier`, `id_product`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
        
        // Supplier sync log
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mpstocksync_supplier_log` (
            `id_log` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_supplier` INT(11) UNSIGNED NOT NULL,
            `id_product` INT(11) NOT NULL,
            `id_shop` INT(11) NOT NULL,
            `supplier_reference` VARCHAR(100),
            `old_quantity` INT(11),
            `new_quantity` INT(11),
            `status` TINYINT(1) DEFAULT 0,
            `error_message` TEXT,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_log`),
            INDEX `supplier_date` (`id_supplier`, `date_add`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
        
        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                $this->_errors[] = 'Database error: ' . Db::getInstance()->getMsgError();
                return false;
            }
        }
        
        return true;
    }
    
    private function installTabs()
    {
        // JAVÍTVA: "Beállítások" menü alá rakjuk
        $tabs = [
            [
                'class_name' => 'AdminMpStockSync',
                'name' => 'Stock Sync',
                'parent' => 'AdminParentPreferences',  // Beállítások menü
                'icon' => 'sync'
            ],
            [
                'class_name' => 'AdminMpStockSyncDashboard',
                'name' => 'Dashboard',
                'parent' => 'AdminMpStockSync'
            ],
            [
                'class_name' => 'AdminMpStockSyncProducts',
                'name' => 'Product Mapping',
                'parent' => 'AdminMpStockSync'
            ],
            [
                'class_name' => 'AdminMpStockSyncSuppliers',
                'name' => 'Suppliers',
                'parent' => 'AdminMpStockSync'
            ],
            [
                'class_name' => 'AdminMpStockSyncLogs',
                'name' => 'Sync Logs',
                'parent' => 'AdminMpStockSync'
            ],
            [
                'class_name' => 'AdminMpStockSyncSettings',
                'name' => 'Settings',
                'parent' => 'AdminMpStockSync'
            ],
            [
                'class_name' => 'AdminMpStockSyncApi',
                'name' => 'API Settings',
                'parent' => 'AdminMpStockSync'
            ]
        ];
        
        foreach ($tabs as $tabData) {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = $tabData['class_name'];
            $tab->module = $this->name;
            
            // JAVÍTVA: Megfelelő parent keresés
            if (isset($tabData['parent'])) {
                $parentId = Tab::getIdFromClassName($tabData['parent']);
                if ($parentId) {
                    $tab->id_parent = $parentId;
                } else {
                    // Ha nem találja a parent tab-ot, rakja a főmenübe
                    $tab->id_parent = 0;
                }
            } else {
                $tab->id_parent = 0;
            }
            
            // JAVÍTVA: Nyelvi nevek
            foreach (Language::getLanguages() as $lang) {
                $tab->name[$lang['id_lang']] = $this->l($tabData['name']);
            }
            
            if (!$tab->add()) {
                $this->_errors[] = 'Failed to create tab: ' . $tabData['name'];
                // JAVÍTVA: Ha nem sikerül, töröljük az eddigieket
                $this->uninstallTabs();
                return false;
            }
        }
        
        return true;
    }
    
    private function uninstallTabs()
    {
        $classes = [
            'AdminMpStockSync',
            'AdminMpStockSyncDashboard',
            'AdminMpStockSyncProducts',
            'AdminMpStockSyncSuppliers',
            'AdminMpStockSyncLogs',
            'AdminMpStockSyncSettings',
            'AdminMpStockSyncApi'
        ];
        
        foreach ($classes as $className) {
            $id_tab = Tab::getIdFromClassName($className);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }
        
        return true;
    }
    
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMpStockSyncDashboard'));
    }
    
    public function hookActionUpdateQuantity($params)
    {
        if (!isset($params['id_product'])) {
            return;
        }
        
        $id_product = (int)$params['id_product'];
        $id_product_attribute = isset($params['id_product_attribute']) ? 
            (int)$params['id_product_attribute'] : 0;
        $new_quantity = (int)$params['quantity'];
        
        // Get old quantity
        $old_quantity = StockAvailable::getQuantityAvailableByProduct(
            $id_product,
            $id_product_attribute
        );
        
        // Log the change
        $this->logChange('quantity_update', $id_product, $id_product_attribute, [
            'old' => $old_quantity,
            'new' => $new_quantity
        ]);
        
        // Auto-sync to marketplaces if enabled
        if (Configuration::get('MP_EMAG_AUTO_SYNC')) {
            $this->syncToEmag($id_product, $id_product_attribute, $new_quantity);
        }
        
        if (Configuration::get('MP_TRENDYOL_AUTO_SYNC')) {
            $this->syncToTrendyol($id_product, $id_product_attribute, $new_quantity);
        }
    }
    
    private function logChange($action, $id_product, $id_product_attribute, $data)
    {
        if (!Configuration::get('MP_LOG_ENABLED')) {
            return;
        }
        
        Db::getInstance()->insert('mpstocksync_log', [
            'api_name' => 'internal',
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'action' => pSQL($action),
            'old_value' => pSQL(json_encode($data['old'])),
            'new_value' => pSQL(json_encode($data['new'])),
            'status' => 1,
            'date_add' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function syncToEmag($id_product, $id_product_attribute, $quantity)
    {
        try {
            // Get mapping
            $mapping = $this->getProductMapping($id_product, $id_product_attribute, 'emag');
            
            if (!$mapping || !$mapping['active']) {
                return false;
            }
            
            // Initialize service
            if ($this->emagService === null) {
                $this->emagService = new EmagService(
                    Configuration::get('MP_EMAG_API_URL'),
                    Configuration::get('MP_EMAG_CLIENT_ID'),
                    Configuration::get('MP_EMAG_CLIENT_SECRET'),
                    Configuration::get('MP_EMAG_USERNAME'),
                    Configuration::get('MP_EMAG_PASSWORD')
                );
            }
            
            // Update stock
            $result = $this->emagService->updateStock($mapping['external_id'], $quantity);
            
            // Log result
            Db::getInstance()->insert('mpstocksync_log', [
                'api_name' => 'emag',
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
                'action' => 'stock_update',
                'status' => $result['success'] ? 1 : 0,
                'error_message' => $result['success'] ? null : json_encode($result),
                'response_data' => pSQL(json_encode($result)),
                'date_add' => date('Y-m-d H:i:s')
            ]);
            
            return $result['success'];
            
        } catch (Exception $e) {
            Db::getInstance()->insert('mpstocksync_log', [
                'api_name' => 'emag',
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
                'action' => 'error',
                'status' => 0,
                'error_message' => pSQL($e->getMessage()),
                'date_add' => date('Y-m-d H:i:s')
            ]);
            
            return false;
        }
    }
    
    private function syncToTrendyol($id_product, $id_product_attribute, $quantity)
    {
        try {
            // Get mapping
            $mapping = $this->getProductMapping($id_product, $id_product_attribute, 'trendyol');
            
            if (!$mapping || !$mapping['active']) {
                return false;
            }
            
            // Get product price
            $price = Product::getPriceStatic($id_product, true, $id_product_attribute);
            
            // Initialize service
            if ($this->trendyolService === null) {
                $this->trendyolService = new TrendyolService(
                    Configuration::get('MP_TRENDYOL_API_URL'),
                    Configuration::get('MP_TRENDYOL_API_KEY'),
                    Configuration::get('MP_TRENDYOL_API_SECRET'),
                    Configuration::get('MP_TRENDYOL_SUPPLIER_ID')
                );
            }
            
            // Update stock and price
            $result = $this->trendyolService->updateStockAndPrice(
                $mapping['external_id'],
                $quantity,
                $price
            );
            
            // Log result
            Db::getInstance()->insert('mpstocksync_log', [
                'api_name' => 'trendyol',
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
                'action' => 'stock_price_update',
                'status' => $result['success'] ? 1 : 0,
                'error_message' => $result['success'] ? null : json_encode($result),
                'response_data' => pSQL(json_encode($result)),
                'date_add' => date('Y-m-d H:i:s')
            ]);
            
            return $result['success'];
            
        } catch (Exception $e) {
            Db::getInstance()->insert('mpstocksync_log', [
                'api_name' => 'trendyol',
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
                'action' => 'error',
                'status' => 0,
                'error_message' => pSQL($e->getMessage()),
                'date_add' => date('Y-m-d H:i:s')
            ]);
            
            return false;
        }
    }
    
    private function getProductMapping($id_product, $id_product_attribute, $api_name)
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'mpstocksync_mapping`
                WHERE id_product = '.(int)$id_product.'
                AND id_product_attribute = '.(int)$id_product_attribute.'
                AND api_name = "'.pSQL($api_name).'"
                AND active = 1';
        
        return Db::getInstance()->getRow($sql);
    }
    
    public function manualSyncAll($api_name = null)
    {
        $products = Product::getProducts(
            Context::getContext()->language->id,
            0,
            0,
            'id_product',
            'ASC',
            false,
            true
        );
        
        $results = [
            'total' => 0,
            'success' => 0,
            'errors' => 0
        ];
        
        foreach ($products as $product) {
            $id_product = (int)$product['id_product'];
            $quantity = StockAvailable::getQuantityAvailableByProduct($id_product);
            
            if ($api_name === 'emag' || $api_name === null) {
                if (Configuration::get('MP_EMAG_AUTO_SYNC')) {
                    $results['total']++;
                    if ($this->syncToEmag($id_product, 0, $quantity)) {
                        $results['success']++;
                    } else {
                        $results['errors']++;
                    }
                }
            }
            
            if ($api_name === 'trendyol' || $api_name === null) {
                if (Configuration::get('MP_TRENDYOL_AUTO_SYNC')) {
                    $results['total']++;
                    if ($this->syncToTrendyol($id_product, 0, $quantity)) {
                        $results['success']++;
                    } else {
                        $results['errors']++;
                    }
                }
            }
        }
        
        return $results;
    }
    
    public function syncSupplier($supplier_id)
    {
        try {
            $service = new SupplierSyncService($supplier_id);
            return $service->syncSupplierToShops($supplier_id);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getSyncStatistics()
    {
        $stats = [];
        
        // Marketplace stats
        $sql = 'SELECT api_name, COUNT(*) as total, 
                SUM(IF(status=1,1,0)) as success,
                SUM(IF(status=0,1,0)) as failed
                FROM `'._DB_PREFIX_.'mpstocksync_log`
                WHERE api_name IN ("emag", "trendyol")
                GROUP BY api_name';
        
        $result = Db::getInstance()->executeS($sql);
        
        foreach ($result as $row) {
            $stats[$row['api_name']] = [
                'total' => (int)$row['total'],
                'success' => (int)$row['success'],
                'failed' => (int)$row['failed']
            ];
        }
        
        // Supplier stats
        $sql = 'SELECT s.name, COUNT(l.id_log) as sync_count,
                SUM(IF(l.status=1,1,0)) as success_count
                FROM `'._DB_PREFIX_.'mpstocksync_suppliers` s
                LEFT JOIN `'._DB_PREFIX_.'mpstocksync_supplier_log` l 
                    ON l.id_supplier = s.id_supplier
                WHERE s.active = 1
                GROUP BY s.id_supplier';
        
        $supplierStats = Db::getInstance()->executeS($sql);
        
        $stats['suppliers'] = $supplierStats;
        
        return $stats;
    }
}
