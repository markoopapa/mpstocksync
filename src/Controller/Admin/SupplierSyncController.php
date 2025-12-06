<?php

namespace MpStockSync\Controller\Admin;

use MpStockSync\Service\SupplierSyncService;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SupplierSyncController extends FrameworkBundleAdminController
{
    /**
     * @var SupplierSyncService
     */
    private $supplierSyncService;

    public function __construct(SupplierSyncService $supplierSyncService)
    {
        $this->supplierSyncService = $supplierSyncService;
    }

    /**
     * Admin felület — beszállítói készlet szinkron oldal.
     */
    public function indexAction()
    {
        return $this->render('@Modules/mpstocksync/views/templates/admin/supplier_sync.tpl', [
            'page_title' => 'Supplier Stock Sync',
        ]);
    }

    /**
     * Szinkron indítása API → Shop
     */
    public function syncAction(Request $request): Response
    {
        try {
            $result = $this->supplierSyncService->syncSupplierStock();

            return $this->json([
                'success' => true,
                'message' => 'Supplier stock successfully synchronized!',
                'result' => $result,
            ]);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error during synchronization: ' . $e->getMessage(),
            ], 500);
        }
    }
}
