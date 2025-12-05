<?php
class AdminMpStockSyncSettingsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        
        $this->meta_title = 'Settings';
    }
    
    public function initContent()
    {
        parent::initContent();
        
        $this->context->smarty->assign([
            'settings_url' => $this->context->link->getAdminLink('AdminMpStockSyncSettings')
        ]);
        
        $this->setTemplate('settings.tpl');
    }
}
