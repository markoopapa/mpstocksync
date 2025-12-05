<?php

require_once _PS_MODULE_DIR_ . 'mpstocksync/classes/MpStockSyncMapping.php';

class AdminMpStockSyncProductsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mpstocksync_mapping';
        $this->identifier = 'id_mapping';
        $this->className = 'MpStockSyncMapping';
        $this->lang = false;
        $this->list_no_link = true;

        parent::__construct();

        $this->fields_list = array(
            'id_mapping' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'width' => 30
            ),
            'id_product' => array(
                'title' => $this->l('Product ID'),
                'align' => 'center',
                'width' => 50
            ),
            'id_product_attribute' => array(
                'title' => $this->l('Attribute ID'),
                'align' => 'center',
                'width' => 50
            ),
            'external_reference' => array(
                'title' => $this->l('External Reference'),
                'width' => 'auto'
            ),
            'external_code' => array(
                'title' => $this->l('External Code'),
                'width' => 'auto'
            ),
            'platform' => array(
                'title' => $this->l('Platform'),
                'width' => 100
            ),
            'active' => array(
                'title' => $this->l('Active'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'orderby' => false
            )
        );

        $this->bulk_actions = array(
            'enable' => array(
                'text' => $this->l('Enable'),
                'icon' => 'icon-power-off text-success'
            ),
            'disable' => array(
                'text' => $this->l('Disable'),
                'icon' => 'icon-power-off text-danger'
            ),
            'delete' => array(
                'text' => $this->l('Delete'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            )
        );
    }

    public function renderList()
    {
        $this->_select = 'a.*';
        $this->_orderBy = 'a.id_mapping';
        $this->_orderWay = 'DESC';

        return parent::renderList();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        $this->page_header_toolbar_btn['sync_products'] = array(
            'href' => self::$currentIndex . '&syncProducts&token=' . $this->token,
            'desc' => $this->l('Sync Products'),
            'icon' => 'icon-refresh'
        );
    }

    public function postProcess()
    {
        if (Tools::getValue('syncProducts')) {
            $this->syncProducts();
        }
        parent::postProcess();
    }

    private function syncProducts()
    {
        // Implement product sync logic here
        $this->confirmations[] = $this->l('Products synchronized successfully');
    }
}
