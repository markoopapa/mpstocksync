<?php
class AdminMpStockSyncSettingsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        
        $this->meta_title = 'Module Settings';
    }
    
    public function initContent()
    {
        parent::initContent();
        
        if (Tools::isSubmit('save_settings')) {
            $this->processSaveSettings();
        }
        
        $this->context->smarty->assign([
            'settings_form' => $this->renderSettingsForm(),
            'module_name' => 'mpstocksync'
        ]);
        
        $this->setTemplate('settings.tpl');
    }
    
    private function renderSettingsForm()
    {
        $fields = [
            'form' => [
                'legend' => [
                    'title' => 'General Settings',
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => 'Enable Logging',
                        'name' => 'MP_LOG_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1],
                            ['id' => 'active_off', 'value' => 0]
                        ],
                        'value' => Configuration::get('MP_LOG_ENABLED')
                    ],
                    [
                        'type' => 'switch',
                        'label' => 'Notify on Errors',
                        'name' => 'MP_NOTIFY_ERRORS',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1],
                            ['id' => 'active_off', 'value' => 0]
                        ],
                        'value' => Configuration::get('MP_NOTIFY_ERRORS')
                    ],
                    [
                        'type' => 'text',
                        'label' => 'Log Retention (days)',
                        'name' => 'MP_LOG_RETENTION',
                        'suffix' => 'days',
                        'value' => Configuration::get('MP_LOG_RETENTION', 30),
                        'desc' => 'Automatically delete logs older than X days'
                    ],
                    [
                        'type' => 'switch',
                        'label' => 'Auto-retry Failed Syncs',
                        'name' => 'MP_AUTO_RETRY',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1],
                            ['id' => 'active_off', 'value' => 0]
                        ],
                        'value' => Configuration::get('MP_AUTO_RETRY')
                    ],
                    [
                        'type' => 'text',
                        'label' => 'Retry Attempts',
                        'name' => 'MP_RETRY_ATTEMPTS',
                        'value' => Configuration::get('MP_RETRY_ATTEMPTS', 3),
                        'desc' => 'Number of retry attempts for failed syncs'
                    ],
                    [
                        'type' => 'text',
                        'label' => 'Retry Delay (seconds)',
                        'name' => 'MP_RETRY_DELAY',
                        'suffix' => 'seconds',
                        'value' => Configuration::get('MP_RETRY_DELAY', 60),
                        'desc' => 'Delay between retry attempts'
                    ],
                    [
                        'type' => 'textarea',
                        'label' => 'Notification Emails',
                        'name' => 'MP_NOTIFICATION_EMAILS',
                        'value' => Configuration::get('MP_NOTIFICATION_EMAILS'),
                        'desc' => 'Comma-separated list of emails for error notifications'
                    ]
                ],
                'submit' => [
                    'title' => 'Save Settings',
                    'name' => 'save_settings',
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];
        
        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->name_controller = $this->controller_name;
        $helper->token = Tools::getAdminTokenLite($this->controller_name);
        $helper->currentIndex = AdminController::$currentIndex;
        $helper->title = $this->meta_title;
        
        return $helper->generateForm([$fields]);
    }
    
    private function processSaveSettings()
    {
        Configuration::updateValue('MP_LOG_ENABLED', Tools::getValue('MP_LOG_ENABLED'));
        Configuration::updateValue('MP_NOTIFY_ERRORS', Tools::getValue('MP_NOTIFY_ERRORS'));
        Configuration::updateValue('MP_LOG_RETENTION', Tools::getValue('MP_LOG_RETENTION'));
        Configuration::updateValue('MP_AUTO_RETRY', Tools::getValue('MP_AUTO_RETRY'));
        Configuration::updateValue('MP_RETRY_ATTEMPTS', Tools::getValue('MP_RETRY_ATTEMPTS'));
        Configuration::updateValue('MP_RETRY_DELAY', Tools::getValue('MP_RETRY_DELAY'));
        Configuration::updateValue('MP_NOTIFICATION_EMAILS', Tools::getValue('MP_NOTIFICATION_EMAILS'));
        
        $this->confirmations[] = 'Settings saved successfully';
    }
}
