<?php

// Fontos: Ellenőrizd, hogy ez a fájl a megfelelő helyen van!
// Elérési út: /modules/mpstocksync_v2/controllers/admin/AdminMpStockSyncProductMappingController.php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminMpStockSyncProductMappingController extends ModuleAdminController
{
    public $module;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->bootstrap = true;
        $this->table = 'mp_stock_product_mapping';
        $this->className = 'MpStockProductMapping';
        $this->identifier = 'id_mapping';
        $this->lang = false;
        
        // Modul példány beállítása
        $this->module = Module::getInstanceByName('mpstocksync_v2');
        
        // Alap lista beállítások
        $this->fields_list = array(
            'id_mapping' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'search' => false
            ),
            'id_product' => array(
                'title' => $this->l('Product ID'),
                'align' => 'center',
                'search' => false
            ),
            'product_name' => array(
                'title' => $this->l('Product Name'),
                'search' => false
            ),
            'marketplace_product_id' => array(
                'title' => $this->l('Marketplace ID'),
                'align' => 'center',
                'search' => false
            ),
            'marketplace_name' => array(
                'title' => $this->l('Marketplace'),
                'align' => 'center',
                'search' => false
            ),
            'last_sync' => array(
                'title' => $this->l('Last Sync'),
                'type' => 'datetime',
                'align' => 'center',
                'search' => false
            )
        );
        
        $this->actions = array('edit', 'delete');
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?')
            )
        );
    }
    
    public function init()
    {
        parent::init();
    }
    
    public function initContent()
    {
        parent::initContent();
        
        // API beállítások ellenőrzése
        $api_key = Configuration::get('MP_STOCK_API_KEY');
        $api_secret = Configuration::get('MP_STOCK_API_SECRET');
        
        if (empty($api_key) || empty($api_secret)) {
            // Információs üzenet ha nincs API beállítva
            $warning_message = $this->l('API settings are not configured. Please configure your API credentials in the "API Settings" tab first.');
            $this->context->smarty->assign(array(
                'warning_message' => $warning_message,
                'has_api_config' => false
            ));
        } else {
            $this->context->smarty->assign(array(
                'has_api_config' => true
            ));
        }
        
        // Rendereljük a tartalmat
        $this->setTemplate('product_mapping.tpl');
    }
    
    public function renderList()
    {
        // Először ellenőrizzük az API beállításokat
        $api_key = Configuration::get('MP_STOCK_API_KEY');
        $api_secret = Configuration::get('MP_STOCK_API_SECRET');
        
        if (empty($api_key) || empty($api_secret)) {
            return $this->displayApiWarning();
        }
        
        // Próbáljuk meg lekérni az adatokat
        try {
            // Itt kellene a tényleges adatlekérdezés
            // Egyelőre üres listát adunk vissza
            
            $this->_select = 'a.*, pl.name as product_name';
            $this->_join = 'LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (a.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id.')';
            $this->_where = 'AND 1';
            $this->_orderBy = 'a.id_mapping';
            $this->_orderWay = 'DESC';
            
            $content = parent::renderList();
            
            // Ha nincs adat
            if (empty($this->_list)) {
                $no_data_message = $this->l('No product mappings found. Click "Add new" to create your first mapping.');
                $this->context->smarty->assign('no_data_message', $no_data_message);
            }
            
            return $content;
            
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Product Mapping Error: ' . $e->getMessage(), 3);
            
            $error_message = $this->l('Error loading product mappings. Please check your API configuration.');
            $this->context->smarty->assign('error_message', $error_message);
            
            return $this->displayErrorMessage();
        }
    }
    
    public function renderForm()
    {
        // API ellenőrzés
        $api_key = Configuration::get('MP_STOCK_API_KEY');
        $api_secret = Configuration::get('MP_STOCK_API_SECRET');
        
        if (empty($api_key) || empty($api_secret)) {
            $this->errors[] = $this->l('Please configure API settings first.');
            return;
        }
        
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Product Mapping'),
                'icon' => 'icon-link'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Product ID'),
                    'name' => 'id_product',
                    'required' => true,
                    'col' => 4,
                    'hint' => $this->l('Enter your PrestaShop product ID')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Marketplace Product ID'),
                    'name' => 'marketplace_product_id',
                    'required' => true,
                    'col' => 4,
                    'hint' => $this->l('Enter the marketplace product ID/SKU')
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Marketplace'),
                    'name' => 'marketplace_name',
                    'required' => true,
                    'options' => array(
                        'query' => array(
                            array('id' => 'shopify', 'name' => 'Shopify'),
                            array('id' => 'amazon', 'name' => 'Amazon'),
                            array('id' => 'ebay', 'name' => 'Ebay'),
                            array('id' => 'woocommerce', 'name' => 'WooCommerce'),
                            array('id' => 'other', 'name' => 'Other')
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    ),
                    'col' => 4
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ),
            'buttons' => array(
                'cancel' => array(
                    'title' => $this->l('Cancel'),
                    'href' => $this->context->link->getAdminLink('AdminMpStockSyncProductMapping'),
                    'icon' => 'process-icon-cancel',
                    'class' => 'btn btn-default'
                )
            )
        );
        
        return parent::renderForm();
    }
    
    protected function displayApiWarning()
    {
        $this->context->smarty->assign(array(
            'api_warning' => true,
            'warning_message' => $this->l('Please configure API settings first to use product mapping.')
        ));
        
        return $this->context->smarty->fetch(_PS_MODULE_DIR_.'mpstocksync_v2/views/templates/admin/api_warning.tpl');
    }
    
    protected function displayErrorMessage()
    {
        return $this->context->smarty->fetch(_PS_MODULE_DIR_.'mpstocksync_v2/views/templates/admin/error_message.tpl');
    }
    
    public function setMedia()
    {
        parent::setMedia();
        
        // CSS és JS fájlok hozzáadása
        $this->addCSS(_PS_MODULE_DIR_.'mpstocksync_v2/views/css/product_mapping.css');
        $this->addJS(_PS_MODULE_DIR_.'mpstocksync_v2/views/js/product_mapping.js');
    }
    
    /**
     * Hozzáférési jogok ellenőrzése
     */
    public function checkAccess()
    {
        return true; // Vagy egyedi jogosultság ellenőrzés
    }
    
    /**
     * View hozzáférés ellenőrzése
     */
    public function viewAccess($disable = false)
    {
        return true;
    }
}
