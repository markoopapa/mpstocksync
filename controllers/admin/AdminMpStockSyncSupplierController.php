<?php
class AdminMpStockSyncSuppliersController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        
        $this->bootstrap = true;
        $this->table = 'mpstocksync_suppliers';
        $this->className = 'MpStockSyncSupplier';
        $this->identifier = 'id_supplier';
        $this->lang = false;
        
        // Lista oszlopok
        $this->fields_list = [
            'id_supplier' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->l('Supplier Name'),
                'width' => 'auto'
            ],
            'connection_type' => [
                'title' => $this->l('Connection Type'),
                'align' => 'center'
            ],
            'auto_sync' => [
                'title' => $this->l('Auto Sync'),
                'align' => 'center',
                'type' => 'bool',
                'active' => 'auto_sync'
            ],
            'last_sync' => [
                'title' => $this->l('Last Sync'),
                'type' => 'datetime',
                'align' => 'center'
            ],
            'active' => [
                'title' => $this->l('Active'),
                'align' => 'center',
                'type' => 'bool',
                'active' => 'active'
            ]
        ];
        
        $this->actions = ['edit', 'delete'];
    }
    
    public function initContent()
    {
        parent::initContent();
        
        $this->content = '
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-truck"></i> ' . $this->l('Suppliers') . '
            </div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <i class="icon-info"></i> 
                    ' . $this->l('Supplier synchronization feature is under development.') . '
                </div>
                <p>
                    ' . $this->l('This feature will allow you to sync stock from external supplier databases.') . '
                </p>
            </div>
        </div>';
        
        // Hozzáadjuk a listát is
        $this->content .= $this->renderList();
        
        $this->context->smarty->assign('content', $this->content);
    }
    
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Supplier'),
                'icon' => 'icon-truck'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Supplier Name'),
                    'name' => 'name',
                    'required' => true,
                    'col' => 6
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Connection Type'),
                    'name' => 'connection_type',
                    'required' => true,
                    'options' => [
                        'query' => [
                            ['id' => 'database', 'name' => 'Database'],
                            ['id' => 'api', 'name' => 'API'],
                            ['id' => 'csv', 'name' => 'CSV/File']
                        ],
                        'id' => 'id',
                        'name' => 'name'
                    ],
                    'col' => 4
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Database Host'),
                    'name' => 'db_host',
                    'col' => 4,
                    'condition' => [
                        'connection_type' => 'database'
                    ]
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Database Name'),
                    'name' => 'db_name',
                    'col' => 4,
                    'condition' => [
                        'connection_type' => 'database'
                    ]
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('API URL'),
                    'name' => 'api_url',
                    'col' => 6,
                    'condition' => [
                        'connection_type' => 'api'
                    ]
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Auto Sync'),
                    'name' => 'auto_sync',
                    'required' => false,
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'auto_sync_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'auto_sync_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ],
                    'col' => 4
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'required' => false,
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ],
                    'col' => 4
                ]
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];
        
        return parent::renderForm();
    }
}
