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
    public function __construct()
    {
        $this->name = 'mpstocksync';
        $this->tab = 'administration';
        $this->version = '2.0.0';
        $this->author = 'markoopapa';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => '1.7.99'
        ];
        
        parent::__construct();
        
        $this->displayName = $this->l('Marketplace Stock Sync');
        $this->description = $this->l('Sync stock between PrestaShop and marketplaces');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }
    
    public function install()
    {
        if (!parent::install()) {
            return false;
        }
        
        // Adatbázis táblák létrehozása
        if (!$this->installTables()) {
            return false;
        }
        
        // Admin tab-ok létrehozása
        if (!$this->installTabs()) {
            return false;
        }
        
        // Alap konfigurációk
        Configuration::updateValue('MP_STOCK_API_KEY', '');
        Configuration::updateValue('MP_STOCK_API_SECRET', '');
        
        return true;
    }
    
    private function installTables()
    {
        $sql = [];
        
        // Product mapping tábla
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
            KEY `marketplace_name` (`marketplace_name`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
        
        // Sync log tábla
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mp_stock_sync_log` (
            `id_log` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `sync_type` varchar(50) NOT NULL,
            `status` varchar(20) NOT NULL,
            `message` text,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_log`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
        
        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                PrestaShopLogger::addLog('MpStockSync install error: ' . Db::getInstance()->getMsgError(), 3);
                return false;
            }
        }
        
        return true;
    }
    
    private function installTabs()
    {
        try {
            // Fő tab
            $parentTab = new Tab();
            $parentTab->class_name = 'AdminMpStockSync';
            $parentTab->module = $this->name;
            $parentTab->id_parent = (int)Tab::getIdFromClassName('AdminParentPreferences');
            
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $parentTab->name[$lang['id_lang']] = $this->l('Stock Sync');
            }
            
            if (!$parentTab->add()) {
                return false;
            }
            
            // Almenü tab-ok
            $tabs = [
                ['AdminMpStockSyncApi', 'API Settings'],
                ['AdminMpStockSyncProductMapping', 'Product Mapping'],
                ['AdminMpStockSyncManualSync', 'Manual Sync'],
                ['AdminMpStockSyncLogs', 'Logs']
            ];
            
            foreach ($tabs as $tab) {
                $newTab = new Tab();
                $newTab->class_name = $tab[0];
                $newTab->module = $this->name;
                $newTab->id_parent = $parentTab->id;
                
                foreach ($languages as $lang) {
                    $newTab->name[$lang['id_lang']] = $this->l($tab[1]);
                }
                
                if (!$newTab->add()) {
                    $this->uninstallTabs();
                    return false;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            PrestaShopLogger::addLog('MpStockSync tab error: ' . $e->getMessage(), 3);
            return false;
        }
    }
    
    public function uninstall()
    {
        // Tab-ok törlése
        $this->uninstallTabs();
        
        // Konfigurációk törlése
        Configuration::deleteByName('MP_STOCK_API_KEY');
        Configuration::deleteByName('MP_STOCK_API_SECRET');
        
        // Adatbázis táblák törlése (opcionális)
        // $this->uninstallTables();
        
        return parent::uninstall();
    }
    
    private function uninstallTabs()
    {
        $tabs = [
            'AdminMpStockSync',
            'AdminMpStockSyncApi',
            'AdminMpStockSyncProductMapping',
            'AdminMpStockSyncManualSync',
            'AdminMpStockSyncLogs'
        ];
        
        foreach ($tabs as $className) {
            $id_tab = Tab::getIdFromClassName($className);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }
    }
    
    private function uninstallTables()
    {
        $tables = [
            'mp_stock_product_mapping',
            'mp_stock_sync_log'
        ];
        
        foreach ($tables as $table) {
            $sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.$table.'`';
            Db::getInstance()->execute($sql);
        }
    }
    
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMpStockSyncApi'));
    }
}
