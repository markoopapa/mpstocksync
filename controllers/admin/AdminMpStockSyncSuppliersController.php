<?php
// Basic admin controller to list suppliers and trigger test/sync
class AdminMpStockSyncSuppliersController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'mpstocksync_suppliers';
        $this->className = 'MpStockSyncSupplier';
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $suppliers = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'mpstocksync_suppliers');

        $this->context->smarty->assign([
            'suppliers' => $suppliers,
            'token' => $this->token
        ]);

        $this->setTemplate('supplier_config.tpl');
    }

    // handle ajax test_connection and sync actions
    public function postProcess()
    {
        if (Tools::isSubmit('test_connection') && Tools::getValue('id_supplier')) {
            $id = (int)Tools::getValue('id_supplier');
            $supplier = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'mpstocksync_suppliers WHERE id_supplier = ' . $id);
            if ($supplier) {
                $client = new \MpStockSync\ApiClient\SupplierApiClient($supplier['api_url'], $supplier['api_key']);
                $res = $client->getProducts();
                if ($res['success']) {
                    $this->confirmations[] = 'Connection OK, products fetched: ' . count($res['products']);
                } else {
                    $this->errors[] = 'Connection failed: ' . ($res['error'] ?? 'unknown');
                }
            }
        }

        if (Tools::isSubmit('sync_supplier') && Tools::getValue('id_supplier')) {
            $id = (int)Tools::getValue('id_supplier');
            $supplier = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'mpstocksync_suppliers WHERE id_supplier = ' . $id);
            if ($supplier) {
                $config = [
                    'api_url' => $supplier['api_url'],
                    'api_key' => $supplier['api_key'],
                    'id_supplier' => $supplier['id_supplier'],
                    'target_shops' => $supplier['target_shops']
                ];
                $svc = new \MpStockSync\Service\SupplierSyncService($config);
                $summary = $svc->sync();
                if ($summary['success']) {
                    $this->confirmations[] = 'Sync completed: updated ' . $summary['updated'] . ' products';
                } else {
                    $this->errors[] = 'Sync failed: ' . implode('; ', $summary['errors']);
                }
            }
        }
    }
}
