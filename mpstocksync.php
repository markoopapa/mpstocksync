<?php
/**
 * Marketplace Stock Sync
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
        if (!parent::install()) {
            return false;
        }
        
        // Adatbázis táblák
        $sql = array();
        
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mp_stock_product_mapping` (
            `id_mapping` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_product` INT(11) UNSIGNED NOT NULL,
            `marketplace_product_id` VARCHAR(255) NOT NULL,
            `marketplace_name` VARCHAR(50) NOT NULL,
            `last_sync` DATETIME NULL,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_mapping`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
        
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mp_stock_sync_log` (
            `id_log` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `sync_type` VARCHAR(50) NOT NULL,
            `status` VARCHAR(20) NOT NULL,
            `message` TEXT,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_log`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
        
        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
        
        // Alap konfiguráció
        Configuration::updateValue('MP_STOCK_API_KEY', '');
        Configuration::updateValue('MP_STOCK_API_SECRET', '');
        
        return true;
    }
    
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }
        
        Configuration::deleteByName('MP_STOCK_API_KEY');
        Configuration::deleteByName('MP_STOCK_API_SECRET');
        
        return true;
    }
    
    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminMpStockSyncApi')
        );
    }
}
