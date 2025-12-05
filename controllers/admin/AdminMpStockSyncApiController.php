<?php
class AdminMpStockSyncApiController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }
    
    public function initContent()
    {
        parent::initContent();
        
        // Konfigurációs értékek gyűjtése
        $config_values = [
            'emag' => [
                'api_url' => Configuration::get('MP_EMAG_API_URL'),
                'client_id' => Configuration::get('MP_EMAG_CLIENT_ID'),
                'client_secret' => Configuration::get('MP_EMAG_CLIENT_SECRET'),
                'username' => Configuration::get('MP_EMAG_USERNAME'),
                'password' => Configuration::get('MP_EMAG_PASSWORD'),
                'auto_sync' => Configuration::get('MP_EMAG_AUTO_SYNC')
            ],
            'trendyol' => [
                'api_url' => Configuration::get('MP_TRENDYOL_API_URL'),
                'api_key' => Configuration::get('MP_TRENDYOL_API_KEY'),
                'api_secret' => Configuration::get('MP_TRENDYOL_API_SECRET'),
                'supplier_id' => Configuration::get('MP_TRENDYOL_SUPPLIER_ID'),
                'auto_sync' => Configuration::get('MP_TRENDYOL_AUTO_SYNC')
            ],
            'general' => [
                'log_enabled' => Configuration::get('MP_LOG_ENABLED'),
                'notify_errors' => Configuration::get('MP_NOTIFY_ERRORS'),
                'auto_retry' => Configuration::get('MP_AUTO_RETRY'),
                'retry_attempts' => Configuration::get('MP_RETRY_ATTEMPTS'),
                'retry_delay' => Configuration::get('MP_RETRY_DELAY')
            ]
        ];
        
        $this->context->smarty->assign([
            'config' => $config_values,
            'module_dir' => Module::getInstanceByName('mpstocksync')->getLocalPath(),
            'post_url' => $this->context->link->getAdminLink('AdminMpStockSyncApi'),
            'token' => Tools::getAdminTokenLite('AdminMpStockSyncApi')
        ]);
        
        $this->setTemplate('api_settings/api_settings.tpl');
    }
    
    public function postProcess()
    {
        if (Tools::isSubmit('submit_api_settings')) {
            // eMAG beállítások
            Configuration::updateValue('MP_EMAG_API_URL', Tools::getValue('emag_api_url'));
            Configuration::updateValue('MP_EMAG_CLIENT_ID', Tools::getValue('emag_client_id'));
            Configuration::updateValue('MP_EMAG_CLIENT_SECRET', Tools::getValue('emag_client_secret'));
            Configuration::updateValue('MP_EMAG_USERNAME', Tools::getValue('emag_username'));
            Configuration::updateValue('MP_EMAG_PASSWORD', Tools::getValue('emag_password'));
            Configuration::updateValue('MP_EMAG_AUTO_SYNC', Tools::getValue('emag_auto_sync'));
            
            // Trendyol beállítások
            Configuration::updateValue('MP_TRENDYOL_API_URL', Tools::getValue('trendyol_api_url'));
            Configuration::updateValue('MP_TRENDYOL_API_KEY', Tools::getValue('trendyol_api_key'));
            Configuration::updateValue('MP_TRENDYOL_API_SECRET', Tools::getValue('trendyol_api_secret'));
            Configuration::updateValue('MP_TRENDYOL_SUPPLIER_ID', Tools::getValue('trendyol_supplier_id'));
            Configuration::updateValue('MP_TRENDYOL_AUTO_SYNC', Tools::getValue('trendyol_auto_sync'));
            
            // Általános beállítások
            Configuration::updateValue('MP_LOG_ENABLED', Tools::getValue('log_enabled'));
            Configuration::updateValue('MP_NOTIFY_ERRORS', Tools::getValue('notify_errors'));
            Configuration::updateValue('MP_AUTO_RETRY', Tools::getValue('auto_retry'));
            Configuration::updateValue('MP_RETRY_ATTEMPTS', Tools::getValue('retry_attempts'));
            Configuration::updateValue('MP_RETRY_DELAY', Tools::getValue('retry_delay'));
            
            $this->confirmations[] = $this->l('Settings saved successfully');
        }
        
        parent::postProcess();
    }
}
