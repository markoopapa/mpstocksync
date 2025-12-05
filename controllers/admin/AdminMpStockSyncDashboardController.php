<?php
class AdminMpStockSyncDashboardController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        parent::__construct();
        
        $this->meta_title = $this->l('Stock Sync Dashboard');
    }
    
    public function initContent()
    {
        parent::initContent();
        
        $module = Module::getInstanceByName('mpstocksync');
        $stats = $module->getSyncStatistics();
        
        // Get API status
        $emag_status = $this->getApiStatus('emag');
        $trendyol_status = $this->getApiStatus('trendyol');
        
        // Get recent syncs
        $recent_syncs = $this->getRecentSyncs(10);
        
        // Get queue status
        $queue_stats = $this->getQueueStats();
        
        $this->context->smarty->assign([
            'stats' => $stats,
            'emag_status' => $emag_status,
            'trendyol_status' => $trendyol_status,
            'recent_syncs' => $recent_syncs,
            'queue_stats' => $queue_stats,
            'dashboard_url' => $this->context->link->getAdminLink('AdminMpStockSyncDashboard'),
            'products_url' => $this->context->link->getAdminLink('AdminMpStockSyncProducts'),
            'logs_url' => $this->context->link->getAdminLink('AdminMpStockSyncLogs'),
            'settings_url' => $this->context->link->getAdminLink('AdminMpStockSyncSettings'),
            'api_url' => $this->context->link->getAdminLink('AdminMpStockSyncApi'),
        ]);
        
        $this->setTemplate('dashboard.tpl');
    }
    
    public function initToolbar()
    {
        parent::initToolbar();
        
        $this->page_header_toolbar_btn['sync_emag'] = [
            'href' => self::$currentIndex . '&sync_emag&token=' . $this->token,
            'desc' => $this->l('Sync eMAG'),
            'icon' => 'process-icon-refresh'
        ];
        
        $this->page_header_toolbar_btn['sync_trendyol'] = [
            'href' => self::$currentIndex . '&sync_trendyol&token=' . $this->token,
            'desc' => $this->l('Sync Trendyol'),
            'icon' => 'process-icon-refresh'
        ];
        
        $this->page_header_toolbar_btn['sync_all'] = [
            'href' => self::$currentIndex . '&sync_all&token=' . $this->token,
            'desc' => $this->l('Sync All'),
            'icon' => 'process-icon-cogs'
        ];
    }
    
    public function initProcess()
    {
        parent::initProcess();
        
        if (Tools::getValue('sync_emag')) {
            $this->processSync('emag');
        }
        
        if (Tools::getValue('sync_trendyol')) {
            $this->processSync('trendyol');
        }
        
        if (Tools::getValue('sync_all')) {
            $this->processSync('all');
        }
    }
    
    private function processSync($api)
    {
        $module = Module::getInstanceByName('mpstocksync');
        $result = $module->manualSyncAll($api == 'all' ? null : $api);
        
        $this->confirmations[] = sprintf(
            $this->l('Sync completed: %d products, %d successful, %d errors'),
            $result['total'],
            $result['success'],
            $result['errors']
        );
        
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMpStockSyncDashboard'));
    }
    
    private function getApiStatus($api_name)
    {
        $sql = 'SELECT status, test_mode, last_sync_date 
                FROM `'._DB_PREFIX_.'mpstocksync_api_config`
                WHERE api_name = "'.pSQL($api_name).'"';
        
        $config = Db::getInstance()->getRow($sql);
        
        if (!$config) {
            return [
                'configured' => false,
                'enabled' => false,
                'test_mode' => true,
                'last_sync' => null
            ];
        }
        
        // Check if API credentials are set
        $configured = false;
        if ($api_name == 'emag') {
            $configured = Configuration::get('MP_EMAG_CLIENT_ID') && 
                         Configuration::get('MP_EMAG_CLIENT_SECRET');
        } elseif ($api_name == 'trendyol') {
            $configured = Configuration::get('MP_TRENDYOL_API_KEY') && 
                         Configuration::get('MP_TRENDYOL_API_SECRET');
        }
        
        return [
            'configured' => $configured,
            'enabled' => (bool)$config['status'],
            'test_mode' => (bool)$config['test_mode'],
            'last_sync' => $config['last_sync_date']
        ];
    }
    
    private function getRecentSyncs($limit = 10)
    {
        $sql = 'SELECT l.*, p.reference, pl.name
                FROM `'._DB_PREFIX_.'mpstocksync_log` l
                LEFT JOIN `'._DB_PREFIX_.'product` p ON p.id_product = l.id_product
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON pl.id_product = p.id_product
                    AND pl.id_lang = '.(int)$this->context->language->id.'
                ORDER BY l.date_add DESC
                LIMIT '.(int)$limit;
        
        return Db::getInstance()->executeS($sql);
    }
    
    private function getQueueStats()
    {
        $sql = 'SELECT api_name, status, COUNT(*) as count
                FROM `'._DB_PREFIX_.'mpstocksync_queue`
                GROUP BY api_name, status';
        
        $result = Db::getInstance()->executeS($sql);
        
        $stats = [
            'emag' => ['pending' => 0, 'processing' => 0, 'failed' => 0],
            'trendyol' => ['pending' => 0, 'processing' => 0, 'failed' => 0]
        ];
        
        foreach ($result as $row) {
            if (isset($stats[$row['api_name']])) {
                if ($row['status'] == 0) {
                    $stats[$row['api_name']]['pending'] = (int)$row['count'];
                } elseif ($row['status'] == 1) {
                    $stats[$row['api_name']]['processing'] = (int)$row['count'];
                } elseif ($row['status'] == 3) {
                    $stats[$row['api_name']]['failed'] = (int)$row['count'];
                }
            }
        }
        
        return $stats;
    }
    
    public function renderView()
    {
        return parent::renderView();
    }
}
