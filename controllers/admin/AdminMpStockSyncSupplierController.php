<?php
class AdminMpStockSyncSuppliersController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        
        $this->meta_title = 'Suppliers';
    }
    
    public function initContent()
    {
        parent::initContent();
        
        $this->context->smarty->assign([
            'suppliers_url' => $this->context->link->getAdminLink('AdminMpStockSyncSuppliers')
        ]);
        
        $this->setTemplate('suppliers.tpl');
    }
}
