<?php

class AdminMpMarketplaceSyncController extends ModuleAdminController
{
    protected $list_emag;
    protected $list_trendyol;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'product'; 
        $this->className = 'Product';
        $this->lang = true;
        parent::__construct();
    }

    /**
     * Két külön listát generálunk és fűzünk össze HTML-ben
     */
    public function renderList()
    {
        // 1. eMAG Lista konfigurálása
        $this->setupEmagList();
        $html_emag = parent::renderList(); 

        // 2. Trendyol Lista konfigurálása (resetelni kell pár dolgot)
        $this->setupTrendyolList();
        // Fontos trükk: A "processStatus" linkeknek tudniuk kell, melyik listában vagyunk
        // Ezt a renderList előtt beállított paraméterekkel oldjuk meg, vagy a processStatus-ban detektáljuk.
        $html_trendyol = parent::renderList();

        return $this->displayInfo() . 
               '<h2>eMAG Synchronization</h2>' . $html_emag . 
               '<hr style="margin: 40px 0;">' . 
               '<h2>Trendyol Synchronization</h2>' . $html_trendyol;
    }

    protected function displayInfo() {
        return '<div class="alert alert-info">Toggle the switches below to enable/disable stock sync for each marketplace individually.</div>';
    }

    // --- eMAG LIST SETUP ---
    protected function setupEmagList()
    {
        // Egyedi azonosító a táblázatnak, hogy a szűrés/lapozás külön működjön
        $this->list_id = 'emag_list'; 
        $this->_filter = ''; // Reset filter
        $this->_select = 'mp.marketplace_sku, mp.sync_enabled, mp.last_synced';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'mp_marketplace_map` mp ON (a.`id_product` = mp.`id_product` AND mp.marketplace_type = "emag")';
        
        $this->fields_list = [
            'id_product' => ['title' => 'ID', 'width' => 30],
            'name' => ['title' => 'Product', 'width' => 'auto'],
            'marketplace_sku' => ['title' => 'eMAG SKU', 'width' => 100],
            'sync_enabled' => [
                'title' => 'eMAG Sync',
                'active' => 'statusEmag', // Egyedi action név!
                'type' => 'bool',
                'align' => 'center',
            ],
            'last_synced' => ['title' => 'Last eMAG Sync', 'type' => 'datetime']
        ];
        
        // Fontos: A token resetelése az aktuális kontextushoz
        $this->token = Tools::getAdminTokenLite('AdminMpMarketplaceSync');
    }

    // --- TRENDYOL LIST SETUP ---
    protected function setupTrendyolList()
    {
        $this->list_id = 'trendyol_list';
        $this->_filter = '';
        $this->_select = 'mp.marketplace_sku, mp.sync_enabled, mp.last_synced';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'mp_marketplace_map` mp ON (a.`id_product` = mp.`id_product` AND mp.marketplace_type = "trendyol")';
        
        $this->fields_list = [
            'id_product' => ['title' => 'ID', 'width' => 30],
            'name' => ['title' => 'Product', 'width' => 'auto'],
            'marketplace_sku' => ['title' => 'Trendyol SKU', 'width' => 100],
            'sync_enabled' => [
                'title' => 'Trendyol Sync',
                'active' => 'statusTrendyol', // Egyedi action név!
                'type' => 'bool',
                'align' => 'center',
            ],
            'last_synced' => ['title' => 'Last Trendyol Sync', 'type' => 'datetime']
        ];
    }

    /**
     * Státusz váltás kezelése (eMAG és Trendyol külön)
     * A PrestaShop "status" + ActionNév metódust keresi.
     */
    
    // eMAG kapcsoló kezelése
    public function processStatusEmag()
    {
        $this->toggleMarketplaceStatus('emag');
    }

    // Trendyol kapcsoló kezelése
    public function processStatusTrendyol()
    {
        $this->toggleMarketplaceStatus('trendyol');
    }

    protected function toggleMarketplaceStatus($type)
    {
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            $id_product = (int)$object->id;
            
            $sql = 'SELECT id_mp_market_map FROM '._DB_PREFIX_.'mp_marketplace_map 
                    WHERE id_product = ' . $id_product . ' AND marketplace_type = "'.pSQL($type).'"';
            $id_map = Db::getInstance()->getValue($sql);
            
            if ($id_map) {
                Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'mp_marketplace_map 
                                            SET sync_enabled = NOT sync_enabled 
                                            WHERE id_mp_market_map = ' . (int)$id_map);
            } else {
                Db::getInstance()->insert('mp_marketplace_map', [
                    'id_product' => $id_product,
                    'marketplace_type' => $type,
                    'marketplace_sku' => $object->reference,
                    'sync_enabled' => 1
                ]);
            }
        }
        Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
    }
}
