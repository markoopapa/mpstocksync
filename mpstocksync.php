<?php
/**
 * Marketplace Stock Sync
 * 
 * @author markoopapa
 * @version 2.0.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MpStockSync extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'mpstocksync';
        $this->tab = 'administration';
        $this->version = '2.0.0';
        $this->author = 'markoopapa';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Marketplace Stock Sync');
        $this->description = $this->l('Sync stock between PrestaShop and marketplaces');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->ps_versions_compliancy = array(
            'min' => '1.6',
            'max' => _PS_VERSION_
        );
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() || 
            !$this->installDatabase() || 
            !$this->installTabs() || 
            !$this->registerHooks()) {
            return false;
        }

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

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mp_stock_product_mapping` (
            `id_mapping` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_product` int(11) UNSIGNED NOT NULL,
            `marketplace_product_id` varchar(255) NOT NULL,
            `marketplace_name` varchar(50) NOT NULL,
            `last_sync` datetime DEFAULT NULL,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_mapping`),
            KEY `id_product` (`id_product`),
            KEY `marketplace_name` (`marketplace_name`),
            KEY `marketplace_product_id` (`marketplace_product_id`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mp_stock_sync_log` (
            `id_log` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `sync_type` varchar(50) NOT NULL,
            `status` varchar(20) NOT NULL,
            `message` text,
            `products_count` int(11) DEFAULT 0,
            `error_details` text,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_log`),
            KEY `sync_type` (`sync_type`),
            KEY `status` (`status`),
            KEY `date_add` (`date_add`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mp_stock_marketplace` (
            `id_marketplace` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `display_name` varchar(100) NOT NULL,
            `api_url` varchar(255),
            `api_key` varchar(255),
            `api_secret` varchar(255),
            `is_active` tinyint(1) DEFAULT 1,
            `auto_sync` tinyint(1) DEFAULT 0,
            `sync_interval` int(11) DEFAULT 3600,
            `last_sync` datetime NULL,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_marketplace`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                PrestaShopLogger::addLog('MP Stock Sync: Failed to execute SQL: ' . $query, 3);
                return false;
            }
        }

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
        $parent_tab = new Tab();
        $parent_tab->class_name = 'AdminMpStockSync';
        $parent_tab->module = $this->name;
        $parent_tab->id_parent = (int)Tab::getIdFromClassName('AdminParentPreferences');
        
        $languages = Language::getLanguages();
        foreach ($languages as $language) {
            $parent_tab->name[$language['id_lang']] = $this->l('Stock Sync');
        }
        
        if (!$parent_tab->add()) {
            return false;
        }

        $sub_tabs = [
            [
                'class_name' => 'AdminMpStockSyncApi',
                'name' => 'API Settings',
                'id_parent' => $parent_tab->id
            ],
            [
                'class_name' => 'AdminMpStockSyncProductMapping',
                'name' => 'Product Mapping',
                'id_parent' => $parent_tab->id
            ],
            [
                'class_name' => 'AdminMpStockSyncManualSync',
                'name' => 'Manual Sync',
                'id_parent' => $parent_tab->id
            ],
            [
                'class_name' => 'AdminMpStockSyncLogs',
                'name' => 'Logs',
                'id_parent' => $parent_tab->id
            ],
            [
                'class_name' => 'AdminMpStockSyncSettings',
                'name' => 'Settings',
                'id_parent' => $parent_tab->id
            ],
        ];

        foreach ($sub_tabs as $tab_data) {
            $tab = new Tab();
            $tab->class_name = $tab_data['class_name'];
            $tab->module = $this->name;
            $tab->id_parent = $tab_data['id_parent'];
            
            foreach ($languages as $language) {
                $tab->name[$language['id_lang']] = $this->l($tab_data['name']);
            }
            
            if (!$tab->add()) {
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
            !$this->uninstallTabs()) {
            return false;
        }

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

    public function hookActionProductUpdate($params)
    {
        // Product update hook implementation
        if (isset($params['id_product'])) {
            // Sync logic here
        }
    }

    public function hookActionUpdateQuantity($params)
    {
        // Quantity update hook implementation
        if (isset($params['id_product'])) {
            // Sync logic here
        }
    }
}
