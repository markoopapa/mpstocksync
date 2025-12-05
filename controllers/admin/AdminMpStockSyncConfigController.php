<?php

class AdminMpStockSyncConfigController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();
        $this->content = $this->renderForm();
        $this->context->smarty->assign('content', $this->content);
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Seller ID (Entity ID)'),
                        'name' => 'MPSTOCKSYNC_TRENDYOL_SELLER_ID',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Integration reference code'),
                        'name' => 'MPSTOCKSYNC_TRENDYOL_INTEGRATION_CODE',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('API Key'),
                        'name' => 'MPSTOCKSYNC_TRENDYOL_API_KEY',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('API Secret'),
                        'name' => 'MPSTOCKSYNC_TRENDYOL_API_SECRET',
                        'required' => true
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Token'),
                        'name' => 'MPSTOCKSYNC_TRENDYOL_TOKEN',
                        'rows' => 3,
                        'cols' => 40,
                        'required' => true
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable Trendyol Sync'),
                        'name' => 'MPSTOCKSYNC_TRENDYOL_ACTIVE',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Stock Update Interval (minutes)'),
                        'name' => 'MPSTOCKSYNC_UPDATE_INTERVAL',
                        'suffix' => $this->l('minutes'),
                        'required' => true,
                        'validation' => 'isUnsignedInt'
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                )
            )
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this->module;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMpStockSyncConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminMpStockSyncConfig', false);
        $helper->token = Tools::getAdminTokenLite('AdminMpStockSyncConfig');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'MPSTOCKSYNC_TRENDYOL_SELLER_ID' => Tools::getValue('MPSTOCKSYNC_TRENDYOL_SELLER_ID', Configuration::get('MPSTOCKSYNC_TRENDYOL_SELLER_ID')),
            'MPSTOCKSYNC_TRENDYOL_INTEGRATION_CODE' => Tools::getValue('MPSTOCKSYNC_TRENDYOL_INTEGRATION_CODE', Configuration::get('MPSTOCKSYNC_TRENDYOL_INTEGRATION_CODE')),
            'MPSTOCKSYNC_TRENDYOL_API_KEY' => Tools::getValue('MPSTOCKSYNC_TRENDYOL_API_KEY', Configuration::get('MPSTOCKSYNC_TRENDYOL_API_KEY')),
            'MPSTOCKSYNC_TRENDYOL_API_SECRET' => Tools::getValue('MPSTOCKSYNC_TRENDYOL_API_SECRET', Configuration::get('MPSTOCKSYNC_TRENDYOL_API_SECRET')),
            'MPSTOCKSYNC_TRENDYOL_TOKEN' => Tools::getValue('MPSTOCKSYNC_TRENDYOL_TOKEN', Configuration::get('MPSTOCKSYNC_TRENDYOL_TOKEN')),
            'MPSTOCKSYNC_TRENDYOL_ACTIVE' => Tools::getValue('MPSTOCKSYNC_TRENDYOL_ACTIVE', Configuration::get('MPSTOCKSYNC_TRENDYOL_ACTIVE')),
            'MPSTOCKSYNC_UPDATE_INTERVAL' => Tools::getValue('MPSTOCKSYNC_UPDATE_INTERVAL', Configuration::get('MPSTOCKSYNC_UPDATE_INTERVAL'))
        );
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitMpStockSyncConfig')) {
            Configuration::updateValue('MPSTOCKSYNC_TRENDYOL_SELLER_ID', Tools::getValue('MPSTOCKSYNC_TRENDYOL_SELLER_ID'));
            Configuration::updateValue('MPSTOCKSYNC_TRENDYOL_INTEGRATION_CODE', Tools::getValue('MPSTOCKSYNC_TRENDYOL_INTEGRATION_CODE'));
            Configuration::updateValue('MPSTOCKSYNC_TRENDYOL_API_KEY', Tools::getValue('MPSTOCKSYNC_TRENDYOL_API_KEY'));
            Configuration::updateValue('MPSTOCKSYNC_TRENDYOL_API_SECRET', Tools::getValue('MPSTOCKSYNC_TRENDYOL_API_SECRET'));
            Configuration::updateValue('MPSTOCKSYNC_TRENDYOL_TOKEN', Tools::getValue('MPSTOCKSYNC_TRENDYOL_TOKEN'));
            Configuration::updateValue('MPSTOCKSYNC_TRENDYOL_ACTIVE', Tools::getValue('MPSTOCKSYNC_TRENDYOL_ACTIVE'));
            Configuration::updateValue('MPSTOCKSYNC_UPDATE_INTERVAL', Tools::getValue('MPSTOCKSYNC_UPDATE_INTERVAL'));
            $this->confirmations[] = $this->l('Settings updated successfully');
        }
        parent::postProcess();
    }
}
