<?php

class AdminMpStockSyncApiController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'configuration';
        $this->className = 'Configuration';
        
        parent::__construct();
        
        // További inicializálások...
    }

    /**
     * Process save
     */
    public function processSave()
    {
        if (Tools::isSubmit('submit' . $this->table)) {
            $api_key = Tools::getValue('MP_STOCK_API_KEY');
            $api_secret = Tools::getValue('MP_STOCK_API_SECRET');
            
            if (!empty($api_key) && !empty($api_secret)) {
                Configuration::updateValue('MP_STOCK_API_KEY', $api_key);
                Configuration::updateValue('MP_STOCK_API_SECRET', $api_secret);
                
                $this->confirmations[] = $this->l('Settings saved successfully');
            } else {
                $this->errors[] = $this->l('Please fill all fields');
            }
        }
    }

    /**
     * Render form
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('API Settings'),
                'icon' => 'icon-cogs'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('API Key'),
                    'name' => 'MP_STOCK_API_KEY',
                    'required' => true,
                    'col' => 4
                ],
                [
                    'type' => 'password',
                    'label' => $this->l('API Secret'),
                    'name' => 'MP_STOCK_API_SECRET',
                    'required' => true,
                    'col' => 4
                ]
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];
        
        $this->fields_value = [
            'MP_STOCK_API_KEY' => Configuration::get('MP_STOCK_API_KEY'),
            'MP_STOCK_API_SECRET' => Configuration::get('MP_STOCK_API_SECRET')
        ];
        
        return parent::renderForm();
    }

    /**
     * Post process
     */
    public function postProcess()
    {
        $this->processSave();
        parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();
        $this->context->smarty->assign('content', $this->renderForm());
        $this->setTemplate('api_settings.tpl');
    }
}
