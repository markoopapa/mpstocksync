<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class MpStockSync_v2 extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'mpstocksync_v2';
        $this->tab = 'administration';
        $this->version = '2.0.0';
        $this->author = 'markoopapa';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Marketplace Stock Sync v2');
        $this->description = $this->l('Sync stock between PrestaShop and marketplaces');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        if (!parent::install() || 
            !$this->installDatabase() || 
            !$this->installTabs() || 
            !$this->registerHooks()) {
            return false;
        }

        // Alap konfigurációk beállítása
        Configuration::updateValue('MP_STOCK_SYNC_ENABLED', '1');
        Configuration::updateValue('MP_STOCK_SYNC_INTERVAL', '3600');
        Configuration::updateValue('MP_STOCK_API_KEY', '');
        Configuration::updateValue('MP_STOCK_API_SECRET', '');
        Configuration::updateValue('MP_STOCK_DEBUG_MODE', '0');

        return true;
    }

    private function installDatabase()
    {
        $sql = array();

        // 1. Termék leképezés tábla
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mp_stock_product_mapping` (
            `id_mapping` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_product` INT(11) UNSIGNED NOT NULL,
            `marketplace_product_id` VARCHAR(255) NOT NULL,
            `marketplace_name` VARCHAR(50) NOT NULL,
            `last_sync` DATETIME NULL,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_mapping`),
            INDEX `id_product` (`id_product`),
            INDEX `marketplace_name` (`marketplace_name`),
            INDEX `marketplace_product_id` (`marketplace_product_id`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        // 2. Szinkronizációs log tábla
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mp_stock_sync_log` (
            `id_log` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `sync_type` VARCHAR(50) NOT NULL,
            `status` VARCHAR(20) NOT NULL,
            `message` TEXT,
            `products_count` INT(11) DEFAULT 0,
            `error_details` TEXT,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_log`),
            INDEX `sync_type` (`sync_type`),
            INDEX `status` (`status`),
            INDEX `date_add` (`date_add`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        // 3. Marketplace beállítások tábla
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mp_stock_marketplace` (
            `id_marketplace` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(50) NOT NULL,
            `display_name` VARCHAR(100) NOT NULL,
            `api_url` VARCHAR(255),
            `api_key` VARCHAR(255),
            `api_secret` VARCHAR(255),
            `is_active` TINYINT(1) DEFAULT 1,
            `auto_sync` TINYINT(1) DEFAULT 0,
            `sync_interval` INT(11) DEFAULT 3600,
            `last_sync` DATETIME NULL,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_marketplace`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        // SQL-ek futtatása
        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                PrestaShopLogger::addLog('MP Stock Sync: Failed to execute SQL: ' . $query, 3);
                return false;
            }
        }

        // Alap marketplace bejegyzések
        $this->insertDefaultMarketplaces();

        return true;
    }

    private function insertDefaultMarketplaces()
    {
        $marketplaces = array(
            array('shopify', 'Shopify', '', '', '', 1, 0, 3600),
            array('amazon', 'Amazon', '', '', '', 1, 0, 3600),
            array('ebay', 'eBay', '', '', '', 1, 0, 3600),
            array('woocommerce', 'WooCommerce', '', '', '', 1, 0, 3600),
        );

        foreach ($marketplaces as $mp) {
            $sql = 'INSERT IGNORE INTO `'._DB_PREFIX_.'mp_stock_marketplace` 
                    (`name`, `display_name`, `api_url`, `api_key`, `api_secret`, `is_active`, `auto_sync`, `sync_interval`, `date_add`, `date_upd`)
                    VALUES ("' . pSQL($mp[0]) . '", "' . pSQL($mp[1]) . '", "' . pSQL($mp[2]) . '", 
                    "' . pSQL($mp[3]) . '", "' . pSQL($mp[4]) . '", ' . (int)$mp[5] . ', ' . (int)$mp[6] . ', 
                    ' . (int)$mp[7] . ', NOW(), NOW())';
            
            Db::getInstance()->execute($sql);
        }
    }

    private function installTabs()
    {
        // Fő admin tab
        $parentTab = new Tab();
        $parentTab->class_name = 'AdminMpStockSync';
        $parentTab->module = $this->name;
        $parentTab->id_parent = Tab::getIdFromClassName('AdminParentPreferences'); // Beállítások menü alá
        $parentTab->active = 1;
        
        $languages = Language::getLanguages();
        foreach ($languages as $lang) {
            $parentTab->name[$lang['id_lang']] = $this->l('Stock Sync');
        }
        
        if (!$parentTab->add()) {
            return false;
        }
        
        // Almenü tab-ok
        $tabs = array(
            array(
                'class_name' => 'AdminMpStockSyncApi',
                'name' => 'API Settings',
                'id_parent' => $parentTab->id
            ),
            array(
                'class_name' => 'AdminMpStockSyncProductMapping',
                'name' => 'Product Mapping',
                'id_parent' => $parentTab->id
            ),
            array(
                'class_name' => 'AdminMpStockSyncManualSync',
                'name' => 'Manual Sync',
                'id_parent' => $parentTab->id
            ),
            array(
                'class_name' => 'AdminMpStockSyncLogs',
                'name' => 'Logs',
                'id_parent' => $parentTab->id
            ),
            array(
                'class_name' => 'AdminMpStockSyncSettings',
                'name' => 'Settings',
                'id_parent' => $parentTab->id
            ),
        );
        
        foreach ($tabs as $tabData) {
            $tab = new Tab();
            $tab->class_name = $tabData['class_name'];
            $tab->module = $this->name;
            $tab->id_parent = $tabData['id_parent'];
            $tab->active = 1;
            
            foreach ($languages as $lang) {
                $tab->name[$lang['id_lang']] = $this->l($tabData['name']);
            }
            
            if (!$tab->add()) {
                // Ha nem sikerül, töröljük az eddigieket
                $this->uninstallTabs();
                return false;
            }
        }
        
        return true;
    }

    private function registerHooks()
    {
        $hooks = array(
            'actionProductUpdate',
            'actionUpdateQuantity',
            'displayBackOfficeHeader',
            'actionAdminControllerSetMedia'
        );

        foreach ($hooks as $hook) {
            if (!$this->registerHook($hook)) {
                PrestaShopLogger::addLog('MP Stock Sync: Failed to register hook ' . $hook, 3);
                return false;
            }
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() ||
            !$this->uninstallDatabase() ||
            !$this->uninstallTabs()) {
            return false;
        }

        // Konfigurációk törlése
        $config_keys = array(
            'MP_STOCK_SYNC_ENABLED',
            'MP_STOCK_SYNC_INTERVAL',
            'MP_STOCK_API_KEY',
            'MP_STOCK_API_SECRET',
            'MP_STOCK_DEBUG_MODE'
        );

        foreach ($config_keys as $key) {
            Configuration::deleteByName($key);
        }

        return true;
    }

    private function uninstallDatabase()
    {
        $tables = array(
            'mp_stock_product_mapping',
            'mp_stock_sync_log',
            'mp_stock_marketplace'
        );

        foreach ($tables as $table) {
            $sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.$table.'`';
            if (!Db::getInstance()->execute($sql)) {
                PrestaShopLogger::addLog('MP Stock Sync: Failed to drop table ' . $table, 3);
            }
        }

        return true;
    }

    private function uninstallTabs()
    {
        $tabs = array(
            'AdminMpStockSync',
            'AdminMpStockSyncApi',
            'AdminMpStockSyncProductMapping',
            'AdminMpStockSyncManualSync',
            'AdminMpStockSyncLogs',
            'AdminMpStockSyncSettings'
        );

        foreach ($tabs as $className) {
            $id_tab = (int)Tab::getIdFromClassName($className);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }

        return true;
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMpStockSyncApi'));
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('controller') == 'AdminMpStockSync' || 
            strpos(Tools::getValue('controller'), 'AdminMpStockSync') === 0) {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
        }
    }

    public function hookActionAdminControllerSetMedia()
    {
        if (Tools::getValue('controller') == 'AdminMpStockSync' || 
            strpos(Tools::getValue('controller'), 'AdminMpStockSync') === 0) {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
        }
    }
}
